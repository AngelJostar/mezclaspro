<x-admin-layout>
    <div class="mt-2 mb-4">
        <h1 class="text-2xl font-medium text-gray-800">
            Selecciona un laboratorio
        </h1>
        <p class="text-sm text-gray-600 mt-1">
            El inventario nutricional, incluyendo presentaciones, lotes, caducidades y stock en ml,
            se gestiona por laboratorio.
        </p>
    </div>

    @if ($errors->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded p-3 mb-4">
            {{ $errors->first('error') }}
        </div>
    @endif

    <form id="form-select-lab" method="GET" action="{{ route('admin.nutricionales.stocks.index') }}">
        <input type="hidden" name="laboratory_id" id="laboratory_id">
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($laboratories as $lab)
            <button
                type="button"
                onclick="selectLab({{ $lab->id }})"
                class="text-left bg-white rounded-lg shadow hover:shadow-md border border-gray-100 hover:border-blue-300 p-4 transition"
            >
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="text-lg font-semibold text-gray-800">
                            {{ $lab->nombre }}
                        </div>

                        <div class="text-sm text-gray-600 mt-1">
                            {{ $lab->estado ?? '—' }}
                        </div>
                    </div>

                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                        {{ $lab->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $lab->activo ? 'Activo' : 'Inactivo' }}
                    </span>
                </div>

                <div class="mt-3 text-xs text-gray-500">
                    Entrar a inventario nutricional →
                </div>
            </button>
        @empty
            <div class="bg-white rounded-lg shadow p-6 text-gray-600">
                No hay laboratorios activos para seleccionar.
            </div>
        @endforelse
    </div>

    @push('js')
        <script>
            function selectLab(id) {
                document.getElementById('laboratory_id').value = id;
                document.getElementById('form-select-lab').submit();
            }
        </script>
    @endpush
</x-admin-layout>
