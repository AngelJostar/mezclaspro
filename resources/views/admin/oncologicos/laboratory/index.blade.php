<x-admin-layout>
    <div class="rounded-lg bg-white p-5 shadow-sm md:p-6">
        @if (session('success'))
            <div class="mb-4 rounded border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($laboratories->isNotEmpty())
            <section class="border-b border-gray-200 pb-4" aria-labelledby="laboratory-carousel-title">
                <div class="mb-3">
                    <h2 id="laboratory-carousel-title" class="text-sm font-semibold text-gray-800">Laboratorios registrados</h2>
                    <p class="text-xs text-gray-500">{{ $laboratories->count() }} {{ $laboratories->count() === 1 ? 'laboratorio disponible' : 'laboratorios disponibles' }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" id="laboratory-carousel-previous"
                        class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-full border border-gray-300 bg-white text-2xl leading-none text-gray-700 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                        title="Laboratorio anterior" aria-label="Laboratorio anterior">
                        <span aria-hidden="true">&lsaquo;</span>
                    </button>

                    <div id="laboratory-carousel" class="flex min-w-0 flex-1 snap-x items-stretch gap-3 overflow-x-auto pb-2 scroll-smooth">
                        <a href="{{ route('admin.oncologicos.laboratory.create') }}"
                            class="inline-flex w-48 flex-none snap-start items-center justify-center gap-2 rounded bg-green-600 px-4 py-4 text-center text-sm font-semibold text-white transition hover:bg-green-700">
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            Nuevo laboratorio
                        </a>

                        @foreach ($laboratories as $laboratory)
                            @php($isSelected = $selectedLaboratory?->id === $laboratory->id)
                            <a href="{{ route('admin.oncologicos.laboratory.index', ['laboratory_id' => $laboratory->id]) }}"
                                class="block w-72 flex-none snap-start rounded border p-4 transition {{ $isSelected ? 'border-cyan-500 bg-cyan-50' : 'border-gray-200 bg-white hover:border-gray-400' }}"
                                @if ($isSelected) aria-current="true" @endif>
                                <div class="flex items-start gap-3">
                                    <span class="inline-flex h-10 w-10 flex-none items-center justify-center rounded bg-blue-50 text-blue-800">
                                        <i class="fa-solid fa-flask-vial" aria-hidden="true"></i>
                                    </span>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-gray-900">{{ $laboratory->nombre }}</span>
                                        <span class="mt-1 block line-clamp-2 text-xs leading-5 text-gray-500">{{ $laboratory->direccion ?: 'Direccion sin registrar' }}</span>
                                    </span>
                                </div>
                                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs font-medium">
                                    <span class="inline-flex items-center gap-1.5 text-cyan-800">
                                        <i class="fa-solid fa-warehouse" aria-hidden="true"></i>
                                        {{ $laboratory->active_warehouses_count }}
                                        {{ $laboratory->active_warehouses_count === 1 ? 'almacen activo' : 'almacenes activos' }}
                                    </span>
                                    <span class="inline-flex items-center gap-1.5 {{ $laboratory->activo ? 'text-green-700' : 'text-red-700' }}">
                                        <span class="h-2 w-2 rounded-full {{ $laboratory->activo ? 'bg-green-500' : 'bg-red-500' }}"></span>
                                        {{ $laboratory->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <button type="button" id="laboratory-carousel-next"
                        class="inline-flex h-10 w-10 flex-none items-center justify-center rounded-full border border-gray-300 bg-white text-2xl leading-none text-gray-700 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                        title="Laboratorio siguiente" aria-label="Laboratorio siguiente">
                        <span aria-hidden="true">&rsaquo;</span>
                    </button>
                </div>
            </section>

            <div class="mt-4 flex justify-end">
                <a href="{{ route('admin.oncologicos.laboratory.edit', $selectedLaboratory) }}"
                    class="inline-flex items-center gap-2 rounded bg-blue-900 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                    Editar informacion
                </a>
            </div>
        @else
            <div class="mt-6 border border-dashed border-gray-300 px-6 py-12 text-center">
                <i class="fa-solid fa-flask-vial text-3xl text-gray-300" aria-hidden="true"></i>
                <p class="mt-3 text-sm font-medium text-gray-700">No hay laboratorios registrados.</p>
                <a href="{{ route('admin.oncologicos.laboratory.create') }}"
                    class="mt-4 inline-flex items-center justify-center gap-2 rounded bg-green-600 px-4 py-2 text-sm font-semibold text-white hover:bg-green-700">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Nuevo laboratorio
                </a>
            </div>
        @endif
    </div>

    @push('js')
        <script>
            (() => {
                const carousel = document.getElementById('laboratory-carousel');
                const previous = document.getElementById('laboratory-carousel-previous');
                const next = document.getElementById('laboratory-carousel-next');

                if (!carousel || !previous || !next) return;

                const updateNavigation = () => {
                    const maximumScroll = Math.max(0, carousel.scrollWidth - carousel.clientWidth);
                    previous.disabled = carousel.scrollLeft <= 1;
                    next.disabled = carousel.scrollLeft >= maximumScroll - 1;
                };

                const move = (direction) => carousel.scrollBy({ left: direction * 300, behavior: 'smooth' });
                previous.addEventListener('click', () => move(-1));
                next.addEventListener('click', () => move(1));
                carousel.addEventListener('scroll', updateNavigation, { passive: true });
                window.addEventListener('resize', updateNavigation);
                requestAnimationFrame(updateNavigation);
            })();
        </script>
    @endpush
</x-admin-layout>
