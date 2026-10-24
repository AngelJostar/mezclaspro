<div class="po-sheet po-document" aria-label="Formato editable de orden de compra">
    @include('admin.oncologicos.laboratory.purchase-orders.document-header')
    <section class="po-section">
        <h3 class="po-section-title"><span>1. Datos de la orden</span></h3>
        <div class="po-section-box">
            <table class="po-inline-fields"><tr>
                <th style="width:19%">No. de Orden de Compra:</th><td style="width:39%"><div class="po-field-value po-readonly">Se asigna al generar</div></td>
                <th style="width:18%"><label for="requested_at">Fecha de elaboración:</label></th><td><x-purchase-order-field id="requested_at" label="Fecha de elaboración" type="date" :value="now()->toDateString()" required /></td>
            </tr></table>
            <table class="po-inline-fields po-location-fields"><tr>
                <td style="width:27%"><label for="department">Depto solicita:</label><x-purchase-order-field id="department" label="Departamento solicitante" value="UNIDAD DE CALIDAD" required /></td>
                <td style="width:27%"><label for="delivery_laboratory_id">Central:</label>
                    <select id="delivery_laboratory_id" name="delivery_laboratory_id" required aria-label="Central receptora" class="po-input">
                        <option value="">Selecciona central</option>
                        @foreach ($deliveryLaboratories as $destinationLaboratory)
                            <option value="{{ $destinationLaboratory->id }}" data-name="{{ $destinationLaboratory->nombre }}"
                                @selected($destinationLaboratory->id === $selectedDeliveryLaboratoryId) @disabled($destinationLaboratory->warehouses->isEmpty())>{{ $destinationLaboratory->nombre }}</option>
                        @endforeach
                    </select>
                </td>
                <td style="width:23%"><label for="warehouse_id">Almacén:</label>
                    <select id="warehouse_id" name="warehouse_id" required @disabled(!$selectedDeliveryLaboratory) aria-label="Almacén receptor" class="po-input">
                        <option value="">Selecciona almacén</option>
                        @foreach ($selectedDeliveryLaboratory?->warehouses ?? collect() as $warehouse)
                            <option value="{{ $warehouse->id }}" data-name="{{ $warehouse->name }}" @selected($warehouse->id === $selectedWarehouseId)>{{ $warehouse->name }}</option>
                        @endforeach
                    </select>
                </td>
                <td><label for="inventory_destination">Subalmacén:</label>
                    <select id="inventory_destination" name="inventory_destination" required @disabled(!$selectedWarehouse) aria-label="Subalmacén receptor" class="po-input">
                        <option value="">Selecciona subalmacén</option>
                        @foreach ($inventoryDestinations as $value => $label)
                            <option value="{{ $value }}" data-label="{{ $label }}" data-order-type="{{ \App\Models\Oncologicos\LaboratoryPurchaseOrder::orderTypeForDestination($value) }}" @selected($value === $selectedInventoryDestination)>{{ $label }}</option>
                        @endforeach
                    </select>
                </td>
            </tr></table>
        </div>
    </section>
    <section class="po-section">
        <h3 class="po-section-title"><span>2. Proveedor y cotización</span></h3>
        <div class="po-section-box">
            <table class="po-split"><tr><td style="width:52%">
                <table class="po-info-table">
                    <tr><th><label for="supplier">Proveedor:</label></th><td>
                        <div class="po-supplier-field">
                            <x-purchase-order-field id="supplier" label="Proveedor" autocomplete="off" placeholder="Buscar por nombre o RFC" role="combobox" aria-autocomplete="list" aria-haspopup="listbox" aria-controls="po-supplier-options" aria-expanded="false" required />
                            <button type="button" id="po-supplier-toggle" aria-label="Mostrar proveedores registrados" aria-haspopup="listbox" aria-controls="po-supplier-options" aria-expanded="false"></button>
                        </div>
                    </td></tr>
                    <tr><th><label for="supplier_rfc">RFC:</label></th><td><x-purchase-order-field id="supplier_rfc" label="RFC del proveedor" /></td></tr>
                    <tr><th><label for="supplier_bank_details">Datos bancarios:</label></th><td><x-purchase-order-field id="supplier_bank_details" label="Datos bancarios" type="textarea" /></td></tr>
                    <tr><th><label for="supplier_address">Dirección:</label></th><td><x-purchase-order-field id="supplier_address" label="Dirección del proveedor" type="textarea" /></td></tr>
                    <tr><th><label for="supplier_contact">Contacto:</label></th><td><x-purchase-order-field id="supplier_contact" label="Contacto del proveedor" /></td></tr>
                    <tr><th><label for="supplier_phone">Teléfono:</label></th><td><x-purchase-order-field id="supplier_phone" label="Teléfono del proveedor" /></td></tr>
                </table>
            </td><td>
                <table class="po-info-table po-quotation-fields">
                    <tr><th><label for="quotation_number">No. Cotización:</label></th><td><x-purchase-order-field id="quotation_number" label="Número de cotización" /></td></tr>
                    <tr><th><label for="order_type">Tipo:</label></th><td><x-purchase-order-field id="order_type" label="Tipo de orden" :value="\App\Models\Oncologicos\LaboratoryPurchaseOrder::orderTypeForDestination($selectedInventoryDestination)" :restore="false" readonly placeholder="Selecciona subalmacén" /></td></tr>
                    <tr><th><label for="supplier_email">E-mail:</label></th><td><x-purchase-order-field id="supplier_email" label="Correo electrónico del proveedor" type="email" /></td></tr>
                    <tr><th><label for="supplier_fax">Fax:</label></th><td><x-purchase-order-field id="supplier_fax" label="Fax del proveedor" value="N/A" /></td></tr>
                </table>
            </td></tr></table>
        </div>
    </section>
    <section class="po-section">
        <h3 class="po-section-title"><span>3. Entrega</span></h3>
        <div class="po-section-box"><table class="po-inline-fields"><tr>
            <th style="width:24%"><label for="proposed_delivery_at">Fecha de entrega propuesta:</label></th>
            <td style="width:22%"><x-purchase-order-field id="proposed_delivery_at" label="Fecha de entrega propuesta" type="date" /></td>
            <th style="width:34%"><label for="urgent_delivery_time">Hora de entrega de pedido “Urgente”:</label></th>
            <td><x-purchase-order-field id="urgent_delivery_time" label="Hora de entrega urgente" value="Inmediata" /></td>
        </tr></table></div>
    </section>
    <section class="po-section">
        <h3 class="po-section-title"><span>4. Facturación y destino</span></h3>
        <div class="po-section-box"><table class="po-split"><tr><td style="width:59%">
            <table class="po-info-table">
                <tr><th><label for="invoice_to">Facturar a:</label></th><td><x-purchase-order-field id="invoice_to" label="Facturar a" value="Prodifem S.A. de C.V." required /></td></tr>
                <tr><th><label for="invoice_address">Dirección:</label></th><td><x-purchase-order-field id="invoice_address" label="Dirección fiscal" type="textarea" value="Calle San Francisco No. 524, int. C, Col. Del Valle, Alcaldia Benito Juarez, Ciudad de Mexico, C.P. 03100" required /></td></tr>
                <tr><th><label for="invoice_rfc">RFC:</label></th><td><x-purchase-order-field id="invoice_rfc" label="RFC para facturación" value="PRO170214P96" required /></td></tr>
                <tr><th><label for="invoice_emails">E-mail facturas:</label></th><td><x-purchase-order-field id="invoice_emails" label="Correos para facturas" value="blandly_8@yahoo.com.mx, cmprodifem@gmail.com" /></td></tr>
            </table>
        </td><td>
            <input id="delivery_attention" name="delivery_attention" type="hidden" value="{{ $initialDeliveryAttention }}">
            <table class="po-info-table">
                <tr><th>Entregar en:</th><td><div id="delivery_destination_summary" class="po-destination-summary">{{ $initialDeliveryAttention ?: 'Seleccionar destino de entrega' }} / {{ $inventoryDestinations[$selectedInventoryDestination] ?? 'Sin subalmacén asignado' }}</div></td></tr>
                <tr><th><label for="delivery_address">Dirección de entrega:</label></th><td><x-purchase-order-field id="delivery_address" label="Dirección de entrega" type="textarea" :value="$initialDeliveryAddress" required /></td></tr>
                <tr><th><label for="delivery_schedule">Horario:</label></th><td><x-purchase-order-field id="delivery_schedule" label="Horario de recepción" value="L-V 08:00 - 14:00 h." /></td></tr>
            </table>
        </td></tr></table></div>
    </section>
    <section class="po-section">
        <h3 class="po-section-title"><span>5. Productos</span></h3>
        <div class="po-section-box">
            <table class="po-products"><colgroup><col style="width:9%"><col style="width:49%"><col style="width:11%"><col style="width:14%"><col style="width:17%"></colgroup>
                <thead><tr><th>Partida</th><th>DESCRIPCIÓN</th><th>CANTIDAD</th><th>PRECIO<br>UNITARIO</th><th>SUBTOTAL POR<br>PARTIDA</th></tr></thead>
                <tbody>@foreach (range(0, 9) as $slot)
                    <tr>
                        <td class="po-part"><span data-item-slot="{{ $slot }}" data-item-part>{{ $slot + 1 }}</span><button type="button" data-remove-item-slot="{{ $slot }}" class="po-item-action po-item-action-remove" title="Eliminar partida" aria-label="Eliminar partida" hidden><span aria-hidden="true">−</span></button></td>
                        <td data-item-slot="{{ $slot }}"><div class="po-cell-description">
                            <input data-item-field="description" type="text" disabled autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="po-product-options" placeholder="Selecciona central, almacén y subalmacén" aria-label="Descripción de la partida">
                            <button type="button" class="po-product-toggle" data-product-toggle="{{ $slot }}" disabled aria-label="Mostrar productos" title="Mostrar productos" aria-expanded="false" aria-controls="po-product-menu"></button>
                        </div></td>
                        <td data-item-slot="{{ $slot }}"><input data-item-field="quantity" type="number" min="0.0001" step="0.0001" class="po-center" aria-label="Cantidad de la partida"></td>
                        <td data-item-slot="{{ $slot }}"><input data-item-field="unit_price" type="number" min="0" step="0.01" class="po-right" aria-label="Precio unitario de la partida"></td>
                        <td class="po-right" data-item-slot="{{ $slot }}" data-item-subtotal></td>
                    </tr>
                @endforeach</tbody>
            </table>
            <div class="po-products-toolbar">
                <button id="add-item" type="button" class="po-item-action po-item-action-add" title="Agregar partida" aria-label="Agregar partida">+ Agregar partida</button>
                <div class="po-item-pagination" aria-label="Partidas de la orden"><button id="po-previous-items" type="button" title="Partidas anteriores" aria-label="Partidas anteriores"></button><span id="po-items-page" aria-live="polite"></span><button id="po-next-items" type="button" title="Partidas siguientes" aria-label="Partidas siguientes"></button></div>
            </div>
            <table class="po-bottom"><tr><td>
                <section class="po-section"><h3 class="po-section-title"><span>6. Observaciones</span></h3><div class="po-section-box"><x-purchase-order-field id="notes" label="Observaciones" type="textarea" :rows="8" :value="$defaultNotes" /></div></section>
            </td><td>
                <table class="po-totals"><tr><th>SUBTOTAL ORDEN</th><td id="subtotal-display">$0.00</td></tr>
                    <tr><th><label for="discount">DESCUENTO</label></th><td><x-purchase-order-field id="discount" label="Descuento" type="number" min="0" step="0.01" value="0.00" class="po-right" /></td></tr>
                    <tr><th>SUBTOTAL CON DESC.</th><td id="discounted-display">$0.00</td></tr><tr><th>I.V.A. 16%</th><td id="tax-display">$0.00</td></tr><tr class="po-total"><th>TOTAL</th><td id="total-display">$0.00</td></tr>
                </table><input type="hidden" id="tax_rate" name="tax_rate" value="16.00">
            </td></tr></table>
        </div>
    </section>
    <div class="po-signature"><x-purchase-order-field id="prepared_by" label="Elaboró" :restore="false" readonly :value="trim((auth()->user()?->name ?? '').' '.(auth()->user()?->lastname ?? ''))" /><div class="po-signature-label">Elaboró.</div></div>
</div>
