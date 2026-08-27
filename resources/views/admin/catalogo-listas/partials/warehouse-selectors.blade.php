@php
    $isBackupList = (bool) ($isBackupList ?? false);
    $initialLaboratoryId = (int) old('laboratory_id', $selectedLaboratoryId ?? 0);
    $initialWarehouseId = (int) old('warehouse_id', $selectedWarehouseId ?? 0);
    $initialBackupWarehouseId = (int) old(
        'backup_warehouse_id',
        $selectedBackupWarehouseId ?? 0
    );
    $initialPrimaryWarehouseId = (int) old('primary_warehouse_id', $primaryWarehouseId ?? 0);
    $initialBackupEnabled = ! $isBackupList && filter_var(
        old('backup_enabled', $backupEnabled ?? false),
        FILTER_VALIDATE_BOOLEAN
    );
    $selectedLaboratory = $laboratories->firstWhere('id', $initialLaboratoryId)
        ?? $laboratories->first();
    $selectedLaboratoryWarehouses = $selectedLaboratory?->warehouses ?? collect();
    $locationOptions = $laboratories->map(fn ($laboratory) => [
        'id' => (int) $laboratory->id,
        'name' => $laboratory->nombre,
        'state' => $laboratory->estado,
        'address' => $laboratory->direccion,
        'warehouses' => $laboratory->warehouses->map(fn ($warehouse) => [
            'id' => (int) $warehouse->id,
            'name' => $warehouse->name,
            'state' => $warehouse->state,
            'address' => $warehouse->address,
        ])->values(),
    ])->values();
@endphp

<input type="hidden" name="is_backup_list" value="{{ $isBackupList ? 1 : 0 }}">

@if ($isBackupList)
    <input type="hidden" name="laboratory_id" value="{{ $initialLaboratoryId }}">
    <input type="hidden" name="warehouse_id" value="{{ $initialWarehouseId }}">
    <input type="hidden" name="primary_warehouse_id" value="{{ $initialPrimaryWarehouseId }}">
    <input type="hidden" name="backup_enabled" value="0">
    <input type="hidden" name="backup_warehouse_id" value="">
@endif

<section class="mb-5 grid gap-3 lg:grid-cols-3" aria-label="Configuración de surtido de la lista">
    <div class="min-h-36 rounded-md border border-cyan-200 bg-cyan-50/40 p-4">
        <div class="mb-3 flex items-center gap-2 text-cyan-900">
            <i class="fa-solid fa-building" aria-hidden="true"></i>
            <h3 class="text-sm font-bold">Central de mezclas</h3>
        </div>

        <label for="priceListLaboratory" class="mb-1 block text-xs font-semibold text-gray-700">
            Central seleccionada
        </label>
        <select id="priceListLaboratory" @disabled($isBackupList)
            @unless ($isBackupList) name="laboratory_id" required @endunless
            class="h-10 w-full rounded-md border-cyan-200 bg-white text-sm focus:border-cyan-500 focus:ring-cyan-500">
            @foreach ($laboratories as $laboratory)
                <option value="{{ $laboratory->id }}" @selected((int) $laboratory->id === $initialLaboratoryId)>
                    {{ $laboratory->nombre }}{{ $laboratory->estado ? ' - ' . $laboratory->estado : '' }}
                </option>
            @endforeach
        </select>
        <p id="priceListLaboratoryAddress" class="mt-2 line-clamp-2 text-xs leading-5 text-gray-500"></p>
        @error('laboratory_id')
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @if ($isBackupList)
        <div class="min-h-36 rounded-md border border-blue-200 bg-blue-50/40 p-4">
            <div class="mb-3 flex items-center gap-2 text-blue-900">
                <span class="flex items-center gap-2">
                    <i class="fa-solid fa-link" aria-hidden="true"></i>
                    <h3 class="text-sm font-bold">Almacén principal</h3>
                </span>
            </div>

            <label for="priceListPrimaryWarehouse" class="mb-1 block text-xs font-semibold text-gray-700">
                Almacén relacionado
            </label>
            <select id="priceListPrimaryWarehouse" disabled
                class="h-10 w-full rounded-md border-blue-200 bg-white text-sm">
                @foreach ($selectedLaboratoryWarehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected((int) $warehouse->id === $initialPrimaryWarehouseId)>
                        {{ $warehouse->name }}
                    </option>
                @endforeach
            </select>
            <p class="mt-2 text-xs leading-5 text-gray-500">Almacén principal desde el que se habilitó este respaldo.</p>
            @error('primary_warehouse_id')
                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <div class="min-h-36 rounded-md border p-4 {{ $isBackupList ? 'border-emerald-200 bg-emerald-50/40' : 'border-blue-200 bg-blue-50/40' }}">
        <div class="mb-3 flex items-center justify-between gap-2">
            <span class="flex items-center gap-2 {{ $isBackupList ? 'text-emerald-900' : 'text-blue-900' }}">
                <i class="fa-solid fa-warehouse" aria-hidden="true"></i>
                <h3 class="text-sm font-bold">{{ $isBackupList ? 'Almacén de respaldo' : 'Almacén surtidor' }}</h3>
            </span>
            @if ($isBackupList)
                <span class="rounded bg-emerald-100 px-2 py-1 text-[11px] font-bold text-emerald-700">RESPALDO</span>
            @endif
        </div>

        <label for="priceListWarehouse" class="mb-1 block text-xs font-semibold text-gray-700">
            {{ $isBackupList ? 'Almacén seleccionado' : 'Surtir solicitudes desde' }}
        </label>
        <select id="priceListWarehouse" @disabled($isBackupList)
            @unless ($isBackupList) name="warehouse_id" required @endunless
            class="h-10 w-full rounded-md bg-white text-sm {{ $isBackupList ? 'border-emerald-200 focus:border-emerald-500 focus:ring-emerald-500' : 'border-blue-200 focus:border-blue-500 focus:ring-blue-500' }}">
            @foreach ($selectedLaboratoryWarehouses as $warehouse)
                <option value="{{ $warehouse->id }}" @selected((int) $warehouse->id === $initialWarehouseId)>
                    {{ $warehouse->name }}
                </option>
            @endforeach
        </select>
        <p id="priceListWarehouseAddress" class="mt-2 line-clamp-2 text-xs leading-5 text-gray-500"></p>
        @error('warehouse_id')
            <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
        @enderror
    </div>

    @unless ($isBackupList)
        <div class="min-h-36 rounded-md border border-emerald-200 bg-emerald-50/40 p-4">
            <div class="mb-3 flex items-center justify-between gap-3">
                <span class="flex items-center gap-2 text-emerald-900">
                    <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                    <h3 class="text-sm font-bold">Almacén de respaldo</h3>
                </span>

                <label class="inline-flex cursor-pointer items-center gap-2">
                    <input type="hidden" name="backup_enabled" value="0">
                    <input type="checkbox" id="priceListBackupEnabled" name="backup_enabled" value="1"
                        class="peer sr-only" @checked($initialBackupEnabled)>
                    <span class="relative h-6 w-11 rounded-full bg-gray-300 transition-colors after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:bg-emerald-600 peer-checked:after:translate-x-5 peer-focus:ring-2 peer-focus:ring-emerald-200"></span>
                    <span id="priceListBackupState" class="w-7 text-xs font-bold text-gray-600">
                        {{ $initialBackupEnabled ? 'ON' : 'OFF' }}
                    </span>
                </label>
            </div>

            <label for="priceListBackupWarehouse" class="mb-1 block text-xs font-semibold text-gray-700">
                Almacén alterno
            </label>
            <select id="priceListBackupWarehouse" name="backup_warehouse_id"
                @disabled(!$initialBackupEnabled)
                class="h-10 w-full rounded-md border-emerald-200 bg-white text-sm focus:border-emerald-500 focus:ring-emerald-500 disabled:cursor-not-allowed disabled:bg-gray-100">
                @foreach ($selectedLaboratoryWarehouses->where('id', '!=', $initialWarehouseId) as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected((int) $warehouse->id === $initialBackupWarehouseId)>
                        {{ $warehouse->name }}
                    </option>
                @endforeach
            </select>

            <button type="button" id="createBackupPriceList"
                data-url="{{ route('admin.catalogo-listas.backup-lists.create', ['category' => $category]) }}"
                class="mt-3 inline-flex h-9 w-full items-center justify-center gap-2 rounded-md bg-emerald-600 px-3 text-xs font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-500"
                @disabled(!$initialBackupEnabled || $selectedLaboratoryWarehouses->count() < 2)>
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Crear lista de respaldo
            </button>
            @error('backup_warehouse_id')
                <p class="mt-1 text-xs font-semibold text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @endunless
</section>

@unless ($isBackupList)
    @push('modals')
        <dialog id="backupPriceListModal"
            aria-labelledby="backupPriceListModalTitle"
            aria-describedby="backupPriceListModalDescription"
            class="fixed inset-0 m-auto h-[96vh] w-[98vw] max-w-none overflow-hidden rounded-md border-0 bg-white p-0 shadow-2xl backdrop:bg-slate-950/70 sm:h-[94vh] sm:w-[96vw]">
            <div class="flex h-full min-h-0 flex-col bg-white">
                <header class="shrink-0 border-b border-emerald-200 bg-white px-4 py-3 sm:px-6">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 hidden h-10 w-10 shrink-0 items-center justify-center rounded-md bg-emerald-100 text-emerald-800 sm:flex">
                            <span class="text-sm font-bold" aria-hidden="true">R</span>
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-[11px] font-bold uppercase text-emerald-700">Captura independiente</p>
                                <span class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">RESPALDO</span>
                            </div>
                            <h2 id="backupPriceListModalTitle" class="mt-0.5 text-lg font-bold text-gray-950 sm:text-xl">
                                Lista de precios del almacén de respaldo
                            </h2>
                            <p id="backupPriceListModalDescription" class="mt-1 max-w-5xl text-xs leading-5 text-gray-600 sm:text-sm">
                                Los precios capturados aquí se guardan únicamente para
                                <strong data-backup-warehouse-name class="text-gray-900">el almacén de respaldo</strong>
                                y no modifican la lista del almacén principal
                                <strong data-primary-warehouse-name class="text-gray-900">principal</strong>.
                            </p>
                        </div>

                        <button type="button" data-close-backup-modal title="Cerrar"
                            aria-label="Cerrar captura de lista de respaldo"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-red-700 bg-red-600 text-white shadow-sm transition hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300 focus:ring-offset-2">
                            <span class="text-lg font-bold leading-none" aria-hidden="true">X</span>
                        </button>
                    </div>
                </header>

                <div class="grid shrink-0 border-b border-gray-200 bg-gray-50 px-4 py-2 text-xs sm:grid-cols-3 sm:px-6">
                    <p class="truncate py-1 text-gray-600">
                        <span class="font-semibold text-gray-900">Central:</span>
                        <span data-backup-laboratory-name>Seleccionada</span>
                    </p>
                    <p class="truncate py-1 text-gray-600">
                        <span class="font-semibold text-gray-900">Almacén principal:</span>
                        <span data-primary-warehouse-name>Principal</span>
                    </p>
                    <p class="truncate py-1 text-gray-600">
                        <span class="font-semibold text-gray-900">Almacén de respaldo:</span>
                        <span data-backup-warehouse-name>Seleccionado</span>
                    </p>
                </div>

                <div class="relative min-h-0 flex-1 bg-white">
                    <div id="backupPriceListLoading"
                        class="absolute inset-0 z-10 flex items-center justify-center gap-3 bg-white text-sm font-semibold text-gray-600">
                        <i class="fa-solid fa-spinner fa-spin text-emerald-700" aria-hidden="true"></i>
                        Preparando la captura de respaldo...
                    </div>
                    <iframe id="backupPriceListFrame" title="Editor de lista de precios del almacén de respaldo"
                        class="h-full w-full border-0 bg-white"></iframe>
                </div>
            </div>
        </dialog>
    @endpush
@endunless

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const locations = @json($locationOptions);
            const isBackupList = @json($isBackupList);
            const initialWarehouseId = @json($initialWarehouseId);
            const initialBackupWarehouseId = @json($initialBackupWarehouseId);
            const laboratorySelect = document.getElementById('priceListLaboratory');
            const warehouseSelect = document.getElementById('priceListWarehouse');
            const primaryWarehouseSelect = document.getElementById('priceListPrimaryWarehouse');
            const backupToggle = document.getElementById('priceListBackupEnabled');
            const backupSelect = document.getElementById('priceListBackupWarehouse');
            const backupState = document.getElementById('priceListBackupState');
            const backupButton = document.getElementById('createBackupPriceList');
            const laboratoryAddress = document.getElementById('priceListLaboratoryAddress');
            const warehouseAddress = document.getElementById('priceListWarehouseAddress');
            const backupModal = document.getElementById('backupPriceListModal');
            const backupFrame = document.getElementById('backupPriceListFrame');
            const backupLoading = document.getElementById('backupPriceListLoading');
            let backupModalTrigger = null;

            const normalize = (value) => String(value || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .trim();

            const selectedLaboratory = () => locations.find(
                (laboratory) => Number(laboratory.id) === Number(laboratorySelect?.value)
            );

            const selectedOptionText = (select, fallback) =>
                select?.selectedOptions?.[0]?.textContent?.trim() || fallback;

            const setModalText = (selector, value) => {
                backupModal?.querySelectorAll(selector).forEach((element) => {
                    element.textContent = value;
                });
            };

            const syncBackupModalContext = () => {
                setModalText(
                    '[data-backup-laboratory-name]',
                    selectedOptionText(laboratorySelect, 'Central seleccionada')
                );
                setModalText(
                    '[data-backup-warehouse-name]',
                    selectedOptionText(backupSelect, 'Almacén de respaldo')
                );
                setModalText(
                    '[data-primary-warehouse-name]',
                    selectedOptionText(warehouseSelect, 'Almacén surtidor')
                );
            };

            const closeBackupModal = () => {
                if (!backupModal) return;

                if (typeof backupModal.close === 'function' && backupModal.open) {
                    backupModal.close();
                } else {
                    backupModal.removeAttribute('open');
                    document.body.classList.remove('overflow-hidden');
                    backupModalTrigger?.focus?.();
                    backupModalTrigger = null;
                }
            };

            const openBackupModal = (url) => {
                if (!backupModal || !backupFrame) {
                    window.location.assign(url.toString());
                    return;
                }

                syncBackupModalContext();
                backupModalTrigger = document.activeElement;
                url.searchParams.set('embedded', '1');

                const editorUrl = url.toString();
                if (backupFrame.dataset.editorUrl !== editorUrl) {
                    backupLoading?.classList.remove('hidden');
                    backupFrame.dataset.editorUrl = editorUrl;
                    backupFrame.src = editorUrl;
                }

                if (typeof backupModal.showModal === 'function') {
                    if (!backupModal.open) backupModal.showModal();
                } else {
                    backupModal.setAttribute('open', '');
                }

                document.body.classList.add('overflow-hidden');
                backupModal.querySelector('[data-close-backup-modal]')?.focus();
            };

            const preferredWarehouse = (warehouses) => warehouses.find((warehouse) => {
                const name = normalize(warehouse.name);
                return name.includes('central') || name.includes('principal') || name.includes('prodifem');
            }) || warehouses[0];

            const setOptions = (select, warehouses, selectedId, emptyLabel) => {
                if (!select) return;

                select.innerHTML = '';

                if (warehouses.length === 0) {
                    const option = new Option(emptyLabel, '');
                    select.add(option);
                    return;
                }

                warehouses.forEach((warehouse) => {
                    select.add(new Option(warehouse.name, warehouse.id));
                });

                if (warehouses.some((warehouse) => Number(warehouse.id) === Number(selectedId))) {
                    select.value = String(selectedId);
                }
            };

            const syncAddresses = () => {
                const laboratory = selectedLaboratory();
                const warehouse = laboratory?.warehouses.find(
                    (item) => Number(item.id) === Number(warehouseSelect?.value)
                );

                if (laboratoryAddress) {
                    laboratoryAddress.textContent = laboratory?.address || laboratory?.state || '';
                }

                if (warehouseAddress) {
                    warehouseAddress.textContent = warehouse?.address || warehouse?.state || '';
                }
            };

            const syncBackupControls = (preferredBackupId = null) => {
                if (isBackupList || !backupSelect || !backupToggle || !backupButton) return;

                const laboratory = selectedLaboratory();
                const available = (laboratory?.warehouses || []).filter(
                    (warehouse) => Number(warehouse.id) !== Number(warehouseSelect?.value)
                );
                const selectedId = preferredBackupId ?? backupSelect.value;

                setOptions(backupSelect, available, selectedId, 'Sin almacenes disponibles');

                const canEnable = available.length > 0;
                backupToggle.disabled = !canEnable;

                if (!canEnable) {
                    backupToggle.checked = false;
                }

                const enabled = canEnable && backupToggle.checked;
                backupSelect.disabled = !enabled;
                backupButton.disabled = !enabled || !backupSelect.value;
                backupState.textContent = enabled ? 'ON' : 'OFF';
                backupState.classList.toggle('text-emerald-700', enabled);
                backupState.classList.toggle('text-gray-600', !enabled);
            };

            const syncWarehouseOptions = (preferredId = null) => {
                if (isBackupList) {
                    syncAddresses();
                    return;
                }

                const laboratory = selectedLaboratory();
                const warehouses = laboratory?.warehouses || [];
                const fallback = preferredWarehouse(warehouses);
                const selectedId = warehouses.some(
                    (warehouse) => Number(warehouse.id) === Number(preferredId)
                ) ? preferredId : fallback?.id;

                setOptions(warehouseSelect, warehouses, selectedId, 'Sin almacenes disponibles');
                warehouseSelect.disabled = warehouses.length === 0;
                syncBackupControls(initialBackupWarehouseId);
                syncAddresses();
            };

            laboratorySelect?.addEventListener('change', () => syncWarehouseOptions());
            warehouseSelect?.addEventListener('change', () => {
                syncBackupControls();
                syncAddresses();
            });
            backupToggle?.addEventListener('change', () => syncBackupControls());
            backupSelect?.addEventListener('change', () => syncBackupControls());
            backupButton?.addEventListener('click', () => {
                if (backupButton.disabled) return;

                const url = new URL(backupButton.dataset.url, window.location.origin);
                url.searchParams.set('laboratory_id', laboratorySelect.value);
                url.searchParams.set('warehouse_id', backupSelect.value);
                url.searchParams.set('primary_warehouse_id', warehouseSelect.value);
                openBackupModal(url);
            });

            backupModal?.querySelectorAll('[data-close-backup-modal]').forEach((button) => {
                button.addEventListener('click', closeBackupModal);
            });

            backupModal?.addEventListener('click', (event) => {
                if (event.target === backupModal) closeBackupModal();
            });

            backupModal?.addEventListener('close', () => {
                document.body.classList.remove('overflow-hidden');
                backupModalTrigger?.focus?.();
                backupModalTrigger = null;
            });

            backupFrame?.addEventListener('load', () => {
                backupLoading?.classList.add('hidden');

                try {
                    const loadedUrl = new URL(backupFrame.contentWindow.location.href);
                    const completedListPath = /^\/admin\/catalogo-listas\/[^/]+\/listas\/?$/;

                    if (loadedUrl.origin === window.location.origin && completedListPath.test(loadedUrl.pathname)) {
                        closeBackupModal();
                        backupFrame.src = 'about:blank';
                        delete backupFrame.dataset.editorUrl;

                        if (window.Swal) {
                            window.Swal.fire({
                                icon: 'success',
                                title: 'Lista de respaldo guardada',
                                text: 'La lista se guardó para el almacén de respaldo sin modificar la lista del almacén surtidor.',
                                confirmButtonText: 'Aceptar',
                            });
                        }
                    }
                } catch (error) {
                    // La navegación se mantiene dentro del editor cuando el contenido no es accesible.
                }
            });

            if (isBackupList) {
                setOptions(
                    primaryWarehouseSelect,
                    selectedLaboratory()?.warehouses || [],
                    @json($initialPrimaryWarehouseId),
                    'Sin almacén principal'
                );
                syncAddresses();
            } else {
                syncWarehouseOptions(initialWarehouseId);
                syncBackupControls(initialBackupWarehouseId);
            }
        });
    </script>
@endpush
