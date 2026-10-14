<?php

require_once __DIR__ . "/../models/Asistencia.php";

class HistorialController
{
    private $modelo;

    public function __construct($conexion)
    {
        $this->modelo = new Asistencia($conexion);
    }

    public function index()
    {
        $desde = $_GET["desde"] ?? "";
        $hasta = $_GET["hasta"] ?? "";
        $mes = $_GET["mes"] ?? "";

        // Variable para el resumen mensual
        $resumenMensual = [];

        // Si se seleccionó un mes
        if ($mes !== "") {

            $fecha = DateTime::createFromFormat("Y-m", $mes);

            if ($fecha) {

                // Primer día del mes
                $desde = $fecha->format("Y-m-01");

                // Último día del mes
                $hasta = $fecha->format("Y-m-t");

                // Obtener resumen de faltas y tardanzas
                $resumenMensual = $this->modelo->obtenerResumenMensual(
                    (int)$fecha->format("m"),
                    (int)$fecha->format("Y")
                );
            }
        }

        if ($desde !== "" && $hasta !== "") {

            $historial = $this->modelo->obtenerHistorial(
                $desde,
                $hasta
            );
        } else {

            $historial = $this->modelo->obtenerHistorial();
        }

        require __DIR__ . "/../views/historial/index.php";
    }


    public function exportarExcel()
    {
        // Obtener los filtros seleccionados.
        $desde = $_GET["desde"] ?? "";
        $hasta = $_GET["hasta"] ?? "";
        $mes = $_GET["mes"] ?? "";

        // Si se seleccionó un mes, calcular su rango de fechas.
        if ($mes !== "") {

            $fecha = DateTime::createFromFormat("!Y-m", $mes);

            if (!$fecha || $fecha->format("Y-m") !== $mes) {
                exit("El mes seleccionado no es válido.");
            }

            $desde = $fecha->format("Y-m-01");
            $hasta = $fecha->format("Y-m-t");
        }

        // Obtener el historial usando el modelo existente.
        if ($desde !== "" && $hasta !== "") {

            $historial = $this->modelo->obtenerHistorial(
                $desde,
                $hasta
            );  
        } else {

            $historial = $this->modelo->obtenerHistorial();
        }

        if (!$historial) {
            exit("Error al obtener el historial de asistencias.");
        }

        // Preparar el archivo para descargar.
        $nombreArchivo = "historial_asistencias_" . date("Y-m-d") . ".csv";

        header("Content-Type: text/csv; charset=UTF-8");
        header(
            'Content-Disposition: attachment; filename="' .
                $nombreArchivo . '"'
        );
        header("Cache-Control: no-cache, no-store, must-revalidate");

        $archivo = fopen("php://output", "w");

        // Permitir que Excel reconozca las tildes y la ñ.
        fwrite($archivo, "\xEF\xBB\xBF");

        // Encabezados de las columnas.
        fputcsv($archivo, [
            "Practicante",
            "Fecha",
            "Hora de entrada",
            "Hora de salida",
            "Estado"
        ], ";");

        // Escribir los registros del historial.
        while ($fila = $historial->fetch_assoc()) {

            fputcsv($archivo, [
                $fila["practicante"] ?? "",
                $fila["fecha"] ?? "",
                $fila["hora_llegada"] ?? "",
                $fila["hora_salida"] ?? "",
                $fila["estado"] ?? ""
            ], ";");
        }

        fclose($archivo);
        exit;
    }
}
