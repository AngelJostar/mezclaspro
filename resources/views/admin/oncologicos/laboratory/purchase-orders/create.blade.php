<x-admin-layout>
    @php
        $defaultNotes = "CADUCIDAD 12 MESES (EN CASO DE NO CONTAR CON ESTA CADUCIDAD, FAVOR DE ENTREGAR CARTA COMPROMISO CANJE)\nENTREGA DE CERTIFICADO ANALITICO\nCOPIA DEL REGISTRO SANITARIO VIGENTE Y/O EN SU CASO SI CUENTA CON PRORROGA\nPRESENTACION COMERCIAL\nFECHA DE ENTREGA: INMEDIATA\nPLAZO DE PAGO: CREDITO 30 DIAS A PARTIR DE LA FECHA DE SOLICITUD";
        $initialItems = array_values(old('items', [
            ['description' => '', 'quantity' => 1, 'unit_price' => 0],
        ]));
        $selectedDeliveryLaboratoryId = (int) old('delivery_laboratory_id', $laboratory->id);
        $selectedDeliveryLaboratory = $deliveryLaboratories->firstWhere('id', $selectedDeliveryLaboratoryId)
            ?? $deliveryLaboratories->firstWhere('id', $laboratory->id)
            ?? $deliveryLaboratories->first();
        if ($selectedDeliveryLaboratory?->warehouses->isEmpty()) {
            $selectedDeliveryLaboratory = $deliveryLaboratories->first(
                fn ($destinationLaboratory) => $destinationLaboratory->warehouses->isNotEmpty(),
            ) ?? $selectedDeliveryLaboratory;
        }
        $selectedDeliveryLaboratoryId = (int) ($selectedDeliveryLaboratory?->id ?? $laboratory->id);
        $selectedWarehouseId = (int) old(
            'warehouse_id',
            $selectedDeliveryLaboratory?->warehouses->first()?->id,
        );
        $selectedWarehouse = $selectedDeliveryLaboratory?->warehouses->firstWhere('id', $selectedWarehouseId)
            ?? $selectedDeliveryLaboratory?->warehouses->first();
        $selectedWarehouseId = (int) ($selectedWarehouse?->id ?? 0);
        $selectedInventoryDestination = old('inventory_destination', 'oncologicos');
        if (! array_key_exists($selectedInventoryDestination, $inventoryDestinations)) {
            $selectedInventoryDestination = array_key_first($inventoryDestinations);
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
        $position = static fn (float $left, float $top, float $width, float $height): string => sprintf(
            'left: %.5f%%; top: %.5f%%; width: %.5f%%; height: %.5f%%;',
            ($left / 612) * 100,
            ($top / 792) * 100,
            ($width / 612) * 100,
            ($height / 792) * 100,
        );
    @endphp

    <style>
        .po-editor {
            --po-blue: #062d70;
            font-family: Arial, Helvetica, sans-serif;
        }

        .po-sheet-scroll {
            overflow-x: auto;
            padding: 10px 2px 18px;
        }

        .po-sheet {
            container-type: inline-size;
            position: relative;
            width: 100%;
            min-width: 820px;
            max-width: 1020px;
            aspect-ratio: 612 / 792;
            margin: 0 auto;
            overflow: hidden;
            background: #fff url('{{ asset('img/purchase-orders/oc-template.png') }}') center / 100% 100% no-repeat;
            box-shadow: 0 8px 24px rgb(15 23 42 / 14%);
        }

        .po-field,
        .po-field-group,
        .po-value,
        .po-cell {
            position: absolute;
            z-index: 2;
            min-width: 0;
            overflow: hidden;
            background: #fff;
            color: #000;
            font-family: "Arial Narrow", Arial, Helvetica, sans-serif;
            font-size: clamp(9px, 1.35cqw, 14px);
            line-height: 1.08;
        }

        .po-field,
        .po-field-group input,
        .po-field-group select,
        .po-field-group textarea,
        .po-cell input,
        .po-notes,
        .po-prepared {
            border: 0;
            border-radius: 0;
            outline: none;
            box-shadow: none;
        }

        .po-field,
        .po-field-group input,
        .po-field-group select,
        .po-field-group textarea,
        .po-cell input {
            width: 100%;
            height: 100%;
            padding: 0.12cqw 0.28cqw;
            background-color: #fff;
            color: #000;
            font: inherit;
        }

        .po-request-destination-row > select {
            appearance: auto;
            padding-right: 0.55cqw;
        }

        .po-field:focus,
        .po-field-group input:focus,
        .po-field-group select:focus,
        .po-field-group textarea:focus,
        .po-cell input:focus,
        .po-notes:focus,
        .po-prepared:focus {
            outline: max(1px, 0.14cqw) solid #2563eb;
            outline-offset: -1px;
        }

        .po-field-group {
            display: flex;
            flex-direction: column;
        }

        .po-field-group > * {
            flex: 1 1 0;
            min-height: 0;
        }

        .po-field-group > * + * {
            border-top: 1px solid #c7c7c7;
        }

        .po-request-destination-row {
            position: absolute;
            z-index: 3;
            display: grid;
            grid-template-columns: 1.05fr 0.95fr 1.05fr 1.25fr;
            overflow: hidden;
            background: #fff;
            color: #000;
            font-family: "Arial Narrow", Arial, Helvetica, sans-serif;
            font-size: clamp(8px, 1.05cqw, 11px);
            line-height: 1.05;
        }

        .po-request-destination-row > * {
            width: 100%;
            min-width: 0;
            height: 100%;
            border: 0;
            border-radius: 0;
            outline: none;
            background-color: #fff;
            padding: 0.08cqw 0.2cqw;
            color: #000;
            font: inherit;
        }

        .po-request-destination-row > * + * {
            border-left: 1px solid #c7c7c7;
        }

        .po-request-destination-row > *:focus {
            outline: max(1px, 0.14cqw) solid #2563eb;
            outline-offset: -1px;
        }

        .po-destination-summary {
            display: flex;
            align-items: center;
            overflow: hidden;
            padding: 0.12cqw 0.28cqw;
            background: #fff;
            font-weight: 700;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .po-center {
            text-align: center;
        }

        .po-right {
            text-align: right;
        }

        .po-bold {
            font-weight: 700;
        }

        .po-blue {
            color: #0000ff;
            text-decoration: underline;
        }

        .po-cell {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .po-cell-description {
            justify-content: flex-start;
        }

        .po-cell-description input {
            text-align: left;
        }

        .po-money-input {
            font-weight: 400;
            text-align: right;
        }

        .po-notes {
            position: absolute;
            z-index: 3;
            width: 100%;
            height: 100%;
            resize: none;
            overflow: hidden;
            padding: 0.15cqw 0.25cqw;
            background: #fff;
            color: #000;
            font-family: Calibri, Arial, sans-serif;
            font-size: clamp(7px, 0.93cqw, 10px);
            line-height: 1.18;
        }

        .po-prepared {
            position: absolute;
            z-index: 3;
            width: 100%;
            height: 100%;
            padding: 0;
            background: transparent;
            text-align: center;
            font-family: "Arial Narrow", Arial, sans-serif;
            font-size: clamp(8px, 1.05cqw, 12px);
        }

        .po-readonly {
            display: flex;
            align-items: center;
            justify-content: center;
            color: #374151;
            font-size: clamp(8px, 1.12cqw, 12px);
        }

        .po-toolbar-button:disabled {
            cursor: not-allowed;
            opacity: 0.42;
        }

        .po-item-action {
            position: absolute;
            z-index: 4;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid currentColor;
            background: #fff;
            font-family: Arial, Helvetica, sans-serif;
            font-size: clamp(13px, 1.9cqw, 20px);
            font-weight: 700;
            line-height: 1;
        }

        .po-item-action[hidden] {
            display: none;
        }

        .po-item-action-add {
            color: #1e3a8a;
        }

        .po-item-action-add:hover,
        .po-item-action-add:focus-visible {
            background: #eff6ff;
        }

        .po-item-action-remove {
            color: #dc2626;
        }

        .po-item-action-remove:hover,
        .po-item-action-remove:focus-visible {
            background: #fef2f2;
        }

        @media (max-width: 900px) {
            .po-sheet-scroll {
                margin-inline: -1rem;
                padding-inline: 1rem;
            }
        }
    </style>

    <div class="po-editor bg-white p-4 shadow-sm md:p-6">
        <div class="flex flex-col gap-3 border-b border-gray-200 pb-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase text-blue-700">{{ $laboratory->nombre }}</p>
                <h1 class="mt-1 text-2xl font-medium text-gray-900">Nueva orden de compra</h1>
                <p class="mt-1 text-sm text-gray-500">Captura directamente sobre el formato original.</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.warehouses.purchase-orders.index', ['section' => 'mine', 'laboratory_id' => $laboratory->id]) }}"
                    class="inline-flex items-center gap-2 border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    Volver a Mis &Oacute;rdenes de Compra
                </a>
                <button form="purchase-order-form" type="submit"
                    class="inline-flex items-center gap-2 bg-green-600 px-5 py-2 text-sm font-semibold text-white hover:bg-green-700">
                    <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                    Generar orden
                </button>
            </div>
        </div>

        @if ($errors->any())
            <div class="mt-4 border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                Revisa los campos marcados antes de generar la orden.
            </div>
        @endif

        <form id="purchase-order-form" method="POST"
            action="{{ route('admin.oncologicos.laboratory.purchase-orders.store', $laboratory) }}" class="mt-4">
            @csrf

            <div class="po-sheet-scroll">
                <div class="po-sheet" aria-label="Formato editable de orden de compra">
                    <div class="po-readonly po-field" style="{{ $position(165.3, 129.8, 117.2, 11.8) }}">Se asigna al generar</div>

                    <input id="requested_at" name="requested_at" type="date" required
                        value="{{ old('requested_at', now()->toDateString()) }}"
                        class="po-field po-center" style="{{ $position(460.4, 129.8, 124.1, 11.8) }}"
                        aria-label="Fecha de elaboraci&oacute;n">

                    <div class="po-request-destination-row" style="{{ $position(86.7, 148.6, 497.7, 11.9) }}">
                        <input id="department" name="department" type="text" required
                            value="{{ old('department', 'UNIDAD DE CALIDAD') }}"
                            aria-label="Departamento solicitante" title="Departamento solicitante">
                        <select id="delivery_laboratory_id" name="delivery_laboratory_id" required
                            aria-label="Central receptora" title="Central receptora">
                            @foreach ($deliveryLaboratories as $destinationLaboratory)
                                <option value="{{ $destinationLaboratory->id }}"
                                    data-name="{{ $destinationLaboratory->nombre }}"
                                    @selected($destinationLaboratory->id === $selectedDeliveryLaboratoryId)
                                    @disabled($destinationLaboratory->warehouses->isEmpty())>
                                    Central: {{ $destinationLaboratory->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <select id="warehouse_id" name="warehouse_id" required
                            aria-label="Almac&eacute;n receptor" title="Almac&eacute;n receptor">
                            @forelse ($selectedDeliveryLaboratory?->warehouses ?? collect() as $warehouse)
                                <option value="{{ $warehouse->id }}" data-name="{{ $warehouse->name }}"
                                    @selected($warehouse->id === $selectedWarehouseId)>
                                    Almac&eacute;n: {{ $warehouse->name }}
                                </option>
                            @empty
                                <option value="" disabled selected>Almac&eacute;n: Sin almacenes</option>
                            @endforelse
                        </select>
                        <select id="inventory_destination" name="inventory_destination" required
                            aria-label="Subalmac&eacute;n receptor" title="Inventario receptor">
                            @foreach ($inventoryDestinations as $value => $label)
                                <option value="{{ $value }}" data-label="{{ $label }}"
                                    @selected($value === $selectedInventoryDestination)>
                                    Subalmac&eacute;n: {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="po-field-group" style="{{ $position(86.7, 167.2, 254, 31.3) }}">
                        <input id="supplier" name="supplier" type="text" value="{{ old('supplier') }}" required
                            list="supplier-catalog-options" autocomplete="off"
                            aria-label="Proveedor" placeholder="Proveedor">
                        <datalist id="supplier-catalog-options">
                            @foreach ($supplierOptions as $supplierOption)
                                <option value="{{ $supplierOption['name'] }}">
                                    {{ $supplierOption['rfc'] ?: $supplierOption['category'] }}
                                </option>
                            @endforeach
                        </datalist>
                        <input id="supplier_rfc" name="supplier_rfc" type="text" value="{{ old('supplier_rfc') }}"
                            aria-label="RFC del proveedor" placeholder="RFC">
                    </div>

                    <textarea id="supplier_bank_details" name="supplier_bank_details" rows="2"
                        class="po-field" style="{{ $position(86.7, 199.9, 254, 25.4) }}"
                        aria-label="Datos bancarios" placeholder="Datos bancarios">{{ old('supplier_bank_details') }}</textarea>

                    <textarea id="supplier_address" name="supplier_address" rows="2"
                        class="po-field" style="{{ $position(86.7, 226.8, 254, 25.4) }}"
                        aria-label="Direcci&oacute;n del proveedor" placeholder="Direcci&oacute;n">{{ old('supplier_address') }}</textarea>

                    <div class="po-field-group" style="{{ $position(86.7, 253.7, 254, 24.1) }}">
                        <input id="supplier_contact" name="supplier_contact" type="text" value="{{ old('supplier_contact') }}"
                            aria-label="Contacto del proveedor" placeholder="Contacto">
                        <input id="supplier_phone" name="supplier_phone" type="text" value="{{ old('supplier_phone') }}"
                            aria-label="Tel&eacute;fono del proveedor" placeholder="Tel&eacute;fono">
                    </div>

                    <input id="quotation_number" name="quotation_number" type="text" value="{{ old('quotation_number') }}"
                        class="po-field po-center" style="{{ $position(461.3, 167.2, 123.2, 29.6) }}"
                        aria-label="N&uacute;mero de cotizaci&oacute;n" placeholder="No. de cotizaci&oacute;n">

                    <input id="order_type" name="order_type" type="text" value="{{ old('order_type', 'Medicamentos') }}"
                        class="po-field po-center" style="{{ $position(461.3, 198.2, 123.2, 25.4) }}"
                        aria-label="Tipo de orden" placeholder="Tipo">

                    <input id="supplier_email" name="supplier_email" type="email" value="{{ old('supplier_email') }}"
                        class="po-field po-center po-blue" style="{{ $position(461.3, 225.1, 123.2, 25.4) }}"
                        aria-label="Correo electr&oacute;nico del proveedor" placeholder="correo@proveedor.com">

                    <input id="supplier_fax" name="supplier_fax" type="text" value="{{ old('supplier_fax', 'N/A') }}"
                        class="po-field po-center po-blue" style="{{ $position(461.3, 252, 123.2, 25.7) }}"
                        aria-label="Fax del proveedor" placeholder="Fax">

                    <input id="proposed_delivery_at" name="proposed_delivery_at" type="date"
                        value="{{ old('proposed_delivery_at') }}"
                        class="po-field po-center" style="{{ $position(165.6, 280.5, 57.7, 13.9) }}"
                        aria-label="Fecha de entrega propuesta">

                    <input id="urgent_delivery_time" name="urgent_delivery_time" type="text"
                        value="{{ old('urgent_delivery_time', 'Inmediata') }}"
                        class="po-field po-center" style="{{ $position(520.5, 280.5, 63.9, 13.9) }}"
                        aria-label="Hora de entrega urgente">

                    <input id="invoice_to" name="invoice_to" type="text" required
                        value="{{ old('invoice_to', 'Prodifem S.A. de C.V.') }}"
                        class="po-field po-bold" style="{{ $position(86.7, 297.2, 497.7, 11.6) }}"
                        aria-label="Facturar a">

                    <input id="invoice_address" name="invoice_address" type="text" required
                        value="{{ old('invoice_address', 'Calle San Francisco No. 524, int. C, Col. Del Valle, Alcaldia Benito Juarez, Ciudad de Mexico, C.P. 03100') }}"
                        class="po-field" style="{{ $position(86.7, 310.3, 497.7, 11.6) }}"
                        aria-label="Direcci&oacute;n fiscal">

                    <input id="invoice_rfc" name="invoice_rfc" type="text" required
                        value="{{ old('invoice_rfc', 'PRO170214P96') }}"
                        class="po-field" style="{{ $position(86.7, 323.4, 497.7, 11.6) }}"
                        aria-label="RFC para facturaci&oacute;n">

                    <input id="invoice_emails" name="invoice_emails" type="text"
                        value="{{ old('invoice_emails', 'blandly_8@yahoo.com.mx, cmprodifem@gmail.com') }}"
                        class="po-field" style="{{ $position(86.7, 336.5, 497.7, 11.6) }}"
                        aria-label="Correos para facturas">

                    <input id="delivery_attention" name="delivery_attention" type="hidden"
                        value="{{ $initialDeliveryAttention }}">
                    <div class="po-field-group" style="{{ $position(86.7, 349.5, 497.7, 22.7) }}">
                        <div id="delivery_destination_summary" class="po-destination-summary min-h-0 flex-1"
                            title="Destino de la compra">
                            {{ $selectedDeliveryLaboratory?->nombre }} / {{ $selectedWarehouse?->name }} /
                            {{ $inventoryDestinations[$selectedInventoryDestination] }}
                        </div>
                        <div class="flex min-h-0 flex-1 border-t border-gray-300">
                            <input id="delivery_address" name="delivery_address" type="text" required
                                value="{{ $initialDeliveryAddress }}"
                                class="min-w-0 flex-1" aria-label="Direcci&oacute;n de entrega">
                            <input id="delivery_schedule" name="delivery_schedule" type="text"
                                value="{{ old('delivery_schedule', 'L-V 08:00 - 14:00 h.') }}"
                                class="w-[26%] po-bold" aria-label="Horario de recepci&oacute;n">
                        </div>
                    </div>

                    @foreach ([0, 1] as $slot)
                        @php
                            $rowTop = $slot === 0 ? 420.9 : 442.9;
                            $rowHeight = $slot === 0 ? 20.5 : 19.6;
                        @endphp
                        <div class="po-cell" data-item-slot="{{ $slot }}" data-item-part
                            style="{{ $position(19, $rowTop, 65.9, $rowHeight) }}"></div>
                        <div class="po-cell po-cell-description" data-item-slot="{{ $slot }}"
                            style="{{ $position(86.8, $rowTop, 312.9, $rowHeight) }}">
                            <input data-item-field="description" type="text" aria-label="Descripci&oacute;n de la partida">
                        </div>
                        <div class="po-cell" data-item-slot="{{ $slot }}"
                            style="{{ $position(401.3, $rowTop, 57.7, $rowHeight) }}">
                            <input data-item-field="quantity" type="number" min="0.0001" step="0.0001"
                                class="po-center po-bold" aria-label="Cantidad de la partida">
                        </div>
                        <div class="po-cell" data-item-slot="{{ $slot }}"
                            style="{{ $position(460.5, $rowTop, 57.6, $rowHeight) }}">
                            <input data-item-field="unit_price" type="number" min="0" step="0.01"
                                class="po-money-input" aria-label="Precio unitario de la partida">
                        </div>
                        <div class="po-cell po-right" data-item-slot="{{ $slot }}" data-item-subtotal
                            style="{{ $position(520.6, $rowTop, 62.8, $rowHeight) }}">$0.00</div>
                        <button type="button" data-remove-item-slot="{{ $slot }}"
                            class="po-item-action po-item-action-remove"
                            style="{{ $position(587.4, $rowTop + 1.5, 18, $rowHeight - 3) }}"
                            title="Eliminar partida" aria-label="Eliminar partida">
                            <span aria-hidden="true">&minus;</span>
                        </button>
                    @endforeach

                    <button id="add-item" type="button"
                        class="po-item-action po-item-action-add"
                        style="{{ $position(19, 468.2, 65.9, 18) }}"
                        title="Agregar partida" aria-label="Agregar partida">
                        <span aria-hidden="true">&plus;</span>
                    </button>

                    <div id="subtotal-display" class="po-value po-right"
                        style="{{ $position(520.6, 463.9, 62.8, 11.7) }}">$0.00</div>

                    <input id="discount" name="discount" type="number" min="0" step="0.01"
                        value="{{ old('discount', '0.00') }}"
                        class="po-field po-right" style="{{ $position(520.6, 477, 62.8, 11.7) }}"
                        aria-label="Descuento">

                    <div id="discounted-display" class="po-value po-right"
                        style="{{ $position(520.6, 490.1, 62.8, 11.7) }}">$0.00</div>
                    <div id="tax-display" class="po-value po-right"
                        style="{{ $position(520.6, 503.2, 62.8, 11.7) }}">$0.00</div>
                    <div id="total-display" class="po-value po-right po-bold"
                        style="{{ $position(520.6, 516.3, 62.8, 11.7) }}">$0.00</div>

                    <input type="hidden" id="tax_rate" name="tax_rate" value="16.00">

                    <div style="position:absolute; z-index:3; {{ $position(165.2, 582.3, 294.8, 64.6) }}">
                        <textarea id="notes" name="notes" class="po-notes" aria-label="Observaciones">{{ old('notes', $defaultNotes) }}</textarea>
                    </div>

                    <div style="position:absolute; z-index:3; {{ $position(220, 715, 172, 13) }}">
                        <input id="prepared_by" name="prepared_by" type="text"
                            value="{{ old('prepared_by', auth()->user()?->name) }}"
                            class="po-prepared" aria-label="Elabor&oacute;">
                    </div>
                </div>
            </div>

            <div id="item-hidden-inputs"></div>

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
        </form>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.getElementById('purchase-order-form');
                const hiddenInputs = document.getElementById('item-hidden-inputs');
                const discountInput = document.getElementById('discount');
                const taxRateInput = document.getElementById('tax_rate');
                const deliveryLaboratorySelect = document.getElementById('delivery_laboratory_id');
                const warehouseSelect = document.getElementById('warehouse_id');
                const inventoryDestinationSelect = document.getElementById('inventory_destination');
                const deliveryAttentionInput = document.getElementById('delivery_attention');
                const deliveryAddressInput = document.getElementById('delivery_address');
                const deliveryDestinationSummary = document.getElementById('delivery_destination_summary');
                const deliveryDestinations = @json($deliveryDestinationOptions);
                const supplierCatalog = @json($supplierOptions);
                const supplierInput = document.getElementById('supplier');
                const slots = [0, 1].map((slot) => ({
                    slot,
                    part: document.querySelector(`[data-item-part][data-item-slot="${slot}"]`),
                    description: document.querySelector(`[data-item-slot="${slot}"] [data-item-field="description"]`),
                    quantity: document.querySelector(`[data-item-slot="${slot}"] [data-item-field="quantity"]`),
                    unitPrice: document.querySelector(`[data-item-slot="${slot}"] [data-item-field="unit_price"]`),
                    subtotal: document.querySelector(`[data-item-subtotal][data-item-slot="${slot}"]`),
                    removeButton: document.querySelector(`[data-remove-item-slot="${slot}"]`),
                }));

                let items = @json($initialItems);
                let currentPage = 0;

                items = items.length ? items.map((item) => ({
                    description: item.description ?? '',
                    quantity: item.quantity ?? 1,
                    unit_price: item.unit_price ?? 0,
                })) : [{ description: '', quantity: 1, unit_price: 0 }];

                const money = new Intl.NumberFormat('es-MX', {
                    style: 'currency',
                    currency: 'MXN',
                    minimumFractionDigits: 2,
                });

                function numberValue(value) {
                    const parsed = Number.parseFloat(value);
                    return Number.isFinite(parsed) ? parsed : 0;
                }

                function applyCatalogSupplier() {
                    const selectedName = supplierInput.value.trim().toLocaleLowerCase('es-MX');
                    const supplier = supplierCatalog.find((item) =>
                        String(item.name ?? '').trim().toLocaleLowerCase('es-MX') === selectedName
                    );

                    if (!supplier) return;

                    const values = {
                        supplier_rfc: supplier.rfc,
                        supplier_contact: supplier.contact_name,
                        supplier_phone: supplier.phone,
                        supplier_email: supplier.email,
                        supplier_fax: supplier.fax || 'N/A',
                        order_type: supplier.category,
                        supplier_address: supplier.address,
                        supplier_bank_details: supplier.bank_details,
                    };

                    Object.entries(values).forEach(([id, value]) => {
                        document.getElementById(id).value = value ?? '';
                    });
                }

                function pageCount() {
                    return Math.max(1, Math.ceil(items.length / 2));
                }

                function selectedDeliveryLaboratory() {
                    return deliveryDestinations.find((item) => item.id === deliveryLaboratorySelect.value);
                }

                function selectedWarehouse() {
                    const destination = selectedDeliveryLaboratory();
                    return destination?.warehouses.find((item) => item.id === warehouseSelect.value);
                }

                function syncDeliveryFields() {
                    const destination = selectedDeliveryLaboratory();
                    const warehouse = selectedWarehouse();
                    const inventoryLabel = inventoryDestinationSelect.selectedOptions[0]?.dataset.label ?? '';

                    deliveryAttentionInput.value = warehouse
                        ? `${warehouse.name} - ${destination.name}`
                        : (destination?.name ?? '');
                    deliveryDestinationSummary.textContent = [destination?.name, warehouse?.name, inventoryLabel]
                        .filter(Boolean)
                        .join(' / ');
                    deliveryDestinationSummary.title = deliveryDestinationSummary.textContent;
                }

                function renderWarehouses() {
                    const destination = selectedDeliveryLaboratory();
                    warehouseSelect.replaceChildren();

                    if (!destination?.warehouses.length) {
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'Sin almacenes registrados';
                        option.disabled = true;
                        option.selected = true;
                        warehouseSelect.appendChild(option);
                        deliveryAddressInput.value = destination?.address ?? '';
                        syncDeliveryFields();
                        return;
                    }

                    destination.warehouses.forEach((warehouse) => {
                        const option = document.createElement('option');
                        option.value = warehouse.id;
                        option.dataset.name = warehouse.name;
                        option.textContent = `Almac\u00e9n: ${warehouse.name}`;
                        warehouseSelect.appendChild(option);
                    });

                    const warehouse = selectedWarehouse();
                    deliveryAddressInput.value = warehouse?.address || destination.address || '';
                    syncDeliveryFields();
                }

                function updateDeliveryAddress() {
                    const destination = selectedDeliveryLaboratory();
                    const warehouse = selectedWarehouse();
                    deliveryAddressInput.value = warehouse?.address || destination?.address || '';
                    syncDeliveryFields();
                }

                function saveVisibleItems() {
                    slots.forEach(({ slot, description, quantity, unitPrice }) => {
                        const itemIndex = currentPage * 2 + slot;
                        if (itemIndex >= items.length) return;

                        items[itemIndex] = {
                            description: description.value,
                            quantity: quantity.value,
                            unit_price: unitPrice.value,
                        };
                    });
                }

                function updateTotals() {
                    saveVisibleItems();
                    let subtotal = 0;

                    items.forEach((item) => {
                        subtotal += numberValue(item.quantity) * numberValue(item.unit_price);
                    });

                    slots.forEach(({ slot, subtotal: subtotalCell }) => {
                        const item = items[currentPage * 2 + slot];
                        subtotalCell.textContent = item
                            ? money.format(numberValue(item.quantity) * numberValue(item.unit_price))
                            : '';
                    });

                    const discount = Math.min(Math.max(numberValue(discountInput.value), 0), subtotal);
                    const discounted = Math.max(subtotal - discount, 0);
                    const tax = discounted * (Math.max(numberValue(taxRateInput.value), 0) / 100);

                    document.getElementById('subtotal-display').textContent = money.format(subtotal);
                    document.getElementById('discounted-display').textContent = money.format(discounted);
                    document.getElementById('tax-display').textContent = money.format(tax);
                    document.getElementById('total-display').textContent = money.format(discounted + tax);
                }

                function renderPage() {
                    slots.forEach(({ slot, part, description, quantity, unitPrice, subtotal, removeButton }) => {
                        const itemIndex = currentPage * 2 + slot;
                        const item = items[itemIndex];
                        const inputs = [description, quantity, unitPrice];

                        if (item) {
                            part.textContent = itemIndex + 1;
                            description.value = item.description ?? '';
                            quantity.value = item.quantity ?? 1;
                            unitPrice.value = item.unit_price ?? 0;
                            inputs.forEach((input) => {
                                input.disabled = false;
                                input.required = true;
                            });
                            subtotal.textContent = money.format(numberValue(item.quantity) * numberValue(item.unit_price));
                            removeButton.hidden = itemIndex === 0;
                            removeButton.disabled = itemIndex === 0;
                            removeButton.title = `Eliminar partida ${itemIndex + 1}`;
                            removeButton.setAttribute('aria-label', `Eliminar partida ${itemIndex + 1}`);
                        } else {
                            part.textContent = '';
                            description.value = '';
                            quantity.value = '';
                            unitPrice.value = '';
                            inputs.forEach((input) => {
                                input.disabled = true;
                                input.required = false;
                            });
                            subtotal.textContent = '';
                            removeButton.hidden = true;
                            removeButton.disabled = true;
                        }
                    });

                    updateTotals();
                }

                function buildHiddenInputs() {
                    hiddenInputs.replaceChildren();

                    items.forEach((item, index) => {
                        ['description', 'quantity', 'unit_price'].forEach((field) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = `items[${index}][${field}]`;
                            input.value = item[field] ?? '';
                            hiddenInputs.appendChild(input);
                        });
                    });
                }

                function validateItems() {
                    const invalidIndex = items.findIndex((item) =>
                        !String(item.description ?? '').trim()
                        || numberValue(item.quantity) <= 0
                        || numberValue(item.unit_price) < 0
                    );

                    if (invalidIndex === -1) return true;

                    currentPage = Math.floor(invalidIndex / 2);
                    renderPage();
                    const slot = slots[invalidIndex % 2];
                    const target = !String(items[invalidIndex].description ?? '').trim()
                        ? slot.description
                        : (numberValue(items[invalidIndex].quantity) <= 0 ? slot.quantity : slot.unitPrice);
                    target.focus();
                    target.reportValidity();
                    return false;
                }

                slots.forEach(({ description, quantity, unitPrice }) => {
                    [description, quantity, unitPrice].forEach((input) => input.addEventListener('input', updateTotals));
                });

                document.getElementById('add-item').addEventListener('click', function () {
                    saveVisibleItems();
                    items.push({ description: '', quantity: 1, unit_price: 0 });
                    currentPage = Math.floor((items.length - 1) / 2);
                    renderPage();
                    slots[(items.length - 1) % 2].description.focus();
                });

                slots.forEach(({ slot, removeButton }) => {
                    removeButton.addEventListener('click', function () {
                        const itemIndex = currentPage * 2 + slot;
                        if (itemIndex === 0 || itemIndex >= items.length) return;

                        saveVisibleItems();
                        items.splice(itemIndex, 1);
                        currentPage = Math.min(currentPage, pageCount() - 1);
                        renderPage();
                    });
                });

                discountInput.addEventListener('input', updateTotals);
                supplierInput.addEventListener('change', applyCatalogSupplier);
                deliveryLaboratorySelect.addEventListener('change', renderWarehouses);
                warehouseSelect.addEventListener('change', updateDeliveryAddress);
                inventoryDestinationSelect.addEventListener('change', syncDeliveryFields);

                form.addEventListener('submit', function (event) {
                    syncDeliveryFields();
                    saveVisibleItems();
                    if (!validateItems()) {
                        event.preventDefault();
                        return;
                    }
                    buildHiddenInputs();
                });

                renderPage();
            });
        </script>
    @endpush
</x-admin-layout>
