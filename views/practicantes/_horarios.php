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
<div class="mb-3">
    <label class="form-label fw-bold">Días y horarios de trabajo</label>
    <div class="form-text mb-2">
        Marca los días y escribe la hora de entrada y de salida.
        Las horas semanales se calculan solas.
    </div>

    <?php foreach ($diasSemana as $dia): ?>
        <div class="border rounded p-3 mb-2">
            <div class="form-check mb-2">
                <input
                    class="form-check-input dia-horario"
                    type="checkbox"
                    name="dias[]"
                    value="<?= $dia ?>"
                    data-dia="<?= $dia ?>"
                    id="<?= $prefijo ?>_dia_<?= $dia ?>">

                <label
                    class="form-check-label fw-bold"
                    for="<?= $prefijo ?>_dia_<?= $dia ?>">
                    <?= ucfirst(strtolower($dia)) ?>
                </label>
            </div>

            <div class="row g-2">
                <div class="col-6">
                    <label class="form-label">Hora de entrada</label>
                    <input
                        type="time"
                        class="form-control"
                        name="horarios[<?= $dia ?>][hora_entrada]"
                        id="<?= $prefijo ?>_entrada_<?= $dia ?>"
                        step="60"
                        disabled>
                </div>

                <div class="col-6">
                    <label class="form-label">Hora de salida</label>
                    <input
                        type="time"
                        class="form-control"
                        name="horarios[<?= $dia ?>][hora_salida]"
                        id="<?= $prefijo ?>_salida_<?= $dia ?>"
                        step="60"
                        disabled>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
