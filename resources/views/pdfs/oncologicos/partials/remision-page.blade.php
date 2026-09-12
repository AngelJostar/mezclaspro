<div class="document">
    <table class="header"><tr>
        <td style="width:25%;text-align:center">@if ($issuerLogo)<img class="logo" src="{{ $issuerLogo }}" alt="{{ $issuerName }}">@endif</td>
        <td style="width:50%;text-align:center">
            <div class="title">REMISIÓN<br>MEZCLAS ESTÉRILES ONCOLÓGICAS</div>
        </td>
        <td style="width:25%;text-align:center">
            @if ($issuerName !== 'CMP Prodifem' || $issuerInformation)
                <strong>{{ $issuerName }}</strong>
                @if ($issuerInformation)<br><span class="muted">{{ $issuerInformation }}</span>@endif
            @endif
        </td>
    </tr></table>
    <div class="blue-band">ENTREGA DE LAS MEZCLAS ONCOLÓGICAS PREPARADAS EN CMP</div>
    <table class="meta"><tr><td>Fecha de envío: <strong>{{ $fmtDateTime($fechaEmision) }}</strong><br>Fecha de solicitud: <strong>{{ $fmtDateTime($solicitud->created_at ?? null) }}</strong></td><td class="text-right">No. Remisión: <strong>{{ $mix?->remision ?: '—' }}</strong></td></tr></table>
    @if (!empty($priceList?->has_contract) && (!empty($priceList?->contract_number) || !empty($priceList?->contract_information)))
        <table class="meta"><tr><td><strong>Contrato:</strong> {{ $priceList->contract_number ?: '—' }}</td><td class="text-right">{{ $priceList->contract_information }}</td></tr></table>
    @endif
    <div class="section-title">DATOS DEL PACIENTE</div>
    <table class="grid"><tr><th>Nombre completo</th><th>Fecha de nacimiento</th><th>Edad</th><th>Género</th><th>Alergias</th></tr><tr><td>{{ $patientName }}</td><td>{{ $birthDate }}</td><td>{{ $age }}</td><td>{{ $gender }}</td><td>{{ $allergies }}</td></tr></table>
    <table class="grid"><tr><th>Diagnóstico</th><th>Servicio</th><th>No. de expediente</th><th>Médico tratante</th></tr><tr><td>{{ $diagnosis }}</td><td>{{ $service }}</td><td>{{ $record }}</td><td>{{ $doctor }}</td></tr></table>
    <div class="section-title" style="margin-top:2px">{{ match ($remissionMode) {
        'frasco' => 'DETALLE DE MEZCLAS Y COBRO POR PRESENTACIÓN',
        'mg' => 'DETALLE DE MEZCLAS Y COBRO POR MILIGRAMO',
        'ml' => 'DETALLE DE MEZCLAS Y COBRO POR MILILITRO',
        default => 'DETALLE DE MEZCLAS Y COBRO',
    } }}</div>
    <div class="mode-row">Modalidad de cobro: {{ match ($remissionMode) {
        'frasco' => 'Por frasco',
        'mg' => 'Por miligramo',
        'ml' => 'Por mililitro',
        default => 'Mixta, según lista de precios',
    } }}</div>
    <table class="detail">
        @if ($showPresentationColumn)
            <thead><tr><th style="width:4%">No.</th><th style="width:13%">Medicamento / Servicio</th><th style="width:8%">Dosis (mg)</th><th style="width:10%">Diluyente</th><th style="width:9%">Volumen mezcla</th><th style="width:11%">Almacén</th><th style="width:8%">Unidad de cobro</th><th style="width:10%">Presentación utilizada</th><th style="width:7%">Cantidad</th><th style="width:9%">Precio unitario</th><th style="width:11%">Total por presentación</th></tr></thead>
        @else
            <thead><tr><th style="width:4%">No.</th><th style="width:15%">Medicamento / Servicio</th><th style="width:9%">Dosis (mg)</th><th style="width:11%">Diluyente</th><th style="width:10%">Volumen mezcla</th><th style="width:13%">Almacén</th><th style="width:9%">Unidad de cobro</th><th style="width:10%">Cantidad</th><th style="width:9%">Precio unitario</th><th style="width:10%">Precio total</th></tr></thead>
        @endif
        <tbody>@php $rowNumber = 1; @endphp
        @foreach ($mezclas as $currentMix)
            @php $mixVolume = isset($currentMix->volumen_dilucion) ? $number($currentMix->volumen_dilucion) . ' mL' : '—'; @endphp
            @foreach ($currentMix->medicamentos as $medicine)
                @php
                    $medicineName = $medicine->denominacion_doc ?? $medicine->denominacion_snapshot ?? $medicine->medicamentoOnco?->catalog?->denominacion ?? $medicine->nombre_medicamento ?? '—';
                    $dose = $number($medicine->dosis ?? 0) . ' mg';
                    $diluent = $medicine->diluyente?->denominacion_generica
                        ?? $currentMix->diluentPresentation?->diluent?->denominacion_generica
                        ?? '—';
                    $presentationLines = collect($medicine->presentation_charge_lines ?? []);
                    $medicineWarehouse = collect($medicine->presentacionesUsadas ?? [])->map(fn ($used) => $used->batch?->warehouse?->name)->filter()->unique()->implode(', ') ?: ($medicine->warehouse_doc ?? '—');
                    $medicineUnit = strtolower((string) ($medicine->unidad_cobro ?? 'mg'));
                    $medicineIsBottle = $medicineUnit === 'frasco';
                    $medicineUnitLabel = $medicineUnit === 'ml' ? 'mL' : ($medicineIsBottle ? 'Frasco' : 'mg');
                @endphp
                @if ($medicineIsBottle)
                    @forelse ($presentationLines as $presentationLine)
                        <tr><td>{{ $loop->first ? $rowNumber : '' }}</td><td class="text-left">{{ $loop->first ? $medicineName : '' }}</td><td>{{ $loop->first ? $dose : '' }}</td><td>{{ $loop->first ? $diluent : '' }}</td><td>{{ $loop->first ? $mixVolume : '' }}</td><td>{{ $presentationLine['warehouse'] ?? $medicineWarehouse }}</td><td>Frasco</td><td>{{ $presentationLine['presentation'] }}</td><td>{{ $number($presentationLine['quantity']) }}</td><td>{{ $money($presentationLine['unit_price']) }}</td><td>{{ $money($presentationLine['subtotal']) }}</td></tr>
                    @empty
                        <tr><td>{{ $rowNumber }}</td><td class="text-left">{{ $medicineName }}</td><td>{{ $dose }}</td><td>{{ $diluent }}</td><td>{{ $mixVolume }}</td><td>{{ $medicineWarehouse }}</td><td>Frasco</td><td>Sin presentación registrada</td><td>{{ $number($medicine->cantidad_cobro ?? 0) }}</td><td>{{ $money($medicine->precio_unitario_calculado ?? 0) }}</td><td>{{ $money($medicine->subtotal_calculado ?? 0) }}</td></tr>
                    @endforelse
                @else
                    <tr><td>{{ $rowNumber }}</td><td class="text-left">{{ $medicineName }}</td><td>{{ $dose }}</td><td>{{ $diluent }}</td><td>{{ $mixVolume }}</td><td>{{ $medicineWarehouse }}</td><td>{{ $medicineUnitLabel }}</td>@if ($showPresentationColumn)<td>NA</td>@endif<td>{{ $number($medicine->cantidad_cobro ?? 0) }}</td><td>{{ $money($medicine->precio_unitario_calculado ?? 0) }}</td><td>{{ $money($medicine->subtotal_calculado ?? 0) }}</td></tr>
                @endif
                @php $rowNumber++; @endphp
            @endforeach
            @if ((float) ($currentMix->mixing_service_total ?? 0) > 0)
                @php
                    $serviceWarehouse = $currentMix->medicamentos
                        ->map(fn ($medicine) => $medicine->warehouse_doc ?? null)
                        ->filter()
                        ->first() ?: 'Almacén principal';
                @endphp
                <tr><td>{{ $rowNumber++ }}</td><td class="text-left">Servicio de mezclado<br><span class="muted">IVA incluido</span></td><td>NA</td><td>NA</td><td>{{ $mixVolume }}</td><td>{{ $serviceWarehouse }}</td><td>Servicio</td>@if ($showPresentationColumn)<td>NA</td>@endif<td>1</td><td>{{ $money($currentMix->mixing_service_total) }}</td><td>{{ $money($currentMix->mixing_service_total) }}</td></tr>
            @endif
            @if (($currentMix->infusor_aplica ?? false) && (float) ($currentMix->infusor_subtotal ?? 0) > 0)
                <tr><td>{{ $rowNumber++ }}</td><td class="text-left">Infusor: {{ $currentMix->infusor_nombre }}</td><td>NA</td><td>NA</td><td>{{ $mixVolume }}</td><td>—</td><td>Pieza</td>@if ($showPresentationColumn)<td>NA</td>@endif<td>1</td><td>{{ $money($currentMix->infusor_precio) }}</td><td>{{ $money($currentMix->infusor_subtotal) }}</td></tr>
            @endif
            @foreach (($currentMix->additional_charge_lines ?? collect()) as $charge)
                <tr><td>{{ $rowNumber++ }}</td><td class="text-left">{{ $charge['description'] }} (IVA incluido)</td><td>NA</td><td>NA</td><td>{{ $mixVolume }}</td><td>Almacén principal<br><span class="muted">Nombre configurado</span></td><td>{{ ucfirst($charge['unit_label'] ?? 'Servicio') }}</td>@if ($showPresentationColumn)<td>NA</td>@endif<td>1</td><td>{{ $money($charge['total'] ?? 0) }}</td><td>{{ $money($charge['total'] ?? 0) }}</td></tr>
            @endforeach
        @endforeach
        <tr><td class="detail-note text-left" colspan="{{ $showPresentationColumn ? 8 : 7 }}">@if ($remissionMode === 'frasco') Cantidades según consumo registrado. Precios según la lista del hospital.<br>Importe por presentación = frascos utilizados × precio unitario. @elseif ($remissionMode === 'mg') Cantidad = miligramos indicados en la dosis.<br>Precio total = miligramos de la solicitud × precio por mg. @elseif ($remissionMode === 'mixed') Cada medicamento se cobra según su configuración: por frasco utilizado o por miligramo indicado en la dosis. @else Registro histórico con cobro por mililitro. @endif</td><td class="total" colspan="3">TOTAL IVA INCLUIDO: {{ $money($totalRemision) }}</td></tr>
        </tbody>
    </table>
    <table class="footer-row"><tr><td><strong>Lote de la mezcla:</strong> {{ $mezclas->pluck('lote')->filter()->unique()->implode(', ') ?: '—' }}</td></tr><tr class="observations"><td><strong>Observaciones:</strong> {{ $observations }}</td></tr></table>
    <div class="signature">Nombre completo y firma<br>Fecha de recibido</div>
</div>
