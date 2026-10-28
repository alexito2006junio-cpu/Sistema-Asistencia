<?php

/** @var mysqli_result|false $practicantes */

$mensaje = $_GET["mensaje"] ?? "";
?>
<?php
$paginaActual = $_GET["pagina"] ?? "practicantes";
?>



<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Practicantes</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>
        .navbar {
            background-color: #111;
        }

        .navbar-brand {
            color: #ff8800 !important;
            font-weight: bold;
        }

        .navbar .nav-link {
            color: #fff !important;
            transition: color 0.2s;
        }

        .navbar .nav-link.active,
        .navbar .nav-link:hover {
            color: #ff8800 !important;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">

            <a class="navbar-brand fw-bold" href="/Sistema_horario/">
                ISHUME - Productora Audiovisual
            </a>

           <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarPrincipal"
                aria-controls="navbarPrincipal"
                aria-expanded="false"
                aria-label="Mostrar navegación">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSistema">


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


            </div>
        </div>
    </nav>

    <div class="container py-5">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h1 class="fw-bold">Practicantes</h1>
                <p class="text-muted mb-0">
                    Gestión de practicantes
                </p>
            </div>

            <button
                type="button"
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#modalNuevoPracticante">
                + Nuevo practicante
            </button>

        </div>


        <div class="card shadow-sm border-0">

            <div class="card-body">

                <div class="table-responsive">

                    <table class="table table-hover align-middle">

                        <thead class="table-dark">

                            <tr>

                                <th>ID</th>
                                <th>Nombres</th>
                                <th>Apellidos</th>
                                <th>DNI</th>
                                <th>Carrera</th>
                                <th>Horas semanales</th>
                                <th>Estado</th>
                                <th>Acciones</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php if ($practicantes && $practicantes->num_rows > 0): ?>

                                <?php while ($practicante = $practicantes->fetch_assoc()): ?>

                                    <tr>

                                        <td>
                                            <?= $practicante['id_practicante'] ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($practicante['nombres']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($practicante['apellidos']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($practicante['dni']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($practicante['carrera']) ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($practicante['horas_semanales']) ?>
                                        </td>

                                        <td>

                                            <?php if ($practicante['activo']): ?>

                                                <span class="badge text-bg-success">
                                                    Activo
                                                </span>

                                            <?php else: ?>

                                                <span class="badge text-bg-secondary">
                                                    Inactivo
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>

                                            <button
                                                type="button"
                                                class="btn btn-warning btn-editar"
                                                data-id="<?= $practicante["id_practicante"] ?>"
                                                data-nombres="<?= htmlspecialchars($practicante["nombres"]) ?>"
                                                data-apellidos="<?= htmlspecialchars($practicante["apellidos"]) ?>"
                                                data-dni="<?= htmlspecialchars($practicante["dni"]) ?>"
                                                data-carrera="<?= htmlspecialchars($practicante["carrera"]) ?>"
                                                data-semestre="<?= htmlspecialchars($practicante["semestre"]) ?>"
                                                data-horas="<?= htmlspecialchars($practicante["horas_semanales"]) ?>"
                                                data-activo="<?= $practicante["activo"] ?>"
                                                data-horarios="<?= htmlspecialchars(json_encode($horariosPorPracticante[$practicante['id_practicante']] ?? []), ENT_QUOTES, 'UTF-8') ?>">
                                                Editar
                                            </button>

                                            <form method="POST" action="index.php?pagina=practicantes&accion=cambiarEstado" style="display:inline;">
                                                <input type="hidden" name="id_practicante" value="<?= $practicante['id_practicante'] ?>">
                                                <input type="hidden" name="activo" value="<?= $practicante['activo'] ? 0 : 1 ?>">
                                                <?php if ($practicante['activo']): ?>
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        Inactivar
                                                    </button>
                                                <?php else: ?>
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        Activar
                                                    </button>
                                                <?php endif; ?>
                                            </form>

                                        </td>

                                    </tr>

                                <?php endwhile; ?>

                            <?php else: ?>

                                <tr>

                                    <td colspan="8" class="text-center text-muted py-4">

                                        No hay practicantes registrados.

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
        <div class="modal-dialog modal-lg modal-dialog-centered">

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



                        </div>

                        <div class="mt-3">
                            <?php $prefijo = "nuevo"; include __DIR__ . "/_horarios.php"; ?>
                        </div>

                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="btn btn-primary">
                            Guardar practicante
                        </button>

                    </div>

                </form>

            </div>

        </div>
    </div>
    <!-- Modal Editar Practicante -->
    <div class="modal fade" id="modalEditarPracticante" tabindex="-1">
        <div class="modal-dialog">
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
                            <label class="form-label">Estado</label>

                            <select
                                class="form-select"
                                name="activo"
                                id="editar_activo"
                                required>
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                        </div>

                    </div>

                    <div class="modal-footer">

                        <button
                            type="button"
                            class="btn btn-secondary"
                            data-bs-dismiss="modal">
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="btn btn-primary">
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


</body>

</html>