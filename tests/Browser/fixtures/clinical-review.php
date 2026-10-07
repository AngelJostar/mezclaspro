<?php

require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config(['session.driver' => 'array']);
?>
<main style="max-width:1000px;margin:24px auto;padding:20px;background:white">
    <h1>Nueva solicitud de mezcla</h1>
    <form id="clinical-fixture" method="post" action="/saved" onsubmit="event.preventDefault(); window.validateClinicalRequest(this)">
        <input type="hidden" name="_token" value="test-token">
        <label for="test-weight">Peso (kg)*</label>
        <input id="test-weight" name="peso" type="number" value="70" min="0.001" step="0.001" required data-clinical-required>
        <label for="test-birth">Fecha de nacimiento*</label>
        <input id="test-birth" name="fecha_nacimiento" type="date" value="1986-01-01" required data-clinical-required>
        <label for="volume">Volumen total (mL)</label>
        <input id="volume" name="volumen_total" type="number" value="1000" min="1" required>
        <?php echo view('admin.nutricionales.solicitudes._npt-selection', ['selectedNpt' => 'ADULT', 'errors' => new Illuminate\Support\ViewErrorBag])->render(); ?>
        <label for="notes">Observaciones</label>
        <textarea id="notes" name="observaciones">Nota del usuario</textarea>
        <?php echo view('admin.solicitudes._clinical-review', ['clinicalKind' => 'nutricionales', 'errors' => new Illuminate\Support\ViewErrorBag])->render(); ?>
        <button type="submit" data-clinical-submit>Validar y Continuar</button>
    </form>
</main>
