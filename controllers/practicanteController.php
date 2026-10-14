<?php

require_once __DIR__ . "/../models/Practicante.php";

class PracticanteController
{
    private $modelo;

    public function __construct($conexion)
    {
        $this->modelo = new Practicante($conexion);
    }


    public function index()
    {
        $practicantes = $this->modelo->obtenerTodos();

        $horariosPorPracticante = [];

        while ($fila = $practicantes->fetch_assoc()) {

            $id = $fila["id_practicante"];

            $resultadoHorarios =
                $this->modelo->obtenerHorariosPorPracticante($id);

            $horariosPorPracticante[$id] = [];

            while ($horario = $resultadoHorarios->fetch_assoc()) {
                $horariosPorPracticante[$id][] = $horario;
            }
        }

        // Volvemos a consultar para que la tabla se muestre normalmente.
        $practicantes = $this->modelo->obtenerTodos();

        require __DIR__ . "/../views/practicantes/index.php";
    }

    public function actualizar()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            $id = $_POST["id_practicante"];
            $nombres = $_POST["nombres"];
            $apellidos = $_POST["apellidos"];
            $dni = $_POST["dni"];
            $carrera = $_POST["carrera"];
            $semestre = $_POST["semestre"];
            $horas_semanales = $_POST["horas_semanales"];
            $activo = $_POST["activo"];

            $resultado = $this->modelo->actualizar(
                $id,
                $nombres,
                $apellidos,
                $dni,
                $carrera,
                $semestre,
                $horas_semanales,
                $activo
            );

            if (!$resultado) {
                die("Error al actualizar el practicante.");
            }

            $dias = $_POST["dias"] ?? [];

            $resultadoDias = $this->modelo->actualizarDiasTrabajo(
                $id,
                $dias
            );

            if (!$resultadoDias) {
                die("No se pudieron actualizar los días de trabajo.");
            }

            header("Location: index.php?pagina=practicantes&mensaje=actualizado");
            exit;
        }
    }

    public function cambiarEstado()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            $id = $_POST["id_practicante"];
            $activo = $_POST["activo"];

            $this->modelo->cambiarEstado($id, $activo);

            header("Location: index.php?pagina=practicantes");
            exit;
        }
    }
}
