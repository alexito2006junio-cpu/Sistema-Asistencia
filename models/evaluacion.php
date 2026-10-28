<?php

class Evaluacion
{
    // Escala y aprobación
    const NOTA_MAXIMA        = 20;
    const NOTA_APROBATORIA   = 10.5;

    // Peso de cada nota en la nota final (deben sumar 1)
    const PESO_ASISTENCIA    = 0.40;
    const PESO_PUNTUALIDAD   = 0.30;
    const PESO_HORAS         = 0.30;

    // Puntos que se restan sobre 20
    const PUNTOS_POR_FALTA    = 3;
    const PUNTOS_POR_TARDANZA = 2;

    // 3 faltas (o más) en una misma semana: la nota final no pasa de este tope
    const FALTAS_SEMANA_LIMITE = 3;
    const TOPE_NOTA_FINAL      = 10;

    private $conexion;

    private $dias = [
        1 => "LUNES",
        2 => "MARTES",
        3 => "MIERCOLES",
        4 => "JUEVES",
        5 => "VIERNES",
        6 => "SABADO",
        7 => "DOMINGO"
    ];

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    // =========================================================
    // CALCULA LAS NOTAS DE TODOS LOS PRACTICANTES ACTIVOS
    // $mes tiene el formato "2026-10"
    // =========================================================
    public function calcularMes($mes)
    {
        $inicio = $mes . "-01";
        $finMes = date("Y-m-t", strtotime($inicio));
        $hoy    = date("Y-m-d");

        // Un mes futuro todavía no tiene nada que evaluar.
        // En el mes actual solo se evalúa hasta hoy.
        $ultimo = min($finMes, $hoy);

        $practicantes = $this->obtenerPracticantesActivos();

        if ($inicio > $hoy) {
            return $this->filasSinDatos($practicantes);
        }

        $horarios    = $this->obtenerHorarios();
        $asistencias = $this->obtenerAsistencias($inicio, $ultimo);

        $filas = [];

        foreach ($practicantes as $p) {

            $id = $p["id_practicante"];

            $faltas      = [];
            $tardanzas   = [];
            $justificados = [];
            $sinSalida   = [];

            $segProgramados = 0;
            $segTrabajados  = 0;
            $diasEvaluados  = 0;

            $dia = new DateTime($inicio);
            $fin = new DateTime($ultimo);

            while ($dia <= $fin) {

                $fecha  = $dia->format("Y-m-d");
                $nombre = $this->dias[(int)$dia->format("N")];

                $dia->modify("+1 day");

                // ¿Le tocaba trabajar ese día?
                if (!isset($horarios[$id][$nombre])) {
                    continue;
                }

                $duracion = $horarios[$id][$nombre]["duracion"];

                if ($duracion <= 0) {
                    continue;
                }

                $registro = $asistencias[$id][$fecha] ?? null;
                $esHoy    = ($fecha === $hoy);

                // Día justificado: no cuenta ni a favor ni en contra
                if ($registro && $registro["estado"] === "JUSTIFICADO") {
                    $justificados[] = [
                        "fecha"  => $fecha,
                        "motivo" => $registro["motivo"] ?? ""
                    ];
                    continue;
                }

                // Sin registro
                if (!$registro) {

                    // Hoy todavía puede llegar: no se cuenta aún
                    if ($esHoy) {
                        continue;
                    }

                    $faltas[] = $fecha;
                    $segProgramados += $duracion;
                    $diasEvaluados++;
                    continue;
                }

                // Con registro
                if ($registro["estado"] === "TARDANZA") {
                    $tardanzas[] = $fecha;
                }

                $tieneSalida = !empty($registro["hora_salida"]);

                // Hoy y todavía en la empresa: sus horas se cuentan mañana
                if ($esHoy && !$tieneSalida) {
                    continue;
                }

                $segProgramados += $duracion;
                $diasEvaluados++;

                if ($tieneSalida && !empty($registro["hora_llegada"])) {

                    $trabajado = $this->segundos($registro["hora_salida"])
                        - $this->segundos($registro["hora_llegada"]);

                    // Nunca más de lo programado ese día, ni menos de 0
                    $segTrabajados += max(0, min($trabajado, $duracion));
                } else {
                    // Marcó entrada pero se olvidó de marcar salida
                    $sinSalida[] = $fecha;
                }
            }

            // ---------------- Notas ----------------
            if ($diasEvaluados === 0) {
                $filas[] = $this->filaVacia($p, $justificados);
                continue;
            }

            $notaAsistencia = max(
                0,
                self::NOTA_MAXIMA - count($faltas) * self::PUNTOS_POR_FALTA
            );

            $notaPuntualidad = max(
                0,
                self::NOTA_MAXIMA - count($tardanzas) * self::PUNTOS_POR_TARDANZA
            );

            $notaHoras = $segProgramados > 0
                ? min(
                    self::NOTA_MAXIMA,
                    self::NOTA_MAXIMA * $segTrabajados / $segProgramados
                )
                : self::NOTA_MAXIMA;

            $final =
                $notaAsistencia  * self::PESO_ASISTENCIA +
                $notaPuntualidad * self::PESO_PUNTUALIDAD +
                $notaHoras       * self::PESO_HORAS;

            // Regla de las 3 faltas en una misma semana
            $semanas = $this->semanasConMuchasFaltas($faltas);

            if (count($semanas) > 0) {
                $final = min($final, self::TOPE_NOTA_FINAL);
            }

            $final = round($final, 2);

            $filas[] = [
                "id_practicante" => $id,
                "practicante"    => $p["nombres"] . " " . $p["apellidos"],
                "sin_datos"      => false,

                "nota_asistencia"  => round($notaAsistencia, 2),
                "nota_puntualidad" => round($notaPuntualidad, 2),
                "nota_horas"       => round($notaHoras, 2),
                "nota_final"       => $final,
                "aprobado"         => $final >= self::NOTA_APROBATORIA,

                "horas_programadas" => $this->formatoHoras($segProgramados),
                "horas_trabajadas"  => $this->formatoHoras($segTrabajados),

                "faltas"       => $faltas,
                "tardanzas"    => $tardanzas,
                "justificados" => $justificados,
                "sin_salida"   => $sinSalida,
                "semanas_3_faltas" => $semanas
            ];
        }

        return $filas;
    }


    // =========================================================
    // JUSTIFICAR UN DÍA
    //
    // Si $idPracticante viene vacío, se justifica a TODOS los
    // practicantes activos que tenían horario ese día.
    // No toca a quien ya tiene un registro ese día.
    //
    // Devuelve cuántos practicantes se justificaron.
    // =========================================================
    public function justificarDia($fecha, $motivo, $idPracticante = null)
    {
        $dia = $this->dias[(int)date("N", strtotime($fecha))];

        $sql = "INSERT INTO Asistencia
                    (id_practicante, fecha, estado, motivo)
                SELECT p.id_practicante, ?, 'JUSTIFICADO', ?
                FROM Practicantes p
                INNER JOIN Horarios h
                    ON h.id_practicante = p.id_practicante
                    AND h.dia_semana = ?
                WHERE p.activo = 1
                AND NOT EXISTS (
                    SELECT 1
                    FROM Asistencia a
                    WHERE a.id_practicante = p.id_practicante
                    AND a.fecha = ?
                )";

        $tipos  = "ssss";
        $params = [$fecha, $motivo, $dia, $fecha];

        if ($idPracticante) {
            $sql .= " AND p.id_practicante = ?";
            $tipos .= "i";
            $params[] = (int)$idPracticante;
        }

        $sql .= " GROUP BY p.id_practicante";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param($tipos, ...$params);
        $stmt->execute();

        return $stmt->affected_rows;
    }


    // =========================================================
    // QUITAR LA JUSTIFICACIÓN DE UN DÍA
    // =========================================================
    public function quitarJustificacion($idPracticante, $fecha)
    {
        $sql = "DELETE FROM Asistencia
                WHERE id_practicante = ?
                AND fecha = ?
                AND estado = 'JUSTIFICADO'";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("is", $idPracticante, $fecha);
        $stmt->execute();

        return $stmt->affected_rows > 0;
    }


    // =========================================================
    // FUNCIONES DE APOYO
    // =========================================================
    private function obtenerPracticantesActivos()
    {
        $resultado = $this->conexion->query(
            "SELECT id_practicante, nombres, apellidos
             FROM Practicantes
             WHERE activo = 1
             ORDER BY apellidos, nombres "
        );

        $lista = [];

        while ($fila = $resultado->fetch_assoc()) {
            $lista[] = $fila;
        }

        return $lista;
    }

    // [id_practicante][DIA] = ["duracion" => segundos]
    private function obtenerHorarios()
    {
        $resultado = $this->conexion->query(
            "SELECT id_practicante, dia_semana, hora_entrada, hora_salida
             FROM Horarios"
        );

        $horarios = [];

        while ($fila = $resultado->fetch_assoc()) {

            $dia = strtoupper(trim($fila["dia_semana"]));

            $horarios[$fila["id_practicante"]][$dia] = [
                "duracion" => $this->segundos($fila["hora_salida"])
                    - $this->segundos($fila["hora_entrada"])
            ];
        }

        return $horarios;
    }

    // [id_practicante][fecha] = registro de asistencia
    private function obtenerAsistencias($desde, $hasta)
    {
        $stmt = $this->conexion->prepare(
            "SELECT id_practicante, fecha, hora_llegada, hora_salida,
                    estado, motivo
             FROM Asistencia
             WHERE fecha BETWEEN ? AND ?"
        );

        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();

        $resultado = $stmt->get_result();

        $asistencias = [];

        while ($fila = $resultado->fetch_assoc()) {
            $asistencias[$fila["id_practicante"]][$fila["fecha"]] = $fila;
        }

        return $asistencias;
    }

    // Semanas (año-semana ISO) con 3 o más faltas
    private function semanasConMuchasFaltas($faltas)
    {
        $porSemana = [];

        foreach ($faltas as $fecha) {
            $semana = (new DateTime($fecha))->format("o-W");
            $porSemana[$semana] = ($porSemana[$semana] ?? 0) + 1;
        }

        $resultado = [];

        foreach ($porSemana as $semana => $cantidad) {
            if ($cantidad >= self::FALTAS_SEMANA_LIMITE) {
                $resultado[] = ["semana" => $semana, "faltas" => $cantidad];
            }
        }

        return $resultado;
    }

    private function segundos($hora)
    {
        if ($hora === null || $hora === "") {
            return 0;
        }

        $partes = explode(":", $hora);

        return ((int)$partes[0]) * 3600
            + ((int)($partes[1] ?? 0)) * 60
            + (int)($partes[2] ?? 0);
    }

    private function formatoHoras($segundos)
    {
        return sprintf(
            "%d:%02d",
            floor($segundos / 3600),
            floor(($segundos % 3600) / 60)
        );
    }

    private function filaVacia($practicante, $justificados = [])
    {
        return [
            "id_practicante" => $practicante["id_practicante"],
            "practicante"    => $practicante["nombres"] . " " . $practicante["apellidos"],
            "sin_datos"      => true,

            "nota_asistencia"  => null,
            "nota_puntualidad" => null,
            "nota_horas"       => null,
            "nota_final"       => null,
            "aprobado"         => false,

            "horas_programadas" => "0:00",
            "horas_trabajadas"  => "0:00",

            "faltas"       => [],
            "tardanzas"    => [],
            "justificados" => $justificados,
            "sin_salida"   => [],
            "semanas_3_faltas" => []
        ];
    }

    private function filasSinDatos($practicantes)
    {
        $filas = [];

        foreach ($practicantes as $p) {
            $filas[] = $this->filaVacia($p);
        }

        return $filas;
    }
}
