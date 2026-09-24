@if ($solicitud->request_quotation_id && $solicitud->quotation)
    <div class="mb-4 flex flex-wrap items-center gap-3 border-b border-gray-200 py-3 text-sm">
        <strong>Solicitud {{ $solicitud->request_folio }}</strong>
        <a class="text-teal-700 underline" href="{{ route('admin.solicitudes.cotizacion.index', ['buscar' => $solicitud->quotation->folio]) }}">Cotizacion de origen: {{ $solicitud->quotation->folio }}</a>
    </div>
@endif
