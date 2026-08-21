@php
    $selectedHospitalIds = collect(old(
        'hospital_ids',
        $distributionRoute->exists ? $distributionRoute->hospitals->pluck('id')->all() : []
    ))->map(fn ($id) => (int) $id)->values()->all();

    $selectedMessengerIds = collect(old(
        'messenger_ids',
        $distributionRoute->exists ? $distributionRoute->messengers->pluck('id')->all() : []
    ))->map(fn ($id) => (int) $id)->values()->all();

    $selectableHospitalIds = $hospitals
        ->reject(fn ($hospital) => $hospitalRouteAssignments->has($hospital->id))
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->values()
        ->all();
@endphp

<div x-data="{
    selectedHospitals: @js($selectedHospitalIds),
    selectedMessengers: @js($selectedMessengerIds),
    hospitalSearch: '',
    messengerSearch: '',
    allHospitalIds: @js($selectableHospitalIds),
    allMessengerIds: @js($messengers->pluck('id')->map(fn ($id) => (int) $id)->values()),
    allHospitalsSelected() {
        return this.allHospitalIds.length > 0
            && this.allHospitalIds.every((id) => this.selectedHospitals.includes(id));
    },
    toggleAllHospitals() {
        if (this.allHospitalsSelected()) {
            this.selectedHospitals = this.selectedHospitals.filter((id) => !this.allHospitalIds.includes(id));
            return;
        }

        this.selectedHospitals = [...new Set([...this.selectedHospitals, ...this.allHospitalIds])];
    },
    allMessengersSelected() {
        return this.allMessengerIds.length > 0
            && this.allMessengerIds.every((id) => this.selectedMessengers.includes(id));
    },
    toggleAllMessengers() {
        if (this.allMessengersSelected()) {
            this.selectedMessengers = this.selectedMessengers.filter((id) => !this.allMessengerIds.includes(id));
            return;
        }

        this.selectedMessengers = [...new Set([...this.selectedMessengers, ...this.allMessengerIds])];
    },
}" class="space-y-4">
    @if ($errors->any())
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
            <p class="font-semibold">Revisa la información de la ruta.</p>
            <ul class="mt-1 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section aria-labelledby="route-general-heading">
        <div class="mb-3 flex items-center gap-2 border-b border-gray-200 pb-2">
            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-700 text-[11px] font-bold text-white">1</span>
            <div>
                <h2 id="route-general-heading" class="text-sm font-semibold text-gray-950">Información de la ruta y mensajeros</h2>
                <p class="text-[11px] text-gray-500">Define los datos operativos y las personas responsables del recorrido.</p>
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-6">
            <label class="block xl:col-span-2">
                <span class="mb-1 block text-[11px] font-semibold text-gray-700">Nombre de la ruta <span class="text-red-600">*</span></span>
                <input type="text" name="name" value="{{ old('name', $distributionRoute->name) }}" required maxlength="255"
                    placeholder="Ej. Ruta Centro 01"
                    class="h-9 w-full rounded-md border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500">
            </label>

            <label class="block">
                <span class="mb-1 block text-[11px] font-semibold text-gray-700">Código</span>
                <input type="text" name="code" value="{{ old('code', $distributionRoute->code) }}" maxlength="80"
                    placeholder="Automático"
                    class="h-9 w-full rounded-md border-gray-300 text-xs uppercase focus:border-blue-500 focus:ring-blue-500">
            </label>

            <label class="block">
                <span class="mb-1 block text-[11px] font-semibold text-gray-700">Inicio <span class="text-red-600">*</span></span>
                <input type="time" name="schedule_start" value="{{ old('schedule_start', $distributionRoute->schedule_start ? substr($distributionRoute->schedule_start, 0, 5) : '07:00') }}" required
                    class="h-9 w-full rounded-md border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500">
            </label>

            <label class="block">
                <span class="mb-1 block text-[11px] font-semibold text-gray-700">Término <span class="text-red-600">*</span></span>
                <input type="time" name="schedule_end" value="{{ old('schedule_end', $distributionRoute->schedule_end ? substr($distributionRoute->schedule_end, 0, 5) : '15:00') }}" required
                    class="h-9 w-full rounded-md border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500">
            </label>

            <label class="block">
                <span class="mb-1 block text-[11px] font-semibold text-gray-700">Estatus <span class="text-red-600">*</span></span>
                <select name="status" required class="h-9 w-full rounded-md border-gray-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected(old('status', $distributionRoute->status ?: \App\Models\DistributionRoute::STATUS_PENDING) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="mt-3 border-t border-gray-200 pt-3">
            <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex-1">
                    <label for="messenger-search" class="mb-1 block text-[11px] font-semibold text-gray-700">Mensajeros asignados</label>
                    <div class="relative max-w-lg">
                        <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-gray-400" aria-hidden="true"></i>
                        <input id="messenger-search" type="search" x-model="messengerSearch" placeholder="Buscar mensajero..."
                            class="h-9 w-full rounded-md border-gray-300 pl-9 text-xs focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>
                <button type="button" @click="toggleAllMessengers()"
                    class="inline-flex h-8 items-center justify-center rounded-md border border-blue-600 px-3 text-[11px] font-semibold text-blue-700 hover:bg-blue-50">
                    <span x-text="allMessengersSelected() ? 'Quitar selección' : 'Seleccionar todo'"></span>
                </button>
            </div>

            <div class="grid max-h-40 gap-2 overflow-y-auto pr-1 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($messengers as $messenger)
                    @php($messengerLabel = mb_strtolower(trim($messenger->name.' '.$messenger->lastname.' '.$messenger->username)))
                    <label x-show="@js($messengerLabel).includes(messengerSearch.toLowerCase())"
                        class="flex min-h-12 cursor-pointer items-center gap-2 rounded-md border border-gray-200 px-2.5 py-2 hover:border-cyan-300 hover:bg-cyan-50/50">
                        <input type="checkbox" name="messenger_ids[]" value="{{ $messenger->id }}" x-model.number="selectedMessengers"
                            class="rounded border-gray-300 text-cyan-700 focus:ring-cyan-500">
                        <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-[10px] font-bold text-cyan-800">
                            {{ mb_strtoupper(mb_substr($messenger->name, 0, 1).mb_substr($messenger->lastname ?: $messenger->name, 0, 1)) }}
                        </span>
                        <span class="min-w-0">
                            <span class="block truncate text-xs font-semibold text-gray-900">{{ trim($messenger->name.' '.$messenger->lastname) }}</span>
                            <span class="block truncate text-[10px] text-gray-500">{{ '@'.$messenger->username }}</span>
                        </span>
                    </label>
                @empty
                    <p class="text-xs text-gray-500">No hay usuarios internos activos disponibles.</p>
                @endforelse
            </div>
            <p class="mt-1.5 text-[11px] font-medium text-cyan-700"><span x-text="selectedMessengers.length"></span> mensajeros seleccionados</p>
        </div>
    </section>

    <section aria-labelledby="route-hospitals-heading">
        <div class="mb-3 flex flex-col gap-2 border-b border-gray-200 pb-2 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex items-center gap-2">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-blue-700 text-[11px] font-bold text-white">2</span>
                <div>
                    <h2 id="route-hospitals-heading" class="text-sm font-semibold text-gray-950">Hospitales cubiertos</h2>
                    <p class="text-[11px] text-gray-500">Selecciona los hospitales disponibles para esta ruta.</p>
                </div>
            </div>

            <div class="flex flex-col gap-2 sm:flex-row sm:items-end">
                <label class="block sm:w-80">
                    <span class="mb-1 block text-[11px] font-semibold text-gray-700">Buscar hospital</span>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[11px] text-gray-400" aria-hidden="true"></i>
                        <input type="search" x-model="hospitalSearch" placeholder="Hospital, institución o ubicación..."
                            class="h-9 w-full rounded-md border-gray-300 pl-9 text-xs focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </label>
                <button type="button" @click="toggleAllHospitals()"
                    class="inline-flex h-8 items-center justify-center rounded-md border border-blue-600 px-3 text-[11px] font-semibold text-blue-700 hover:bg-blue-50">
                    <span x-text="allHospitalsSelected() ? 'Quitar selección' : 'Seleccionar todo'"></span>
                </button>
            </div>
        </div>

        <div class="max-h-[28rem] overflow-auto rounded-md border border-gray-200">
            <table class="w-full min-w-[1280px] table-fixed text-left text-xs">
                <thead class="sticky top-0 z-10 bg-gray-50 text-[10px] uppercase text-gray-600 shadow-sm">
                    <tr>
                        <th scope="col" class="w-10 px-2 py-2 text-center"><span class="sr-only">Seleccionar</span></th>
                        <th scope="col" class="w-44 px-3 py-2">Nombre del hospital</th>
                        <th scope="col" class="w-44 px-3 py-2">Institución</th>
                        <th scope="col" class="w-36 px-3 py-2">Alcaldía o municipio</th>
                        <th scope="col" class="w-20 px-3 py-2">CP</th>
                        <th scope="col" class="w-28 px-3 py-2">Estado</th>
                        <th scope="col" class="w-64 px-3 py-2">Dirección</th>
                        <th scope="col" class="w-28 px-3 py-2">Disponibilidad</th>
                        <th scope="col" class="w-48 px-3 py-2">Ruta asignada</th>
                        <th scope="col" class="w-24 px-3 py-2 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($hospitals as $hospital)
                        @php
                            $assignment = $hospitalRouteAssignments->get($hospital->id);
                            $institutionNames = $hospital->instituciones->pluck('nombre')->filter()->implode(', ') ?: 'Sin institución';
                            $hospitalAddress = collect([
                                $hospital->adress,
                                $hospital->street_number,
                                $hospital->neighborhood,
                            ])->filter()->implode(', ') ?: 'Sin dirección';
                            $hospitalLabel = mb_strtolower(implode(' ', [
                                $hospital->name,
                                $hospital->short_name,
                                $institutionNames,
                                $hospital->municipality,
                                $hospital->postal_code,
                                $hospital->state,
                                $hospitalAddress,
                            ]));
                        @endphp
                        <tr x-show="@js($hospitalLabel).includes(hospitalSearch.toLowerCase())" class="hover:bg-gray-50">
                            <td class="px-2 py-2 text-center">
                                @if ($assignment)
                                    <i class="fa-solid fa-lock text-gray-500" aria-label="Hospital asignado a otra ruta" title="Hospital asignado a otra ruta"></i>
                                @else
                                    <input type="checkbox" name="hospital_ids[]" value="{{ $hospital->id }}" x-model.number="selectedHospitals"
                                        aria-label="Seleccionar {{ $hospital->short_name ?: $hospital->name }}"
                                        class="rounded border-gray-300 text-blue-700 focus:ring-blue-500">
                                @endif
                            </td>
                            <td class="px-3 py-2 font-semibold text-gray-900">{{ $hospital->short_name ?: $hospital->name }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ $institutionNames }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ $hospital->municipality ?: 'Sin registrar' }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ $hospital->postal_code ?: '-' }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ $hospital->state ?: 'Sin registrar' }}</td>
                            <td class="px-3 py-2 text-gray-700">{{ $hospitalAddress }}</td>
                            <td class="px-3 py-2">
                                @if ($assignment)
                                    <span class="inline-flex items-center gap-1 rounded-md border border-gray-300 bg-gray-50 px-2 py-1 text-[10px] font-semibold text-gray-600">
                                        <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                        Bloqueado
                                    </span>
                                @else
                                    <span class="inline-flex rounded-md border border-green-200 bg-green-50 px-2 py-1 text-[10px] font-semibold text-green-700">Disponible</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                @if ($assignment)
                                    <a href="{{ route('admin.distribution.show', $assignment->id) }}" class="font-medium text-blue-700 hover:underline">
                                        {{ $assignment->name }}@if ($assignment->code) · {{ $assignment->code }}@endif
                                    </a>
                                @else
                                    <span class="text-gray-500">Sin asignar</span>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-center">
                                @if ($assignment)
                                    <a href="{{ route('admin.distribution.show', $assignment->id) }}"
                                        class="inline-flex h-7 items-center justify-center rounded-md border border-blue-600 px-2 text-[10px] font-semibold text-blue-700 hover:bg-blue-50">
                                        Ver ruta
                                    </a>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-sm text-gray-500">No hay hospitales activos disponibles.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <p class="mt-1.5 text-[11px] font-medium text-blue-700"><span x-text="selectedHospitals.length"></span> hospitales seleccionados</p>
    </section>
</div>
