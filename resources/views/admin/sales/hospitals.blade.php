<x-admin-layout>
    <h1 class="text-2xl font-medium text-gray-800">Ventas · Hospitales asignados</h1>
    <a class="text-emerald-700" href="{{ route('admin.solicitudes.cotizacion.index') }}">Volver a cotizaciones</a>
    @if (session('status')) <p class="my-4 text-emerald-700">{{ session('status') }}</p> @endif
    @if ($errors->any()) <p class="my-4 text-red-700">{{ $errors->first() }}</p> @endif
    <p class="my-4 text-gray-600">Asigna los hospitales cuyas entregas podrá consultar cada vendedor desde Ventas.</p>
    @foreach ($hospitals as $hospital)
        <form method="POST" action="{{ route('admin.sales.hospitals.update', $hospital) }}" class="mb-4 rounded-lg border bg-white p-4">
            @csrf @method('PUT')
            <h2 class="mb-3 font-semibold">{{ $hospital->name }}</h2>
            <div class="flex flex-wrap gap-4">
                @forelse ($sellers as $seller)
                    <label class="flex items-center gap-2"><input type="checkbox" name="seller_ids[]" value="{{ $seller->id }}" @checked($hospital->salespeople->contains('id', $seller->id))> {{ $seller->name }} {{ $seller->lastname }}</label>
                @empty <p>No hay vendedores activos.</p> @endforelse
            </div>
            <button class="mt-4 rounded-md bg-emerald-600 px-4 py-2 text-white">Guardar asignación</button>
        </form>
    @endforeach
    {{ $hospitals->links() }}
</x-admin-layout>
