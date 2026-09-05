@php
    $usesMedicineCatalog = in_array($categoryKey, ['oncologicos', 'antibioticos'], true);
    $usesMilligrams = $usesMedicineCatalog;
    $usesChargeMethod = $usesMedicineCatalog || $categoryKey === 'nutricionales';
    $chargeUnitValue = $usesMilligrams ? 'mg' : 'ml';
    $chargeUnitLabel = $usesMilligrams ? 'Miligramo' : 'Mililitro';
    $defaultChargeBy = $usesMilligrams ? 'frasco' : 'ml';
    $inputPrefix = 'category_items.' . $categoryKey;
    $rowIndex = 0;
@endphp

<section id="category-panel-{{ $categoryKey }}" role="tabpanel"
    data-category-panel="{{ $categoryKey }}" @class(['hidden' => $categoryKey !== $activeCategory])>
    <div class="overflow-x-auto rounded-md border border-gray-200" data-sticky-x-position="viewport">
        <table class="min-w-full divide-y divide-gray-200 text-xs">
            <thead class="bg-gray-50 text-gray-700">
                <tr>
                    <th class="w-12 px-3 py-2 text-center">
                        <input type="checkbox" data-select-all="{{ $categoryKey }}"
                            class="h-4 w-4 rounded border-gray-300 text-blue-700 focus:ring-blue-500"
                            title="Seleccionar todos los productos visibles"
                            aria-label="Seleccionar todos los productos visibles de {{ $categories[$categoryKey]['label'] }}">
                    </th>
                    <th class="whitespace-nowrap px-3 py-2 text-left font-bold uppercase">Producto</th>
                    <th class="whitespace-nowrap px-3 py-2 text-left font-bold uppercase">Presentación</th>
                    <th class="whitespace-nowrap px-3 py-2 text-left font-bold uppercase">Denominación comercial</th>
                    <th class="whitespace-nowrap px-3 py-2 text-left font-bold uppercase">Descripción remisión</th>
                    <th class="whitespace-nowrap px-3 py-2 text-right font-bold uppercase">{{ $usesMilligrams ? 'MG' : 'ML' }}</th>
                    <th class="whitespace-nowrap px-3 py-2 text-right font-bold uppercase">Precio por frasco</th>
                    <th class="whitespace-nowrap px-3 py-2 text-right font-bold uppercase">Precio por {{ Str::lower($chargeUnitLabel) }}</th>
                    @if ($usesChargeMethod)
                        <th class="min-w-40 px-0 py-2 text-center font-bold uppercase">
                            <span class="block px-3">Cobrar por</span>
                            <span class="mt-1 grid grid-cols-2 border-t border-gray-200 pt-1 text-[10px]">
                                <label class="inline-flex cursor-pointer items-center justify-center gap-1.5 border-r border-gray-200"
                                    title="Seleccionar o deseleccionar Frasco en todos los renglones visibles">
                                    <span>Frasco</span>
                                    <input type="checkbox" data-charge-by-select-all="{{ $categoryKey }}"
                                        data-charge-by-option="frasco"
                                        class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                        aria-label="Seleccionar o deseleccionar cobro por frasco en todos los renglones visibles">
                                </label>
                                <label class="inline-flex cursor-pointer items-center justify-center gap-1.5"
                                    title="Seleccionar o deseleccionar {{ $chargeUnitLabel }} en todos los renglones visibles">
                                    <span>{{ $chargeUnitLabel }}</span>
                                    <input type="checkbox" data-charge-by-select-all="{{ $categoryKey }}"
                                        data-charge-by-option="{{ $chargeUnitValue }}"
                                        class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                        aria-label="Seleccionar o deseleccionar cobro por {{ Str::lower($chargeUnitLabel) }} en todos los renglones visibles">
                                </label>
                            </span>
                        </th>
                    @endif

                    @if ($usesMedicineCatalog)
                        <th class="whitespace-nowrap px-3 py-2 text-center font-bold uppercase">IVA desglosado</th>
                    @endif
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($catalogs as $catalog)
                    @foreach ($catalog->presentations as $presentation)
                        @php
                            $productName = $usesMedicineCatalog
                                ? ($catalog->denominacion ?? '-')
                                : ($catalog->denominacion_generica ?? '-');
                            $presentationName = $presentation->presentacion ?? '-';
                            $commercialName = $usesMedicineCatalog
                                ? ($presentation->marca ?? '-')
                                : ($presentation->denominacion_comercial ?? '-');
                            $unitAmount = $usesMilligrams
                                ? (float) ($presentation->contentInMilligrams() ?: 0)
                                : (float) ($presentation->presentacion_ml ?: 0);
                            $defaultDescription = trim($productName . ' ' . $presentationName);
                            $initialBottle = (float) old($inputPrefix . '.' . $rowIndex . '.price_bottle', 0);
                            $initialUnit = $unitAmount > 0
                                ? $initialBottle / $unitAmount
                                : (float) old($inputPrefix . '.' . $rowIndex . '.price_unit', 0);
                            $initialDescription = old(
                                $inputPrefix . '.' . $rowIndex . '.remission_description',
                                $defaultDescription
                            );
                            $initialVat = (bool) old($inputPrefix . '.' . $rowIndex . '.vat_breakdown', false);
                            $isSelected = (bool) old($inputPrefix . '.' . $rowIndex . '.selected', false);
                            $fieldName = 'category_items[' . $categoryKey . '][' . $rowIndex . ']';
                            $initialChargeBy = strtolower((string) old(
                                $inputPrefix . '.' . $rowIndex . '.charge_by',
                                $defaultChargeBy
                            ));

                            if (! in_array($initialChargeBy, ['frasco', $chargeUnitValue], true)) {
                                $initialChargeBy = $defaultChargeBy;
                            }
                        @endphp

                        <tr class="price-editor-row hover:bg-gray-50 {{ $isSelected ? 'bg-blue-50' : '' }}"
                            data-category="{{ $categoryKey }}"
                            data-search="{{ Str::lower($productName . ' ' . $presentationName . ' ' . $commercialName . ' ' . $initialDescription) }}"
                            data-unit-amount="{{ $unitAmount }}">
                            <td class="px-3 py-2 text-center">
                                <input type="checkbox" name="{{ $fieldName }}[selected]" value="1"
                                    class="price-editor-selection h-4 w-4 rounded border-gray-300 text-blue-700 focus:ring-blue-500"
                                    aria-label="Incluir {{ $productName }} - {{ $presentationName }} en la lista"
                                    @checked($isSelected)>
                            </td>
                            <td class="max-w-xs px-3 py-2 font-semibold text-gray-900">
                                {{ $productName }}
                                <input type="hidden" name="{{ $fieldName }}[presentation_id]" value="{{ $presentation->id }}">
                            </td>
                            <td class="max-w-xs px-3 py-2 text-gray-700">{{ $presentationName }}</td>
                            <td class="max-w-xs px-3 py-2 text-gray-700">{{ $commercialName }}</td>
                            <td class="min-w-72 px-3 py-2">
                                <input type="text" name="{{ $fieldName }}[remission_description]"
                                    value="{{ $initialDescription }}" maxlength="500"
                                    class="w-full min-w-72 rounded border-gray-300 text-xs"
                                    aria-label="Descripción para remisión de {{ $productName }}">
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-700">
                                {{ $unitAmount > 0 ? rtrim(rtrim(number_format($unitAmount, 4, '.', ','), '0'), '.') : '-' }}
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="relative inline-block w-32">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-semibold text-gray-500">$</span>
                                    <input type="number" min="0" step="0.0001" name="{{ $fieldName }}[price_bottle]"
                                        class="price-bottle-input w-32 rounded border-gray-300 pl-7 text-right text-xs"
                                        value="{{ number_format($initialBottle, 4, '.', '') }}">
                                </div>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <div class="relative inline-block w-32">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-semibold text-gray-500">$</span>
                                    <input type="number" min="0" step="0.0001" name="{{ $fieldName }}[price_unit]"
                                        class="price-derived-input w-32 rounded border-gray-200 bg-gray-50 pl-7 text-right text-xs"
                                        value="{{ number_format($initialUnit, 4, '.', '') }}" readonly>
                                </div>
                            </td>
                            @if ($usesChargeMethod)
                                <td class="px-0 py-2 text-center">
                                    @include('admin.catalogo-listas.partials.charge-method-selector', [
                                        'fieldName' => $fieldName,
                                        'initialChargeBy' => $initialChargeBy,
                                        'productName' => $productName,
                                        'presentationName' => $presentationName,
                                        'unitOptionValue' => $chargeUnitValue,
                                        'unitOptionLabel' => $chargeUnitLabel,
                                    ])
                                </td>
                            @endif

                            @if ($usesMedicineCatalog)
                                <td class="px-3 py-2 text-center">
                                    <input type="hidden" name="{{ $fieldName }}[vat_breakdown]" value="0">
                                    <label class="inline-flex cursor-pointer items-center justify-center"
                                        title="Agregar IVA del 16% y mostrarlo por separado en la remisión">
                                        <input type="checkbox" name="{{ $fieldName }}[vat_breakdown]" value="1"
                                            class="peer sr-only" @checked($initialVat)>
                                        <span class="relative h-6 w-11 rounded-full bg-gray-200 transition-colors after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow-sm after:transition-transform peer-checked:bg-emerald-500 peer-checked:after:translate-x-5 peer-focus:ring-2 peer-focus:ring-emerald-200"></span>
                                        <span class="sr-only">Aplicar IVA desglosado</span>
                                    </label>
                                </td>
                            @endif
                        </tr>

                        @php $rowIndex++; @endphp
                    @endforeach
                @empty
                    <tr>
                        <td colspan="{{ $usesMedicineCatalog ? 10 : ($usesChargeMethod ? 9 : 8) }}" class="px-3 py-8 text-center text-sm text-gray-500">
                            No hay productos disponibles en esta categoría.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
