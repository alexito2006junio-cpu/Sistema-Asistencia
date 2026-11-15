<?php

/**
 * Variables que envía HistorialController.
 *
 * @var mysqli_result|false $historial
 * @var string $tituloFiltro
 * @var string $mesCampo
 * @var string $consultaExportar
 */

$tituloFiltro     = $tituloFiltro ?? "";
$mesCampo         = $mesCampo ?? "";
$consultaExportar = $consultaExportar ?? "";
?>
<?php
$paginaActual = $_GET["pagina"] ?? "historial";
?>

<!DOCTYPE html>
<html lang="es" data-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Historial de asistencia</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">
    <link rel="stylesheet" href="views/historial/css/style.css?v=20261114-2">

<script>
    try {
        document.documentElement.dataset.theme = localStorage.getItem('sistema-horario-theme') || 'dark';
    } catch (error) {
        document.documentElement.dataset.theme = 'dark';
    }
</script>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark">

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




    <!-- CONTENIDO -->
    <div class="container mt-5">

        <h2 class="titulo mb-4">
            Historial de asistencia
        </h2>

        <!-- Filtros -->
        <div class="tabla-contenedor mb-4">

            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                <p class="mb-0">
                    Mostrando:
                    <strong><?= htmlspecialchars($tituloFiltro) ?></strong>
                </p>

                <a
                    href="index.php?pagina=historial&accion=exportarExcel&<?= htmlspecialchars($consultaExportar) ?>"
                    class="btn btn-exportar">
                    Exportar Excel
                </a>
            </div>

            <!-- Ver un mes -->
            <form method="GET" action="/Sistema_horario/">
                <input type="hidden" name="pagina" value="historial">

                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">
                            Mes
                        </label>
                        <input
                            type="month"
                            class="form-control"
                            name="mes"
                            value="<?= htmlspecialchars($mesCampo) ?>"
                            required>
                    </div>

                    <div class="col-md-2">
                        <button
                            type="submit"
                            class="btn w-100"
                            style="background-color: #ff8800; color: #000000; font-weight: bold;">
                            Ver mes
                        </button>
                    </div>

                    <div class="col-md-3">
                        <a
                            href="/Sistema_horario/?pagina=historial"
                            class="btn btn-dark w-100">
                            Mes actual
                        </a>
                    </div>

                    <div class="col-md-3">
                        <a
                            href="/Sistema_horario/?pagina=historial&todo=1"
                            class="btn btn-outline-dark w-100">
                            Ver todo el historial
                        </a>
                    </div>
                </div>
            </form>

        </div>

        <div class="tabla-contenedor">

            <div class="table-responsive">

                <table class="table table-hover align-middle tabla-movil">

                    <thead class="table-dark">

                        <tr>
                            <th>Fecha</th>
                            <th>Practicante</th>
                            <th>Hora de llegada</th>
                            <th>Hora de Salida</th>
                            <th>Estado</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if ($historial && $historial->num_rows > 0): ?>

                            <?php while ($registro = $historial->fetch_assoc()): ?>

                                <tr>

                                    <td data-label="Fecha">
                                        <?= htmlspecialchars($registro["fecha"]) ?>
                                    </td>

                                    <td data-label="Practicante">
                                        <?= htmlspecialchars($registro["practicante"]) ?>
                                    </td>

                                    <td data-label="Hora de llegada">
                                        <?= htmlspecialchars($registro["hora_llegada"] ?? "") ?>
                                    </td>
                                    <td data-label="Hora de salida">
                                        <?= htmlspecialchars($registro["hora_salida"] ?? "") ?>
                                    </td>

                                    <td data-label="Estado">

                                        <?php
                                        $estado = $registro["estado"];

                                        if ($estado === "PUNTUAL") {
                                            echo '<span class="estado-puntual">PUNTUAL</span>';
                                        } elseif ($estado === "TARDANZA") {
                                            echo '<span class="estado-tardanza">TARDANZA</span>';
                                        } elseif ($estado === "ADELANTADO") {
                                            echo '<span class="estado-adelantado">ADELANTADO</span>';
                                        } elseif ($estado === "JUSTIFICADO") {
                                            echo '<span class="badge text-bg-info">JUSTIFICADO</span>';
                                            if (!empty($registro["motivo"])) {
                                                echo '<div class="small text-muted">' .
                                                    htmlspecialchars($registro["motivo"]) .
                                                    '</div>';
                                            }
                                        } else {
                                            echo htmlspecialchars($estado ?? "");
                                        }
                                        ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    No hay registros de asistencia en este período.
                                </td>
                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>




    <script
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js">
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
