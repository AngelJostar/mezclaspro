<x-admin-layout>
    @php
        $usesMedicineCatalog = in_array($category, ['oncologicos', 'antibioticos'], true);
        $usesMilligrams = $usesMedicineCatalog;
        $hasOldMedicineRows = old('medicamentos') !== null;
        $hasOldNutritionRows = old('items') !== null;
        $subdistributor = $list?->distributor;
        $hasSubdistributor = filter_var(
            old('has_subdistributor', $subdistributor ? 1 : 0),
            FILTER_VALIDATE_BOOLEAN
        );
        $hasContract = filter_var(
            old('has_contract', $list?->has_contract ? 1 : 0),
            FILTER_VALIDATE_BOOLEAN
        );
    @endphp

    <div class="rounded-xl bg-white p-5 shadow-sm">
        @include('admin.catalogo-listas.partials.section-nav', [
            'categories' => $categories,
            'category' => $category,
            'mode' => $mode,
        ])

        <div class="mt-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900">
                    {{ $list ? 'Editar lista' : 'Nueva lista' }} - {{ $categories[$category]['label'] }}
                </h2>
                <p class="text-sm text-gray-500">
                    Captura el precio antes de IVA por frasco; el precio por {{ $usesMilligrams ? 'miligramo' : 'mililitro' }} se calcula automaticamente.
                </p>
            </div>

            <a href="{{ route('admin.catalogo-listas.lists', ['category' => $category]) }}"
                class="text-sm font-semibold text-blue-700 hover:text-blue-900">
                Volver a listas
            </a>
        </div>

        @if (!$formAction)
            <div class="mt-5 rounded-lg border border-yellow-200 bg-yellow-50 p-4 text-sm text-yellow-800">
                Esta categoria aun no tiene catalogo de productos configurado.
            </div>
        @else
            <form id="priceListEditorForm" action="{{ $formAction }}" method="POST" enctype="multipart/form-data" class="mt-5">
                @csrf
                @if (($formMethod ?? 'POST') !== 'POST')
                    @method($formMethod)
                @endif
                <input type="hidden" name="from_catalogo_listas" value="{{ $category }}">

                @if ($usesMedicineCatalog)
                    <input type="hidden" name="charge_by" value="mg">
                    <input type="hidden" name="active_brands" value="0">
                    <input type="hidden" name="show_label_lot_expiry" value="0">
                @else
                    <input type="hidden" name="is_active" value="1">
                    <input type="hidden" name="active_brands" value="0">
                @endif

                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto_auto] lg:items-end">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-700">Nombre de la lista</label>
                        <input type="text" name="name" value="{{ old('name', $list?->name) }}"
                            class="h-10 w-full rounded-md border-gray-300 text-sm" required>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-700">Descripcion</label>
                        <input type="text" name="description" value="{{ old('description', $list?->description) }}"
                            class="h-10 w-full rounded-md border-gray-300 text-sm">
                    </div>

                    <label class="flex h-10 cursor-pointer items-center gap-2 whitespace-nowrap rounded-md border border-gray-300 px-3 text-sm font-semibold text-gray-700">
                        <input type="hidden" name="has_subdistributor" value="0">
                        <input type="checkbox" id="hasSubdistributor" name="has_subdistributor" value="1"
                            class="peer sr-only" @checked($hasSubdistributor)>
                        <span class="relative h-5 w-9 rounded-full bg-gray-200 transition-colors after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-cyan-600 peer-checked:after:translate-x-4 peer-focus:ring-2 peer-focus:ring-cyan-200"></span>
                        Agregar subdistribuidor
                    </label>

                    <label class="flex h-10 cursor-pointer items-center gap-2 whitespace-nowrap rounded-md border border-gray-300 px-3 text-sm font-semibold text-gray-700">
                        <input type="hidden" name="has_contract" value="0">
                        <input type="checkbox" id="hasContract" name="has_contract" value="1"
                            class="peer sr-only" @checked($hasContract)>
                        <span class="relative h-5 w-9 rounded-full bg-gray-200 transition-colors after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-blue-700 peer-checked:after:translate-x-4 peer-focus:ring-2 peer-focus:ring-blue-200"></span>
                        Contrato
                    </label>
                </div>

                <div id="subdistributorFields" class="mt-3 border-t border-gray-200 pt-3 {{ $hasSubdistributor ? '' : 'hidden' }}">
                    <h3 class="mb-2 text-sm font-semibold text-gray-800">Datos del subdistribuidor</h3>
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-700">Razon social</label>
                            <input type="text" name="subdistributor_razon_social" maxlength="255"
                                value="{{ old('subdistributor_razon_social', $subdistributor?->nombre) }}"
                                data-required-when-visible
                                class="h-9 w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-700">RFC</label>
                            <input type="text" name="subdistributor_rfc" maxlength="20"
                                value="{{ old('subdistributor_rfc', $subdistributor?->rfc) }}"
                                data-required-when-visible
                                class="h-9 w-full rounded-md border-gray-300 text-sm uppercase">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-700">Direccion</label>
                            <input type="text" name="subdistributor_direccion" maxlength="500"
                                value="{{ old('subdistributor_direccion', $subdistributor?->direccion) }}"
                                data-required-when-visible
                                class="h-9 w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-700">Contacto</label>
                            <input type="text" name="subdistributor_contacto" maxlength="255"
                                value="{{ old('subdistributor_contacto', $subdistributor?->contacto) }}"
                                data-required-when-visible
                                class="h-9 w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-700">Logotipo</label>
                            <input type="file" name="subdistributor_logo" accept=".jpg,.jpeg,.png,.webp"
                                class="block h-9 w-full rounded-md border border-gray-300 bg-white text-xs file:mr-2 file:h-9 file:border-0 file:bg-gray-100 file:px-3 file:text-xs file:font-semibold">
                            @if ($subdistributor?->logo_path)
                                <a href="{{ asset('storage/' . $subdistributor->logo_path) }}" target="_blank"
                                    class="mt-1 inline-block text-xs font-semibold text-blue-700 hover:text-blue-900">
                                    Ver logotipo actual
                                </a>
                            @endif
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-semibold text-gray-700">Informacion adicional</label>
                        <textarea name="subdistributor_additional_information" rows="3" maxlength="10000"
                            class="min-h-20 w-full resize-y rounded-md border-gray-300 text-sm leading-5"
                            placeholder="Agrega informacion adicional del subdistribuidor. Usa Enter para escribir en otro renglon.">{{ old('subdistributor_additional_information', $subdistributor?->informacion_adicional) }}</textarea>
                    </div>
                </div>

                <div id="contractFields" class="mt-3 border-t border-gray-200 pt-3 {{ $hasContract ? '' : 'hidden' }}">
                    <h3 class="mb-2 text-sm font-semibold text-gray-800">Datos del Contrato</h3>
                    <div class="grid gap-3 md:grid-cols-[minmax(220px,0.45fr)_minmax(0,1.55fr)] md:items-start">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-700">Contrato</label>
                            <input type="text" name="contract_number" maxlength="255"
                                value="{{ old('contract_number', $list?->contract_number) }}"
                                data-required-when-visible
                                class="h-9 w-full rounded-md border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-gray-700">Informacion del contrato</label>
                            <textarea name="contract_information" rows="4" maxlength="10000"
                                class="min-h-24 w-full resize-y rounded-md border-gray-300 text-sm leading-5"
                                placeholder="Agrega la informacion del contrato. Usa Enter para escribir en otro renglon.">{{ old('contract_information', $list?->contract_information) }}</textarea>
                        </div>
                    </div>
                </div>

                @if ($errors->hasAny(['subdistributor_razon_social', 'subdistributor_rfc', 'subdistributor_direccion', 'subdistributor_contacto', 'subdistributor_additional_information', 'subdistributor_logo', 'contract_number', 'contract_information']))
                    <p class="mt-2 text-sm font-medium text-red-600">
                        {{ $errors->first() }}
                    </p>
                @endif

                <div class="mt-3">
                    <input type="search" id="priceEditorSearch"
                        class="w-full rounded-lg border-gray-300 text-sm"
                        placeholder="Buscar producto o presentacion...">
                </div>

                @php
                    $selectionError = $errors->first($usesMedicineCatalog ? 'medicamentos' : 'items');
                @endphp
                @if ($selectionError)
                    <p class="mt-2 text-sm font-medium text-red-600">{{ $selectionError }}</p>
                @endif

                <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50 text-gray-700">
                            <tr>
                                <th class="w-12 px-3 py-2 text-center">
                                    <input type="checkbox" id="priceEditorSelectAll"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-700 focus:ring-blue-500"
                                        title="Seleccionar todos los productos visibles"
                                        aria-label="Seleccionar todos los productos visibles">
                                </th>
                                <th class="whitespace-nowrap px-3 py-2 text-left font-bold uppercase">Producto</th>
                                <th class="whitespace-nowrap px-3 py-2 text-left font-bold uppercase">Presentacion</th>
                                <th class="whitespace-nowrap px-3 py-2 text-left font-bold uppercase">Denominacion Comercial</th>
                                <th class="whitespace-nowrap px-3 py-2 text-left font-bold uppercase">Descripcion remision</th>
                                <th class="whitespace-nowrap px-3 py-2 text-right font-bold uppercase">{{ $usesMilligrams ? 'MG' : 'ML' }}</th>
                                <th class="whitespace-nowrap px-3 py-2 text-right font-bold uppercase">Precio por frasco</th>
                                <th class="whitespace-nowrap px-3 py-2 text-right font-bold uppercase">Precio por {{ $usesMilligrams ? 'miligramo' : 'mililitro' }}</th>
                                @if ($usesMedicineCatalog)
                                    <th class="whitespace-nowrap px-3 py-2 text-center font-bold uppercase">IVA desglosado</th>
                                @endif
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200 bg-white">
                            @php $rowIndex = 0; @endphp

                            @forelse ($catalogs as $catalog)
                                @foreach ($catalog->presentations as $presentation)
                                    @php
                                        $productName = $usesMedicineCatalog
                                            ? ($catalog->denominacion ?? '-')
                                            : ($catalog->denominacion_generica ?? '-');

                                        $presentationName = $presentation->presentacion ?? '-';
                                        $commercialName = $usesMilligrams
                                            ? ($presentation->marca ?? '-')
                                            : ($presentation->denominacion_comercial ?? '-');

                                        $unitAmount = $usesMilligrams
                                            ? (float) ($presentation->contentInMilligrams() ?: 0)
                                            : (float) ($presentation->presentacion_ml ?: 0);

                                        $priceData = $pricesByPresentation->get($presentation->id, [
                                            'price_bottle' => 0,
                                            'price_unit' => 0,
                                            'vat_breakdown' => false,
                                            'remission_description' => null,
                                        ]);

                                        $initialBottle = (float) ($priceData['price_bottle'] ?? 0);
                                        $initialUnitPrice = (float) ($priceData['price_unit'] ?? 0);
                                        $initialVatBreakdown = (bool) ($priceData['vat_breakdown'] ?? false);
                                        $defaultRemissionDescription = trim($productName . ' ' . $presentationName);
                                        $initialRemissionDescription = trim((string) ($priceData['remission_description'] ?? ''));

                                        if ($initialRemissionDescription === '') {
                                            $initialRemissionDescription = $defaultRemissionDescription;
                                        }

                                        if ($usesMedicineCatalog) {
                                            $oldBottle = old('medicamentos.' . $rowIndex . '.precio', null);
                                            if ($oldBottle !== null) {
                                                $initialBottle = (float) $oldBottle;
                                                $initialUnitPrice = $unitAmount > 0 ? $initialBottle / $unitAmount : 0;
                                            }

                                            $initialVatBreakdown = (bool) old(
                                                'medicamentos.' . $rowIndex . '.iva_desglosado',
                                                $initialVatBreakdown
                                            );
                                            $initialRemissionDescription = old(
                                                'medicamentos.' . $rowIndex . '.descripcion_remision',
                                                $initialRemissionDescription
                                            );
                                        } else {
                                            $oldMl = old('items.' . $rowIndex . '.precio_ml', null);
                                            if ($oldMl !== null) {
                                                $initialUnitPrice = (float) $oldMl;
                                                $initialBottle = $unitAmount > 0 ? $initialUnitPrice * $unitAmount : 0;
                                            }

                                            $initialRemissionDescription = old(
                                                'items.' . $rowIndex . '.descripcion_remision',
                                                $initialRemissionDescription
                                            );
                                        }

                                        $isSelected = $usesMedicineCatalog
                                            ? ($hasOldMedicineRows
                                                ? (bool) old('medicamentos.' . $rowIndex . '.selected', false)
                                                : $pricesByPresentation->has($presentation->id))
                                            : ($hasOldNutritionRows
                                                ? (bool) old('items.' . $rowIndex . '.selected', false)
                                                : $pricesByPresentation->has($presentation->id));
                                    @endphp

                                    <tr class="price-editor-row hover:bg-gray-50 {{ $isSelected ? 'bg-blue-50' : '' }}"
                                        data-search="{{ Str::lower($productName . ' ' . $presentationName . ' ' . $commercialName . ' ' . $initialRemissionDescription) }}"
                                        data-unit-amount="{{ $unitAmount }}">
                                        <td class="px-3 py-2 text-center">
                                            <input type="checkbox"
                                                @if ($usesMedicineCatalog)
                                                    name="medicamentos[{{ $rowIndex }}][selected]"
                                                @else
                                                    name="items[{{ $rowIndex }}][selected]"
                                                @endif
                                                value="1"
                                                class="price-editor-selection h-4 w-4 rounded border-gray-300 text-blue-700 focus:ring-blue-500"
                                                aria-label="Incluir {{ $productName }} - {{ $presentationName }} en la lista"
                                                @checked($isSelected)>
                                        </td>
                                        <td class="max-w-xs px-3 py-2 font-semibold text-gray-900">
                                            {{ $productName }}

                                            @if ($usesMedicineCatalog)
                                                <input type="hidden" name="medicamentos[{{ $rowIndex }}][catalog_id]"
                                                    value="{{ $catalog->id }}">
                                                <input type="hidden" name="medicamentos[{{ $rowIndex }}][presentation_id]"
                                                    value="{{ $presentation->id }}">
                                            @else
                                                <input type="hidden"
                                                    name="items[{{ $rowIndex }}][nutrition_medicine_presentation_id]"
                                                    value="{{ $presentation->id }}">
                                            @endif
                                        </td>

                                        <td class="max-w-xs px-3 py-2 text-gray-700">
                                            {{ $presentationName ?: '-' }}
                                        </td>

                                        <td class="max-w-xs px-3 py-2 text-gray-700">
                                            {{ $commercialName ?: '-' }}
                                        </td>

                                        <td class="min-w-72 px-3 py-2">
                                            <input type="text"
                                                @if ($usesMedicineCatalog)
                                                    name="medicamentos[{{ $rowIndex }}][descripcion_remision]"
                                                @else
                                                    name="items[{{ $rowIndex }}][descripcion_remision]"
                                                @endif
                                                value="{{ $initialRemissionDescription }}"
                                                maxlength="500"
                                                class="w-full min-w-72 rounded border-gray-300 text-xs"
                                                aria-label="Descripcion para remision de {{ $productName }}">
                                        </td>

                                        <td class="px-3 py-2 text-right tabular-nums text-gray-700">
                                            {{ $unitAmount > 0 ? rtrim(rtrim(number_format($unitAmount, 4, '.', ','), '0'), '.') : '-' }}
                                        </td>

                                        <td class="px-3 py-2 text-right">
                                            <div class="relative inline-block w-32">
                                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-semibold text-gray-500">$</span>
                                                <input type="number" min="0" step="0.0001"
                                                    @if ($usesMedicineCatalog) name="medicamentos[{{ $rowIndex }}][precio]" @endif
                                                    class="price-bottle-input w-32 rounded border-gray-300 pl-7 text-right text-xs"
                                                    value="{{ number_format($initialBottle, 4, '.', '') }}">
                                            </div>
                                        </td>

                                        <td class="px-3 py-2 text-right">
                                            <div class="relative inline-block w-32">
                                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs font-semibold text-gray-500">$</span>
                                                @if ($usesMedicineCatalog)
                                                    <input type="number" min="0" step="0.0001"
                                                        name="medicamentos[{{ $rowIndex }}][precio_mg]"
                                                        class="price-derived-input w-32 rounded border-gray-200 bg-gray-50 pl-7 text-right text-xs"
                                                        value="{{ number_format($initialUnitPrice, 4, '.', '') }}" readonly>
                                                @else
                                                    <input type="number" min="0" step="0.0001"
                                                        name="items[{{ $rowIndex }}][precio_ml]"
                                                        class="price-derived-input w-32 rounded border-gray-200 bg-gray-50 pl-7 text-right text-xs"
                                                        value="{{ number_format($initialUnitPrice, 4, '.', '') }}" readonly>
                                                @endif
                                            </div>
                                        </td>

                                        @if ($usesMedicineCatalog)
                                            <td class="px-3 py-2 text-center">
                                                <input type="hidden"
                                                    name="medicamentos[{{ $rowIndex }}][iva_desglosado]"
                                                    value="0">
                                                <label class="inline-flex cursor-pointer items-center justify-center"
                                                    title="Agregar IVA del 16% y mostrarlo por separado en la remision">
                                                    <input type="checkbox"
                                                        name="medicamentos[{{ $rowIndex }}][iva_desglosado]"
                                                        value="1"
                                                        class="peer sr-only"
                                                        @checked($initialVatBreakdown)>
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
                                    <td colspan="{{ $usesMedicineCatalog ? 9 : 8 }}" class="px-3 py-8 text-center text-sm text-gray-500">
                                        No hay productos disponibles para crear una lista.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 flex justify-end">
                    <x-table-action-button type="submit" variant="green" icon="fa-solid fa-floppy-disk">
                        Guardar lista
                    </x-table-action-button>
                </div>
            </form>
        @endif
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const search = document.getElementById('priceEditorSearch');
                const rows = Array.from(document.querySelectorAll('.price-editor-row'));
                const selectAll = document.getElementById('priceEditorSelectAll');
                const form = document.getElementById('priceListEditorForm');
                const hasSubdistributor = document.getElementById('hasSubdistributor');
                const subdistributorFields = document.getElementById('subdistributorFields');
                const hasContract = document.getElementById('hasContract');
                const contractFields = document.getElementById('contractFields');

                const syncConditionalSection = (toggle, section) => {
                    if (!toggle || !section) {
                        return;
                    }

                    const isVisible = toggle.checked;
                    section.classList.toggle('hidden', !isVisible);
                    section.querySelectorAll('input, textarea, select').forEach((input) => {
                        input.disabled = !isVisible;
                        input.required = isVisible && input.hasAttribute('data-required-when-visible');
                    });
                };

                hasSubdistributor?.addEventListener('change', () => {
                    syncConditionalSection(hasSubdistributor, subdistributorFields);
                });
                hasContract?.addEventListener('change', () => {
                    syncConditionalSection(hasContract, contractFields);
                });

                const normalize = (value) => String(value || '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .trim();

                const calculateRow = (row) => {
                    const bottle = Number(row.querySelector('.price-bottle-input')?.value || 0);
                    const unitAmount = Number(row.dataset.unitAmount || 0);
                    const result = row.querySelector('.price-derived-input');

                    if (!result) {
                        return;
                    }

                    result.value = unitAmount > 0 ? (bottle / unitAmount).toFixed(4) : '0.0000';
                };

                const syncRowSelection = (row) => {
                    const selection = row.querySelector('.price-editor-selection');
                    row.classList.toggle('bg-blue-50', Boolean(selection?.checked));
                };

                const visibleRows = () => rows.filter((row) => !row.classList.contains('hidden'));

                const syncSelectAll = () => {
                    if (!selectAll) {
                        return;
                    }

                    const visibleSelections = visibleRows()
                        .map((row) => row.querySelector('.price-editor-selection'))
                        .filter(Boolean);
                    const selectedCount = visibleSelections.filter((selection) => selection.checked).length;

                    selectAll.checked = visibleSelections.length > 0 && selectedCount === visibleSelections.length;
                    selectAll.indeterminate = selectedCount > 0 && selectedCount < visibleSelections.length;
                    selectAll.disabled = visibleSelections.length === 0;
                };

                rows.forEach((row) => {
                    const bottle = row.querySelector('.price-bottle-input');
                    const selection = row.querySelector('.price-editor-selection');

                    bottle?.addEventListener('input', () => {
                        calculateRow(row);

                        if (Number(bottle.value || 0) > 0 && selection && !selection.checked) {
                            selection.checked = true;
                            syncRowSelection(row);
                            syncSelectAll();
                        }
                    });
                    selection?.addEventListener('change', () => {
                        syncRowSelection(row);
                        syncSelectAll();
                    });
                    calculateRow(row);
                    syncRowSelection(row);
                });

                selectAll?.addEventListener('change', function() {
                    visibleRows().forEach((row) => {
                        const selection = row.querySelector('.price-editor-selection');
                        if (selection) {
                            selection.checked = this.checked;
                            syncRowSelection(row);
                        }
                    });

                    syncSelectAll();
                });

                search?.addEventListener('input', function() {
                    const term = normalize(this.value);

                    rows.forEach((row) => {
                        row.classList.toggle('hidden', term !== '' && !normalize(row.dataset.search).includes(term));
                    });

                    syncSelectAll();
                });

                form?.addEventListener('submit', function(event) {
                    const hasSelection = rows.some((row) => row.querySelector('.price-editor-selection')?.checked);

                    if (!hasSelection) {
                        event.preventDefault();
                        window.Swal?.fire({
                            icon: 'warning',
                            title: 'Selecciona productos',
                            text: 'Selecciona al menos un producto para conformar la lista de precios.',
                            confirmButtonText: 'Entendido',
                        }) || window.alert('Selecciona al menos un producto para conformar la lista de precios.');
                    }
                });

                syncSelectAll();
                syncConditionalSection(hasSubdistributor, subdistributorFields);
                syncConditionalSection(hasContract, contractFields);
            });
        </script>
    @endpush
</x-admin-layout>
