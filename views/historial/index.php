<?php

/** @var mysqli_result|false $historial */
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Historial de asistencia</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>
        body {
            background-color: #f5f5f5;
        }

        .navbar {
            background-color: #000000 !important;
        }

        .navbar-brand,
        .nav-link {
            color: #ff8800 !important;
        }

        .nav-link:hover {
            color: #ff8800 !important;
        }

        .titulo {
            color: #ff8800;
            font-weight: bold;
        }

        .tabla-contenedor {
            background-color: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .estado-puntual {
            color: #198754;
            font-weight: bold;
        }

        .estado-tardanza {
            color: #dc3545;
            font-weight: bold;
        }

        .estado-adelantado {
            color: #0d6efd;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark">

        <div class="container">

            <a class="navbar-brand fw-bold" href="/Sistema_horario/">
                ISHUME - Productora Audiovisual
            </a>

            <button
                class="navbar-toggler"
                type="button"
                data-bs-toggle="collapse"
                data-bs-target="#menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="menu">

                <ul class="navbar-nav ms-auto">

                    <li class="nav-item">
                        <a class="nav-link" href="/Sistema_horario/">
                            Inicio
                        </a>
                    </li>

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

    <!------ BOTON EXPORTAR EXCEL ------>

    <div class="d-flex justify-content-end mt-3">
        <a href="index.php?pagina=historial&accion=exportarExcel"
            class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel"></i>
            Exportar Excel
        </a>
    </div>



    <!-- CONTENIDO -->
    <div class="container mt-5">

        <h2 class="titulo mb-4">
            Historial de asistencia
        </h2>

        <!-- Filtro por fechas -->
        <div class="tabla-contenedor mb-4">

            <h5 class="fw-bold mb-3">
                Filtrar por fechas
            </h5>

            <form method="GET" action="/Sistema_horario/">

                <input type="hidden" name="pagina" value="historial">

                <div class="row g-3 align-items-end">

                    <div class="col-md-4">
                        <label class="form-label fw-bold">
                            Desde
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            name="desde"
                            value="<?= htmlspecialchars($_GET["desde"] ?? "") ?>">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">
                            Hasta
                        </label>

                        <input
                            type="date"
                            class="form-control"
                            name="hasta"
                            value="<?= htmlspecialchars($_GET["hasta"] ?? "") ?>">
                    </div>

                    <div class="col-md-2">
                        <button
                            type="submit"
                            class="btn w-100"
                            style="background-color: #ff8800; color: #000000; font-weight: bold;">
                            Buscar
                        </button>
                    </div>

                    <div class="col-md-2">
                        <a
                            href="/Sistema_horario/?pagina=historial"
                            class="btn btn-dark w-100">
                            Limpiar
                        </a>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-bold">
                            Buscar por mes
                        </label>

                        <input
                            type="month"
                            class="form-control"
                            name="mes"
                            value="<?= htmlspecialchars($_GET["mes"] ?? "") ?>">
                    </div>

                </div>

            </form>

        </div>

        <div class="tabla-contenedor">

            <div class="table-responsive">

                <table class="table table-hover align-middle">

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

                                    <td>
                                        <?= htmlspecialchars($registro["fecha"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($registro["practicante"]) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($registro["hora_llegada"]) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($registro["hora_salida"]) ?>
                                    </td>

                                    <td>

                                        <?php
                                        $estado = $registro["estado"];

                                        if ($estado === "PUNTUAL") {
                                            echo '<span class="estado-puntual">PUNTUAL</span>';
                                        } elseif ($estado === "TARDANZA") {
                                            echo '<span class="estado-tardanza">TARDANZA</span>';
                                        } elseif ($estado === "ADELANTADO") {
                                            echo '<span class="estado-adelantado">ADELANTADO</span>';
                                        } else {
                                            echo htmlspecialchars($estado);
                                        }
                                        ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">
                                    No hay registros de asistencia.
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

</body>

</html>