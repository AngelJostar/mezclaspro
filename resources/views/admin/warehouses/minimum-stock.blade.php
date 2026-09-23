<x-admin-layout>
    <div class="minimum-stock-screen bg-white p-5 md:p-6" data-minimum-stock>
        <h1 class="mb-5 text-2xl font-medium text-gray-900">Compras</h1>
        @include('admin.warehouses.partials.purchase-navigation', ['section' => 'minimum-stock'])
        <h2 class="text-lg font-semibold text-gray-900">{{ match ($stockView) { 'history' => 'OC Automatizadas Historial', 'automated' => 'Ordenes de compra automatizadas', default => 'Stock Mínimo' } }}</h2>
        @if ($selectedLaboratory)
            <p class="mt-2 text-sm text-gray-500">{{ $selectedLaboratory->nombre }}</p>
        @endif
        @if ($stockView !== 'stock')
            @include('admin.warehouses.partials.automatic-orders')
        @else
            <div class="minimum-stock-scroll mt-4 overflow-x-auto rounded-md border border-gray-200"
                data-sticky-x-position="viewport" role="region" aria-label="Stock minimo por producto" tabindex="0">
                <table class="minimum-stock-table w-full text-left text-xs text-gray-700">
                    <thead class="bg-gray-50 text-gray-800">
                        <tr>
                            @foreach (['Estado', 'Producto', 'Dosis', 'Presentacion', 'Denominacion comercial', 'Proveedor', 'Correo electronico', 'Stock minimo (piezas)', 'Punto de reorden (piezas)'] as $label)
                                <th scope="col" data-force-column-filter @class(['bg-cyan-50' => $loop->index >= 5])>{{ $label }}</th>
                            @endforeach
                            <th scope="col" data-force-column-filter class="bg-cyan-50"
                                title="Piezas en lotes activos de la central, incluidas las reservadas.">Stock actual (piezas)</th>
                            <th scope="col" data-command-column class="bg-cyan-50">Proveedor predeterminado</th>
                            <th scope="col" data-command-column>Editar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($stockRows as $row)
                            @php
                                $formId = 'minimum-stock-'.$row->type.'-'.$row->id;
                                $canEdit = (bool) $selectedLaboratory?->activo && !auth()->user()->hasAnyRole(['Cliente', 'Institucion']);
                            @endphp
                            <tr data-stock-row data-stock-key="{{ $row->key }}"
                                data-stock-url="{{ route('admin.purchases.minimum-stock.update', [$selectedLaboratory, $row->type, $row->id]) }}"
                                data-supplier-id="{{ $row->supplier?->id }}"
                                data-product-label="{{ $row->product }} - {{ $row->presentation }} - {{ $row->commercial_name }}">
                                <td><span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-1 {{ $row->active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                    <span class="h-2 w-2 rounded-full {{ $row->active ? 'bg-green-500' : 'bg-red-500' }}" aria-hidden="true"></span>
                                    {{ $row->active ? 'Activo' : 'Inactivo' }}
                                </span></td>
                                <td class="font-semibold text-gray-900">{{ $row->product }}</td>
                                <td class="whitespace-nowrap">{{ $row->dose }}</td>
                                <td>{{ $row->presentation }}</td>
                                <td>{{ $row->commercial_name ?: '-' }}</td>
                                <td data-column-filter-value="{{ $row->supplier?->name ?: 'Sin proveedor' }}">
                                    <span data-stock-supplier-name class="block font-semibold">{{ $row->supplier?->name ?: 'Sin proveedor' }}</span>
                                    <span data-stock-supplier-state class="mt-1 block text-xs text-blue-600">{{ $row->supplier ? ($row->supplier->status === 'active' ? 'Predeterminado' : 'Proveedor inactivo') : '' }}</span>
                                </td>
                                <td data-stock-supplier-email class="break-words">{{ $row->supplier?->email ?: '-' }}</td>
                                <td><input type="number" name="minimum_stock" form="{{ $formId }}" value="{{ $row->minimum_stock }}" data-stock-min
                                    min="0" max="1000000" step="1" readonly required placeholder="-" aria-label="Stock minimo de {{ $row->product }} {{ $row->presentation }}"></td>
                                <td><input type="number" name="maximum_stock" form="{{ $formId }}" value="{{ $row->maximum_stock }}" data-stock-max
                                    min="0" max="1000000" step="1" readonly required placeholder="-" aria-label="Punto de reorden de {{ $row->product }} {{ $row->presentation }}"></td>
                                <td data-stock-current data-column-filter-value="{{ $row->current_stock }}" class="text-right font-semibold tabular-nums">
                                    {{ rtrim(rtrim(number_format($row->current_stock, 4, '.', ','), '0'), '.') }}
                                </td>
                                <td><button type="button" data-stock-change-supplier @disabled(!$canEdit)
                                    class="minimum-stock-provider-button">Cambiar de proveedor</button></td>
                                <td>
                                    <form id="{{ $formId }}" data-stock-limits>
                                        @csrf
                                        <div class="flex items-center gap-1">
                                            <button type="button" data-stock-edit class="minimum-stock-edit" @disabled(!$canEdit)>Editar</button>
                                            <button type="submit" data-stock-save class="minimum-stock-edit" hidden>Guardar</button>
                                            <button type="button" data-stock-cancel class="minimum-stock-icon-button" title="Cancelar edicion" aria-label="Cancelar edicion" hidden><i data-stock-icon="x" aria-hidden="true"></i></button>
                                        </div>
                                        <p data-stock-error class="mt-2 text-xs text-red-700" role="alert" hidden></p>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="12" class="py-12 text-center text-gray-500">{{ $selectedLaboratory ? (($stockType ?? 'todas') === 'todas' ? 'No hay presentaciones registradas en el catalogo.' : 'No hay productos del tipo seleccionado.') : 'Selecciona una central de mezclas.' }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-gray-500">{{ $stockRows->count() }} productos</p>
            <p data-stock-notice role="status" class="sr-only"></p>
            <dialog class="minimum-stock-dialog" data-stock-supplier-dialog aria-labelledby="stock-supplier-title">
                <form data-stock-supplier-form>
                    @csrf
                    <header class="flex items-center justify-between gap-3 border-b border-gray-200 p-5">
                        <h3 id="stock-supplier-title" class="text-base font-semibold">Proveedor predeterminado</h3>
                        <button type="button" data-stock-close-dialog class="minimum-stock-icon-button" title="Cerrar" aria-label="Cerrar"><i data-stock-icon="x" aria-hidden="true"></i></button>
                    </header>
                    <div class="space-y-4 p-5">
                        <p data-stock-dialog-product class="break-words text-sm font-medium"></p>
                        <label class="block text-sm">Proveedor
                            <select name="supplier_id" class="mt-2 w-full rounded border-gray-300 text-sm">
                                <option value="">Sin proveedor predeterminado</option>
                                @foreach ($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" data-email="{{ $supplier->email }}" @disabled($supplier->status !== 'active')>{{ $supplier->name }}{{ $supplier->status !== 'active' ? ' (inactivo)' : '' }}</option>
                                @endforeach
                            </select>
                        </label>
                        <div class="text-sm"><span class="block text-gray-500">Correo electronico</span><span data-stock-dialog-email class="break-all">-</span></div>
                        <p data-stock-dialog-error class="text-sm text-red-700" role="alert" hidden></p>
                    </div>
                    <footer class="flex flex-wrap justify-end gap-2 border-t border-gray-200 p-4">
                        <button type="button" data-stock-close-dialog class="minimum-stock-provider-button">Cancelar</button>
                        <button type="submit" class="minimum-stock-edit">Guardar proveedor</button>
                    </footer>
                </form>
            </dialog>
        @endif
    </div>
</x-admin-layout>
