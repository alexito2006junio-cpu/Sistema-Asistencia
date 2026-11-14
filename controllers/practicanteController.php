<?php

require_once __DIR__ . "/../models/Practicante.php";

class PracticanteController
{
    private $modelo;

    public function __construct($conexion)
    {
        $this->modelo = new Practicante($conexion);
    }


    // Un período se escribe "AÑO-I" o "AÑO-II", por ejemplo 2026-II
    private function periodoValido($periodo)
    {
        if (is_string($periodo) && preg_match('/^\d{4}-(I|II)$/', $periodo)) {
            return $periodo;
        }

        return null;
    }

    // Dirección de la lista de practicantes para un período
    private function urlPeriodo($periodo, $extra = "")
    {
        return "index.php?pagina=practicantes&periodo=" .
            urlencode($periodo) . $extra;
    }

    // Dirección de la lista manteniendo el período que se estaba viendo
    private function urlLista($extra = "")
    {
        $vista = $_POST["vista"] ?? "";

        if ($vista === "todos" || $this->periodoValido($vista) !== null) {
            return $this->urlPeriodo($vista, $extra);
        }

        return "index.php?pagina=practicantes" . $extra;
    }


    public function index()
    {
        $periodos = $this->modelo->obtenerPeriodos();

        // Qué período se está viendo
        $pedido   = $_GET["periodo"] ?? "";
        $verTodos = ($pedido === "todos");

        $periodoActual = null;

        if (!$verTodos) {

            $periodoActual = $this->periodoValido($pedido);

            // Si no se pidió ninguno, se muestra el más reciente
            if ($periodoActual === null && count($periodos) > 0) {
                $periodoActual = $periodos[0]["periodo"];
            }
        }

        // Un período recién creado y todavía vacío también aparece en la lista
        $activosPeriodo = 0;
        $existe = false;

        foreach ($periodos as $p) {
            if ($p["periodo"] === $periodoActual) {
                $existe = true;
                $activosPeriodo = $p["activos"];
            }
        }

        if ($periodoActual !== null && !$existe) {

            $periodos[] = [
                "periodo" => $periodoActual,
                "total"   => 0,
                "activos" => 0
            ];

            usort($periodos, function ($a, $b) {
                return strcmp($b["periodo"], $a["periodo"]);
            });
        }

        // Valor que viaja en los formularios para volver a esta misma vista
        $vistaActual = $verTodos ? "todos" : (string)$periodoActual;

        $practicantes = $this->modelo->obtenerTodos($periodoActual);

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
        $practicantes = $this->modelo->obtenerTodos($periodoActual);

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
        header("Location: " . $this->urlLista(
            "&mensaje=error&detalle=" . urlencode($mensaje)
        ));
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

        $periodo = $this->periodoValido($_POST["periodo"] ?? "");

        if ($periodo === null) {
            $this->volverConError("Elige el período del practicante.");
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
            $horarios,
            $periodo
        );

        if (!$ok) {
            $this->volverConError("No se pudo guardar el practicante.");
        }

        // Se muestra el período donde quedó el practicante nuevo
        header("Location: " . $this->urlPeriodo($periodo, "&mensaje=creado"));
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

        // Cambio de período (si se eligió uno válido)
        $periodo = $this->periodoValido($_POST["periodo"] ?? "");

        if ($periodo !== null) {
            $this->modelo->actualizarPeriodo($id, $periodo);
        }

        header("Location: " . $this->urlLista("&mensaje=actualizado"));
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

        header("Location: " . $this->urlLista());
        exit;
    }


    // =====================================================
    // Crea un período nuevo (por ejemplo 2027-I) y, si se
    // pide, pasa a él a los practicantes ACTIVOS del período
    // que se estaba viendo. Los retirados se quedan donde
    // estaban: no se borra a nadie.
    // =====================================================
    public function nuevoPeriodo()
    {
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: index.php?pagina=practicantes");
            exit;
        }

        $anio     = (int)($_POST["anio"] ?? 0);
        $semestre = $_POST["semestre"] ?? "";

        if ($anio < 2024 || $anio > 2100 || !in_array($semestre, ["I", "II"], true)) {
            $this->volverConError("Escribe un año y un semestre válidos.");
        }

        $nuevo = $anio . "-" . $semestre;
        $desde = $this->periodoValido($_POST["desde"] ?? "");

        $pasados = 0;

        if (isset($_POST["continuar"]) && $desde !== null) {

            if ($desde === $nuevo) {
                $this->volverConError("El período nuevo debe ser distinto al actual.");
            }

            $pasados = $this->modelo->moverActivos($desde, $nuevo);
        }

        header("Location: " . $this->urlPeriodo(
            $nuevo,
            "&mensaje=periodo_creado&n=" . (int)$pasados
        ));
        exit;
    }
}
