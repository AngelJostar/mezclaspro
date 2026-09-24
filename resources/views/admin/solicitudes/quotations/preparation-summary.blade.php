<style>
    [data-quotation-preparation] { width: 100%; min-width: 0; }
    [data-quotation-preparation] [hidden] { display: none !important; }
    [data-quotation-preparation] > .flex { flex-wrap: wrap; }
    [data-quotation-preparation] > .flex > div { min-width: 0; flex: 1 1 12rem; }
    [data-quotation-preparation] .quoted-concentration { min-width: 9rem; padding: .5rem .75rem; font-weight: 600; vertical-align: middle; }
    [data-quotation-preparation] .quoted-presentation { display: block; padding: .35rem .5rem; font-size: .75rem; color: #64748b; white-space: normal; }
    [data-quotation-preparation] .oncology-medicine-table-wrap { overflow-x: auto; border-radius: 4px; }
    [data-quotation-preparation] .quoted-medicine-table { width: 100%; min-width: 1050px; border-collapse: collapse; }
    [data-quotation-preparation] .quoted-medicine-table td { vertical-align: middle; }
    [data-quotation-preparation] .quoted-nutrition-table { width: 100%; min-width: 560px; border-collapse: collapse; text-align: left; font-size: .875rem; }
    [data-quotation-preparation] .quoted-nutrition-table th,
    [data-quotation-preparation] .quoted-nutrition-table td { padding: .5rem; border-bottom: 1px solid #e5e7eb; }
    [data-quotation-preparation] .quoted-nutrition-table input { width: 100%; min-width: 7rem; }
    @media (max-width: 640px) {
        [data-quotation-preparation] { padding: 1rem; }
        [data-quotation-preparation] .flex.items-baseline,
        [data-quotation-preparation] .flex.items-stretch { flex-wrap: wrap; }
        [data-quotation-preparation] input, [data-quotation-preparation] select { min-width: 0; max-width: 100%; }
    }
</style>
<section class="mb-6 border-b border-gray-200 pb-4" aria-label="Cotizacion de origen">
    <div class="flex flex-wrap justify-between gap-3 mb-3">
        <div>
            <span class="text-sm text-gray-500">Cotizacion de origen</span>
            <a class="ml-2 font-semibold text-teal-700 underline" href="{{ route('admin.solicitudes.cotizacion.index', ['buscar' => $preparationQuotation->folio]) }}">{{ $preparationQuotation->folio }}</a>
            <p class="mt-1 font-medium">{{ $preparationQuotation->hospital->name }}</p>
        </div>
        <div class="text-right">
            <span class="text-sm text-gray-500">Total autorizado</span>
            <p class="font-semibold">${{ number_format($preparationQuotation->total, 2) }} MXN</p>
        </div>
    </div>
    <div class="overflow-x-auto">
        @php $formatConcentration = fn ($value) => rtrim(rtrim(number_format($value, 4, '.', ','), '0'), '.'); @endphp
        <table class="w-full text-left text-sm" data-disable-column-filters data-quoted-summary>
            <thead class="bg-gray-50"><tr><th scope="col" class="p-2">Medicamento cotizado</th><th scope="col" class="p-2">Concentraci&oacute;n por presentaci&oacute;n</th><th scope="col" class="p-2">Cantidad</th><th scope="col" class="p-2">Precio unitario</th></tr></thead>
            <tbody>
                @foreach ($quotedMedications as $medication)
                  @foreach ($medication['items'] as $item)
                    <tr class="border-b border-gray-100">
                        <td class="p-2"><span class="block text-xs text-gray-500">Mezcla {{ $item['mixture_number'] }}</span>{{ $item['name'] }}<span class="block text-xs text-gray-500">{{ $item['presentation'] }}</span></td>
                        <td class="p-2 whitespace-nowrap" data-presentation-concentration>{{ $item['capacity'] === null ? 'No registrada' : $formatConcentration($item['capacity']).' '.$item['concentration_unit'] }}</td>
                        <td class="p-2 whitespace-nowrap">{{ $item['quantity'] }} {{ $item['unit'] }}</td>
                        <td class="p-2 whitespace-nowrap">${{ number_format($item['line']['unit_price'], 4) }} / {{ $item['unit'] }}</td>
                    </tr>
                  @endforeach
                    <tr class="bg-gray-50 border-b border-gray-200 font-semibold" data-quoted-medication-total="{{ $medication['catalog_id'] }}">
                        <th scope="row" class="p-2">Total cotizado de {{ $medication['name'] }}</th>
                        <td class="p-2 whitespace-nowrap" colspan="3">{{ $medication['quoted_concentration'] === null ? 'Concentracion incompleta' : $formatConcentration($medication['quoted_concentration']).' '.$medication['concentration_unit'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
