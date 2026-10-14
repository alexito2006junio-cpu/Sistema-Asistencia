<?php

require_once __DIR__ . "/../models/Practicante.php";
require_once __DIR__ . "/../models/Asistencia.php";

class AsistenciaController
{
    private $modeloPracticante;
    private $modeloAsistencia;

    public function __construct($conexion)
    {
        $this->modeloPracticante = new Practicante($conexion);
        $this->modeloAsistencia = new Asistencia($conexion);
    }

    public function index()
    {
        $dias = [
            1 => "LUNES",
            2 => "MARTES",
            3 => "MIERCOLES",
            4 => "JUEVES",
            5 => "VIERNES",
            6 => "SABADO",
            7 => "DOMINGO"
        ];

        $dia = $dias[(int)date("N")];

        $practicantes = $this->modeloPracticante->obtenerPorDia($dia);


        require __DIR__ . "/../views/asistencia/index.php";
    }

    public function registrarEntrada()
    {
        header("Content-Type: application/json; charset=utf-8");

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            echo json_encode([
                "ok" => false,
                "mensaje" => "Método no permitido."
            ]);
            exit;
        }

        $id_practicante = $_POST["id_practicante"] ?? null;

        if (!$id_practicante) {
            echo json_encode([
                "ok" => false,
                "mensaje" => "No se recibió el practicante."
            ]);
            exit;
        }

        // Verificar que esté activo
        if (!$this->modeloAsistencia->practicanteActivo($id_practicante)) {
            echo json_encode([
                "ok" => false,
                "mensaje" => "Este practicante está inactivo."
            ]);
            exit;
        }

        // Fecha y hora actuales
        $fecha = date("Y-m-d");
        $hora_llegada = date("H:i:s");

        // Verificar si ya registró hoy
        if ($this->modeloAsistencia->yaRegistroHoy($id_practicante, $fecha)) {

            echo json_encode([
                "ok" => false,
                "mensaje" => "Ya registraste tu asistencia hoy."
            ]);

            exit;
        }

        // Obtener día actual
        $dias = [
            1 => "LUNES",
            2 => "MARTES",
            3 => "MIERCOLES",
            4 => "JUEVES",
            5 => "VIERNES",
            6 => "SABADO",
            7 => "DOMINGO"
        ];

        $dia = $dias[(int)date("N")];

        // Obtener horario del practicante
        $horario = $this->modeloAsistencia->obtenerHorario(
            $id_practicante,
            $dia
        );

        if (!$horario) {

            echo json_encode([
                "ok" => false,
                "mensaje" => "El practicante no tiene un horario registrado para hoy."
            ]);

            exit;
        }

        $hora_programada = $horario["hora_entrada"];

        // Convertir horas a minutos para compararlas
        $horaActual = strtotime($hora_llegada);
        $horaEntrada = strtotime($hora_programada);

        // Tolerancia de 15 minutos
        $horaLimite = strtotime("+15 minutes", $horaEntrada);

        if ($horaActual < $horaEntrada) {

            $estado = "ADELANTADO";
        } elseif ($horaActual <= $horaLimite) {

            $estado = "PUNTUAL";
        } else {

            $estado = "TARDANZA";
        }

        // Registrar
        $resultado = $this->modeloAsistencia->registrar(
            $id_practicante,
            $fecha,
            $hora_llegada,
            $estado
        );

        if (!$resultado) {

            echo json_encode([
                "ok" => false,
                "mensaje" => "No se pudo registrar la asistencia."
            ]);

            exit;
        }

        echo json_encode([
            "ok" => true,
            "mensaje" => "Asistencia registrada correctamente.",
            "estado" => $estado,
            "hora_llegada" => $hora_llegada,
            "hora_programada" => $hora_programada
        ]);

        exit;
    }

    public function registrarSalida()
    {
        header("Content-Type: application/json; charset=utf-8");

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            echo json_encode([
                "ok" => false,
                "mensaje" => "Método no permitido."
            ]);
            exit;
        }

        $id_practicante = $_POST["id_practicante"] ?? null;

        if (!$id_practicante) {
            echo json_encode([
                "ok" => false,
                "mensaje" => "No se recibió el practicante."
            ]);
            exit;
        }

        $fecha = date("Y-m-d");
        $hora_salida = date("H:i:s");

        $resultado = $this->modeloAsistencia->registrarSalida(
            $id_practicante,
            $fecha,
            $hora_salida
        );

        if ($resultado) {
            echo json_encode([
                "ok" => true,
                "mensaje" => "Salida registrada correctamente.",
                "hora_salida" => $hora_salida
            ]);
        } else {
            echo json_encode([
                "ok" => false,
                "mensaje" => "No se pudo registrar la salida."
            ]);
        }

        exit;
    }
}
