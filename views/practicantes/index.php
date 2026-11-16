<?php

/**
 * Variables que envía PracticanteController.
 *
 * @var mysqli_result|false $practicantes
 * @var array $horariosPorPracticante
 * @var array $periodos
 * @var string|null $periodoActual
 * @var bool $verTodos
 * @var string $vistaActual
 * @var int $activosPeriodo
 */

$horariosPorPracticante = $horariosPorPracticante ?? [];
$periodos               = $periodos ?? [];
$periodoActual          = $periodoActual ?? null;
$verTodos               = $verTodos ?? false;
$vistaActual            = $vistaActual ?? "";
$activosPeriodo         = $activosPeriodo ?? 0;


$mensaje = $_GET["mensaje"] ?? "";
$pasados = (int)($_GET["n"] ?? 0);

// Período que se selecciona por defecto al agregar un practicante
$periodoPorDefecto = $periodoActual ?? ($periodos[0]["periodo"] ?? "");

// Período que se sugiere al crear uno nuevo (el siguiente al actual)
$base = $periodoActual ?? ($periodos[0]["periodo"] ?? (date("Y") . "-I"));
[$anioBase, $semBase] = explode("-", $base);

if ($semBase === "I") {
    $anioSugerido = (int)$anioBase;
    $semSugerido  = "II";
} else {
    $anioSugerido = (int)$anioBase + 1;
    $semSugerido  = "I";
}
?>
<?php
$paginaActual = $_GET["pagina"] ?? "practicantes";
?>



<!DOCTYPE html>

<html lang="es" data-theme="dark">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Practicantes</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">
    <link rel="stylesheet" href="views/practicantes/css/style.css?v=20261116-2">

<script>
    try {
        document.documentElement.dataset.theme = localStorage.getItem('sistema-horario-theme') || 'dark';
    } catch (error) {
        document.documentElement.dataset.theme = 'dark';
    }
</script>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">

            <a class="navbar-brand fw-bold" href="/Sistema_horario/">
                ISHUME
            </a>

           <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarPrincipal"
                aria-controls="navbarPrincipal"
                aria-expanded="false"
                aria-label="Mostrar navegación">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarPrincipal">


                <ul class="navbar-nav ms-auto">

                    <li class="nav-item">
                        <a class="nav-link <?= $paginaActual === 'asistencia' ? 'active' : '' ?>"
                            href="/Sistema_horario/">
                            Inicio
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= $paginaActual === 'practicantes' ? 'active' : '' ?>"
                            href="index.php?pagina=practicantes">
                            Practicantes
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= $paginaActual === 'historial' ? 'active' : '' ?>"
                            href="index.php?pagina=historial">
                            Historial
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?= $paginaActual === 'evaluacion' ? 'active' : '' ?>"
                            href="index.php?pagina=evaluacion">
                            Notas
                        </a>
                    </li>

                </ul>
                <button type="button" class="theme-toggle ms-lg-3" data-theme-toggle role="switch" aria-checked="true" aria-label="Tema oscuro">
                    <span class="theme-toggle-track" aria-hidden="true"><span class="theme-toggle-thumb"></span></span>
                    <span class="theme-toggle-label">Modo oscuro</span>
                </button>


            </div>
        </div>
    </nav>

    <div class="container py-5">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

            <div>
                <h1 class="fw-bold titulo-vista">Practicantes</h1>
                <p class="text-muted mb-0">
                    Gestión de practicantes
                </p>
            </div>

            <button
                type="button"
                class="btn btn-naranja boton-completo"
                data-bs-toggle="modal"
                data-bs-target="#modalNuevoPracticante">
                + Nuevo practicante
            </button>

        </div>


        <!-- Períodos -->
        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
            <span class="text-muted">Período:</span>

            <div class="chips-periodo">
                <a class="chip-periodo <?= $verTodos ? 'activo' : '' ?>"
                    href="index.php?pagina=practicantes&periodo=todos">
                    Todos
                </a>

                <?php foreach ($periodos as $p): ?>
                    <a class="chip-periodo <?= (!$verTodos && $p['periodo'] === $periodoActual) ? 'activo' : '' ?>"
                        href="index.php?pagina=practicantes&periodo=<?= urlencode($p['periodo']) ?>">
                        <?= htmlspecialchars($p['periodo']) ?>
                        <small><?= (int)$p['activos'] ?> activos</small>
                    </a>
                <?php endforeach; ?>

                <button type="button"
                    class="chip-periodo chip-mas"
                    data-bs-toggle="modal"
                    data-bs-target="#modalNuevoPeriodo"
                    title="Nuevo período"
                    aria-label="Nuevo período">
                    +
                </button>
            </div>
        </div>

        <p class="text-muted small mb-3">
            Pulsa «+» para crear un período. El traslado de activos es opcional; usa «Devolver activos» para moverlos en bloque a otro período.
        </p>
        <?php if ($periodoActual !== null && $activosPeriodo > 0): ?>
            <button type="button" class="btn btn-negro btn-sm mb-3"
                data-bs-toggle="modal" data-bs-target="#modalMoverActivos">
                Devolver activos a otro período
            </button>
        <?php endif; ?>

        <div class="card shadow-sm border-0">

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-hover align-middle tabla-movil">

                        <thead class="table-dark">

                            <tr>

                                <th>ID</th>
                                <th>Nombres</th>
                                <th>Apellidos</th>
                                <th>DNI</th>
                                <th>Carrera</th>
                                <th>Semestre</th>
                                <th>Horas semanales</th>
                                <th>Estado</th>
                                <th>Acciones</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if ($practicantes && $practicantes->num_rows > 0): ?>

                                <?php while ($practicante = $practicantes->fetch_assoc()): ?>

                                    <tr class="<?= $practicante['activo'] ? '' : 'fila-retirada' ?>">

                                        <td data-label="ID" class="d-none d-md-table-cell">
                                            <?= $practicante['id_practicante'] ?>
                                        </td>

                                        <td data-label="Nombres">
                                            <?= htmlspecialchars($practicante['nombres']) ?>
                                        </td>

                                        <td data-label="Apellidos">
                                            <?= htmlspecialchars($practicante['apellidos']) ?>
                                        </td>

                                        <td data-label="DNI">
                                            <?= htmlspecialchars($practicante['dni']) ?>
                                        </td>

                                        <td data-label="Carrera">
                                            <?= htmlspecialchars($practicante['carrera']) ?>
                                        </td>
                                        <td data-label="Semestre">
                                            <?= htmlspecialchars($practicante['semestre'] ?? '') ?>
                                        </td>

                                        <td data-label="Horas semanales">
                                            <?= htmlspecialchars($practicante['horas_semanales']) ?>
                                        </td>

                                        <td data-label="Estado">

                                            <?php if ($practicante['activo']): ?>

                                                <span class="badge badge-activo">
                                                    Activo
                                                </span>

                                            <?php else: ?>

                                                <span class="badge badge-retirado">
                                                    Retirado
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td data-label="">
<div class="acciones-fila">

                                            <button
                                                type="button"
                                                class="btn btn-sm btn-negro btn-editar"
                                                data-id="<?= $practicante["id_practicante"] ?>"
                                                data-nombres="<?= htmlspecialchars($practicante["nombres"]) ?>"
                                                data-apellidos="<?= htmlspecialchars($practicante["apellidos"]) ?>"
                                                data-dni="<?= htmlspecialchars($practicante["dni"]) ?>"
                                                data-carrera="<?= htmlspecialchars($practicante["carrera"]) ?>"
                                                data-semestre="<?= htmlspecialchars($practicante["semestre"]) ?>"
                                                data-horas="<?= htmlspecialchars($practicante["horas_semanales"]) ?>"
                                                data-activo="<?= $practicante["activo"] ?>"
                                                data-periodo="<?= htmlspecialchars($practicante["periodo"] ?? "") ?>"
                                                data-horarios="<?= htmlspecialchars(json_encode($horariosPorPracticante[$practicante['id_practicante']] ?? []), ENT_QUOTES, 'UTF-8') ?>">
                                                Editar
                                            </button>

                                            <form method="POST" action="index.php?pagina=practicantes&accion=cambiarEstado" style="display:inline;">
                                                <input type="hidden" name="vista" value="<?= htmlspecialchars($vistaActual) ?>">
                                                <input type="hidden" name="id_practicante" value="<?= $practicante['id_practicante'] ?>">
                                                <input type="hidden" name="activo" value="<?= $practicante['activo'] ? 0 : 1 ?>">
                                                <?php if ($practicante['activo']): ?>
                                                    <button type="submit" class="btn btn-sm btn-gris">
                                                        Retirar
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-sm btn-reactivar">
                                                        Reactivar
                                                    </button>
                                                <?php endif; ?>
                                            </form>
</div>
</td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="9" class="text-center text-muted py-4">

                                        Aún no hay practicantes en este período. Usa «+ Nuevo practicante» para agregarlos.

                                    </td>

                                </tr>

                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>

        </div>

    </div>

    <!-- Modal Nuevo Practicante -->
    <div
        class="modal fade"
        id="modalNuevoPracticante"
        tabindex="-1"
        aria-labelledby="modalNuevoPracticanteLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-fullscreen-sm-down">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title" id="modalNuevoPracticanteLabel">
                        Nuevo practicante
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"
                        aria-label="Cerrar"></button>

                </div>

                <form method="POST" action="index.php?pagina=practicantes&accion=crear">
                    <input type="hidden" name="vista" value="<?= htmlspecialchars($vistaActual) ?>">

                    <div class="modal-body">

                        <div class="row g-3">

                            <!-- Nombres -->
                            <div class="col-md-6">

                                <label for="nombres" class="form-label">
                                    Nombres
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="nombres"
                                    name="nombres"
                                    maxlength="50"
                                    required>
                            </div>

                            <!-- Apellidos -->
                            <div class="col-md-6">

                                <label for="apellidos" class="form-label">
                                    Apellidos
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="apellidos"
                                    name="apellidos"
                                    maxlength="50"
                                    required>

                            </div>

                            <!-- DNI -->
                            <div class="col-md-6">

                                <label for="dni" class="form-label">
                                    DNI
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="dni"
                                    name="dni"
                                    maxlength="8"
                                    minlength="8"
                                    inputmode="numeric"
                                    pattern="[0-9]{8}"
                                    required>

                                <div class="form-text">
                                    Debe contener exactamente 8 números.
                                </div>

                            </div>

                            <!-- Carrera -->
                            <div class="col-md-6">

                                <label for="carrera" class="form-label">
                                    Carrera
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="carrera"
                                    name="carrera"
                                    maxlength="100"
                                    required>

                            </div>

                            <!-- Semestre -->
                            <div class="col-md-6">
                                <label for="semestre" class="form-label">
                                    Semestre
                                </label>
                                <input
                                    type="text"
                                    class="form-control"
                                    id="semestre"
                                    name="semestre"
                                    maxlength="20"
                                    placeholder="Ejemplo: 4TO"
                                    required>
                            </div>

                            <!-- Período -->
                            <div class="col-md-6">
                                <label for="periodo_nuevo" class="form-label">
                                    Período
                                </label>
                                <select
                                    class="form-select"
                                    id="periodo_nuevo"
                                    name="periodo"
                                    required>
                                    <?php foreach ($periodos as $p): ?>
                                        <option
                                            value="<?= htmlspecialchars($p['periodo']) ?>"
                                            <?= $p['periodo'] === $periodoPorDefecto ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['periodo']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>



                        </div>

                        <div class="mt-3">
                            <?php $prefijo = "nuevo"; include __DIR__ . "/_horarios.php"; ?>
                        </div>

                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-gris"
                            data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="btn btn-naranja">
                            Guardar practicante
                        </button>

                    </div>

                </form>

            </div>

        </div>
    </div>
    <!-- Modal Nuevo Período -->
    <div class="modal fade" id="modalNuevoPeriodo" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <form method="POST" action="index.php?pagina=practicantes&accion=nuevoPeriodo">

                    <input type="hidden" name="vista" value="<?= htmlspecialchars($vistaActual) ?>">
                    <input type="hidden" name="desde" value="<?= htmlspecialchars((string)$periodoActual) ?>">

                    <div class="modal-header">
                        <h5 class="modal-title">Nuevo período</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body">

                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label for="periodo_anio" class="form-label">Año</label>
                                <input
                                    type="number"
                                    class="form-control"
                                    id="periodo_anio"
                                    name="anio"
                                    min="2024"
                                    max="2100"
                                    value="<?= $anioSugerido ?>"
                                    required>
                            </div>

                            <div class="col-6">
                                <label for="periodo_semestre" class="form-label">Semestre</label>
                                <select class="form-select" id="periodo_semestre" name="semestre" required>
                                    <option value="I" <?= $semSugerido === "I" ? "selected" : "" ?>>I</option>
                                    <option value="II" <?= $semSugerido === "II" ? "selected" : "" ?>>II</option>
                                </select>
                            </div>
                        </div>

                        <?php if ($periodoActual !== null): ?>
                            <div class="form-check">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="continuar"
                                    value="1"
                                    id="periodo_continuar">
                                <label class="form-check-label" for="periodo_continuar">
                                    Pasar al nuevo período a los
                                    <strong><?= (int)$activosPeriodo ?> practicantes activos</strong>
                                    de <?= htmlspecialchars($periodoActual) ?>.
                                    Los retirados se quedan en <?= htmlspecialchars($periodoActual) ?>.
                                </label>
                            </div>
                        <?php else: ?>
                            <div class="form-text">
                                Para pasar practicantes al período nuevo, primero elige
                                un período de la lista (no «Todos»).
                            </div>
                        <?php endif; ?>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-gris" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-naranja">
                            Crear período
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    <!-- Modal para mover en bloque los practicantes activos -->
    <?php if ($periodoActual !== null && $activosPeriodo > 0): ?>
        <div class="modal fade" id="modalMoverActivos" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" id="formMoverActivosPeriodo"
                        action="index.php?pagina=practicantes&accion=moverActivosPeriodo"
                        data-desde="<?= htmlspecialchars($periodoActual, ENT_QUOTES) ?>"
                        data-cantidad="<?= (int)$activosPeriodo ?>">
                        <input type="hidden" name="desde" value="<?= htmlspecialchars($periodoActual) ?>">
                        <input type="hidden" name="vista" value="<?= htmlspecialchars($vistaActual) ?>">

                        <div class="modal-header">
                            <h5 class="modal-title">Devolver activos en bloque</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <p>Se moverán los <strong><?= (int)$activosPeriodo ?> practicantes activos</strong> de
                                <strong><?= htmlspecialchars($periodoActual) ?></strong> al período que elijas.
                                Seguirán activos y los retirados no se moverán.</p>
                            <label for="periodo_destino_activos" class="form-label">Período destino</label>
                            <input type="text" class="form-control" id="periodo_destino_activos"
                                name="hacia" list="periodosDestinoActivos" pattern="\d{4}-(I|II)"
                                placeholder="Ejemplo: 2026-II" required>
                            <datalist id="periodosDestinoActivos">
                                <?php foreach ($periodos as $p): ?>
                                    <?php if ($p['periodo'] !== $periodoActual): ?>
                                        <option value="<?= htmlspecialchars($p['periodo']) ?>"></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </datalist>
                            <div class="form-text">Puedes elegir un período existente o escribir otro con formato AAAA-I o AAAA-II.</div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-gris" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-naranja">Mover activos</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <!-- Modal Editar Practicante -->
    <div class="modal fade" id="modalEditarPracticante" tabindex="-1">
        <div class="modal-dialog modal-fullscreen-sm-down">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Editar practicante</h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>
                </div>

                <form method="POST" action="index.php?pagina=practicantes&accion=actualizar">
                    <input type="hidden" name="vista" value="<?= htmlspecialchars($vistaActual) ?>">

                    <div class="modal-body">

                        <input
                            type="hidden"
                            name="id_practicante"
                            id="editar_id">

                        <div class="mb-3">
                            <label class="form-label">Nombres</label>

                            <input
                                type="text"
                                class="form-control"
                                name="nombres"
                                id="editar_nombres"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Apellidos</label>

                            <input
                                type="text"
                                class="form-control"
                                name="apellidos"
                                id="editar_apellidos"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">DNI</label>

                            <input
                                type="text"
                                class="form-control"
                                name="dni"
                                id="editar_dni"
                                maxlength="8"
                                minlength="8"
                                inputmode="numeric"
                                pattern="[0-9]{8}"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Carrera</label>

                            <input
                                type="text"
                                class="form-control"
                                name="carrera"
                                id="editar_carrera"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Semestre</label>

                            <input
                                type="text"
                                class="form-control"
                                name="semestre"
                                id="editar_semestre"
                                required>
                        </div>



                        <?php $prefijo = "editar"; include __DIR__ . "/_horarios.php"; ?>

                        <div class="mb-3">
                            <label class="form-label">Período</label>
                            <select
                                class="form-select"
                                name="periodo"
                                id="editar_periodo">
                                <?php foreach ($periodos as $p): ?>
                                    <option value="<?= htmlspecialchars($p['periodo']) ?>">
                                        <?= htmlspecialchars($p['periodo']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Estado</label>

                            <select
                                class="form-select"
                                name="activo"
                                id="editar_activo"
                                required>
                                <option value="1">Activo</option>
                                <option value="0">Retirado</option>
                            </select>
                        </div>

                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-gris"
                            data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="btn btn-naranja">
                            Guardar cambios
                        </button>

                    </div>

                </form>

            </div>
        </div>
    </div>


    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const formulario = document.querySelector('#modalNuevoPracticante form');

        const nombres = document.getElementById('nombres');
        const apellidos = document.getElementById('apellidos');
        const carrera = document.getElementById('carrera');
        const dni = document.getElementById('dni');


        formulario.addEventListener('submit', function(event) {

            // Permite letras, espacios, tildes y ñ
            const soloLetras = /^[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s]+$/;


            // Validar nombres
            if (!soloLetras.test(nombres.value.trim())) {

                event.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: 'Nombres no válidos',
                    text: 'No se permiten números ni símbolos. Utiliza solamente letras y espacios.',
                    confirmButtonText: 'Entendido'
                });

                nombres.focus();

                return;
            }


            // Validar apellidos
            if (!soloLetras.test(apellidos.value.trim())) {

                event.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: 'Apellidos no válidos',
                    text: 'No se permiten números ni símbolos. Utiliza solamente letras y espacios.',
                    confirmButtonText: 'Entendido'
                });

                apellidos.focus();

                return;
            }


            // Validar carrera
            if (!soloLetras.test(carrera.value.trim())) {

                event.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: 'Carrera no válida',
                    text: 'No se permiten números ni símbolos. Utiliza solamente letras y espacios.',
                    confirmButtonText: 'Entendido'
                });

                carrera.focus();

                return;
            }


            // Validar DNI
            if (!/^[0-9]{8}$/.test(dni.value)) {

                event.preventDefault();

                Swal.fire({
                    icon: 'warning',
                    title: 'DNI no válido',
                    text: 'El DNI debe contener exactamente 8 números.',
                    confirmButtonText: 'Entendido'
                });

                dni.focus();

                return;
            }

            // Debe tener al menos un día de trabajo
            if (!formulario.querySelector('.dia-horario:checked')) {
                event.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Faltan los días de trabajo',
                    text: 'Marca al menos un día y escribe su hora de entrada y de salida.',
                    confirmButtonText: 'Entendido'
                });
                return;
            }

        });
    </script>


    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
    </script>


    <script>
        // Al marcar un día se habilitan sus horas; al desmarcarlo se limpian.
        function sincronizarDia(checkbox) {
            const prefijo = checkbox.id.split("_dia_")[0];
            const dia = checkbox.dataset.dia;

            ["entrada", "salida"].forEach(function(tipo) {
                const campo = document.getElementById(prefijo + "_" + tipo + "_" + dia);

                campo.disabled = !checkbox.checked;
                campo.required = checkbox.checked;

                if (!checkbox.checked) {
                    campo.value = "";
                }
            });
        }

        document.querySelectorAll(".dia-horario").forEach(function(checkbox) {
            checkbox.addEventListener("change", function() {
                sincronizarDia(checkbox);
            });
        });
    </script>
    <script>
        const botonesEditar = document.querySelectorAll(".btn-editar");

        botonesEditar.forEach(function(boton) {

            boton.addEventListener("click", function() {

                document.getElementById("editar_id").value = this.dataset.id;
                document.getElementById("editar_nombres").value = this.dataset.nombres;
                document.getElementById("editar_apellidos").value = this.dataset.apellidos;
                document.getElementById("editar_dni").value = this.dataset.dni;
                document.getElementById("editar_carrera").value = this.dataset.carrera;
                document.getElementById("editar_semestre").value = this.dataset.semestre;
                document.getElementById("editar_activo").value = this.dataset.activo;
                document.getElementById("editar_periodo").value = this.dataset.periodo;


                const horarios = JSON.parse(this.dataset.horarios || "[]");

                // Limpiar los días antes de cargar otro practicante
                document.querySelectorAll("#modalEditarPracticante .dia-horario").forEach(function(checkbox) {
                    checkbox.checked = false;
                    sincronizarDia(checkbox);
                });

                // Marcar los días que ya tiene y mostrar sus horas
                horarios.forEach(function(horario) {
                    const checkbox = document.getElementById("editar_dia_" + horario.dia_semana);

                    if (!checkbox) {
                        return;
                    }

                    checkbox.checked = true;
                    sincronizarDia(checkbox);

                    document.getElementById("editar_entrada_" + horario.dia_semana).value =
                        (horario.hora_entrada || "").substring(0, 5);

                    document.getElementById("editar_salida_" + horario.dia_semana).value =
                        (horario.hora_salida || "").substring(0, 5);
                });

                const modal = new bootstrap.Modal(
                    document.getElementById("modalEditarPracticante")
                );

                modal.show();
            });

        });
    </script>
    <script>
        <?php if ($mensaje === "periodo_creado"): ?>
            Swal.fire({
                icon: "success",
                title: "Período listo",
                text: <?= json_encode(
                            $pasados > 0
                                ? "Se pasaron " . $pasados . " practicantes activos a este período."
                                : "Ya puedes agregar practicantes a este período.",
                            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                        ) ?>,
                confirmButtonColor: "#ff8800"
            });
        <?php endif; ?>

        <?php if ($mensaje === "activos_movidos"): ?>
            Swal.fire({
                icon: "success",
                title: "Período actualizado",
                text: <?= json_encode(
                    "Se movieron " . $pasados . " practicantes activos de " .
                    ($_GET["desde"] ?? "") . " a " . ($_GET["hacia"] ?? "") . ".",
                    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                ) ?>,
                confirmButtonColor: "#ff8800"
            });
        <?php endif; ?>
        <?php if ($mensaje === "creado"): ?>
            Swal.fire({
                icon: "success",
                title: "¡Practicante agregado!",
                text: "El practicante se registró correctamente.",
                confirmButtonColor: "#ff8800"
            });
        <?php endif; ?>

        <?php if ($mensaje === "error"): ?>
            Swal.fire({
                icon: "error",
                title: "No se pudo guardar",
                text: <?= json_encode(
                            $_GET["detalle"] ?? "Ocurrió un error.",
                            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                        ) ?>,
                confirmButtonColor: "#ff8800"
            });
        <?php endif; ?>

        <?php if ($mensaje === "actualizado"): ?>
            Swal.fire({
                icon: "success",
                title: "¡Actualizado!",
                text: "El practicante se ha actualizado correctamente.",
                confirmButtonColor: "#ff8800"
            });
        <?php endif; ?>
    </script>


    <script>
        const formMoverActivos = document.getElementById("formMoverActivosPeriodo");
        if (formMoverActivos) {
            formMoverActivos.addEventListener("submit", function (event) {
                event.preventDefault();
                const destino = document.getElementById("periodo_destino_activos").value.trim();
                const desde = this.dataset.desde;
                const cantidad = Number(this.dataset.cantidad);

                if (destino === desde) {
                    Swal.fire({ icon: "warning", title: "Elige otro período", text: "El período destino debe ser distinto al actual." });
                    return;
                }

                Swal.fire({
                    icon: "question",
                    title: "¿Mover todos los activos?",
                    text: `Se moverán ${cantidad} practicantes activos de ${desde} a ${destino}. Seguirán activos.`,
                    showCancelButton: true,
                    confirmButtonText: "Sí, moverlos",
                    cancelButtonText: "Cancelar",
                    confirmButtonColor: "#ff8800"
                }).then((resultado) => {
                    if (resultado.isConfirmed) this.submit();
                });
            });
        }
    </script>

    <script>
        (function () {
            const root = document.documentElement;
            const toggle = document.querySelector('[data-theme-toggle]');
            if (!toggle) return;

            function updateToggle() {
                const isDark = root.dataset.theme === 'dark';
                toggle.querySelector('.theme-toggle-label').textContent = isDark ? 'Modo oscuro' : 'Modo claro';
                toggle.setAttribute('aria-label', isDark ? 'Tema oscuro' : 'Tema claro');
                toggle.setAttribute('aria-checked', String(isDark));
            }

            toggle.addEventListener('click', function () {
                const nextTheme = root.dataset.theme === 'dark' ? 'light' : 'dark';
                root.dataset.theme = nextTheme;
                try {
                    localStorage.setItem('sistema-horario-theme', nextTheme);
                } catch (error) {
                    // El tema seguirá funcionando aunque el navegador no permita guardar preferencias.
                }
                updateToggle();
            });

            updateToggle();
        }());
    </script>
</body>

</html>
