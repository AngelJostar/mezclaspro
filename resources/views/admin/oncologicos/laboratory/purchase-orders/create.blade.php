<x-admin-layout>
    @php
        $isPurchasePopup = request()->boolean('purchase_popup');
        $defaultNotes = "CADUCIDAD 12 MESES (EN CASO DE NO CONTAR CON ESTA CADUCIDAD, FAVOR DE ENTREGAR CARTA COMPROMISO CANJE)\nENTREGA DE CERTIFICADO ANALITICO\nCOPIA DEL REGISTRO SANITARIO VIGENTE Y/O EN SU CASO SI CUENTA CON PRORROGA\nPRESENTACION COMERCIAL\nFECHA DE ENTREGA: INMEDIATA\nPLAZO DE PAGO: CREDITO 30 DIAS A PARTIR DE LA FECHA DE SOLICITUD";
        $initialItems = array_values(old('items', [
            ['description' => '', 'quantity' => 1, 'unit_price' => 0],
        ]));
        $selectedDeliveryLaboratoryId = (int) old('delivery_laboratory_id', 0);
        $selectedDeliveryLaboratory = $deliveryLaboratories->firstWhere('id', $selectedDeliveryLaboratoryId);
        $selectedWarehouseId = (int) old('warehouse_id', 0);
        $selectedWarehouse = $selectedDeliveryLaboratory?->warehouses->firstWhere('id', $selectedWarehouseId);
        $selectedWarehouseId = (int) ($selectedWarehouse?->id ?? 0);
        $selectedInventoryDestination = old('inventory_destination', '');
        if (! array_key_exists($selectedInventoryDestination, $inventoryDestinations)) {
            $selectedInventoryDestination = '';
        }
        $initialDeliveryAttention = old(
            'delivery_attention',
            $selectedWarehouse
                ? $selectedWarehouse->name . ' - ' . $selectedDeliveryLaboratory->nombre
                : $selectedDeliveryLaboratory?->nombre,
        );
        $initialDeliveryAddress = old(
            'delivery_address',
            $selectedWarehouse?->address ?: ($selectedDeliveryLaboratory?->direccion ?: $laboratory->direccion),
        );
        $deliveryDestinationOptions = $deliveryLaboratories->map(fn ($destinationLaboratory) => [
            'id' => (string) $destinationLaboratory->id,
            'name' => $destinationLaboratory->nombre,
            'address' => $destinationLaboratory->direccion,
            'warehouses' => $destinationLaboratory->warehouses->map(fn ($warehouse) => [
                'id' => (string) $warehouse->id,
                'name' => $warehouse->name,
                'address' => $warehouse->address,
            ])->values(),
        ])->values();
    @endphp

    @include('admin.oncologicos.laboratory.purchase-orders.document-styles')

    <div class="po-editor bg-white p-4 shadow-sm md:p-6 {{ $isPurchasePopup ? 'po-popup-editor' : '' }}">
        @unless ($isPurchasePopup)
        <h1 class="mb-5 text-2xl font-medium text-gray-900">Compras</h1>
        @include('admin.warehouses.partials.purchase-navigation', ['selectedLaboratory' => $laboratory, 'section' => 'new'])
        @endunless
        <div class="po-editor-heading flex flex-col gap-3 border-b border-gray-200 pb-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase text-blue-700">{{ $laboratory->nombre }}</p>
                <h2 data-workflow-heading class="mt-1 text-xl font-medium text-gray-900">Nueva orden de compra</h2>
                @unless ($isPurchasePopup)
                <p class="mt-1 text-sm text-gray-500">Completa los datos de la orden y selecciona los productos del catálogo.</p>
                @endunless
            </div>

            @unless ($isPurchasePopup)
            <div class="flex flex-wrap items-center gap-2">
                @if (\App\Support\AdminMenuAccess::allows(auth()->user(), 'menu.compras.mine'))
                <a href="{{ route('admin.warehouses.purchase-orders.index', ['section' => 'mine', 'laboratory_id' => $laboratory->id]) }}"
                    class="inline-flex items-center gap-2 border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    Volver a &Oacute;rdenes de compra
                </a>
                @endif
                <button form="purchase-order-form" type="submit"
                    class="inline-flex items-center gap-2 bg-green-600 px-5 py-2 text-sm font-semibold text-white hover:bg-green-700">
                    <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                    Generar orden
                </button>
            </div>
            @endunless
        </div>

        @if ($errors->any())
            <div class="mt-4 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($isPurchasePopup)
            <div class="po-popup-errors" data-po-errors role="alert" tabindex="-1" hidden></div>
            <section class="po-popup-result" data-po-success role="status" tabindex="-1" hidden>
                <h3 data-po-success-title></h3>
                <div class="po-popup-actions">
                    <a data-po-download class="po-popup-generate"><i data-po-icon="download" aria-hidden="true"></i>Descargar orden</a>
                    <a href="{{ route('admin.warehouses.purchase-orders.index', ['section' => 'mine', 'laboratory_id' => $laboratory->id]) }}" data-purchase-popup-close class="po-popup-close"><i data-po-icon="x" aria-hidden="true"></i>Cerrar</a>
                </div>
            </section>
        @endif
        <form id="purchase-order-form" method="POST"
            action="{{ route('admin.oncologicos.laboratory.purchase-orders.store', ['laboratory' => $laboratory] + ($isPurchasePopup ? ['purchase_popup' => 1] : [])) }}" class="mt-4 {{ $isPurchasePopup ? 'po-popup-form' : '' }}">
            @csrf

            <div class="po-form-body">
            <div id="po-catalog-status" class="po-catalog-status" role="status" tabindex="-1">
                <span id="po-catalog-message">Catálogo pendiente: central, almacén y subalmacén.</span>
                <button id="po-catalog-retry" type="button" hidden>Reintentar</button>
            </div>

            <div class="po-sheet-scroll">
                @include('admin.oncologicos.laboratory.purchase-orders.form-document')
            </div>

            <div id="item-hidden-inputs"></div>
            </div>

            @if ($isPurchasePopup)
            <footer class="po-popup-actions">
                <a href="{{ route('admin.warehouses.purchase-orders.index', ['section' => 'mine', 'laboratory_id' => $laboratory->id]) }}" data-purchase-popup-close class="po-popup-close"><i data-po-icon="x" aria-hidden="true"></i>Cerrar</a>
                <button type="submit" class="po-popup-generate"><i data-po-icon="file-plus-2" aria-hidden="true"></i><span data-po-submit-label>Generar orden</span></button>
            </footer>
            @else
            <div class="flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-end">
                <div class="flex justify-end gap-2">
                    <a href="{{ route('admin.warehouses.index', ['laboratory_id' => $laboratory->id]) }}"
                        class="border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
                    <button type="submit"
                        class="inline-flex items-center gap-2 bg-green-600 px-5 py-2 text-sm font-semibold text-white hover:bg-green-700">
                        <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                        Generar orden de compra
                    </button>
                </div>
            </div>
            @endif
        </form>
    </div>

    @php
        $purchaseOrderConfig = [
            'popup' => $isPurchasePopup,
            'destinations' => $deliveryDestinationOptions,
            'suppliers' => $supplierOptions,
            'items' => $initialItems,
            'productsUrl' => route('admin.oncologicos.laboratory.purchase-orders.products', $laboratory),
        ];
    @endphp
    <script id="purchase-order-config" type="application/json">@json($purchaseOrderConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)</script>
</x-admin-layout>
