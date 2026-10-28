<?php

require_once __DIR__ . "/../models/Evaluacion.php";

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
}
