<dialog class="ht-send-dialog" data-conciliation-dialog aria-labelledby="conciliation-send-title">
    <span class="ht-send-check" data-success-symbol hidden><i data-tools-icon="check" aria-hidden="true"></i></span>
    <header class="ht-send-heading">
        <span class="ht-send-symbol" data-confirm-symbol><i data-tools-icon="send" aria-hidden="true"></i></span>
        <h2 id="conciliation-send-title" tabindex="-1">Confirmar envío de conciliación</h2>
        <button type="button" class="ht-icon" data-cancel-send aria-label="Cerrar" title="Cerrar"><i data-tools-icon="x" aria-hidden="true"></i></button>
    </header>
    <p data-send-loading role="status" hidden>Cargando resumen de conciliación...</p>
    <p role="alert" class="ht-error" data-send-error hidden></p>
    <button type="button" class="ht-secondary" data-retry-preview hidden>Reintentar</button>
    <form data-conciliation-send-form data-preview-url="{{ route('admin.hospital.conciliacion.resumen') }}" action="{{ route('admin.hospital.conciliacion.enviar') }}" method="POST" hidden>
        @csrf
        <p class="ht-send-question">¿Deseas enviar esta conciliación al proveedor?</p>
        <p><strong>Hospital:</strong> <span data-summary="hospital"></span><br><strong>Proveedor:</strong> <span data-summary="provider"></span></p>
        <div class="ht-send-period"><i data-tools-icon="calendar-days" aria-hidden="true"></i><div>Periodo de conciliación<strong data-summary="period" data-send-period></strong></div></div>
        <table class="ht-send-summary" data-disable-column-filters aria-label="Resumen de conciliación">
            <thead><tr><th>Resumen</th><th>Mezclas</th><th>Monto (MXN)</th></tr></thead>
            <tbody>
                <tr><th scope="row">Total filtrado del periodo</th><td data-summary="total.count"></td><td data-money="total.amount_cents"></td></tr>
                <tr class="ht-send-yes"><th scope="row">Conciliables · Sí</th><td data-summary="yes.count"></td><td data-money="yes.amount_cents"></td></tr>
                <tr class="ht-send-no"><th scope="row">No conciliables · No</th><td data-summary="no.count"></td><td data-money="no.amount_cents"></td></tr>
            </tbody>
        </table>
        <div class="ht-send-total"><div><strong>Monto conciliable a enviar</strong><span data-included-label></span></div><strong data-money="yes.amount_cents"></strong></div>
        <p class="ht-send-note" data-no-note><i data-tools-icon="info" aria-hidden="true"></i><span data-no-description></span></p>
        <p class="ht-send-missing" data-missing-amounts hidden>Hay <span data-summary="total.missing_amounts"></span> mezclas sin importe registrado. Los totales afectados se muestran como «Sin registrar».</p>
        <fieldset class="ht-send-reasons" data-send-reasons hidden><legend>Motivos de las mezclas no conciliables</legend><div data-reason-inputs></div></fieldset>
        <footer><button type="button" class="ht-secondary" data-cancel-send>Cancelar</button><button type="submit" class="ht-primary" data-confirm-send><i data-tools-icon="send" aria-hidden="true"></i><span data-confirm-label>Confirmar y enviar</span></button></footer>
    </form>
    <section class="ht-send-success" data-send-success hidden aria-label="Conciliación enviada">
        <p>La conciliación se envió a PROMESA para su revisión.</p>
        <dl>
            <div><dt>Hospital</dt><dd data-summary="hospital"></dd></div>
            <div><dt>Periodo</dt><dd data-summary="period"></dd></div>
            <div><dt>Mezclas conciliables</dt><dd data-summary="yes.count"></dd></div>
            <div><dt>Monto conciliable enviado</dt><dd class="ht-send-amount" data-money="yes.amount_cents"></dd></div>
        </dl>
        <p data-no-note data-no-confirm></p>
        <p class="ht-send-missing" data-missing-amounts hidden>Hay importes sin registrar en el reporte enviado.</p>
        <p class="ht-send-pending"><i data-tools-icon="clock" aria-hidden="true"></i> Enviada · Pendiente de revisión</p>
        <p class="ht-send-folio" data-send-folio></p>
        <button type="button" class="ht-primary" data-cancel-send>Cerrar</button>
    </section>
</dialog>
