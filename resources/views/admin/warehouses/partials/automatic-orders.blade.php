<div class="mt-4 overflow-x-auto rounded-md border border-gray-200" data-sticky-x-position="viewport"
    role="region" aria-label="{{ $stockView === 'history' ? 'OC Automatizadas Historial' : 'Ordenes de compra automatizadas' }}" tabindex="0">
    <table class="automatic-orders-table w-full text-left text-xs text-gray-700">
        <thead class="bg-gray-50 text-gray-800">
            <tr>
                @foreach (['Folio OC', 'Fecha de generacion', 'Estado', 'Tipo de producto', 'Producto', 'Dosis', 'Presentacion', 'Denominacion comercial', 'Proveedor', 'Correo electronico', 'Punto de reorden (piezas)', 'Stock maximo (piezas)', 'Stock al generar (piezas)', 'Stock actual (piezas)', 'Cantidad a comprar (piezas)', 'Precio unitario', 'Subtotal', 'Descuento', 'IVA', 'Total', 'Central de entrega', 'Almacen receptor', 'Entrega propuesta'] as $label)
                    <th scope="col" data-force-column-filter>{{ $label }}</th>
                @endforeach
                <th scope="col" data-command-column>Detalle</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($automaticOrders as $order)
                @php
                    $snapshot = $order->reorder_snapshot ?? [];
                    $pendingPrice = $snapshot['pricing_pending'] ?? false;
                    $quantity = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
                    $money = fn ($value) => $pendingPrice ? 'Por cotizar' : '$'.number_format((float) $value, 2);
                    $status = $order->status === 'pendiente_revision' ? 'Pendiente de revision' : ucfirst(str_replace('_', ' ', $order->status));
                @endphp
                <tr data-automatic-order="{{ $order->id }}">
                    <td class="font-semibold text-gray-900 whitespace-nowrap">{{ $order->folio }}</td>
                    <td class="whitespace-nowrap">{{ $order->created_at?->format('d/m/Y H:i') }}</td>
                    <td><span class="inline-flex rounded px-2 py-1 {{ $order->status === 'pendiente_revision' ? 'bg-yellow-100 text-yellow-900' : 'bg-gray-100 text-gray-700' }}">{{ $status }}</span></td>
                    <td>{{ $order->inventoryDestinationLabel() }}</td>
                    <td>{{ $snapshot['product'] ?? '-' }}</td>
                    <td>{{ $snapshot['dose'] ?? '-' }}</td>
                    <td>{{ $snapshot['presentation'] ?? '-' }}</td>
                    <td>{{ $snapshot['commercial_name'] ?? '-' }}</td>
                    <td>{{ $order->supplier }}</td>
                    <td>{{ $order->supplier_email ?: 'Pendiente' }}</td>
                    @foreach (['minimum_stock', 'maximum_stock', 'trigger_stock'] as $field)
                        <td class="text-right tabular-nums">{{ $quantity($snapshot[$field] ?? 0) }}</td>
                    @endforeach
                    <td class="text-right tabular-nums">{{ $currentStockRows->has(($snapshot['product_type'] ?? '').':'.($snapshot['presentation_id'] ?? '')) ? $quantity($currentStockRows[($snapshot['product_type'] ?? '').':'.$snapshot['presentation_id']]->current_stock) : 'No disponible' }}</td>
                    <td class="text-right tabular-nums">{{ $quantity($snapshot['quantity'] ?? 0) }}</td>
                    <td>{{ $money($order->items[0]['unit_price'] ?? null) }}</td>
                    <td>{{ $money($order->subtotal) }}</td>
                    <td>{{ $money($order->discount) }}</td>
                    <td>{{ $money($order->tax_amount) }}</td>
                    <td class="font-semibold">{{ $money($order->total) }}</td>
                    <td>{{ $order->deliveryLaboratory?->nombre ?: $order->laboratory?->nombre }}</td>
                    <td>{{ $order->warehouse?->name ?: 'Pendiente de asignar' }}</td>
                    <td>{{ $order->proposed_delivery_at?->format('d/m/Y') ?: 'Pendiente' }}</td>
                    <td><a href="{{ route('admin.purchases.minimum-stock.orders.show', [$selectedLaboratory, $order, 'view' => $stockView] + ($stockType === 'todas' ? [] : ['tipo' => $stockType])) }}"
                        class="minimum-stock-edit" aria-label="Ver orden {{ $order->folio }}">Ver</a></td>
                </tr>
            @empty
                <tr><td colspan="24" class="py-12 text-center text-gray-500">{{ $stockView === 'history' ? 'No hay ordenes de compra automatizadas en el historial para esta seleccion.' : 'No hay ordenes de compra automatizadas para esta seleccion.' }}</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<p class="mt-3 text-xs text-gray-500">{{ $automaticOrders->count() }} ordenes de compra</p>
