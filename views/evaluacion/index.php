<?php
/** @var array $filas */

$paginaActual = $_GET["pagina"] ?? "evaluacion";

$mes = $mes ?? date("Y-m");
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notas mensuales</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css"
        rel="stylesheet">

    <style>
        body {
            background-color: #f5f5f5;
        }

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

        .titulo {
            font-weight: bold;
            color: #222;
        }

        .btn-naranja {
            background-color: #ff8800;
            color: white;
            border: none;
        }

        .btn-naranja:hover {
            background-color: #e67800;
            color: white;
        }
    </style>
</head>

<body>

    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">

            <a class="navbar-brand" href="/Sistema_horario/">
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

            </div>
        </div>
    </nav>

    <!-- Contenido del módulo -->
    <?php
    // Pequeñas ayudas para mostrar los datos
    $nota = function ($valor) {
        return $valor === null ? "—" : number_format($valor, 2);
    };

    $color = function ($valor) {
        if ($valor === null) {
            return "text-muted";
        }

        return $valor >= Evaluacion::NOTA_APROBATORIA
            ? "text-success"
            : "text-danger";
    };

    $plural = function ($cantidad, $singular, $plural) {
        return $cantidad . " " . ($cantidad === 1 ? $singular : $plural);
    };

    $porcentaje = function ($peso) {
        return round($peso * 100) . " %";
    };
    ?>

    <div class="container py-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
            <h2 class="titulo mb-0">Notas mensuales</h2>

            <button type="button"
                class="btn btn-naranja"
                data-bs-toggle="modal"
                data-bs-target="#modalJustificar">
                Justificar día
            </button>
        </div>

        <!-- Mensajes -->
        <?php if ($mensaje === "justificado"): ?>
            <div class="alert alert-success">
                Día justificado para <?= $cantidad ?>
                <?= $cantidad === 1 ? "practicante" : "practicantes" ?>.
            </div>
        <?php elseif ($mensaje === "sin_cambios"): ?>
            <div class="alert alert-warning">
                No se justificó a nadie: ese día no hay practicantes con horario
                o todos ya tienen un registro.
            </div>
        <?php elseif ($mensaje === "quitada"): ?>
            <div class="alert alert-success">
                Se quitó la justificación.
            </div>
        <?php elseif ($mensaje === "fecha_invalida"): ?>
            <div class="alert alert-danger">La fecha no es válida.</div>
        <?php elseif ($mensaje === "motivo_vacio"): ?>
            <div class="alert alert-danger">
                Escribe el motivo de la justificación.
            </div>
        <?php elseif ($mensaje === "error"): ?>
            <div class="alert alert-danger">
                No se pudo completar la acción.
            </div>
        <?php endif; ?>

        <!-- Selector de mes -->
        <form method="GET" action="index.php" class="mb-3">
            <input type="hidden" name="pagina" value="evaluacion">

            <div class="row align-items-end">
                <div class="col-md-4">
                    <label for="mes" class="form-label">
                        Mes de evaluación
                    </label>

                    <input type="month"
                        id="mes"
                        name="mes"
                        class="form-control"
                        value="<?= htmlspecialchars($mes) ?>">
                </div>

                <div class="col-md-2 mt-2">
                    <button type="submit" class="btn btn-naranja">
                        Consultar
                    </button>
                </div>
            </div>
        </form>

        <?php if ($enCurso): ?>
            <div class="alert alert-info">
                Este mes todavía no termina: las notas son
                <strong>parciales</strong> y cambian cada día.
            </div>
        <?php endif; ?>

        <!-- Cómo se calcula -->
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body small text-muted">
                <strong class="text-dark">Cómo se calcula (sobre <?= Evaluacion::NOTA_MAXIMA ?>):</strong>
                Asistencia <?= $porcentaje(Evaluacion::PESO_ASISTENCIA) ?>
                (−<?= Evaluacion::PUNTOS_POR_FALTA ?> por falta) ·
                Puntualidad <?= $porcentaje(Evaluacion::PESO_PUNTUALIDAD) ?>
                (−<?= Evaluacion::PUNTOS_POR_TARDANZA ?> por tardanza) ·
                Horas <?= $porcentaje(Evaluacion::PESO_HORAS) ?>
                (horas trabajadas ÷ horas programadas).
                Se aprueba con <?= Evaluacion::NOTA_APROBATORIA ?> o más.
                Con <?= Evaluacion::FALTAS_SEMANA_LIMITE ?> faltas en una misma semana
                la nota final no pasa de <?= Evaluacion::TOPE_NOTA_FINAL ?>.
                Los días justificados no cuentan.
            </div>
        </div>

        <!-- Tabla de notas -->
        <div class="card shadow-sm border-0">
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Practicante</th>
                                <th class="text-center">Asistencia</th>
                                <th class="text-center">Puntualidad</th>
                                <th class="text-center">Horas</th>
                                <th class="text-center">Nota final</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Detalle</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (count($filas) > 0): ?>

                                <?php foreach ($filas as $f): ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($f["practicante"]) ?>
                                        </td>

                                        <td class="text-center">
                                            <div class="fw-bold <?= $color($f["nota_asistencia"]) ?>">
                                                <?= $nota($f["nota_asistencia"]) ?>
                                            </div>
                                            <?php if (!$f["sin_datos"]): ?>
                                                <div class="small text-muted">
                                                    <?= $plural(count($f["faltas"]), "falta", "faltas") ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <div class="fw-bold <?= $color($f["nota_puntualidad"]) ?>">
                                                <?= $nota($f["nota_puntualidad"]) ?>
                                            </div>
                                            <?php if (!$f["sin_datos"]): ?>
                                                <div class="small text-muted">
                                                    <?= $plural(count($f["tardanzas"]), "tardanza", "tardanzas") ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <div class="fw-bold <?= $color($f["nota_horas"]) ?>">
                                                <?= $nota($f["nota_horas"]) ?>
                                            </div>
                                            <?php if (!$f["sin_datos"]): ?>
                                                <div class="small text-muted">
                                                    <?= htmlspecialchars($f["horas_trabajadas"]) ?> /
                                                    <?= htmlspecialchars($f["horas_programadas"]) ?> h
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center fs-5 fw-bold <?= $color($f["nota_final"]) ?>">
                                            <?= $nota($f["nota_final"]) ?>
                                        </td>

                                        <td class="text-center">
                                            <?php if ($f["sin_datos"]): ?>
                                                <span class="badge text-bg-secondary">Sin datos</span>
                                            <?php elseif ($f["aprobado"]): ?>
                                                <span class="badge text-bg-success">Aprobado</span>
                                            <?php else: ?>
                                                <span class="badge text-bg-danger">Desaprobado</span>
                                            <?php endif; ?>

                                            <?php if (count($f["semanas_3_faltas"]) > 0): ?>
                                                <div class="small text-danger mt-1">
                                                    3 faltas en una semana
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td class="text-center">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-dark btn-detalle"
                                                data-id="<?= (int)$f["id_practicante"] ?>"
                                                data-nombre="<?= htmlspecialchars($f["practicante"]) ?>"
                                                data-detalle="<?= htmlspecialchars(json_encode([
                                                                    "faltas"       => $f["faltas"],
                                                                    "tardanzas"    => $f["tardanzas"],
                                                                    "justificados" => $f["justificados"],
                                                                    "sin_salida"   => $f["sin_salida"],
                                                                    "semanas"      => $f["semanas_3_faltas"]
                                                                ]), ENT_QUOTES, "UTF-8") ?>">
                                                Ver
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        No hay practicantes activos.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

    </div>


    <!-- Modal: Justificar día -->
    <div class="modal fade" id="modalJustificar" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <form method="POST" action="index.php?pagina=evaluacion&accion=justificar">

                    <div class="modal-header">
                        <h5 class="modal-title">Justificar día</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>

                    <div class="modal-body">

                        <input type="hidden" name="mes" value="<?= htmlspecialchars($mes) ?>">

                        <div class="mb-3">
                            <label for="just_fecha" class="form-label">Fecha</label>
                            <input type="date"
                                class="form-control"
                                id="just_fecha"
                                name="fecha"
                                value="<?= date("Y-m-d") ?>"
                                required>
                        </div>

                        <div class="mb-3">
                            <label for="just_practicante" class="form-label">¿A quién?</label>
                            <select class="form-select" id="just_practicante" name="id_practicante">
                                <option value="">Todos los que tenían horario ese día</option>
                                <?php foreach ($filas as $f): ?>
                                    <option value="<?= (int)$f["id_practicante"] ?>">
                                        <?= htmlspecialchars($f["practicante"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-2">
                            <label for="just_motivo" class="form-label">
                                Motivo de la justificación
                            </label>
                            <input type="text"
                                class="form-control"
                                id="just_motivo"
                                name="motivo"
                                maxlength="255"
                                placeholder="Ejemplo: Feriado, cita médica, permiso…"
                                required>
                        </div>

                        <div class="form-text">
                            Un día justificado no cuenta como falta ni resta horas.
                            Solo se justifica a quienes aún no tienen registro ese día.
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-naranja">
                            Justificar
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>


    <!-- Modal: Detalle del practicante -->
    <div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detalleTitulo"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="detalleCuerpo"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const MES = <?= json_encode($mes) ?>;

        function formatoFecha(fecha) {
            const partes = fecha.split("-");
            const d = new Date(partes[0], partes[1] - 1, partes[2]);

            return d.toLocaleDateString("es-PE", {
                weekday: "short",
                day: "2-digit",
                month: "2-digit"
            });
        }

        // Crea un bloque con título y una lista (todo con textContent: seguro)
        function bloque(titulo, claseTitulo) {
            const cont = document.createElement("div");
            cont.className = "mb-3";

            const h = document.createElement("div");
            h.className = "fw-bold " + claseTitulo;
            h.textContent = titulo;
            cont.appendChild(h);

            const ul = document.createElement("ul");
            ul.className = "list-unstyled mb-0 small";
            cont.appendChild(ul);

            return {
                cont: cont,
                lista: ul
            };
        }

        function formularioQuitar(idPracticante, fecha) {
            const form = document.createElement("form");
            form.method = "POST";
            form.action = "index.php?pagina=evaluacion&accion=quitarJustificacion";
            form.className = "d-inline ms-2";

            [
                ["id_practicante", idPracticante],
                ["fecha", fecha],
                ["mes", MES]
            ].forEach(function(par) {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = par[0];
                input.value = par[1];
                form.appendChild(input);
            });

            const boton = document.createElement("button");
            boton.type = "submit";
            boton.className = "btn btn-sm btn-link text-danger p-0";
            boton.textContent = "Quitar";
            form.appendChild(boton);

            form.addEventListener("submit", function(e) {
                if (!confirm("¿Quitar esta justificación?")) {
                    e.preventDefault();
                }
            });

            return form;
        }

        document.querySelectorAll(".btn-detalle").forEach(function(boton) {

            boton.addEventListener("click", function() {

                const id = this.dataset.id;
                const detalle = JSON.parse(this.dataset.detalle);
                const cuerpo = document.getElementById("detalleCuerpo");

                document.getElementById("detalleTitulo").textContent = this.dataset.nombre;
                cuerpo.innerHTML = "";

                let hayAlgo = false;

                // Faltas
                if (detalle.faltas.length > 0) {
                    hayAlgo = true;
                    const b = bloque("Faltas (" + detalle.faltas.length + ")", "text-danger");
                    detalle.faltas.forEach(function(fecha) {
                        const li = document.createElement("li");
                        li.textContent = formatoFecha(fecha);
                        b.lista.appendChild(li);
                    });
                    cuerpo.appendChild(b.cont);
                }

                // Tardanzas
                if (detalle.tardanzas.length > 0) {
                    hayAlgo = true;
                    const b = bloque("Tardanzas (" + detalle.tardanzas.length + ")", "text-warning");
                    detalle.tardanzas.forEach(function(fecha) {
                        const li = document.createElement("li");
                        li.textContent = formatoFecha(fecha);
                        b.lista.appendChild(li);
                    });
                    cuerpo.appendChild(b.cont);
                }

                // Justificados (con opción de quitar)
                if (detalle.justificados.length > 0) {
                    hayAlgo = true;
                    const b = bloque("Días justificados (" + detalle.justificados.length + ")", "text-info");
                    detalle.justificados.forEach(function(item) {
                        const li = document.createElement("li");
                        li.textContent = formatoFecha(item.fecha) +
                            (item.motivo ? " — " + item.motivo : "");
                        li.appendChild(formularioQuitar(id, item.fecha));
                        b.lista.appendChild(li);
                    });
                    cuerpo.appendChild(b.cont);
                }

                // Entrada sin salida
                if (detalle.sin_salida.length > 0) {
                    hayAlgo = true;
                    const b = bloque("Sin salida registrada (" + detalle.sin_salida.length + ")", "text-secondary");
                    detalle.sin_salida.forEach(function(fecha) {
                        const li = document.createElement("li");
                        li.textContent = formatoFecha(fecha) + " — esas horas no se cuentan";
                        b.lista.appendChild(li);
                    });
                    cuerpo.appendChild(b.cont);
                }

                // 3 faltas en una semana
                if (detalle.semanas.length > 0) {
                    hayAlgo = true;
                    const aviso = document.createElement("div");
                    aviso.className = "alert alert-danger small mb-0";
                    aviso.textContent = "Tiene 3 o más faltas en una misma semana: " +
                        "su nota final no puede pasar de 10.";
                    cuerpo.appendChild(aviso);
                }

                if (!hayAlgo) {
                    const p = document.createElement("p");
                    p.className = "text-muted mb-0";
                    p.textContent = "Sin novedades este mes.";
                    cuerpo.appendChild(p);
                }

                new bootstrap.Modal(document.getElementById("modalDetalle")).show();
            });
        });
    </script>

</body>

</html>
