<section class="my-5 border-y border-gray-200 py-4">
    @php $formatConcentration = fn ($value) => rtrim(rtrim(number_format($value, 4, '.', ','), '0'), '.'); @endphp
    @foreach ($quotedMixtures as $number => $medications)
        <h2 class="mb-3 mt-4 font-semibold">Mezcla {{ $number }}</h2>
        <div class="overflow-x-auto">
            <table class="quoted-nutrition-table" data-disable-column-filters>
                <thead class="bg-gray-50"><tr>
                    <th scope="col">Medicamento</th><th scope="col">Concentraci&oacute;n cotizada</th>
                    <th scope="col">Presentaci&oacute;n</th><th scope="col">Volumen (mL)</th>
                </tr></thead>
                <tbody>
                    @foreach ($medications as $medication)
                        @foreach ($medication['items'] as $index => $item)
                            <tr>
                                @if ($loop->first)
                                    <th scope="rowgroup" rowspan="{{ count($medication['items']) }}">{{ $medication['name'] }}</th>
                                    <td rowspan="{{ count($medication['items']) }}" data-quoted-concentration="{{ $medication['catalog_id'] }}">{{ $medication['quoted_concentration'] === null ? 'Concentracion incompleta' : $formatConcentration($medication['quoted_concentration']).' mL' }}</td>
                                @endif
                                <td>{{ $item['presentation'] }}</td>
                                <td>
                                    <label class="sr-only" for="quoted-volume-{{ $index }}">Volumen de {{ $item['name'] }}, {{ $item['presentation'] }}, en mL</label>
                                    <input id="quoted-volume-{{ $index }}" name="quoted_volumes[{{ $index }}]" type="number"
                                        min="0.0001" step="0.0001" required class="rounded border-gray-300"
                                        @if ($item['clinical_quantity'] !== null) readonly value="{{ $item['clinical_quantity'] }}"
                                        @else value="{{ old('quoted_volumes.'.$index) }}" max="{{ $item['quoted_concentration'] }}" @endif>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach
</section>
