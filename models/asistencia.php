<?php

class Asistencia
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    // =========================================================
    // VERIFICA SI EL PRACTICANTE YA REGISTRÓ ASISTENCIA HOY
    // =========================================================
    public function yaRegistroHoy($id_practicante, $fecha)
    {
        $sql = "SELECT id_asistencia
                FROM Asistencia
                WHERE id_practicante = ?
                AND fecha = ?";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("is", $id_practicante, $fecha);
        $stmt->execute();

        $resultado = $stmt->get_result();

        return $resultado->num_rows > 0;
    }


    // =========================================================
    // OBTIENE EL HORARIO DEL PRACTICANTE PARA UN DÍA
    // =========================================================
    public function obtenerHorario($id_practicante, $dia)
    {
        $sql = "SELECT hora_entrada, hora_salida
                FROM Horarios
                WHERE id_practicante = ?
                AND dia_semana = ?
                LIMIT 1";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("is", $id_practicante, $dia);
        $stmt->execute();

        $resultado = $stmt->get_result();

        return $resultado->fetch_assoc();
    }


    // =========================================================
    // VERIFICA QUE EL PRACTICANTE ESTÉ ACTIVO
    // =========================================================
    public function practicanteActivo($id_practicante)
    {
        $sql = "SELECT id_practicante
                FROM Practicantes
                WHERE id_practicante = ?
                AND activo = 1";

        $stmt = $this->conexion->prepare($sql);
        $stmt->bind_param("i", $id_practicante);
        $stmt->execute();

        $resultado = $stmt->get_result();

        return $resultado->num_rows > 0;
    }


    // =========================================================
    // REGISTRA LA ASISTENCIA
    // =========================================================
    public function registrar(
        $id_practicante,
        $fecha,
        $hora_llegada,
        $estado
    ) {
        $sql = "INSERT INTO Asistencia
                (id_practicante, fecha, hora_llegada, estado)
                VALUES (?, ?, ?, ?)";

        $stmt = $this->conexion->prepare($sql);

        $stmt->bind_param(
            "isss",
            $id_practicante,
            $fecha,
            $hora_llegada,
            $estado
        );

        return $stmt->execute();
    }

    public function registrarSalida($id_practicante, $fecha, $hora_salida)
    {
        $sql = "UPDATE Asistencia
            SET hora_salida = ?
            WHERE id_practicante = ?
            AND fecha = ?
            AND hora_salida IS NULL";

        $stmt = $this->conexion->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param(
            "sis",
            $hora_salida,
            $id_practicante,
            $fecha
        );

        if (!$stmt->execute()) {
            return false;
        }

        return $stmt->affected_rows > 0;
    }


    // =========================================================
    // OBTIENE HISTORIAL
    // =========================================================
    public function obtenerHistorial($desde = null, $hasta = null)
    {
        $sql = "SELECT
                    a.id_asistencia,
                    a.fecha,
                    a.hora_llegada,
                    a.hora_salida,
                    a.estado,
                    CONCAT(p.nombres, ' ', p.apellidos) AS practicante
                FROM Asistencia a
                INNER JOIN Practicantes p
                    ON a.id_practicante = p.id_practicante";

        if ($desde && $hasta) {
            $sql .= " WHERE a.fecha BETWEEN ? AND ?";
        }

        $sql .= " ORDER BY a.fecha DESC, a.hora_llegada DESC";

        if ($desde && $hasta) {

            $stmt = $this->conexion->prepare($sql);

            $stmt->bind_param(
                "ss",
                $desde,
                $hasta
            );

            $stmt->execute();

            return $stmt->get_result();
        }

        return $this->conexion->query($sql);
    }

    // =========================================================
    // RESUMEN MENSUAL DE FALTAS Y TARDANZAS
    //
    // NO GUARDA PUNTOS.
    // LOS CALCULA AUTOMÁTICAMENTE.
    // =========================================================
    public function obtenerResumenMensual($mes, $anio)
    {
        $fechaInicio = sprintf(
            "%04d-%02d-01",
            $anio,
            $mes
        );

        $fechaFin = date(
            "Y-m-t",
            strtotime($fechaInicio)
        );

        /*
         * Si estamos consultando el mes actual,
         * no debemos contar como faltas los días futuros.
         */
        $hoy = date("Y-m-d");

        if ($anio == date("Y") && $mes == date("m")) {

            $fechaFin = $hoy;
        }

        // -----------------------------------------------------
        // 1. OBTENER PRACTICANTES ACTIVOS
        // -----------------------------------------------------
        $sqlPracticantes = "SELECT
                                id_practicante,
                                nombres,
                                apellidos
                            FROM Practicantes
                            WHERE activo = 1
                            ORDER BY nombres, apellidos";

        $resultadoPracticantes = $this->conexion->query(
            $sqlPracticantes
        );

        if (!$resultadoPracticantes) {
            return [];
        }


        // -----------------------------------------------------
        // 2. OBTENER TODOS LOS HORARIOS
        // -----------------------------------------------------
        $sqlHorarios = "SELECT
                            id_practicante,
                            dia_semana
                        FROM Horarios";

        $resultadoHorarios = $this->conexion->query(
            $sqlHorarios
        );

        $horarios = [];

        if ($resultadoHorarios) {

            while ($horario = $resultadoHorarios->fetch_assoc()) {

                $id = $horario["id_practicante"];

                $dia = strtoupper(
                    trim($horario["dia_semana"])
                );

                /*
                 * Normalizamos por si existen tildes.
                 */
                $dia = str_replace(
                    ["Á", "É", "Í", "Ó", "Ú"],
                    ["A", "E", "I", "O", "U"],
                    $dia
                );

                $horarios[$id][$dia] = true;
            }
        }


        // -----------------------------------------------------
        // 3. OBTENER TODAS LAS ASISTENCIAS DEL MES
        // -----------------------------------------------------
        $asistencias = [];

        $sqlAsistencias = "SELECT
                                id_practicante,
                                fecha,
                                estado
                           FROM Asistencia
                           WHERE fecha BETWEEN ? AND ?";

        $stmt = $this->conexion->prepare(
            $sqlAsistencias
        );

        $stmt->bind_param(
            "ss",
            $fechaInicio,
            $fechaFin
        );

        $stmt->execute();

        $resultadoAsistencias = $stmt->get_result();

        while ($asistencia = $resultadoAsistencias->fetch_assoc()) {

            $id = $asistencia["id_practicante"];
            $fecha = $asistencia["fecha"];

            $asistencias[$id][$fecha] = $asistencia["estado"];
        }


        // -----------------------------------------------------
        // 4. RECORRER CADA PRACTICANTE
        // -----------------------------------------------------
        $resumen = [];

        while ($practicante = $resultadoPracticantes->fetch_assoc()) {

            $id = $practicante["id_practicante"];

            $nombreCompleto =
                $practicante["nombres"] . " " .
                $practicante["apellidos"];


            $faltas = [];
            $tardanzas = [];

            // -------------------------------------------------
            // RECORRER LOS DÍAS DEL MES
            // -------------------------------------------------
            $fechaActual = new DateTime($fechaInicio);
            $fechaLimite = new DateTime($fechaFin);

            while ($fechaActual <= $fechaLimite) {

                $fecha = $fechaActual->format("Y-m-d");

                /*
                 * N:
                 * 1 = Lunes
                 * 2 = Martes
                 * 3 = Miércoles
                 * 4 = Jueves
                 * 5 = Viernes
                 * 6 = Sábado
                 * 7 = Domingo
                 */
                $numeroDia = (int)$fechaActual->format("N");

                $dias = [
                    1 => "LUNES",
                    2 => "MARTES",
                    3 => "MIERCOLES",
                    4 => "JUEVES",
                    5 => "VIERNES",
                    6 => "SABADO",
                    7 => "DOMINGO"
                ];

                $diaSemana = $dias[$numeroDia];


                // ---------------------------------------------
                // ¿EL PRACTICANTE TENÍA HORARIO ESE DÍA?
                // ---------------------------------------------
                $teniaHorario =
                    isset($horarios[$id][$diaSemana]);


                if ($teniaHorario) {

                    // -----------------------------------------
                    // ¿TIENE REGISTRO DE ASISTENCIA?
                    // -----------------------------------------
                    if (!isset($asistencias[$id][$fecha])) {

                        /*
                         * No existe asistencia en un día
                         * donde sí tenía horario.
                         *
                         * Por lo tanto:
                         * FALTA = -3
                         */
                        $faltas[] = [
                            "fecha" => $fecha,
                            "estado" => "FALTA",
                            "puntos" => -3
                        ];
                    } else {

                        $estado =
                            $asistencias[$id][$fecha];


                        // -------------------------------------
                        // TARDANZA
                        // -------------------------------------
                        if ($estado === "TARDANZA") {

                            $tardanzas[] = [
                                "fecha" => $fecha,
                                "estado" => "TARDANZA",
                                "puntos" => -2
                            ];
                        }

                        /*
                         * PUNTUAL y ADELANTADO
                         * no generan puntos perdidos.
                         */
                    }
                }

                $fechaActual->modify("+1 day");
            }


            // -------------------------------------------------
            // 5. CALCULAR PUNTOS PERDIDOS
            // -------------------------------------------------
            $cantidadFaltas =
                count($faltas);

            $cantidadTardanzas =
                count($tardanzas);

            $puntosFaltas =
                $cantidadFaltas * 3;

            $puntosTardanzas =
                $cantidadTardanzas * 2;

            $totalPerdido =
                $puntosFaltas + $puntosTardanzas;


            // -------------------------------------------------
            // 6. DETECTAR 3 FALTAS EN UNA MISMA SEMANA
            // -------------------------------------------------
            $semanas = [];

            foreach ($faltas as $falta) {

                $fechaFalta = new DateTime(
                    $falta["fecha"]
                );

                /*
                 * "o-W" = año ISO + número de semana.
                 *
                 * Ejemplo:
                 * 2026-37
                 */
                $semana = $fechaFalta->format("o-W");

                if (!isset($semanas[$semana])) {
                    $semanas[$semana] = 0;
                }

                $semanas[$semana]++;
            }


            $semanasDesaprobadas = [];

            foreach ($semanas as $semana => $cantidad) {

                if ($cantidad >= 3) {

                    $semanasDesaprobadas[] = [
                        "semana" => $semana,
                        "faltas" => $cantidad
                    ];
                }
            }


            // -------------------------------------------------
            // 7. GUARDAR RESULTADO DEL PRACTICANTE
            // -------------------------------------------------
            $resumen[] = [
                "id_practicante" => $id,
                "practicante" => $nombreCompleto,

                "cantidad_faltas" =>
                $cantidadFaltas,

                "cantidad_tardanzas" =>
                $cantidadTardanzas,

                "puntos_faltas" =>
                -$puntosFaltas,

                "puntos_tardanzas" =>
                -$puntosTardanzas,

                "puntos_perdidos" =>
                -$totalPerdido,

                "faltas" =>
                $faltas,

                "tardanzas" =>
                $tardanzas,

                "semanas_desaprobadas" =>
                $semanasDesaprobadas
            ];
        }


        return $resumen;
    }
}
