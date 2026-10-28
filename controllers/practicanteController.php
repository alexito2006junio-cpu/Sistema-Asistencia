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

    // =====================================================
    // Lee y valida los datos del formulario (crear y editar)
    // Devuelve [datos, horarios, error]
    // =====================================================
    private function leerFormulario()
    {
        $nombres   = trim($_POST["nombres"] ?? "");
        $apellidos = trim($_POST["apellidos"] ?? "");
        $dni       = trim($_POST["dni"] ?? "");
        $carrera   = trim($_POST["carrera"] ?? "");
        $semestre  = trim($_POST["semestre"] ?? "");

        $soloLetras = '/^[\p{L}\s]+$/u';

        if (!preg_match($soloLetras, $nombres) || mb_strlen($nombres) > 50) {
            return [null, null, "Los nombres solo pueden tener letras y espacios."];
        }

        if (!preg_match($soloLetras, $apellidos) || mb_strlen($apellidos) > 50) {
            return [null, null, "Los apellidos solo pueden tener letras y espacios."];
        }

        if (!preg_match($soloLetras, $carrera) || mb_strlen($carrera) > 100) {
            return [null, null, "La carrera solo puede tener letras y espacios."];
        }

        if (!preg_match('/^[0-9]{8}$/', $dni)) {
            return [null, null, "El DNI debe tener exactamente 8 números."];
        }

        if ($semestre === "" || mb_strlen($semestre) > 20) {
            return [null, null, "Escribe el semestre (por ejemplo 4TO)."];
        }

        // ---------- Días y horarios ----------
        $permitidos = [
            "LUNES", "MARTES", "MIERCOLES", "JUEVES",
            "VIERNES", "SABADO", "DOMINGO"
        ];

        $dias = array_values(array_unique($_POST["dias"] ?? []));
        $horas = $_POST["horarios"] ?? [];

        $horarios = [];

        foreach ($dias as $dia) {

            if (!in_array($dia, $permitidos, true)) {
                return [null, null, "Día de trabajo no válido."];
            }

            $entrada = $this->normalizarHora($horas[$dia]["hora_entrada"] ?? "");
            $salida  = $this->normalizarHora($horas[$dia]["hora_salida"] ?? "");

            if ($entrada === null || $salida === null) {
                return [null, null, "Completa la hora de entrada y de salida de cada día marcado."];
            }

            if ($salida <= $entrada) {
                return [null, null, "En " . ucfirst(strtolower($dia)) .
                    " la hora de salida debe ser mayor que la de entrada."];
            }

            $horarios[$dia] = [$entrada, $salida];
        }

        $datos = [
            "nombres"   => $nombres,
            "apellidos" => $apellidos,
            "dni"       => $dni,
            "carrera"   => $carrera,
            "semestre"  => $semestre
        ];

        return [$datos, $horarios, null];
    }

    // "8:00" / "08:00" / "08:00:00" -> "08:00:00" (o null si no es válida)
    private function normalizarHora($hora)
    {
        if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)(:[0-5]\d)?$/', trim($hora), $m)) {
            return null;
        }

        return sprintf("%02d:%s:00", $m[1], $m[2]);
    }

    private function volverConError($mensaje)
    {
        header(
            "Location: index.php?pagina=practicantes&mensaje=error&detalle=" .
                urlencode($mensaje)
        );
        exit;
    }


    public function crear()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: index.php?pagina=practicantes");
            exit;
        }

        [$datos, $horarios, $error] = $this->leerFormulario();

        if ($error) {
            $this->volverConError($error);
        }

        if (count($horarios) === 0) {
            $this->volverConError("Marca al menos un día de trabajo.");
        }

        if ($this->modelo->existeDni($datos["dni"])) {
            $this->volverConError("Ya existe un practicante con ese DNI.");
        }

        $ok = $this->modelo->crear(
            $datos["nombres"],
            $datos["apellidos"],
            $datos["dni"],
            $datos["carrera"],
            $datos["semestre"],
            $horarios
        );

        if (!$ok) {
            $this->volverConError("No se pudo guardar el practicante.");
        }

        header("Location: index.php?pagina=practicantes&mensaje=creado");
        exit;
    }


    public function actualizar()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: index.php?pagina=practicantes");
            exit;
        }

        $id = (int)($_POST["id_practicante"] ?? 0);
        $activo = (($_POST["activo"] ?? "1") === "0") ? 0 : 1;

        if ($id <= 0) {
            $this->volverConError("Practicante no válido.");
        }

        [$datos, $horarios, $error] = $this->leerFormulario();

        if ($error) {
            $this->volverConError($error);
        }

        if ($this->modelo->existeDni($datos["dni"], $id)) {
            $this->volverConError("Ya existe otro practicante con ese DNI.");
        }

        // Primero los horarios, para calcular las horas semanales
        if (!$this->modelo->actualizarHorarios($id, $horarios)) {
            $this->volverConError("No se pudieron actualizar los días y horarios.");
        }

        $horas = $this->modelo->calcularHorasSemanales($horarios);

        $resultado = $this->modelo->actualizar(
            $id,
            $datos["nombres"],
            $datos["apellidos"],
            $datos["dni"],
            $datos["carrera"],
            $datos["semestre"],
            $horas,
            $activo
        );

        if (!$resultado) {
            $this->volverConError("No se pudieron guardar los datos del practicante.");
        }

        header("Location: index.php?pagina=practicantes&mensaje=actualizado");
        exit;
    }


    public function cambiarEstado()
    {
        if ($_SERVER["REQUEST_METHOD"] === "POST") {

            $id = (int)($_POST["id_practicante"] ?? 0);
            $activo = (($_POST["activo"] ?? "") === "1") ? 1 : 0;

            if ($id > 0) {
                $this->modelo->cambiarEstado($id, $activo);
            }
        }

        header("Location: index.php?pagina=practicantes");
        exit;
    }
}
