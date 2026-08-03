<x-admin-layout>
  <div class="mt-2">
    <h1 class="text-2xl font-medium text-gray-800">Nuevo medicamento (Catálogo)</h1>
  </div>

  <form action="{{ route('admin.oncologicos.medicines.catalog.store') }}" method="POST" class="mb-6">
    @csrf

    @if ($errors->any())
      <div class="mb-4 p-3 rounded bg-red-100 text-red-800">
        <div class="font-semibold mb-1">Revisa los campos:</div>
        <ul class="list-disc list-inside text-sm">
          @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
      </div>
    @endif

    <div class="grid grid-cols-4 gap-4">
      <div>
        <label class="block mb-2 text-sm font-medium text-gray-700">Denominación</label>
        <input name="denominacion" value="{{ old('denominacion') }}" required
               class="w-full p-2 text-sm bg-gray-50 border rounded-lg" />
      </div>
      <div>
        <label class="block mb-2 text-sm font-medium text-gray-700">Concentración mínima (mg/mL)</label>
        <input type="number" step="0.01" min="0" name="conc_min" value="{{ old('conc_min') }}"
               class="w-full p-2 text-sm bg-gray-50 border rounded-lg" />
      </div>

      <div>
        <label class="block mb-2 text-sm font-medium text-gray-700">Concentración máxima (mg/mL)</label>
        <input type="number" step="0.01" min="0" name="conc_max" value="{{ old('conc_max') }}"
               class="w-full p-2 text-sm bg-gray-50 border rounded-lg" />
      </div>


      <div>
        <label class="block mb-2 text-sm font-medium text-gray-700">¿Requiere infusor?</label>
        <input type="hidden" name="requires_infusor" value="0">
        <label class="inline-flex items-center">
          <input type="checkbox" name="requires_infusor" value="1" class="form-checkbox text-blue-600"
                 {{ old('requires_infusor', 0) ? 'checked' : '' }}>
          <span class="ml-2 text-sm text-gray-700">Sí</span>
        </label>
      </div>
    </div>

    <div class="grid grid-cols-2 gap-6 mt-6">
      <div>
        <p class="mb-2 text-sm font-medium text-gray-700">Diluyentes permitidos</p>
        @php $oldDils = old('diluents', []); @endphp
        <div class="space-y-2 max-h-48 overflow-auto p-2 border rounded">
          @foreach ($diluents as $d)
            <label class="flex items-center gap-2">
              <input type="checkbox" name="diluents[]" value="{{ $d->id }}" class="form-checkbox"
                     {{ in_array($d->id, $oldDils) ? 'checked' : '' }}>
              <span>{{ $d->denominacion_generica }}</span>
            </label>
          @endforeach
        </div>
      </div>

      <div>
        <p class="mb-2 text-sm font-medium text-gray-700">Vías de administración</p>
        @php $oldRoutes = old('routes', []); @endphp
        <div class="space-y-2 max-h-48 overflow-auto p-2 border rounded">
          @foreach ($routes as $r)
            <label class="flex items-center gap-2">
              <input type="checkbox" name="routes[]" value="{{ $r->id }}" class="form-checkbox"
                     {{ in_array($r->id, $oldRoutes) ? 'checked' : '' }}>
              <span>{{ $r->name }}</span>
            </label>
          @endforeach
        </div>
      </div>
    </div>

    <div class="mt-6 text-right">
      <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
        Guardar catálogo
      </button>
    </div>
  </form>
</x-admin-layout>
