<x-admin-layout>
    <div class="rounded-lg bg-white p-5 shadow-sm md:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-900">Ordenes de compra</h1>
                <p class="mt-1 text-sm text-gray-500">Consulta y descarga las ordenes de compra del laboratorio seleccionado.</p>
            </div>
            <a href="{{ route('admin.warehouses.index', ['laboratory_id' => $selectedLaboratory?->id]) }}"
                class="inline-flex items-center justify-center gap-2 rounded border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Volver a almacenes
            </a>
        </div>

        @if ($selectedLaboratory)
            <div class="mt-6 flex flex-col gap-3 border-y border-gray-200 py-4 sm:flex-row sm:items-end sm:justify-between">
                <form method="GET" action="{{ route('admin.warehouses.purchase-orders.index') }}" class="w-full max-w-xl">
                    <label for="laboratory_id" class="mb-1 block text-sm font-medium text-gray-700">Laboratorio</label>
                    <select id="laboratory_id" name="laboratory_id" onchange="this.form.submit()"
                        class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        @foreach ($laboratories as $laboratory)
                            <option value="{{ $laboratory->id }}" @selected($selectedLaboratory->id === $laboratory->id)>{{ $laboratory->nombre }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="{{ route('admin.oncologicos.laboratory.purchase-orders.create', $selectedLaboratory) }}"
                    class="inline-flex items-center justify-center gap-2 rounded bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Nueva orden de compra
                </a>
            </div>

            <div class="mt-5 overflow-x-auto border border-gray-200">
                <table class="w-full min-w-[780px] text-left text-sm text-gray-600">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                        <tr>
                            <th class="px-3 py-2">Folio</th>
                            <th class="px-3 py-2">Fecha</th>
                            <th class="px-3 py-2">Proveedor</th>
                            <th class="px-3 py-2">Total</th>
                            <th class="px-3 py-2">Estatus</th>
                            <th class="px-3 py-2">Descargar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($purchaseOrders as $order)
                            <tr class="border-t border-gray-200">
                                <td class="px-3 py-2 font-medium text-gray-900">{{ $order->folio }}</td>
                                <td class="px-3 py-2">{{ $order->requested_at?->format('d/m/Y') }}</td>
                                <td class="px-3 py-2">{{ $order->supplier }}</td>
                                <td class="px-3 py-2">${{ number_format((float) $order->total, 2) }}</td>
                                <td class="px-3 py-2"><span class="inline-flex rounded bg-green-100 px-2 py-1 text-xs font-medium text-green-800">Enviada</span></td>
                                <td class="px-3 py-2">
                                    <a href="{{ route('admin.oncologicos.laboratory.purchase-orders.download', [$selectedLaboratory, $order]) }}"
                                        class="inline-flex items-center gap-1 font-semibold text-blue-700 hover:text-blue-900">
                                        <i class="fa-solid fa-file-pdf" aria-hidden="true"></i>
                                        Descargar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-3 py-8 text-center text-gray-500">Todavia no hay ordenes de compra para este laboratorio.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <p class="mt-6 text-sm text-gray-600">No hay laboratorios registrados.</p>
        @endif
    </div>
</x-admin-layout>
