<?php

require_once __DIR__ . "/config/conexion.php";

$pagina = $_GET["pagina"] ?? "asistencia";
$accion = $_GET["accion"] ?? "index";

if ($pagina === "asistencia") {

    require_once __DIR__ . "/controllers/asistenciaController.php";

    $controller = new AsistenciaController($conexion);

    if ($accion === "registrarEntrada") {
        $controller->registrarEntrada();
    } elseif ($accion === "registrarSalida") {
        $controller->registrarSalida();
    } else {
        $controller->index();
    }
} elseif ($pagina === "practicantes") {

    require_once __DIR__ . "/controllers/practicanteController.php";

    $controller = new PracticanteController($conexion);

    if ($accion === "crear") {
        $controller->crear();
    } elseif ($accion === "actualizar") {
        $controller->actualizar();
    } elseif ($accion === "cambiarEstado") {
        $controller->cambiarEstado();
    } else {
        $controller->index();
    }
} elseif ($pagina === "historial") {

    require_once __DIR__ . "/controllers/historialController.php";

    $controller = new HistorialController($conexion);

    if ($accion === "exportarExcel") {
        $controller->exportarExcel();
    } else {
        $controller->index();
    }
} elseif ($pagina === "evaluacion") {

    require_once __DIR__ . "/controllers/evaluacionController.php";

    $controller = new EvaluacionController($conexion);

    if ($accion === "justificar") {
        $controller->justificar();
    } elseif ($accion === "quitarJustificacion") {
        $controller->quitarJustificacion();
    } else {
        $controller->index();
    }
} else {
    // Página desconocida: volver al inicio
    header("Location: index.php");
    exit;
}
