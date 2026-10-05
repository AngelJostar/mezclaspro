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
        <section class="hospital-tools client-conciliation-panel mt-6" aria-labelledby="client-conciliation-title">
            <div class="ht-log-heading"><h2 id="client-conciliation-title">Solicitudes de conciliación enviadas</h2></div>
            <div class="ht-table-scroll" data-sticky-x-position="viewport" tabindex="0" aria-label="Solicitudes de conciliación enviadas">
                <table class="ht-table">
                    <thead><tr><th>Folio</th><th>Fecha de envío</th><th>Institución</th><th>Hospital</th><th>Enviado por</th><th>Periodo</th><th>Mezclas</th><th>Conciliables Sí</th><th>Conciliables No</th><th>Acciones</th></tr></thead>
                    <tbody>
                        @forelse ($submissions as $submission)
                            @php($noCount = $submission->mixture_count - $submission->conciliable_count)
                            <tr>
                                <td>{{ $submission->folio() }}</td>
                                <td class="ht-nowrap">{{ $submission->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ $institutionName ?: 'Sin institución' }}</td>
                                <td>{{ $submission->hospital_name }}</td>
                                <td>{{ $submission->sender_name }}</td>
                                <td>{{ $submission->periodLabel() }}</td>
                                <td>{{ $submission->mixture_count }}</td>
                                <td>{{ $submission->conciliable_count }}</td>
                                <td data-column-filter-value="{{ $noCount }}">@if ($noCount > 0)<span class="ht-no-count" title="{{ $noCount }} mezclas no conciliables">{{ $noCount }}</span>@else 0 @endif</td>
                                <td><a class="ht-secondary" href="{{ route('admin.herramientas.conciliaciones.download', $submission) }}">Descargar reporte</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="ht-empty">No hay solicitudes de conciliación enviadas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="client-conciliation-pagination">{{ $submissions->links() }}</div>
        </section>
    @endif
</x-admin-layout>
