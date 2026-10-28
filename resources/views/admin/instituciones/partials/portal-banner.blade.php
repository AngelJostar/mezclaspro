@if (auth()->user()?->isInstitutionAccount())
    @php
        $portalUser = auth()->user();
        $portalHospitals = \App\Models\Hospital::whereIn('id', $portalUser->accessibleHospitalIds())->orderBy('name')->get(['id', 'name']);
    @endphp
    <section class="mb-5 flex flex-wrap items-center justify-between gap-4 rounded-lg border border-blue-200 bg-blue-50 p-4" aria-label="Acceso de institución">
        <div>
            <p class="font-semibold text-blue-900">{{ $portalUser->institution?->nombre }}</p>
            <p class="text-sm text-blue-800">Perfil Institución · Solo consulta · {{ $portalHospitals->count() }} hospitales relacionados</p>
        </div>
        <form method="GET" action="{{ route('admin.solicitudes.index') }}" class="flex flex-wrap items-end gap-2">
            <div>
                <label for="institution-hospital-filter" class="block text-xs text-blue-900">Hospital</label>
                <select id="institution-hospital-filter" name="institution_hospital" class="max-w-full rounded-md border-blue-200 text-sm">
                    <option value="">Todos los hospitales</option>
                    @foreach ($portalHospitals as $hospital)
                        <option value="{{ $hospital->id }}" @selected(request()->attributes->get('institution_hospital') === $hospital->id)>{{ $hospital->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-md bg-blue-800 px-4 py-2 text-sm text-white">Consultar</button>
        </form>
    </section>
@endif
