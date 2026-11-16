<?php

/** @var mysqli_result|false $practicantes */
?>

<?php
$paginaActual = $_GET["pagina"] ?? "asistencia";
?>

<!DOCTYPE html>
<html lang="es" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registrar asistencia</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">
    <link rel="stylesheet" href="views/asistencia/css/style.css?v=20261116-4">

<script>
    try {
        document.documentElement.dataset.theme = localStorage.getItem('sistema-horario-theme') || 'dark';
    } catch (error) {
        document.documentElement.dataset.theme = 'dark';
    }
</script>
</head>
<script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<body class="bg-light">
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

    <div class="container py-4">



        <!-- Encabezado -->
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

            <div>
                <h2 class="fw-bold mb-1 titulo-vista">
                    Registrar asistencia
                </h2>

                <p class="text-muted mb-0">
                    Seleccione su nombre para registrar la entrada
                </p>
            </div>

            <div class="text-end">

                <div class="text-center mb-4">
                    <h2 id="reloj">00:00:00</h2>
                    <p id="fecha"></p>
                </div>
            </div>

        </div>


        <!-- Practicantes de hoy -->
        <div class="card shadow-sm border-0">

            <div class="card-body p-4">

                <h5 class="fw-bold mb-4">
                    Practicantes de hoy
                </h5>

                <?php
                $manana = [];
                $tarde = [];
                $tardeNoche = [];

                while ($practicante = $practicantes->fetch_assoc()) {

                    $horaEntrada = strtotime($practicante["hora_entrada"]);

                    if ($horaEntrada < strtotime("12:30:00")) {

                        $manana[] = $practicante;
                    } elseif ($horaEntrada < strtotime("17:00:00")) {

                        $tarde[] = $practicante;
                    } else {

                        $tardeNoche[] = $practicante;
                    }
                }
                ?>

                <!-- TURNO MAÑANA -->
                <?php if (count($manana) > 0): ?>

                    <h4 class="fw-bold mt-2 mb-3">
                        🌅 Turno mañana
                    </h4>

                    <div class="row g-3 mb-4">

                        <?php foreach ($manana as $practicante): ?>

                            <div class="col-md-6 col-lg-4">

                                <div

                                    type="button"
                                    class="btn btn-outline-primary w-100 p-4 btn-practicante"
                                    data-id="<?= $practicante["id_practicante"] ?>"
                                    data-nombre="<?= htmlspecialchars($practicante["nombres"] . " " . $practicante["apellidos"]) ?>">

                                    <div class="mb-2" aria-hidden="true">
                                        <svg class="icono-practicante" viewBox="0 0 64 64" focusable="false">
                                            <circle cx="32" cy="19" r="13" fill="currentColor" />
                                            <path d="M7 59c1-15 11-24 25-24s24 9 25 24H7z" fill="currentColor" />
                                        </svg>
                                    </div>

                                    <div class="fs-5 fw-bold">
                                        <?= htmlspecialchars($practicante["nombres"] . " " . $practicante["apellidos"]) ?>
                                    </div>

                                    <?php if (empty($practicante["hora_llegada"])): ?>

                                        <!-- Todavía no registra entrada -->

                                        <div class="small text-muted">
                                            Seleccionar para registrar entrada
                                        </div>

                                    <?php elseif (empty($practicante["hora_salida_real"])): ?>

                                        <!-- Ya registró entrada -->

                                        <div class="d-flex justify-content-center align-items-center gap-2 mt-2">

                                            <span
                                                class="bg-success rounded-circle luz-en-empresa"
                                                style="width: 12px; height: 12px;"></span>

                                            <span class="text-success fw-bold">
                                                En la empresa
                                            </span>

                                        </div>

                                        <div class="small mt-2">
                                            Entrada:
                                            <strong>
                                                <?= htmlspecialchars($practicante["hora_llegada"]) ?>
                                            </strong>
                                        </div>

                                        <button
                                            type="button"
                                            class="btn btn-salida mt-3 w-100"
                                            data-id="<?= $practicante["id_practicante"] ?>"
                                            data-nombre="<?= htmlspecialchars($practicante["nombres"] . " " . $practicante["apellidos"]) ?>">
                                            <svg class="icono-salida" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <path d="M14 7l5 5-5 5M19 12H8" />
                                                <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h7" />
                                            </svg>
                                            <span>Registrar salida</span>
                                            </button>

                                    <?php else: ?>

                                        <!-- Ya terminó su jornada -->

                                        <div class="d-flex justify-content-center align-items-center gap-2 mt-2">

                                            <span
                                                class="bg-dark rounded-circle"
                                                style="width: 12px; height: 12px;"></span>

                                            <span class="fw-bold">
                                                Jornada finalizada
                                            </span>

                                        </div>

                                        <div class="small mt-2">

                                            Entrada:
                                            <strong>
                                                <?= htmlspecialchars($practicante["hora_llegada"]) ?>
                                            </strong>

                                            <br>

                                            Salida:
                                            <strong>
                                                <?= htmlspecialchars($practicante["hora_salida_real"]) ?>
                                            </strong>

                                        </div>

                                    <?php endif; ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


                <!-- TURNO TARDE -->
                <?php if (count($tarde) > 0): ?>

                    <h4 class="fw-bold mt-4 mb-3">
                        ☀️ Turno tarde
                    </h4>

                    <div class="row g-3 mb-4">

                        <?php foreach ($tarde as $practicante): ?>

                            <div class="col-md-6 col-lg-4">

                                <div
                                    type="button"
                                    class="btn btn-outline-primary w-100 p-4 btn-practicante"
                                    data-id="<?= $practicante["id_practicante"] ?>"
                                    data-nombre="<?= htmlspecialchars($practicante["nombres"] . " " . $practicante["apellidos"]) ?>">

                                    <div class="mb-2" aria-hidden="true">
                                        <svg class="icono-practicante" viewBox="0 0 64 64" focusable="false">
                                            <circle cx="32" cy="19" r="13" fill="currentColor" />
                                            <path d="M7 59c1-15 11-24 25-24s24 9 25 24H7z" fill="currentColor" />
                                        </svg>
                                    </div>

                                    <div class="fs-5 fw-bold">
                                        <?= htmlspecialchars($practicante["nombres"] . " " . $practicante["apellidos"]) ?>
                                    </div>

                                    <?php if (empty($practicante["hora_llegada"])): ?>

                                        <!-- Todavía no registra entrada -->

                                        <div class="small text-muted">
                                            Seleccionar para registrar entrada
                                        </div>

                                    <?php elseif (empty($practicante["hora_salida_real"])): ?>

                                        <!-- Ya registró entrada -->

                                        <div class="d-flex justify-content-center align-items-center gap-2 mt-2">

                                            <span
                                                class="bg-success rounded-circle luz-en-empresa"
                                                style="width: 12px; height: 12px;"></span>

                                            <span class="text-success fw-bold">
                                                En la empresa
                                            </span>

                                        </div>

                                        <div class="small mt-2">
                                            Entrada:
                                            <strong>
                                                <?= htmlspecialchars($practicante["hora_llegada"]) ?>
                                            </strong>
                                        </div>

                                        <button
                                            type="button"
                                            class="btn btn-salida mt-3 w-100"
                                            data-id="<?= $practicante["id_practicante"] ?>"
                                            data-nombre="<?= htmlspecialchars($practicante["nombres"] . " " . $practicante["apellidos"]) ?>">
                                            <svg class="icono-salida" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <path d="M14 7l5 5-5 5M19 12H8" />
                                                <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h7" />
                                            </svg>
                                            <span>Registrar salida</span>
                                            </button>

                                    <?php else: ?>

                                        <!-- Ya terminó su jornada -->

                                        <div class="d-flex justify-content-center align-items-center gap-2 mt-2">

                                            <span
                                                class="bg-dark rounded-circle"
                                                style="width: 12px; height: 12px;"></span>

                                            <span class="fw-bold">
                                                Jornada finalizada
                                            </span>

                                        </div>

                                        <div class="small mt-2">

                                            Entrada:
                                            <strong>
                                                <?= htmlspecialchars($practicante["hora_llegada"]) ?>
                                            </strong>

                                            <br>

                                            Salida:
                                            <strong>
                                                <?= htmlspecialchars($practicante["hora_salida_real"]) ?>
                                            </strong>

                                        </div>

                                    <?php endif; ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


                <!-- TURNO TARDE-NOCHE -->
                <?php if (count($tardeNoche) > 0): ?>

                    <h4 class="fw-bold mt-4 mb-3">
                        🌙 Turno tarde-noche
                    </h4>

                    <div class="row g-3">

                        <?php foreach ($tardeNoche as $practicante): ?>

                            <div class="col-md-6 col-lg-4">

                                <div class="btn btn-outline-primary w-100 p-4 btn-practicante"
                                    data-id="<?= $practicante["id_practicante"] ?>"
                                    data-nombre="<?= htmlspecialchars($practicante["nombres"] . " " . $practicante["apellidos"]) ?>">

                                    <div class="mb-2" aria-hidden="true">
                                        <svg class="icono-practicante" viewBox="0 0 64 64" focusable="false">
                                            <circle cx="32" cy="19" r="13" fill="currentColor" />
                                            <path d="M7 59c1-15 11-24 25-24s24 9 25 24H7z" fill="currentColor" />
                                        </svg>
                                    </div>

                                    <div class="fs-5 fw-bold">
                                        <?= htmlspecialchars($practicante["nombres"] . " " . $practicante["apellidos"]) ?>
                                    </div>

                                    <?php if (empty($practicante["hora_llegada"])): ?>

                                        <!-- Todavía no registra entrada -->

                                        <div class="small text-muted">
                                            Seleccionar para registrar entrada
                                        </div>

                                    <?php elseif (empty($practicante["hora_salida_real"])): ?>

                                        <!-- Ya registró entrada -->

                                        <div class="d-flex justify-content-center align-items-center gap-2 mt-2">

                                            <span
                                                class="bg-success rounded-circle luz-en-empresa"
                                                style="width: 12px; height: 12px;"></span>

                                            <span class="text-success fw-bold">
                                                En la empresa
                                            </span>

                                        </div>

                                        <div class="small mt-2">
                                            Entrada:
                                            <strong>
                                                <?= htmlspecialchars($practicante["hora_llegada"]) ?>
                                            </strong>
                                        </div>

                                        <button
                                            type="button"
                                            class="btn btn-salida mt-3 w-100"
                                            data-id="<?= $practicante["id_practicante"] ?>"
                                            data-nombre="<?= htmlspecialchars($practicante["nombres"] . " " . $practicante["apellidos"]) ?>">
                                            <svg class="icono-salida" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                <path d="M14 7l5 5-5 5M19 12H8" />
                                                <path d="M12 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h7" />
                                            </svg>
                                            <span>Registrar salida</span>
                                            </button>

                                    <?php else: ?>

                                        <!-- Ya terminó su jornada -->

                                        <div class="d-flex justify-content-center align-items-center gap-2 mt-2">

                                            <span
                                                class="bg-dark rounded-circle"
                                                style="width: 12px; height: 12px;"></span>

                                            <span class="fw-bold">
                                                Jornada finalizada
                                            </span>

                                        </div>

                                        <div class="small mt-2">

                                            Entrada:
                                            <strong>
                                                <?= htmlspecialchars($practicante["hora_llegada"]) ?>
                                            </strong>

                                            <br>

                                            Salida:
                                            <strong>
                                                <?= htmlspecialchars($practicante["hora_salida_real"]) ?>
                                            </strong>

                                        </div>

                                    <?php endif; ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>


                <!-- SI NO HAY PRACTICANTES -->
                <?php if (
                    count($manana) === 0 &&
                    count($tarde) === 0 &&
                    count($tardeNoche) === 0
                ): ?>

                    <div class="alert alert-info mb-0">
                        No hay practicantes con prácticas programadas para hoy.
                    </div>

                <?php endif; ?>

            </div>

        </div>

    </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        const botones = document.querySelectorAll(".btn-practicante");

        // Evita que el nombre se interprete como HTML dentro de las alertas
        function escaparHtml(texto) {
            const div = document.createElement("div");
            div.textContent = texto;
            return div.innerHTML;
        }

        botones.forEach(function(boton) {

            boton.addEventListener("click", function() {

                // Si ya registró su entrada (o ya terminó), no se hace nada
                if (!this.textContent.includes("Seleccionar para registrar entrada")) {
                    return;
                }

                const id = this.dataset.id;
                const nombre = this.dataset.nombre;

                Swal.fire({
                    icon: "question",
                    title: "Registrar asistencia",
                    text: "¿Desea registrar su asistencia, " + nombre + "?",
                    showCancelButton: true,
                    confirmButtonText: "Sí, registrar",
                    cancelButtonText: "Cancelar"
                }).then(function(resultado) {

                    if (!resultado.isConfirmed) {
                        return;
                    }

                    fetch("index.php?pagina=asistencia&accion=registrarEntrada", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded"
                            },
                            body: "id_practicante=" + encodeURIComponent(id)
                        })

                        .then(response => response.json())

                        .then(data => {


                            if (data.ok === true) {

                                Swal.fire({
                                    icon: "success",
                                    title: "¡Asistencia registrada!",
                                    html: "<b>" + escaparHtml(nombre) + "</b><br><br>" +
                                        "Hora programada: <b>" + data.hora_programada + "</b><br>" +
                                        "Hora de llegada: <b>" + data.hora_llegada + "</b><br>" +
                                        "Estado: <b>" + data.estado + "</b>",
                                    confirmButtonText: "Aceptar"
                                }).then(() => {

                                    location.reload();

                                });

                            } else {

                                Swal.fire({
                                    icon: "warning",
                                    title: "Asistencia ya registrada",
                                    text: data.mensaje,
                                    confirmButtonText: "Aceptar"
                                });

                            }

                        })

                        .catch(error => {

                            console.error(error);

                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: "Ocurrió un problema al registrar la asistencia."
                            });

                        });

                });

            });

        });
    </script>

    <script>
        function actualizarReloj() {
            const ahora = new Date();

            const hora = ahora.toLocaleTimeString("es-PE", {
                hour: "2-digit",
                minute: "2-digit",
                second: "2-digit",
                hour12: false
            });

            const fecha = ahora.toLocaleDateString("es-PE", {
                weekday: "long",
                day: "numeric",
                month: "long",
                year: "numeric"
            });

            document.getElementById("reloj").textContent = hora;
            document.getElementById("fecha").textContent = fecha;
        }

        actualizarReloj();
        setInterval(actualizarReloj, 1000);

        const botonesSalida = document.querySelectorAll(".btn-salida");

        botonesSalida.forEach(function(boton) {

            boton.addEventListener("click", function(event) {

                event.stopPropagation();

                const id = this.dataset.id;
                const nombre = this.dataset.nombre;

                Swal.fire({
                    title: "¿Registrar salida?",
                    text: "Se registrará la hora de salida de " + nombre,
                    icon: "question",
                    showCancelButton: true,
                    confirmButtonText: "Sí, registrar salida",
                    cancelButtonText: "Cancelar"
                }).then(function(resultado) {

                    if (!resultado.isConfirmed) return;

                    fetch("index.php?pagina=asistencia&accion=registrarSalida", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded"
                            },
                            body: "id_practicante=" + encodeURIComponent(id)
                        })
                        .then(response => response.json())
                        .then(data => {

                            if (data.ok === true) {

                                Swal.fire({
                                    icon: "success",
                                    title: "¡Salida registrada!",
                                    text: "La salida de " + nombre + " fue registrada correctamente.",
                                    confirmButtonText: "Aceptar"
                                }).then(() => {
                                    location.reload();
                                });

                            } else {

                                Swal.fire({
                                    icon: "warning",
                                    title: "No se pudo registrar",
                                    text: data.mensaje,
                                    confirmButtonText: "Aceptar"
                                });

                            }

                        })
                        .catch(error => {

                            console.error("ERROR COMPLETO:", error);

                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                html: `
                                    <b>Ocurrió un error al registrar la salida.</b>
                                    <br><br>
                                    Revisa la consola del navegador.
                                `
                            });

                        });

                });
            });

        });
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
