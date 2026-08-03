<x-admin-layout>
    <div class="mt-2 mb-4">
        <h1 class="text-2xl font-medium text-gray-800">
            Selecciona un laboratorio
        </h1>
        <p class="text-sm text-gray-600 mt-1">
            El inventario de medicamentos, lotes, caducidades y stock real se gestiona por laboratorio.
        </p>
    </div>

    @if ($errors->has('error'))
        <div class="bg-red-50 border border-red-200 text-red-800 rounded p-3 mb-4">
            {{ $errors->first('error') }}
        </div>
    @endif

    <form id="form-select-lab" method="POST" action="{{ route('admin.oncologicos.inventory.setLaboratory') }}">
        @csrf
        <input type="hidden" name="laboratory_id" id="laboratory_id" value="">
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($laboratories as $lab)
            <button type="button"
                class="text-left bg-white rounded-lg shadow hover:shadow-md border border-gray-100 hover:border-blue-300 p-4 transition"
                onclick="selectLab({{ $lab->id }})">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="text-lg font-semibold text-gray-800">
                            {{ $lab->nombre }}
                        </div>
                        <div class="text-sm text-gray-600 mt-1">
                            {{ $lab->estado ?? '—' }}
                        </div>
                    </div>

                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                        Activo
                    </span>
                </div>

                <div class="mt-3 text-xs text-gray-500">
                    Entrar a inventario →
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
                const input = document.getElementById('laboratory_id');
                const form = document.getElementById('form-select-lab');
                input.value = id;
                form.submit();
            }
        </script>
    @endpush
</x-admin-layout>
