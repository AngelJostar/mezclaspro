<x-admin-layout>
    @php
        $toolSections = ['reportes', 'conciliacion', 'facturacion', 'pagos'];
        $requestedSection = request()->query('seccion', 'reportes');
        $selectedSection = is_string($requestedSection) && in_array($requestedSection, $toolSections, true)
            ? $requestedSection : 'reportes';
        $toolLinks = [];
        foreach ($toolSections as $section) {
            $toolLinks[$section] = route('admin.herramientas.index', ['seccion' => $section]);
        }
    @endphp

    <h1 class="mb-4 text-2xl font-semibold">Herramientas</h1>

    @include('admin.instituciones.partials.administration-carousel', [
        'administrationSection' => $selectedSection,
        'administrationLinks' => $toolLinks,
        'navigationLabel' => 'Secciones de herramientas',
    ])
    @if ($selectedSection === 'facturacion')
        <section class="hospital-tools mt-6" aria-labelledby="client-billing-title">
            <div class="ht-log-heading"><h2 id="client-billing-title">Facturación</h2></div>
            <div class="ht-table-scroll" data-sticky-x-position="viewport" tabindex="0" aria-label="Facturación del hospital">
                <table class="ht-table" style="min-width: 1900px">
                    <thead><tr><th>No. de remisión</th><th>Nombre del médico</th><th>Nombre del paciente</th><th>Fecha de remisión</th><th>Cantidad</th><th>Unidad</th><th>Descripción</th><th>Precio unitario IVA incluido</th><th>Precio total IVA incluido</th><th>Conciliable</th><th>Folio factura UUID</th><th>Folio factura interno</th><th>Fecha de factura</th></tr></thead>
                    <tbody>
                        @forelse ($billingRecords as $item)
                            <tr>
                                <td>{{ $item['remision'] }}</td><td>{{ $item['medico'] }}</td><td>{{ $item['patient_name'] }}</td><td class="ht-nowrap">{{ $item['fecha'] }}</td>
                                <td>@foreach ($item['quantity_lines'] as $quantity)<div>{{ $quantity }}</div>@endforeach</td>
                                <td>@foreach ($item['sale_unit_lines'] as $unit)<div>{{ $unit }}</div>@endforeach</td>
                                <td>@foreach ($item['description_lines'] as $description)<div>{{ $description }}</div>@endforeach</td>
                                <td>@foreach ($item['unit_price_lines'] as $price)<div>{{ $price }}</div>@endforeach</td>
                                <td>{{ $item['pv_total'] }}</td><td>{{ $item['billing']?->conciliable ?: '—' }}</td>
                                <td>{{ $item['billing']?->folio_factura_uuid ?: '—' }}</td><td>{{ $item['billing']?->folio_interno ?: '—' }}</td>
                                <td>{{ $item['billing']?->fecha_facturacion ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="13" class="ht-empty">No hay registros de facturación.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $billingRecords->links() }}</div>
        </section>
    @endif
    @if ($selectedSection === 'conciliacion')
        @push('css')
            @include('admin.instituciones.conciliacion._period-style')
            <style>
                .admin-content:has(.client-conciliation-panel) {
                    min-height: calc(100dvh - var(--corporate-header-height) - 12px - 1.5rem);
                    display: flex;
                    flex-direction: column;
                }
                .client-conciliation-panel { flex: 1; display: flex; flex-direction: column; }
                .client-conciliation-panel > .client-conciliation-pagination { margin-top: auto; padding-top: 1rem; }
                @media (max-width: 767px) {
                    .admin-content:has(.client-conciliation-panel) {
                        min-height: calc(100dvh - var(--corporate-header-height) - 12px - .75rem);
                    }
                }
            </style>
        @endpush
        <section class="hospital-tools client-conciliation-panel mt-6" aria-labelledby="client-conciliation-title" data-client-conciliation>
            <div class="ht-log-heading"><h2 id="client-conciliation-title">Solicitudes de conciliación</h2></div>
            <p class="mb-4 text-sm">Puedes solicitar cambios en los periodos recibidos. Su autorización corresponde exclusivamente al personal de PROMESA Prodifem.</p>
            <p data-client-conciliation-feedback role="status" hidden></p>
            <div class="ht-table-scroll" data-sticky-x-position="viewport" tabindex="0" aria-label="Solicitudes de conciliación">
                <table class="ht-table">
                    <thead><tr><th>Folio</th><th>Fecha de envío</th><th>Hospital</th><th>Enviado por</th><th>Periodo</th><th>Monto del periodo</th><th class="ht-nowrap" data-command-column>Conciliación</th><th>Monto después de conciliación</th><th>Mezclas</th><th>Conciliables Sí</th><th>Conciliables No</th><th>Estatus</th><th class="ht-nowrap" data-command-column>Observaciones</th><th class="ht-nowrap" data-command-column>Solicitud de cambios</th><th class="ht-nowrap" data-command-column>Reporte</th></tr></thead>
                    <tbody>
                        @forelse ($submissions as $submission)
                            @php($review = $submission->clientReview ?? $submission)
                            @php($noCount = $review->mixture_count - $review->conciliable_count)
                            @php($summary = $review->summary())
                            @php($conciliationStatus = ($review->filters['response_type'] ?? null) === 'accepted' ? 'Aceptada' : (($review->filters['response_type'] ?? null) === 'changes_requested' ? 'Con cambios' : ($submission->direction === 'sent' ? 'Recibida' : 'Enviada')))
                            <tr data-client-submission="{{ $submission->id }}" data-conciliation-differences="{{ $noCount > 0 || ($review->filters['response_type'] ?? null) === 'changes_requested' ? 'true' : 'false' }}">
                                <td>{{ $submission->folio() }}</td>
                                <td class="ht-nowrap">{{ $submission->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $submission->hospital_name }}</td>
                                <td>{{ $submission->sender_name }}</td>
                                <td>{{ $submission->periodLabel() }}</td>
                                <td class="ht-nowrap">{{ \App\Services\HospitalConciliationSummary::money($summary['total']['amount_cents']) }}</td>
                                <td class="ht-nowrap"><button type="button" class="ht-view" data-client-detail-open data-url="{{ route('admin.herramientas.conciliaciones.detail', $submission) }}">Ver</button></td>
                                <td class="ht-nowrap" data-client-new-amount>{{ \App\Services\HospitalConciliationSummary::money($summary['yes']['amount_cents']) }}</td>
                                <td data-client-total-count><span class="ht-conciliation-count ht-conciliation-count--total">{{ $submission->mixture_count }}</span></td>
                                <td data-client-yes-count><span class="ht-conciliation-count ht-conciliation-count--yes">{{ $review->conciliable_count }}</span></td>
                                <td data-client-no-count data-column-filter-value="{{ $noCount }}"><span class="ht-conciliation-count ht-conciliation-count--no">{{ $noCount }}</span></td>
                                <td class="ht-nowrap"><span class="ht-client-status" data-client-submission-status data-status="{{ $conciliationStatus }}">{{ $conciliationStatus }}</span></td>
                                <td class="ht-nowrap"><button type="button" class="ht-view" data-client-observations-open data-url="{{ route('admin.herramientas.conciliaciones.detail', $submission) }}">Ver</button></td>
                                <td class="ht-nowrap">
                                    @if ($submission->direction === 'sent')<button type="button" class="ht-secondary ht-conciliation-action" data-client-request-open data-url="{{ route('admin.herramientas.conciliaciones.change-request', $submission) }}">Solicitar cambios</button>@else — @endif
                                </td>
                                <td class="ht-nowrap"><a class="ht-secondary ht-conciliation-action" href="{{ route('admin.herramientas.conciliaciones.download', $submission) }}">Descargar reporte</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="15" class="ht-empty">No hay solicitudes de conciliación.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="client-conciliation-pagination">{{ $submissions->links() }}</div>
            <dialog class="cp-dialog" data-client-detail-dialog aria-labelledby="client-detail-title">
                <header><h2 id="client-detail-title" tabindex="-1">Detalle de conciliación</h2><button type="button" class="cp-close" data-client-detail-close aria-label="Cerrar detalle">×</button></header>
                <p data-client-detail-loading role="status" hidden>Cargando conciliación...</p>
                <p data-client-detail-error class="cp-error" role="alert" hidden></p>
                <button type="button" class="cp-secondary" data-client-detail-retry hidden>Reintentar</button>
                <div data-client-detail-content></div>
                <footer><button type="button" class="cp-secondary" data-client-detail-close>Cancelar</button><button type="button" class="cp-primary" data-client-detail-accept disabled>Aceptar y continuar</button></footer>
            </dialog>
            <dialog class="cp-dialog client-reason-dialog" data-client-reason-dialog aria-labelledby="client-reason-title">
                <header><h2 id="client-reason-title">Motivo de No conciliable</h2><button type="button" class="cp-close" data-client-reason-close aria-label="Cerrar motivo">×</button></header>
                <p class="cp-subtitle" data-client-reason-remission></p>
                <form data-client-reason-form>
                    <label for="client-reason-note">Observaciones y motivo <span class="cp-required">*</span></label>
                    <textarea id="client-reason-note" data-client-review-note maxlength="2000" rows="4" required></textarea>
                    <p class="cp-error" data-client-reason-error role="alert" hidden></p>
                    <p class="cp-note">El motivo se enviará a PROMESA Prodifem al pulsar «Aceptar y continuar» en el detalle.</p>
                    <footer><button type="button" class="cp-secondary" data-client-reason-close>Cancelar</button><button type="submit" class="cp-primary">Aceptar y guardar</button></footer>
                </form>
            </dialog>
            <dialog class="cp-dialog client-observations-dialog" data-client-observations-dialog aria-labelledby="client-observations-title">
                <header><h2 id="client-observations-title" tabindex="-1">Observaciones</h2><button type="button" class="cp-close" data-client-observations-close aria-label="Cerrar observaciones">×</button></header>
                <p data-client-observations-loading role="status" hidden>Cargando observaciones...</p>
                <p data-client-observations-error class="cp-error" role="alert" hidden></p>
                <button type="button" class="cp-secondary" data-client-observations-retry hidden>Reintentar</button>
                <div data-client-observations-content></div>
                <footer><button type="button" class="cp-secondary" data-client-observations-close>Cerrar</button></footer>
            </dialog>
            <dialog class="ht-send-dialog client-change-dialog" data-client-request-dialog aria-labelledby="client-change-title">
                <header class="ht-send-heading"><h2 id="client-change-title" tabindex="-1">Solicitar cambios</h2><button type="button" class="ht-icon" data-client-request-close aria-label="Cerrar">×</button></header>
                <p data-client-request-loading role="status" hidden>Cargando remisiones...</p>
                <p data-client-request-error class="ht-error" role="alert" hidden></p>
                <div data-client-request-content></div>
            </dialog>
        </section>
        @push('css')
            <style>
                .client-change-dialog { width: min(1100px, calc(100vw - 32px)); max-width: calc(100vw - 32px); }
                [data-client-review-note] { display: block; width: 100%; margin-top: 6px; border: 1px solid #d3dbe7; border-radius: 5px; padding: 8px; }
                .client-reason-dialog, .client-observations-dialog { width: min(640px, calc(100vw - 2rem)); }
                .client-reason-dialog header { gap: 1rem; align-items: flex-start; }
                .client-reason-dialog .cp-close { margin-left: auto; }
                .client-reason-dialog textarea { resize: vertical; }
                .client-reason-dialog footer { flex-wrap: wrap; }
                [data-client-observations-content] { margin: 1rem 0; }
                .client-observation { border-bottom: 1px solid #dbe2ee; padding: .8rem 0; }
                .client-observation p { white-space: pre-wrap; overflow-wrap: anywhere; margin-top: .4rem; }
                [data-client-row-observations]:disabled { background: #e5e7eb; color: #6b7280; cursor: default; }
                [data-client-detail-dialog] .cp-toggle button:disabled { cursor: default; }
                [data-client-detail-dialog] .cp-status { white-space: nowrap; }
                .cp-status[data-status="Con Cambios"] { background: #fee2e2; color: #b91c1c; }
                .cp-status[data-status="Sin Cambios"] { background: #d1fae5; color: #047857; }
                .client-change-table { min-width: 800px; }
                .client-change-table textarea { width: 100%; min-width: 230px; min-height: 64px; border: 1px solid #cbd5e1; border-radius: 4px; padding: 8px; }
                .client-change-table select { border: 1px solid #cbd5e1; border-radius: 4px; max-width: 170px; }
                .client-change-dialog footer { display: flex; justify-content: space-between; gap: 12px; margin-top: 20px; }
            </style>
        @endpush
    @endif
</x-admin-layout>
