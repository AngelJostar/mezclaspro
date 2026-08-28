@php
    $activeCategory = old('active_category', $category);

    if (!array_key_exists($activeCategory, $categories)) {
        $activeCategory = array_key_first($categories);
    }

    $hasSubdistributor = filter_var(old('has_subdistributor', false), FILTER_VALIDATE_BOOLEAN);
    $hasContract = filter_var(old('has_contract', false), FILTER_VALIDATE_BOOLEAN);
    $backupListUrls = collect(array_keys($categories))->mapWithKeys(fn ($key) => [
        $key => route('admin.catalogo-listas.backup-lists.create', ['category' => $key]),
    ]);
@endphp

<x-admin-layout>
    <div class="rounded-xl bg-white p-5 shadow-sm">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-xl font-bold text-gray-950">Nueva lista de precios</h1>
                <p class="text-sm text-gray-500">Captura los productos y precios de las categorías que formarán la lista.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2 md:justify-end">
                <x-table-action-button type="submit" form="unifiedPriceListForm" variant="green"
                    icon="fa-solid fa-floppy-disk">
                    Guardar lista de precios
                </x-table-action-button>

                <x-table-action-link
                    href="{{ route('admin.catalogo-listas.lists', ['category' => $category]) }}"
                    variant="primary" icon="fa-solid fa-arrow-left">
                    Volver a listas
                </x-table-action-link>
            </div>
        </div>

        <form id="unifiedPriceListForm" action="{{ route('admin.catalogo-listas.lists.store-unified') }}"
            method="POST" enctype="multipart/form-data" class="mt-4">
            @csrf
            <input type="hidden" name="return_category" value="{{ $category }}">
            <input type="hidden" id="activePriceCategory" name="active_category" value="{{ $activeCategory }}">

            <div class="flex max-w-3xl items-center gap-2" aria-label="Categorías de la lista de precios">
                <button type="button" data-category-direction="previous" title="Categoría anterior"
                    aria-label="Mostrar categoría anterior"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">
                    <span class="text-xl leading-none" aria-hidden="true">&lsaquo;</span>
                </button>

                <div class="flex min-w-0 flex-1 gap-2 overflow-x-auto" role="tablist" data-disable-sticky-x>
                    @foreach ($categories as $key => $settings)
                        <button type="button" role="tab" data-category-tab="{{ $key }}"
                            aria-controls="category-panel-{{ $key }}"
                            aria-selected="{{ $key === $activeCategory ? 'true' : 'false' }}"
                            class="flex h-14 min-w-40 flex-1 items-center gap-3 rounded-md border px-3 text-left transition {{ $key === $activeCategory ? 'border-cyan-500 bg-cyan-50 text-cyan-950 shadow-sm' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}">
                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-gray-50 text-xs font-bold" aria-hidden="true">
                                {{ Str::upper(Str::substr($settings['label'], 0, 1)) }}
                            </span>
                            <span class="min-w-0 flex-1 truncate text-sm font-bold">{{ $settings['label'] }}</span>
                            <span data-category-count="{{ $key }}"
                                class="inline-flex min-w-6 items-center justify-center rounded bg-gray-100 px-1.5 py-1 text-[11px] font-bold text-gray-600">0</span>
                        </button>
                    @endforeach
                </div>

                <button type="button" data-category-direction="next" title="Categoría siguiente"
                    aria-label="Mostrar categoría siguiente"
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-50">
                    <span class="text-xl leading-none" aria-hidden="true">&rsaquo;</span>
                </button>
            </div>

            @if ($errors->any())
                <div role="alert" class="mt-4 flex items-start gap-2 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                    <i class="fa-solid fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <section class="mt-4 rounded-lg border border-cyan-200 bg-cyan-50 p-4">
                <h2 class="font-bold text-gray-900">Cargos adicionales por categoría</h2>
                <p class="mt-1 text-xs text-gray-600">Se aplican automáticamente: por solicitud en Nutrición y por mezcla en Oncología/Antibióticos.</p>
                @foreach ($categories as $key => $settings)
                    <div class="mt-3 rounded bg-white p-3" data-additional-charge-category="{{ $key }}">
                        <p class="mb-2 text-sm font-semibold">{{ $settings['label'] }}</p>
                        <div class="grid gap-2 sm:grid-cols-3">
                            <input name="additional_charges[{{ $key }}][0][name]" placeholder="Nombre del cargo" class="rounded border-gray-300 text-sm">
                            <select name="additional_charges[{{ $key }}][0][concept_type]" class="rounded border-gray-300 text-sm"><option>Servicio</option><option>Insumo</option></select>
                            <input name="additional_charges[{{ $key }}][0][amount]" type="number" min="0" step="0.01" placeholder="Precio con IVA" class="rounded border-gray-300 text-sm">
                        </div>
                    </div>
                @endforeach
            </section>

            <div class="mt-5">
                @include('admin.catalogo-listas.partials.warehouse-selectors', [
                    'category' => $activeCategory,
                    'laboratories' => $laboratories,
                    'selectedLaboratoryId' => $selectedLaboratoryId,
                    'selectedWarehouseId' => $selectedWarehouseId,
                    'selectedBackupWarehouseId' => $selectedBackupWarehouseId,
                    'backupEnabled' => $backupEnabled,
                    'isBackupList' => false,
                    'primaryWarehouseId' => 0,
                ])
            </div>

            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                <div class="w-full sm:w-64">
                    <label class="mb-1 block text-xs font-semibold text-gray-700">Nombre de la lista</label>
                    <input type="text" name="name" value="{{ old('name') }}"
                        class="h-8 w-full rounded-md border-gray-300 px-2 py-1 text-sm" required>
                </div>

                <div class="w-full sm:w-80">
                    <label class="mb-1 block text-xs font-semibold text-gray-700">Descripción</label>
                    <input type="text" name="description" value="{{ old('description') }}"
                        class="h-8 w-full rounded-md border-gray-300 px-2 py-1 text-sm">
                </div>

                <label class="flex h-8 cursor-pointer items-center gap-2 whitespace-nowrap rounded-md border border-gray-300 px-3 text-xs font-semibold text-gray-700">
                    <input type="hidden" name="has_subdistributor" value="0">
                    <input type="checkbox" id="hasSubdistributor" name="has_subdistributor" value="1"
                        class="peer sr-only" @checked($hasSubdistributor)>
                    <span class="relative h-5 w-9 rounded-full bg-gray-200 transition-colors after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-cyan-600 peer-checked:after:translate-x-4 peer-focus:ring-2 peer-focus:ring-cyan-200"></span>
                    Agregar subdistribuidor
                </label>

                <label class="flex h-8 cursor-pointer items-center gap-2 whitespace-nowrap rounded-md border border-gray-300 px-3 text-xs font-semibold text-gray-700">
                    <input type="hidden" name="has_contract" value="0">
                    <input type="checkbox" id="hasContract" name="has_contract" value="1"
                        class="peer sr-only" @checked($hasContract)>
                    <span class="relative h-5 w-9 rounded-full bg-gray-200 transition-colors after:absolute after:left-0.5 after:top-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-blue-700 peer-checked:after:translate-x-4 peer-focus:ring-2 peer-focus:ring-blue-200"></span>
                    Contrato
                </label>

                <div class="flex h-8 shrink-0 self-end items-center gap-2 rounded-md border border-gray-200 bg-gray-50 px-3 text-xs font-semibold text-gray-600 sm:ml-auto">
                    <span id="activeCategoryLabel">{{ $categories[$activeCategory]['label'] }}</span>
                    <span class="text-gray-300">|</span>
                    <span><strong id="totalSelectedProducts" class="text-gray-900">0</strong> seleccionados</span>
                </div>
            </div>

            <div id="subdistributorFields" class="mt-3 border-t border-gray-200 pt-3 {{ $hasSubdistributor ? '' : 'hidden' }}">
                <h2 class="mb-2 text-sm font-semibold text-gray-800">Datos del subdistribuidor</h2>
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-700">Razón social</label>
                        <input type="text" name="subdistributor_razon_social" maxlength="255"
                            value="{{ old('subdistributor_razon_social') }}" data-required-when-visible
                            class="h-9 w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-700">RFC</label>
                        <input type="text" name="subdistributor_rfc" maxlength="20"
                            value="{{ old('subdistributor_rfc') }}" data-required-when-visible
                            class="h-9 w-full rounded-md border-gray-300 text-sm uppercase">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-700">Dirección</label>
                        <input type="text" name="subdistributor_direccion" maxlength="500"
                            value="{{ old('subdistributor_direccion') }}" data-required-when-visible
                            class="h-9 w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-700">Contacto</label>
                        <input type="text" name="subdistributor_contacto" maxlength="255"
                            value="{{ old('subdistributor_contacto') }}" data-required-when-visible
                            class="h-9 w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-700">Logotipo</label>
                        <input type="file" name="subdistributor_logo" accept=".jpg,.jpeg,.png,.webp"
                            class="block h-9 w-full rounded-md border border-gray-300 bg-white text-xs file:mr-2 file:h-9 file:border-0 file:bg-gray-100 file:px-3 file:text-xs file:font-semibold">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="mb-1 block text-xs font-semibold text-gray-700">Información adicional</label>
                    <textarea name="subdistributor_additional_information" rows="3" maxlength="10000"
                        class="min-h-20 w-full resize-y rounded-md border-gray-300 text-sm leading-5">{{ old('subdistributor_additional_information') }}</textarea>
                </div>
            </div>

            <div id="contractFields" class="mt-3 border-t border-gray-200 pt-3 {{ $hasContract ? '' : 'hidden' }}">
                <h2 class="mb-2 text-sm font-semibold text-gray-800">Datos del contrato</h2>
                <div class="grid gap-3 md:grid-cols-[minmax(220px,0.45fr)_minmax(0,1.55fr)] md:items-start">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-700">Contrato</label>
                        <input type="text" name="contract_number" maxlength="255"
                            value="{{ old('contract_number') }}" data-required-when-visible
                            class="h-9 w-full rounded-md border-gray-300 text-sm">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-700">Información del contrato</label>
                        <textarea name="contract_information" rows="4" maxlength="10000"
                            class="min-h-24 w-full resize-y rounded-md border-gray-300 text-sm leading-5">{{ old('contract_information') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                @foreach ($categories as $key => $settings)
                    @include('admin.catalogo-listas.partials.unified-price-table', [
                        'categoryKey' => $key,
                        'catalogs' => $catalogsByCategory->get($key, collect()),
                        'categories' => $categories,
                        'activeCategory' => $activeCategory,
                    ])
                @endforeach
            </div>

        </form>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const categories = @json(array_keys($categories));
                const categoryLabels = @json(collect($categories)->mapWithKeys(fn ($settings, $key) => [$key => $settings['label']]));
                const backupListUrls = @json($backupListUrls);
                const form = document.getElementById('unifiedPriceListForm');
                const activeCategoryInput = document.getElementById('activePriceCategory');
                const activeCategoryLabel = document.getElementById('activeCategoryLabel');
                const tabs = Array.from(document.querySelectorAll('[data-category-tab]'));
                const panels = Array.from(document.querySelectorAll('[data-category-panel]'));
                const rows = Array.from(document.querySelectorAll('.price-editor-row'));
                const selectAllControls = Array.from(document.querySelectorAll('[data-select-all]'));
                const chargeBySelectAllControls = Array.from(document.querySelectorAll('[data-charge-by-select-all]'));
                const totalSelected = document.getElementById('totalSelectedProducts');
                const backupButton = document.getElementById('createBackupPriceList');
                const hasSubdistributor = document.getElementById('hasSubdistributor');
                const subdistributorFields = document.getElementById('subdistributorFields');
                const hasContract = document.getElementById('hasContract');
                const contractFields = document.getElementById('contractFields');
                let activeCategory = activeCategoryInput?.value || categories[0];

                const rowsFor = (category) => rows.filter((row) => row.dataset.category === category);
                const visibleRowsFor = (category) => rowsFor(category).filter((row) => !row.classList.contains('hidden'));

                const syncConditionalSection = (toggle, section) => {
                    if (!toggle || !section) {
                        return;
                    }

                    const visible = toggle.checked;
                    section.classList.toggle('hidden', !visible);
                    section.querySelectorAll('input, textarea, select').forEach((input) => {
                        input.disabled = !visible;
                        input.required = visible && input.hasAttribute('data-required-when-visible');
                    });
                };

                const calculateRow = (row) => {
                    const bottle = Number(row.querySelector('.price-bottle-input')?.value || 0);
                    const unitAmount = Number(row.dataset.unitAmount || 0);
                    const result = row.querySelector('.price-derived-input');

                    if (result) {
                        result.value = unitAmount > 0 ? (bottle / unitAmount).toFixed(4) : '0.0000';
                    }
                };

                const syncRowSelection = (row) => {
                    const selection = row.querySelector('.price-editor-selection');
                    row.classList.toggle('bg-blue-50', Boolean(selection?.checked));
                };

                const syncCounts = () => {
                    let overall = 0;

                    categories.forEach((category) => {
                        const count = rowsFor(category).filter((row) => row.querySelector('.price-editor-selection')?.checked).length;
                        const badge = document.querySelector(`[data-category-count="${category}"]`);

                        if (badge) {
                            badge.textContent = String(count);
                        }

                        overall += count;
                    });

                    if (totalSelected) {
                        totalSelected.textContent = String(overall);
                    }
                };

                const syncSelectAll = (category) => {
                    const control = selectAllControls.find((item) => item.dataset.selectAll === category);

                    if (!control) {
                        return;
                    }

                    const selections = visibleRowsFor(category)
                        .map((row) => row.querySelector('.price-editor-selection'))
                        .filter(Boolean);
                    const selectedCount = selections.filter((selection) => selection.checked).length;

                    control.checked = selections.length > 0 && selectedCount === selections.length;
                    control.indeterminate = selectedCount > 0 && selectedCount < selections.length;
                    control.disabled = selections.length === 0;
                };

                const syncChargeBySelectAll = (category) => {
                    const categoryRows = visibleRowsFor(category)
                        .filter((row) => row.querySelector('.charge-method-input'));

                    chargeBySelectAllControls
                        .filter((control) => control.dataset.chargeBySelectAll === category)
                        .forEach((control) => {
                            const option = control.dataset.chargeByOption;
                            const selectedCount = categoryRows.filter((row) =>
                                row.querySelector(`.charge-method-input[data-charge-by-option="${option}"]`)?.checked
                            ).length;

                            control.checked = categoryRows.length > 0 && selectedCount === categoryRows.length;
                            control.indeterminate = selectedCount > 0 && selectedCount < categoryRows.length;
                            control.disabled = categoryRows.length === 0;
                        });
                };

                const activateCategory = (category) => {
                    if (!categories.includes(category)) {
                        return;
                    }

                    activeCategory = category;

                    if (activeCategoryInput) {
                        activeCategoryInput.value = category;
                        activeCategoryInput.setAttribute('value', category);
                    }

                    if (activeCategoryLabel) {
                        activeCategoryLabel.textContent = categoryLabels[category] || category;
                    }

                    tabs.forEach((tab) => {
                        const selected = tab.dataset.categoryTab === category;
                        tab.setAttribute('aria-selected', selected ? 'true' : 'false');
                        tab.classList.toggle('border-cyan-500', selected);
                        tab.classList.toggle('bg-cyan-50', selected);
                        tab.classList.toggle('text-cyan-950', selected);
                        tab.classList.toggle('shadow-sm', selected);
                        tab.classList.toggle('border-gray-200', !selected);
                        tab.classList.toggle('bg-white', !selected);
                        tab.classList.toggle('text-gray-700', !selected);
                    });

                    panels.forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.categoryPanel !== category);
                    });

                    tabs.find((tab) => tab.dataset.categoryTab === category)?.scrollIntoView({
                        behavior: 'smooth',
                        block: 'nearest',
                        inline: 'nearest',
                    });

                    if (backupButton && backupListUrls[category]) {
                        backupButton.dataset.url = backupListUrls[category];
                    }

                    syncSelectAll(category);
                    syncChargeBySelectAll(category);
                };

                tabs.forEach((tab) => {
                    tab.addEventListener('click', () => activateCategory(tab.dataset.categoryTab));
                });

                document.querySelectorAll('[data-category-direction]').forEach((button) => {
                    button.addEventListener('click', () => {
                        const currentIndex = categories.indexOf(activeCategory);
                        const offset = button.dataset.categoryDirection === 'next' ? 1 : -1;
                        const nextIndex = (currentIndex + offset + categories.length) % categories.length;
                        activateCategory(categories[nextIndex]);
                    });
                });

                rows.forEach((row) => {
                    const bottle = row.querySelector('.price-bottle-input');
                    const selection = row.querySelector('.price-editor-selection');

                    bottle?.addEventListener('input', () => {
                        calculateRow(row);

                        if (Number(bottle.value || 0) > 0 && selection && !selection.checked) {
                            selection.checked = true;
                            syncRowSelection(row);
                            syncCounts();
                            syncSelectAll(row.dataset.category);
                        }
                    });
                    selection?.addEventListener('change', () => {
                        syncRowSelection(row);
                        syncCounts();
                        syncSelectAll(row.dataset.category);
                    });
                    row.querySelectorAll('.charge-method-input').forEach((input) => {
                        input.addEventListener('change', () => syncChargeBySelectAll(row.dataset.category));
                    });
                    calculateRow(row);
                    syncRowSelection(row);
                });

                selectAllControls.forEach((control) => {
                    control.addEventListener('change', () => {
                        visibleRowsFor(control.dataset.selectAll).forEach((row) => {
                            const selection = row.querySelector('.price-editor-selection');

                            if (selection) {
                                selection.checked = control.checked;
                                syncRowSelection(row);
                            }
                        });

                        syncCounts();
                        syncSelectAll(control.dataset.selectAll);
                    });
                });

                chargeBySelectAllControls.forEach((control) => {
                    control.addEventListener('change', () => {
                        const category = control.dataset.chargeBySelectAll;
                        const option = control.dataset.chargeByOption;

                        visibleRowsFor(category).forEach((row) => {
                            const input = row.querySelector(
                                `.charge-method-input[data-charge-by-option="${option}"]`
                            );

                            if (input) {
                                input.checked = control.checked;
                            }
                        });

                        syncChargeBySelectAll(category);
                    });
                });

                hasSubdistributor?.addEventListener('change', () => syncConditionalSection(hasSubdistributor, subdistributorFields));
                hasContract?.addEventListener('change', () => syncConditionalSection(hasContract, contractFields));

                form?.addEventListener('submit', function(event) {
                    const selectedRows = rows.filter((row) => row.querySelector('.price-editor-selection')?.checked);

                    if (selectedRows.length === 0) {
                        event.preventDefault();
                        window.Swal?.fire({
                            icon: 'warning',
                            title: 'Selecciona productos',
                            text: 'Selecciona al menos un producto en alguna categoría.',
                            confirmButtonText: 'Entendido',
                        }) || window.alert('Selecciona al menos un producto en alguna categoría.');
                        return;
                    }

                    rows.forEach((row) => {
                        if (!row.querySelector('.price-editor-selection')?.checked) {
                            row.querySelectorAll('input[name], textarea[name], select[name]').forEach((input) => {
                                input.disabled = true;
                            });
                        }
                    });
                });

                syncConditionalSection(hasSubdistributor, subdistributorFields);
                syncConditionalSection(hasContract, contractFields);
                syncCounts();
                categories.forEach(syncChargeBySelectAll);
                activateCategory(activeCategory);
            });
        </script>
    @endpush
</x-admin-layout>
