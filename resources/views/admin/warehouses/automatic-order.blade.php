<x-admin-layout>
    @php
        $snapshot = $order->reorder_snapshot ?? [];
        $pendingPrice = $snapshot['pricing_pending'] ?? false;
        $money = fn ($value) => $pendingPrice ? 'Por cotizar' : '$'.number_format((float) $value, 2);
        $quantity = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
        $returnQuery = ['laboratory_id' => $order->laboratory_id, 'view' => request()->query('view') === 'history' ? 'history' : 'automated'];
        if (in_array(request()->query('tipo'), ['nutricionales', 'oncologicos', 'antibioticos'], true)) {
            $returnQuery['tipo'] = request()->query('tipo');
        }
        $sections = [
            'Datos de la orden' => [
                'Estado' => $order->status === 'pendiente_revision' ? 'Pendiente de revision' : ucfirst(str_replace('_', ' ', $order->status)),
                'Fecha de generacion' => $order->created_at?->format('d/m/Y H:i'),
                'Central' => $order->laboratory?->nombre, 'Departamento' => $order->department,
                'Tipo de orden' => $order->order_type, 'Tipo de producto' => $order->inventoryDestinationLabel(),
                'Numero de cotizacion' => $order->quotation_number, 'Elaborada por' => $order->prepared_by,
            ],
            'Proveedor' => [
                'Nombre' => $order->supplier, 'RFC' => $order->supplier_rfc,
                'Contacto' => $order->supplier_contact, 'Telefono' => $order->supplier_phone,
                'Correo electronico' => $order->supplier_email, 'Fax' => $order->supplier_fax,
                'Direccion' => $order->supplier_address, 'Datos bancarios' => $order->supplier_bank_details,
            ],
            'Entrega y facturacion' => [
                'Central de entrega' => $order->deliveryLaboratory?->nombre,
                'Almacen receptor' => $order->warehouse?->name, 'Atencion' => $order->delivery_attention,
                'Direccion de entrega' => $order->delivery_address,
                'Entrega propuesta' => $order->proposed_delivery_at?->format('d/m/Y'),
                'Horario de entrega' => $order->delivery_schedule, 'Entrega urgente' => $order->urgent_delivery_time,
                'Facturar a' => $order->invoice_to, 'RFC de facturacion' => $order->invoice_rfc,
                'Direccion fiscal' => $order->invoice_address, 'Correo para facturas' => $order->invoice_emails,
            ],
        ];
    @endphp
    <div class="automatic-order-detail bg-white p-5 md:p-6">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 pb-4">
            <h1 class="text-xl font-semibold">Orden de compra {{ $order->folio }}</h1>
            <a class="minimum-stock-provider-button" href="{{ route('admin.purchases.minimum-stock', $returnQuery) }}">Volver a ordenes</a>
        </header>
        @foreach ($sections as $title => $fields)
            <section class="border-b border-gray-200 py-5">
                <h2 class="mb-4 text-base font-semibold">{{ $title }}</h2>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($fields as $label => $value)
                        <div class="min-w-0"><dt class="text-xs text-gray-500">{{ $label }}</dt><dd class="mt-1 break-words text-sm">{{ $value ?: 'Pendiente' }}</dd></div>
                    @endforeach
                </dl>
            </section>
        @endforeach
        <section class="border-b border-gray-200 py-5">
            <h2 class="mb-4 text-base font-semibold">Reposicion y partidas</h2>
            <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach (['minimum_stock' => 'Punto de reorden', 'maximum_stock' => 'Stock maximo', 'trigger_stock' => 'Stock al generar', 'current_stock' => 'Stock en ultima evaluacion'] as $key => $label)
                    <div><dt class="text-xs text-gray-500">{{ $label }}</dt><dd class="mt-1 text-sm">{{ $quantity($snapshot[$key] ?? 0) }} piezas</dd></div>
                @endforeach
            </dl>
            <div class="mt-4 overflow-x-auto" data-sticky-x-position="viewport">
                <table class="automatic-order-items w-full text-left text-sm">
                    <thead class="bg-gray-50"><tr><th>Producto / Presentacion</th><th>Cantidad (piezas)</th><th>Precio unitario</th><th>Subtotal</th></tr></thead>
                    <tbody>@foreach ($order->items ?? [] as $item)
                        <tr><td>{{ $item['description'] }}</td><td>{{ $quantity($item['quantity']) }}</td><td>{{ $money($item['unit_price']) }}</td><td>{{ $money($item['subtotal']) }}</td></tr>
                    @endforeach</tbody>
                </table>
            </div>
            <dl class="mt-4 grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach (['subtotal' => 'Subtotal', 'discount' => 'Descuento', 'tax_amount' => 'IVA', 'total' => 'Total MXN'] as $key => $label)
                    <div><dt class="text-xs text-gray-500">{{ $label }}</dt><dd class="mt-1 text-sm font-semibold">{{ $money($order->$key) }}</dd></div>
                @endforeach
            </dl>
        </section>
        <section class="py-5"><h2 class="mb-3 text-base font-semibold">Observaciones</h2><p class="text-sm break-words">{{ $order->notes ?: 'Sin observaciones' }}</p>
            @if (!empty($snapshot['closed_reason']))<p class="mt-2 text-sm">{{ $snapshot['closed_reason'] }}</p>@endif
        </section>
    </div>
</x-admin-layout>
