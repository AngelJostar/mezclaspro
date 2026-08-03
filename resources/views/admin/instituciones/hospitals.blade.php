<x-admin-layout>
    <div class="mt-2 mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-medium text-gray-800">Hospitales de la institucion</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $institucion->nombre }} | {{ $institucion->razon_social }}
            </p>
        </div>

        <a href="{{ route('admin.instituciones.index') }}" class="text-blue-600 hover:text-blue-800 text-sm">
            ← Volver a instituciones
        </a>
    </div>

    <form action="{{ route('admin.instituciones.hospitals.update', $institucion) }}" method="POST"
        class="bg-white rounded-lg shadow-lg overflow-hidden"
        x-data="hospitalesPicker(@js($hospitals), @js(old('hospitals', $selectedHospitalIds ?? [])))">
        @csrf
        @method('PUT')

        <div class="p-6 pb-4">
            <x-validation-errors class="mb-4" />

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                <div class="border rounded-lg p-3 flex flex-col min-h-0">
                    <div class="mb-3 shrink-0">
                        <x-label class="mb-2">Hospitales disponibles</x-label>
                        <x-input x-model="search" class="w-full" placeholder="Buscar hospital..." />
                    </div>

                    <div class="overflow-y-auto overflow-x-hidden divide-y pr-1"
                        style="height: calc(100vh - 24rem); min-height: 24rem; max-height: 34rem;">
                        <template x-for="hospital in filteredDisponibles()" :key="hospital.id">
                            <div class="flex items-start justify-between py-3 gap-4">
                                <div class="text-gray-800 min-w-0 flex-1">
                                    <div class="font-medium break-words" x-text="hospital.name"></div>
                                    <div class="text-sm text-gray-500 whitespace-normal break-words"
                                        x-text="hospital.adress || 'Sin direccion'"></div>
                                </div>

                                <button type="button"
                                    class="shrink-0 flex h-9 w-9 items-center justify-center text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-semibold rounded-full text-lg leading-none"
                                    title="Vincular hospital"
                                    @click="add(hospital)">
                                    <span aria-hidden="true">+</span>
                                </button>
                            </div>
                        </template>

                        <div x-show="filteredDisponibles().length === 0" class="py-6 text-center text-gray-500">
                            No hay hospitales con ese filtro.
                        </div>
                    </div>
                </div>

                <div class="border rounded-lg p-3 flex flex-col min-h-0">
                    <div class="flex items-center justify-between mb-3 shrink-0 gap-4">
                        <div>
                            <x-label class="mb-1">Hospitales vinculados</x-label>
                            <p class="text-sm text-gray-600">
                                Seleccionados: <span class="font-semibold" x-text="selected.length"></span>
                            </p>
                        </div>

                        <button type="button"
                            class="text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 font-medium rounded-full text-sm px-4 py-2"
                            x-show="selected.length > 0"
                            @click="clearAll()">
                            Limpiar
                        </button>
                    </div>

                    <div class="overflow-y-auto overflow-x-hidden divide-y pr-1"
                        style="height: calc(100vh - 24rem); min-height: 24rem; max-height: 34rem;">
                        <template x-for="hospital in selected" :key="hospital.id">
                            <div class="flex items-start justify-between py-3 gap-4">
                                <div class="text-gray-800 min-w-0 flex-1">
                                    <div class="font-medium break-words" x-text="hospital.name"></div>
                                    <div class="text-sm text-gray-500 whitespace-normal break-words"
                                        x-text="hospital.adress || 'Sin direccion'"></div>
                                </div>

                                <button type="button"
                                    class="shrink-0 flex h-9 w-9 items-center justify-center text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300 font-semibold rounded-full text-lg leading-none"
                                    title="Quitar hospital"
                                    @click="remove(hospital)">
                                    <span aria-hidden="true">−</span>
                                </button>
                            </div>
                        </template>

                        <div x-show="selected.length === 0" class="py-6 text-center text-gray-500">
                            Aun no has vinculado hospitales a esta institucion.
                        </div>
                    </div>

                    <template x-for="id in selectedIds()" :key="'hospital_' + id">
                        <input type="hidden" name="hospitals[]" :value="id">
                    </template>
                </div>
            </div>

            <p class="text-sm text-gray-500 mt-3">
                Aqui controlas que hospitales pertenecen a esta institucion. Los cambios se guardan al final.
            </p>
        </div>

        <div class="sticky bottom-0 border-t bg-white px-6 py-4 flex justify-end shadow-[0_-6px_16px_rgba(15,23,42,0.08)]">
            <x-button>
                Guardar hospitales
            </x-button>
        </div>
    </form>

    <script>
        function hospitalesPicker(hospitals, oldSelectedIds) {
            const oldIds = (oldSelectedIds || []).map(v => Number(v));

            const byId = new Map((hospitals || []).map(h => [Number(h.id), {
                id: Number(h.id),
                name: h.name ?? '',
                adress: h.adress ?? ''
            }]));

            const selectedInitial = oldIds
                .map(id => byId.get(id))
                .filter(Boolean);

            return {
                search: '',
                all: Array.from(byId.values()),
                selected: selectedInitial,

                selectedIds() {
                    return this.selected.map(s => s.id);
                },

                disponibles() {
                    const selected = new Set(this.selectedIds());
                    return this.all.filter(h => !selected.has(h.id));
                },

                filteredDisponibles() {
                    const q = (this.search || '').trim().toLowerCase();
                    const list = this.disponibles();

                    if (!q) return list;

                    return list.filter(h => `${h.name} ${h.adress}`.toLowerCase().includes(q));
                },

                add(hospital) {
                    if (this.selectedIds().includes(hospital.id)) return;
                    this.selected.push(hospital);
                    this.search = '';
                },

                remove(hospital) {
                    this.selected = this.selected.filter(x => x.id !== hospital.id);
                },

                clearAll() {
                    this.selected = [];
                }
            }
        }
    </script>
</x-admin-layout>
