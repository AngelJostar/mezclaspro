<div class="document">
    <table class="header"><tr>
        <td style="width:25%;text-align:center">@if ($issuerLogo)<img class="logo" src="{{ $issuerLogo }}" alt="{{ $issuerName }}">@endif</td>
        <td style="width:50%;text-align:center">
            <div class="title">REMISIÓN<br>MEZCLAS ESTÉRILES NUTRICIONALES</div>
        </td>
        <td style="width:25%;text-align:center">
            @if ($issuerName !== 'CMP Prodifem' || $issuerInformation)
                <strong>{{ $issuerName }}</strong>
                @if ($issuerInformation)<br><span class="muted">{{ $issuerInformation }}</span>@endif
            @endif
        </td>
    </tr></table>
    <div class="blue-band">ENTREGA DE LAS MEZCLAS NUTRICIONALES PREPARADAS EN CMP</div>
    <table class="meta"><tr><td>Fecha de envío: <strong>{{ $fmtDateTime($detail?->fecha_hora_entrega) }}</strong><br>Fecha de solicitud: <strong>{{ $fmtDateTime($solicitud_detalles->created_at) }}</strong></td><td class="text-right">No. Remisión: <strong>{{ $solicitud_detalles->remision ?: str_pad($solicitud_detalles->id, 6, '0', STR_PAD_LEFT) }}</strong></td></tr></table>
    @if (!empty($priceList?->has_contract) && (!empty($priceList?->contract_number) || !empty($priceList?->contract_information)))
        <table class="meta"><tr><td><strong>Contrato:</strong> {{ $priceList->contract_number ?: '—' }}</td><td class="text-right">{{ $priceList->contract_information }}</td></tr></table>
    @endif
    <div class="section-title">DATOS DEL PACIENTE</div>
    <table class="grid"><tr><th>Nombre completo</th><th>Fecha de nacimiento</th><th>Edad</th><th>Género</th><th>Superficie corporal</th></tr><tr><td>{{ trim(($patient?->nombre_paciente ?? '') . ' ' . ($patient?->apellidos_paciente ?? '')) ?: '—' }}</td><td>{{ $fmtDate($patient?->fecha_nacimiento) }}</td><td>{{ $patient?->edad ?? '—' }}</td><td>{{ $patient?->sexo ?? '—' }}</td><td>S/D</td></tr></table>
    <table class="grid"><tr><th>Diagnóstico</th><th>Servicio</th><th>No. de expediente</th><th>Médico tratante</th></tr><tr><td>{{ $patient?->diagnostico ?: '—' }}</td><td>{{ $patient?->servicio ?: '—' }}</td><td>{{ $patient?->registro ?: 'S/D' }}</td><td>{{ $detail?->nombre_medico ?: '—' }}</td></tr></table>
    <div class="section-title" style="margin-top:2px">{{ $remissionMode === 'frasco' ? 'DETALLE DE MEZCLA Y COBRO POR PRESENTACIÓN' : 'DETALLE DE MEZCLA Y COBRO POR MILILITRO' }}</div>
    <div class="mode-row">Modalidad de cobro: {{ $remissionMode === 'frasco' ? 'Por frasco' : 'Por mililitro' }}</div>
    @php $mixVolume = $number($detail?->volumen_total_final ?? 0, 2) . ' mL'; @endphp
    <table class="detail">
        @if ($remissionMode === 'frasco')
            <thead><tr><th style="width:4%">No.</th><th style="width:17%">Medicamento / Servicio</th><th style="width:8%">Dosis</th><th style="width:10%">Volumen mezcla</th><th style="width:12%">Almacén</th><th style="width:9%">Unidad de cobro</th><th style="width:11%">Presentación utilizada</th><th style="width:8%">Cantidad</th><th style="width:10%">Precio unitario</th><th style="width:11%">Total</th></tr></thead>
        @else
            <thead><tr><th style="width:4%">No.</th><th style="width:21%">Medicamento / Servicio</th><th style="width:9%">Dosis</th><th style="width:11%">Volumen mezcla</th><th style="width:14%">Almacén</th><th style="width:10%">Unidad de cobro</th><th style="width:9%">Cantidad</th><th style="width:11%">Precio unitario</th><th style="width:11%">Precio total</th></tr></thead>
        @endif
        <tbody>@php $rowNumber = 1; @endphp
        @foreach ($billableInputs as $item)
            @php $line = $medicationLines->get($loop->index, []); $dose = $number($item->valor ?? 0) . ' ' . $adjustUnit($item->input?->unidad ?? ''); $warehouse = $almacenesPorSolicitudInput[$item->id] ?? '—'; @endphp
            <tr><td>{{ $rowNumber++ }}</td><td class="text-left">{{ $documentName($item) }}</td><td>{{ $dose }}</td><td>{{ $mixVolume }}</td><td>{{ $warehouse }}</td><td>{{ $remissionMode === 'frasco' ? 'Frasco' : 'mL' }}</td>@if ($remissionMode === 'frasco')<td>{{ $presentationName($item) }}</td>@endif<td>{{ $number($line['quantity'] ?? 0) }}</td><td>{{ $money($line['unit_price'] ?? 0) }}</td><td>{{ $money($line['subtotal'] ?? 0) }}</td></tr>
        @endforeach
        @if ((float) ($pricingSummary['service_total'] ?? 0) > 0)
            <tr><td>{{ $rowNumber++ }}</td><td class="text-left">Servicio de mezclado<br><span class="muted">IVA incluido</span></td><td>NA</td><td>{{ $mixVolume }}</td><td>Almacén principal</td><td>Servicio</td>@if ($remissionMode === 'frasco')<td>NA</td>@endif<td>1</td><td>{{ $money($pricingSummary['service_total']) }}</td><td>{{ $money($pricingSummary['service_total']) }}</td></tr>
        @endif
        @foreach (collect($pricingSummary['supply_lines'] ?? []) as $line)
            @php $source = str_contains(mb_strtolower((string) ($line['description'] ?? '')), 'bolsa') ? $bolsa_eva : $set_infusion; @endphp
            <tr><td>{{ $rowNumber++ }}</td><td class="text-left">{{ $line['description'] }} (IVA incluido)</td><td>NA</td><td>{{ $mixVolume }}</td><td>{{ $almacenesPorSolicitudInput[$source?->id] ?? '—' }}</td><td>{{ ucfirst($line['unit_label'] ?? 'Pieza') }}</td>@if ($remissionMode === 'frasco')<td>{{ $presentationName($source) }}</td>@endif<td>{{ $number($line['quantity'] ?? 1) }}</td><td>{{ $money($line['unit_price'] ?? 0) }}</td><td>{{ $money($line['subtotal'] ?? 0) }}</td></tr>
        @endforeach
        @foreach (collect($pricingSummary['additional_charge_lines'] ?? []) as $line)
            <tr><td>{{ $rowNumber++ }}</td><td class="text-left">{{ $line['description'] }} (IVA incluido)</td><td>NA</td><td>{{ $mixVolume }}</td><td>Almacén principal</td><td>{{ ucfirst($line['unit_label'] ?? 'Servicio') }}</td>@if ($remissionMode === 'frasco')<td>NA</td>@endif<td>1</td><td>{{ $money($line['total'] ?? 0) }}</td><td>{{ $money($line['total'] ?? 0) }}</td></tr>
        @endforeach
        <tr><td class="detail-note text-left" colspan="{{ $remissionMode === 'frasco' ? 7 : 6 }}">@if ($remissionMode === 'frasco') Cantidades según consumo registrado. Precios según la lista del hospital.<br>Importe por presentación = frascos utilizados × precio unitario. @else Cantidad = mililitros registrados en la solicitud.<br>Precio unitario = precio por mL de la lista del hospital.<br>Precio total = mililitros de la solicitud × precio por mL. @endif</td><td class="total" colspan="3">TOTAL IVA INCLUIDO: {{ $money($pricingSummary['total_iva_included'] ?? 0) }}</td></tr>
        </tbody>
    </table>
    <table class="footer-row"><tr><td><strong>Lote de la mezcla:</strong> {{ $solicitud_detalles->lote ?: '—' }}</td></tr><tr><td><strong>Observaciones:</strong> {{ $detail?->observaciones ?: '—' }}</td></tr></table>
    <div class="receipt"><strong>Recepción Institución</strong><br>Fecha: ____________________________________ &nbsp; Hora: __________________<br>Temperatura: _______________________________<br>Nombre completo, firma y sello: __________________________________________</div>
    <div class="important"><strong>NOTA IMPORTANTE:</strong> La institución reconoce que la mezcla estéril entregada debe mantenerse bajo condiciones adecuadas de almacenamiento, asegurando la conservación de la red fría en todo momento.</div>
</div>
