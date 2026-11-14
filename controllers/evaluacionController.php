<?php

require_once __DIR__ . "/../models/Evaluacion.php";
require_once __DIR__ . "/../helpers/ExcelSimple.php";

class EvaluacionController
{
    private $modelo;

    public function __construct($conexion)
    {
        $this->modelo = new Evaluacion($conexion);
    }

    // Devuelve un mes válido ("2026-10") o el mes actual
    private function mesValido($mes)
    {
        if (is_string($mes) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $mes)) {
            return $mes;
        }

        return date("Y-m");
    }

    // Vuelve a la pantalla de notas con un mensaje
    private function volver($mes, $mensaje, $extra = "")
    {
        header(
            "Location: index.php?pagina=evaluacion&mes=" . urlencode($mes) .
                "&mensaje=" . urlencode($mensaje) . $extra
        );
        exit;
    }


    public function index()
    {
        $mes = $this->mesValido($_GET["mes"] ?? "");

        $filas = $this->modelo->calcularMes($mes);

        // El mes actual todavía no termina: las notas son parciales
        $enCurso = ($mes === date("Y-m"));

        // ¿Es un mes anterior al inicio del sistema de notas?
        $mesInicio     = substr(Evaluacion::FECHA_INICIO, 0, 7);
        $antesDelInicio = ($mes < $mesInicio);

        $mensaje = $_GET["mensaje"] ?? "";
        $cantidad = (int)($_GET["n"] ?? 0);

        require __DIR__ . "/../views/evaluacion/index.php";
    }


    // Justificar un día (a todos o a un practicante)
    public function justificar()
    {
        $mes = $this->mesValido($_POST["mes"] ?? "");

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->volver($mes, "error");
        }

        $fecha  = trim($_POST["fecha"] ?? "");
        $motivo = trim($_POST["motivo"] ?? "");
        $id     = (int)($_POST["id_practicante"] ?? 0);

        // Validar fecha
        $f = DateTime::createFromFormat("!Y-m-d", $fecha);

        if (!$f || $f->format("Y-m-d") !== $fecha) {
            $this->volver($mes, "fecha_invalida");
        }

        // Validar motivo
        if ($motivo === "") {
            $this->volver($mes, "motivo_vacio");
        }

        if (mb_strlen($motivo) > 255) {
            $motivo = mb_substr($motivo, 0, 255);
        }

        $cantidad = $this->modelo->justificarDia(
            $fecha,
            $motivo,
            $id > 0 ? $id : null
        );

        // Mostrar el mes de la fecha justificada
        $mesFecha = $f->format("Y-m");

        if ($cantidad > 0) {
            $this->volver($mesFecha, "justificado", "&n=" . $cantidad);
        }

        $this->volver($mesFecha, "sin_cambios");
    }


    // Quitar una justificación (por si se puso por error)
    public function quitarJustificacion()
    {
        $mes = $this->mesValido($_POST["mes"] ?? "");

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            $this->volver($mes, "error");
        }

        $id    = (int)($_POST["id_practicante"] ?? 0);
        $fecha = trim($_POST["fecha"] ?? "");

        $f = DateTime::createFromFormat("!Y-m-d", $fecha);

        if ($id <= 0 || !$f || $f->format("Y-m-d") !== $fecha) {
            $this->volver($mes, "error");
        }

        $this->modelo->quitarJustificacion($id, $fecha);

        $this->volver($mes, "quitada");
    }


    // =====================================================
    // Descarga las notas del mes en un archivo Excel (.xlsx)
    // con dos hojas: "Notas" y "Detalle"
    // =====================================================
    public function exportar()
    {
        $mes   = $this->mesValido($_GET["mes"] ?? "");
        $filas = $this->modelo->calcularMes($mes);

        $meses = [
            1 => "enero", 2 => "febrero", 3 => "marzo", 4 => "abril",
            5 => "mayo", 6 => "junio", 7 => "julio", 8 => "agosto",
            9 => "septiembre", 10 => "octubre", 11 => "noviembre", 12 => "diciembre"
        ];

        $dias = [
            1 => "lun", 2 => "mar", 3 => "mié", 4 => "jue",
            5 => "vie", 6 => "sáb", 7 => "dom"
        ];

        $fechaMes  = DateTime::createFromFormat("!Y-m", $mes);
        $nombreMes = $meses[(int)$fechaMes->format("n")] . " de " . $fechaMes->format("Y");

        $enCurso        = ($mes === date("Y-m"));
        $antesDelInicio = ($mes < substr(Evaluacion::FECHA_INICIO, 0, 7));

        // ---------- Texto explicativo de la segunda fila ----------
        $explicacion = "Escala sobre " . Evaluacion::NOTA_MAXIMA
            . " · Aprueba con " . Evaluacion::NOTA_APROBATORIA . " o más"
            . " · Asistencia " . round(Evaluacion::PESO_ASISTENCIA * 100) . " %"
            . ", Puntualidad " . round(Evaluacion::PESO_PUNTUALIDAD * 100) . " %"
            . ", Horas " . round(Evaluacion::PESO_HORAS * 100) . " %";

        if ($antesDelInicio) {
            $explicacion .= " · Este mes no se evalúa: las notas empiezan el "
                . date("d/m/Y", strtotime(Evaluacion::FECHA_INICIO));
        } elseif ($enCurso) {
            $explicacion .= " · Mes en curso: notas parciales";
        }

        $explicacion .= " · Generado el " . date("d/m/Y H:i");

        // ---------- Ayudas para armar las celdas ----------
        $celdaNota = function ($valor, $esFinal = false) {

            if ($valor === null) {
                return ["—", "centro"];
            }

            $aprueba = $valor >= Evaluacion::NOTA_APROBATORIA;

            if ($esFinal) {
                $estilo = $aprueba ? "final_ok" : "final_mal";
            } else {
                $estilo = $aprueba ? "nota_ok" : "nota_mal";
            }

            return [(float)$valor, $estilo];
        };

        $encabezado = function ($titulos) {
            $fila = [];

            foreach ($titulos as $titulo) {
                $fila[] = [$titulo, "encabezado"];
            }

            return $fila;
        };

        // ================== Hoja 1: Notas ==================
        $hojaNotas = [
            [["Notas mensuales — " . $nombreMes, "titulo"]],
            [[$explicacion, "subtitulo"]],
            [],
            $encabezado([
                "Practicante", "Asistencia", "Puntualidad", "Horas",
                "Nota final", "Estado", "Faltas", "Tardanzas",
                "Días justificados", "Horas trabajadas",
                "Horas programadas", "Observaciones"
            ])
        ];

        foreach ($filas as $f) {

            if ($f["sin_datos"]) {
                $estado = ["Sin datos", "sin_datos"];
            } elseif ($f["aprobado"]) {
                $estado = ["Aprobado", "aprobado"];
            } else {
                $estado = ["Desaprobado", "desaprobado"];
            }

            $observaciones = [];

            if (count($f["semanas_3_faltas"]) > 0) {
                $observaciones[] = Evaluacion::FALTAS_SEMANA_LIMITE
                    . " faltas en una semana: nota final limitada a "
                    . Evaluacion::TOPE_NOTA_FINAL;
            }

            if (count($f["sin_salida"]) > 0) {
                $observaciones[] = count($f["sin_salida"])
                    . (count($f["sin_salida"]) === 1
                        ? " día sin salida registrada"
                        : " días sin salida registrada");
            }

            $hojaNotas[] = [
                [$f["practicante"], "texto"],
                $celdaNota($f["nota_asistencia"]),
                $celdaNota($f["nota_puntualidad"]),
                $celdaNota($f["nota_horas"]),
                $celdaNota($f["nota_final"], true),
                $estado,
                [count($f["faltas"]), "centro"],
                [count($f["tardanzas"]), "centro"],
                [count($f["justificados"]), "centro"],
                [$f["horas_trabajadas"], "centro"],
                [$f["horas_programadas"], "centro"],
                [implode("; ", $observaciones), "texto"]
            ];
        }

        // ================== Hoja 2: Detalle ==================
        $hojaDetalle = [
            $encabezado(["Practicante", "Fecha", "Tipo", "Motivo"])
        ];

        foreach ($filas as $f) {

            $eventos = [];

            foreach ($f["faltas"] as $fecha) {
                $eventos[] = [$fecha, "Falta", ""];
            }

            foreach ($f["tardanzas"] as $fecha) {
                $eventos[] = [$fecha, "Tardanza", ""];
            }

            foreach ($f["justificados"] as $j) {
                $eventos[] = [$j["fecha"], "Justificado", $j["motivo"] ?? ""];
            }

            foreach ($f["sin_salida"] as $fecha) {
                $eventos[] = [$fecha, "Sin salida registrada", "Esas horas no se cuentan"];
            }

            usort($eventos, function ($a, $b) {
                return strcmp($a[0], $b[0]);
            });

            foreach ($eventos as $e) {

                $fechaTexto = $dias[(int)date("N", strtotime($e[0]))]
                    . " " . date("d/m/Y", strtotime($e[0]));

                $hojaDetalle[] = [
                    [$f["practicante"], "texto"],
                    [$fechaTexto, "centro"],
                    [$e[1], "centro"],
                    [$e[2], "texto"]
                ];
            }
        }

        if (count($hojaDetalle) === 1) {
            $hojaDetalle[] = [["Sin novedades este mes.", "texto"], ["", "texto"], ["", "texto"], ["", "texto"]];
        }

        // ================== Armar y descargar ==================
        $excel = new ExcelSimple();

        $excel->agregarHoja(
            "Notas " . $meses[(int)$fechaMes->format("n")] . " " . $fechaMes->format("Y"),
            [32, 13, 13, 13, 13, 15, 9, 10, 12, 13, 13, 46],
            $hojaNotas,
            4
        );

        $excel->agregarHoja("Detalle", [32, 18, 24, 40], $hojaDetalle, 1);

        try {
            $excel->descargar("notas_" . $mes . ".xlsx");
        } catch (Throwable $e) {
            http_response_code(500);
            exit("No se pudo crear el Excel: " . $e->getMessage());
        }
    }
}
