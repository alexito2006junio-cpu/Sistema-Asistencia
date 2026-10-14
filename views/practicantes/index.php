<?php

/** @var mysqli_result|false $practicantes */

$mensaje = $_GET["mensaje"] ?? "";
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
            background-color: #000000 !important;
        }

        .navbar-brand {
            color: #ff8800 !important;
        }

        .navbar-brand:hover {
            color: #ff8800 !important;
        }

        .nav-link {
            color: #ffffff !important;
        }

        .nav-link:hover,
        .nav-link.active {
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

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarSistema">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSistema">

                <ul class="navbar-nav ms-auto">

                    <!-- INICIO -->
                    <li class="nav-item">
                        <a class="nav-link active" href="/Sistema_horario/">
                            Inicio
                        </a>
                    </li>

                    <!-- PRACTICANTES -->
                    <li class="nav-item">
                        <a class="nav-link" href="/Sistema_horario/?pagina=practicantes">
                            Practicantes
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link active" href="/Sistema_horario/?pagina=historial">
                            Historial
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
                                            

                                            </button>

                                            <form method="POST" action="index.php?pagina=practicantes&accion=cambiarEstado" style="display:inline;">
                                                <input type="hidden" name="id_practicante" value="<?= $practicante['id_practicante'] ?>">
                                                <input type="hidden" name="activo" value="0">

                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    Inactivar
                                                </button>
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

                <form method="POST" action="">

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

                            <!-- Horas semanales -->
                            <div class="col-md-6">

                                <label for="horas_semanales" class="form-label">
                                    Horas semanales
                                </label>

                                <input
                                    type="text"
                                    class="form-control"
                                    id="horas_semanales"
                                    name="horas_semanales"
                                    required>

                            </div>

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

                        <div class="mb-3">
                            <label class="form-label">Horas semanales</label>

                            <input
                                type="text"
                                class="form-control"
                                name="horas_semanales"
                                id="editar_horas"
                                required>
                        </div>


                        <div class="mb-3">
                            <label class="form-label fw-bold">Días y horarios de trabajo</label>

                            <?php
                            $diasSemana = [
                                "LUNES",
                                "MARTES",
                                "MIERCOLES",
                                "JUEVES",
                                "VIERNES",
                                "SABADO",
                                "DOMINGO"
                            ];
                            ?>

                            <?php foreach ($diasSemana as $dia): ?>
                                <div class="border rounded p-3 mb-2">
                                    <div class="form-check mb-2">
                                        <input
                                            class="form-check-input dia-horario"
                                            type="checkbox"
                                            name="dias[]"
                                            value="<?= $dia ?>"
                                            id="editar_dia_<?= $dia ?>">

                                        <label
                                            class="form-check-label fw-bold"
                                            for="editar_dia_<?= $dia ?>">
                                            <?= ucfirst(strtolower($dia)) ?>
                                        </label>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label">Hora de entrada</label>
                                            <input
                                                type="time"
                                                class="form-control hora-entrada"
                                                name="horarios[<?= $dia ?>][hora_entrada]"
                                                id="entrada_<?= $dia ?>"
                                                step="60">
                                        </div>

                                        <div class="col-6">
                                            <label class="form-label">Hora de salida</label>
                                            <input
                                                type="time"
                                                class="form-control hora-salida"
                                                name="horarios[<?= $dia ?>][hora_salida]"
                                                id="salida_<?= $dia ?>"
                                                step="60">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>




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

        });
    </script>


    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
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
                document.getElementById("editar_horas").value = this.dataset.horas;
                document.getElementById("editar_activo").value = this.dataset.activo;


                const horarios = JSON.parse(this.dataset.horarios || "[]");

                // Desmarcar los días antes de cargar otro practicante
                document.querySelectorAll(".dia-horario").forEach(function(checkbox) {
                    checkbox.checked = false;
                });

                // Marcar los días que ya tiene asignados
                horarios.forEach(function(horario) {
                    const checkbox = document.getElementById(
                        "editar_dia_" + horario.dia_semana
                    );

                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });

                const modal = new bootstrap.Modal(
                    document.getElementById("modalEditarPracticante")
                );

                modal.show();
            });

        });
    </script>
    <script>
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