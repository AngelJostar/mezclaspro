<x-admin-layout>
    <div class="mb-5"><h1 class="text-2xl font-semibold">Ingreso de lote de consumible</h1><p class="text-sm text-slate-500">{{ $warehouse->laboratory->nombre }} · {{ $warehouse->name }}</p></div>
    <form method="POST" action="{{ route('admin.warehouses.consumables.store', $warehouse) }}" class="grid max-w-3xl gap-4 rounded border bg-white p-5 md:grid-cols-2">@csrf
        <p class="md:col-span-2 rounded border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">Selecciona un consumible del catálogo y registra su lote. Los genéricos y presentaciones se crean desde Catálogo y listas de precios.</p>
        <label class="md:col-span-2">Consumible y presentación<select required name="catalog_presentation_id" class="mt-1 w-full rounded border-slate-300"><option value="">Seleccionar...</option>@foreach($presentations as $presentation)<option value="{{ $presentation->id }}" @selected(old('catalog_presentation_id') == $presentation->id)>{{ $presentation->item->name }} · {{ $presentation->presentation }}{{ $presentation->commercial_name ? ' · '.$presentation->commercial_name : '' }}</option>@endforeach</select>@error('catalog_presentation_id')<span class="text-sm text-red-600">{{ $message }}</span>@enderror</label>
        <label>Lote<input required name="lot" value="{{ old('lot') }}" class="mt-1 w-full rounded border-slate-300"></label>
        <label>Caducidad<input name="expires_at" type="date" value="{{ old('expires_at') }}" class="mt-1 w-full rounded border-slate-300"></label>
        <label>Fecha de ingreso<input required name="received_at" type="date" value="{{ old('received_at', now()->toDateString()) }}" class="mt-1 w-full rounded border-slate-300"></label>
        <label>Piezas ingresadas<input required name="stock_actual" value="{{ old('stock_actual') }}" type="number" min="0.01" step="0.01" class="mt-1 w-full rounded border-slate-300">@error('stock_actual')<span class="text-sm text-red-600">{{ $message }}</span>@enderror</label>
        <div class="flex items-end gap-2"><a href="{{ route('admin.warehouses.consumables.index',$warehouse) }}" class="rounded border px-4 py-2">Cancelar</a><button class="rounded bg-green-600 px-4 py-2 text-white">Registrar ingreso</button></div>
    </form>
</x-admin-layout>
