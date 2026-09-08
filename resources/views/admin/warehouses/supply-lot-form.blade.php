<x-admin-layout>
    <div class="mb-5 flex items-start justify-between gap-3">
        <div><h1 class="text-2xl font-medium text-gray-800">Ingreso de lote de diluyente</h1><p class="mt-1 text-sm text-gray-600">Central de mezclas: <strong>{{ $warehouse->laboratory?->nombre }}</strong><span class="mx-1 text-gray-300">|</span>Almacén: <strong>{{ $warehouse->name }}</strong></p></div>
        <a href="{{ route('admin.warehouses.supplies.index', $warehouse) }}" class="rounded bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-700">Volver</a>
    </div>

    <form method="POST" action="{{ route('admin.warehouses.supplies.store', $warehouse) }}" class="rounded border border-gray-200 bg-white p-6 shadow-sm">
        @csrf
        <p class="mb-5 rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">Selecciona una presentación ya registrada en el catálogo y agrega su lote. Si el lote ya existe para esa presentación, la cantidad se sumará.</p>
        <div class="grid gap-4 md:grid-cols-2">
            <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium">Producto y presentación</label><select name="catalog_presentation_id" required class="w-full rounded border-gray-300"><option value="">Seleccionar...</option>@foreach($presentations as $presentation)<option value="{{ $presentation->id }}" @selected(old('catalog_presentation_id') == $presentation->id)>{{ $presentation->diluent?->denominacion_generica }} · {{ $presentation->presentation }}{{ $presentation->commercial_name ? ' · '.$presentation->commercial_name : '' }}</option>@endforeach</select>@error('catalog_presentation_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="mb-1 block text-sm font-medium">Lote</label><input name="lote" value="{{ old('lote') }}" required class="w-full rounded border-gray-300">@error('lote')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="mb-1 block text-sm font-medium">Caducidad</label><input type="date" name="caducidad" value="{{ old('caducidad') }}" class="w-full rounded border-gray-300"></div>
            <div><label class="mb-1 block text-sm font-medium">Piezas ingresadas</label><input type="number" min="0.01" step="0.01" name="cantidad" value="{{ old('cantidad') }}" required class="w-full rounded border-gray-300">@error('cantidad')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label class="mb-1 block text-sm font-medium">Fecha de ingreso</label><input type="date" name="fecha_ingreso" value="{{ old('fecha_ingreso', now()->toDateString()) }}" required class="w-full rounded border-gray-300"></div>
            <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium">Notas</label><textarea name="notas" rows="3" class="w-full rounded border-gray-300">{{ old('notas') }}</textarea></div>
        </div>
        <div class="mt-6 text-right"><button class="rounded bg-green-600 px-5 py-2 text-sm font-semibold text-white">Registrar ingreso</button></div>
    </form>
</x-admin-layout>
