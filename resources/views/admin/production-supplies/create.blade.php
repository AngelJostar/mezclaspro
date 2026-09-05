<x-admin-layout>
    @php
        $oldLines = old('lines', [['supply_id' => '', 'quantity' => '']]);
    @endphp

    <h1 class="text-2xl font-semibold">Nueva solicitud de consumibles</h1>
    <p class="mt-1 text-sm text-slate-500">Solo se muestran consumibles; los diluyentes de mezcla están excluidos.</p>

    @if ($errors->any())
        <div class="mt-4 rounded border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <p class="font-semibold">No fue posible enviar la solicitud. Revisa los datos marcados:</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.production-supplies.store') }}" class="mt-5 space-y-4 rounded border bg-white p-5">
        @csrf
        <label class="block text-sm font-semibold">
            Almacén origen
            <select required name="warehouse_id" id="warehouse" class="mt-1 w-full rounded border-slate-300">
                <option value="">Seleccione</option>
                @foreach ($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>{{ $warehouse->laboratory->nombre }} · {{ $warehouse->name }}</option>
                @endforeach
            </select>
        </label>

        <div id="lines" class="space-y-3"></div>
        <p class="text-xs text-slate-500">Puedes agregar varios consumibles. Si repites el mismo lote, las cantidades se sumarán en una sola línea de solicitud.</p>
        <button type="button" id="add" class="rounded bg-slate-100 px-3 py-2 text-sm font-semibold">Agregar insumo</button>

        <label class="block text-sm font-semibold">
            Observaciones
            <textarea name="observations" class="mt-1 w-full rounded border-slate-300">{{ old('observations') }}</textarea>
        </label>
        <div class="flex gap-2">
            <a href="{{ route('admin.production-supplies.index') }}" class="rounded border px-4 py-2">Cancelar</a>
            <button class="rounded bg-blue-700 px-4 py-2 font-semibold text-white">Enviar solicitud</button>
        </div>
    </form>

    <template id="line">
        <div class="line grid grid-cols-1 gap-2 rounded border p-3 md:grid-cols-[1fr_150px_40px]">
            <select class="supply rounded border-slate-300" required>
                <option value="">Insumo</option>
                @foreach ($supplies as $supply)
                    <option value="{{ $supply->id }}" data-warehouse="{{ $supply->warehouse_id }}">{{ $supply->item->name }} · {{ $supply->presentation }} · lote {{ $supply->lot }} ({{ $supply->stock_actual }} {{ $supply->item->unit }})</option>
                @endforeach
            </select>
            <input class="quantity rounded border-slate-300" type="number" min="0.01" step="0.01" placeholder="Cantidad" required>
            <button type="button" class="remove rounded bg-red-50 text-red-700" aria-label="Quitar insumo">×</button>
        </div>
    </template>

    <script>
        const lines = document.querySelector('#lines');
        const template = document.querySelector('#line');
        const warehouse = document.querySelector('#warehouse');
        const oldLines = @json($oldLines);

        function reindex() {
            [...lines.querySelectorAll('.line')].forEach((row, index) => {
                row.querySelector('.supply').name = `lines[${index}][supply_id]`;
                row.querySelector('.quantity').name = `lines[${index}][quantity]`;
            });
        }

        function filter() {
            document.querySelectorAll('.supply option[data-warehouse]').forEach(option => {
                option.hidden = Boolean(warehouse.value && option.dataset.warehouse !== warehouse.value);
            });
        }

        function add(values = {}) {
            const fragment = template.content.cloneNode(true);
            const row = fragment.querySelector('.line');
            const supply = fragment.querySelector('.supply');
            const quantity = fragment.querySelector('.quantity');
            supply.value = values.supply_id ?? '';
            quantity.value = values.quantity ?? '';
            fragment.querySelector('.remove').onclick = () => {
                row.remove();
                if (!lines.children.length) add();
                reindex();
            };
            lines.append(fragment);
            reindex();
            filter();
        }

        document.querySelector('#add').onclick = () => add();
        warehouse.onchange = filter;
        oldLines.forEach(line => add(line));
    </script>
</x-admin-layout>
