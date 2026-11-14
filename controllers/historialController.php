<?php

require_once __DIR__ . "/../models/Asistencia.php";
require_once __DIR__ . "/../helpers/ExcelSimple.php";

class HistorialController
{
    private $modelo;

    private $meses = [
        1 => "enero",
        2 => "febrero",
        3 => "marzo",
        4 => "abril",
        5 => "mayo",
        6 => "junio",
        7 => "julio",
        8 => "agosto",
        9 => "septiembre",
        10 => "octubre",
        11 => "noviembre",
        12 => "diciembre"
    ];

    public function __construct($conexion)
    {
        $this->modelo = new Asistencia($conexion);
    }


    // =====================================================
    // Decide qué período mostrar.
    //
    // Prioridad:
    //   1. todo=1          -> todo el historial
    //   2. mes=2026-11     -> ese mes
    //   3. (nada)          -> el MES ACTUAL
    //
    // Como por defecto se usa el mes actual, cuando empieza un
    // mes nuevo el historial se "limpia" solo. Los registros
    // anteriores siguen guardados y se ven eligiendo su mes.
    // =====================================================
    private function resolverFiltro()
    {
        $todo  = $_GET["todo"] ?? "";
        $mes   = trim($_GET["mes"] ?? "");

        // 1. Todo el historial
        if ($todo === "1") {
            return [
                "modo"     => "todo",
                "desde"    => "",
                "hasta"    => "",
                "mes"      => "",
                "titulo"   => "todo el historial",
                "consulta" => ["todo" => 1],
                "archivo"  => "todo"
            ];
        }

        // 2. Un mes concreto
        if ($mes !== "") {

            $fecha = DateTime::createFromFormat("!Y-m", $mes);

            if ($fecha && $fecha->format("Y-m") === $mes) {
                return $this->filtroDeMes($fecha, "mes");
            }
        }

        // 3. Por defecto: el mes actual
        return $this->filtroDeMes(new DateTime("first day of this month midnight"), "mes");
    }

    private function filtroDeMes($fecha, $modo)
    {
        $nombreMes = $this->meses[(int)$fecha->format("n")];

        return [
            "modo"   => $modo,
            "desde"  => $fecha->format("Y-m-01"),
            "hasta"  => $fecha->format("Y-m-t"),
            "mes"    => $fecha->format("Y-m"),
            "titulo" => $nombreMes . " de " . $fecha->format("Y"),
            "consulta" => ["mes" => $fecha->format("Y-m")],
            "archivo"  => $fecha->format("Y-m")
        ];
    }

    // Trae los registros del período elegido
    private function obtenerRegistros($filtro)
    {
        if ($filtro["modo"] === "todo") {
            return $this->modelo->obtenerHistorial();
        }

        return $this->modelo->obtenerHistorial(
            $filtro["desde"],
            $filtro["hasta"]
        );
    }


    public function index()
    {
        $filtro = $this->resolverFiltro();

        $historial = $this->obtenerRegistros($filtro);

        // Datos para la vista
        $tituloFiltro     = $filtro["titulo"];
        $mesCampo         = $filtro["modo"] === "mes" ? $filtro["mes"] : "";
        $consultaExportar = http_build_query($filtro["consulta"]);

        require __DIR__ . "/../views/historial/index.php";
    }


    public function exportarExcel()
    {
        // Se exporta exactamente lo que se está viendo en pantalla.
        $filtro = $this->resolverFiltro();

        $historial = $this->obtenerRegistros($filtro);

        if (!$historial) {
            exit("Error al obtener el historial de asistencias.");
        }

        $encabezado = [];

        foreach (
            ["Practicante", "Fecha", "Hora de entrada", "Hora de salida", "Estado", "Motivo"]
            as $titulo
        ) {
            $encabezado[] = [$titulo, "encabezado"];
        }

        $filas = [
            [["Historial de asistencia — " . $filtro["titulo"], "titulo"]],
            [["Generado el " . date("d/m/Y H:i"), "subtitulo"]],
            [],
            $encabezado
        ];

        while ($fila = $historial->fetch_assoc()) {

            $estado = $fila["estado"] ?? "";

            if ($estado === "PUNTUAL" || $estado === "ADELANTADO") {
                $estiloEstado = "aprobado";
            } elseif ($estado === "TARDANZA") {
                $estiloEstado = "desaprobado";
            } elseif ($estado === "JUSTIFICADO") {
                $estiloEstado = "sin_datos";
            } else {
                $estiloEstado = "centro";
            }

            $filas[] = [
                [$fila["practicante"] ?? "", "texto"],
                [date("d/m/Y", strtotime($fila["fecha"])), "centro"],
                [$fila["hora_llegada"] ?? "", "centro"],
                [$fila["hora_salida"] ?? "", "centro"],
                [$estado, $estiloEstado],
                [$fila["motivo"] ?? "", "texto"]
            ];
        }

        $excel = new ExcelSimple();

        $excel->agregarHoja(
            "Historial " . $filtro["archivo"],
            [32, 13, 16, 16, 15, 40],
            $filas,
            4
        );

        try {
            $excel->descargar("historial_asistencias_" . $filtro["archivo"] . ".xlsx");
        } catch (Throwable $e) {
            http_response_code(500);
            exit("No se pudo crear el Excel: " . $e->getMessage());
        }
    }
}
