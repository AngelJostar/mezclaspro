<x-admin-layout>
    @php
        $sortUrl = fn (string $column) => route('admin.users.index', [
            'status' => $status,
            'laboratory_id' => $centralFilter,
            'sort' => $column,
            'direction' => $sort === $column && $direction === 'asc' ? 'desc' : 'asc',
        ]);
        $sortIcon = fn (string $column) => $sort === $column
            ? ($direction === 'asc' ? 'fa-sort-up' : 'fa-sort-down')
            : 'fa-sort';
    @endphp

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-5 py-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-semibold text-slate-800">
                    Personal y Capacitaciones <span class="font-normal text-slate-400">/ Personal</span>
                </h1>

                <a href="{{ route('admin.users.create') }}"
                    class="inline-flex items-center rounded-md bg-azul-prodifem px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-300">
                    <i class="fa-solid fa-user-plus mr-2" aria-hidden="true"></i>
                    Nuevo personal
                </a>
            </div>

            <section class="mt-4 border-t border-slate-200 pt-4" aria-labelledby="personnel-central-heading">
                <div class="mb-3">
                    <h2 id="personnel-central-heading" class="text-sm font-semibold text-slate-800">
                        Selecciona una central
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-500">Consulta el personal asignado a los almacenes de cada central.</p>
                </div>

                <div class="flex items-center gap-2">
                    <button id="personnel-central-previous" type="button"
                        class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-slate-300 bg-white text-xl text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                        title="Central anterior" aria-label="Central anterior">
                        <span aria-hidden="true">&lsaquo;</span>
                    </button>

                    <div id="personnel-central-carousel"
                        class="flex min-w-0 flex-1 snap-x gap-2 overflow-x-auto scroll-smooth pb-1"
                        data-disable-sticky-x>
                        <a href="{{ route('admin.users.index', ['status' => $status, 'laboratory_id' => 'all']) }}"
                            @class([
                                'flex h-24 w-52 shrink-0 snap-start items-center gap-3 rounded-md border px-3 text-left transition',
                                'border-blue-700 bg-blue-50 text-blue-900 ring-1 ring-blue-700' => $centralFilter === 'all',
                                'border-slate-200 bg-white text-slate-700 hover:border-blue-300 hover:bg-blue-50' => $centralFilter !== 'all',
                            ])
                            @if ($centralFilter === 'all') aria-current="true" @endif>
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded bg-slate-100 text-slate-700">
                                <i class="fa-solid fa-building" aria-hidden="true"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold">Todas las centrales</span>
                                <span class="mt-1 block text-xs text-slate-500">{{ $totalPersonnelCount }} personas</span>
                            </span>
                        </a>

                        @foreach ($laboratories as $laboratory)
                            @php
                                $isSelectedCentral = $centralFilter === (string) $laboratory->id;
                            @endphp
                            <a href="{{ route('admin.users.index', ['status' => $status, 'laboratory_id' => $laboratory->id]) }}"
                                @class([
                                    'flex h-24 w-60 shrink-0 snap-start items-center gap-3 rounded-md border px-3 text-left transition',
                                    'border-cyan-600 bg-cyan-50 text-cyan-950 ring-1 ring-cyan-600' => $isSelectedCentral,
                                    'border-slate-200 bg-white text-slate-700 hover:border-cyan-300 hover:bg-cyan-50' => !$isSelectedCentral,
                                ])
                                @if ($isSelectedCentral) aria-current="true" @endif>
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded bg-cyan-50 text-cyan-800">
                                    <i class="fa-solid fa-flask-vial" aria-hidden="true"></i>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold">{{ $laboratory->nombre }}</span>
                                    <span class="mt-0.5 block line-clamp-2 text-[11px] leading-4 text-slate-500">
                                        {{ $laboratory->direccion ?: ($laboratory->estado ?: 'Sin direcci&oacute;n registrada') }}
                                    </span>
                                    <span class="mt-1 inline-flex items-center gap-1.5 text-xs text-slate-600">
                                        <span class="h-2 w-2 rounded-full {{ $laboratory->activo ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                        {{ (int) ($centralPersonnelCounts[$laboratory->id] ?? 0) }} personas
                                    </span>
                                </span>
                            </a>
                        @endforeach

                        @if ($unassignedCentralPersonnelCount > 0)
                            <a href="{{ route('admin.users.index', ['status' => $status, 'laboratory_id' => 'unassigned']) }}"
                                @class([
                                    'flex h-24 w-52 shrink-0 snap-start items-center gap-3 rounded-md border px-3 text-left transition',
                                    'border-amber-500 bg-amber-50 text-amber-950 ring-1 ring-amber-500' => $centralFilter === 'unassigned',
                                    'border-slate-200 bg-white text-slate-700 hover:border-amber-300 hover:bg-amber-50' => $centralFilter !== 'unassigned',
                                ])
                                @if ($centralFilter === 'unassigned') aria-current="true" @endif>
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded bg-amber-50 text-amber-700">
                                    <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-semibold">Sin central</span>
                                    <span class="mt-1 block text-xs text-slate-500">{{ $unassignedCentralPersonnelCount }} personas</span>
                                </span>
                            </a>
                        @endif
                    </div>

                    <button id="personnel-central-next" type="button"
                        class="grid h-9 w-9 shrink-0 place-items-center rounded-full border border-slate-300 bg-white text-xl text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                        title="Central siguiente" aria-label="Central siguiente">
                        <span aria-hidden="true">&rsaquo;</span>
                    </button>
                </div>
            </section>

            <nav class="mt-4 inline-flex overflow-hidden rounded-md border border-slate-300 bg-white p-1"
                aria-label="Estatus del personal">
                <a href="{{ route('admin.users.index', ['status' => 'contratados', 'laboratory_id' => $centralFilter]) }}"
                    @class([
                        'inline-flex min-w-32 items-center justify-center gap-2 rounded px-4 py-2 text-sm font-semibold transition',
                        'bg-emerald-600 text-white shadow-sm' => $status === 'contratados',
                        'text-slate-600 hover:bg-slate-100' => $status !== 'contratados',
                    ])
                    @if ($status === 'contratados') aria-current="page" @endif>
                    <i class="fa-solid fa-users" aria-hidden="true"></i>
                    Contratados
                    <span @class([
                        'inline-flex min-w-5 items-center justify-center rounded-full px-1.5 text-xs',
                        'bg-white/20 text-white' => $status === 'contratados',
                        'bg-slate-200 text-slate-700' => $status !== 'contratados',
                    ])>{{ $personnelCounts['contratados'] }}</span>
                </a>
                <a href="{{ route('admin.users.index', ['status' => 'bajas', 'laboratory_id' => $centralFilter]) }}"
                    @class([
                        'inline-flex min-w-32 items-center justify-center gap-2 rounded px-4 py-2 text-sm font-semibold transition',
                        'bg-red-600 text-white shadow-sm' => $status === 'bajas',
                        'text-slate-600 hover:bg-slate-100' => $status !== 'bajas',
                    ])
                    @if ($status === 'bajas') aria-current="page" @endif>
                    <i class="fa-solid fa-user-slash" aria-hidden="true"></i>
                    Bajas
                    <span @class([
                        'inline-flex min-w-5 items-center justify-center rounded-full px-1.5 text-xs',
                        'bg-white/20 text-white' => $status === 'bajas',
                        'bg-slate-200 text-slate-700' => $status !== 'bajas',
                    ])>{{ $personnelCounts['bajas'] }}</span>
                </a>
            </nav>
        </div>

        @if (session('status'))
            <div class="mx-5 mt-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mx-5 mt-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                role="alert">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="overflow-x-auto">
            <table class="w-full min-w-[900px] table-fixed text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase text-slate-700">
                    <tr>
                        <th class="w-[25%] px-5 py-3">
                            <a href="{{ $sortUrl('personal') }}"
                                class="inline-flex w-full items-center gap-2 text-left hover:text-blue-700"
                                title="Ordenar por personal" aria-label="Ordenar por personal">
                                <span class="min-w-0 flex-1">Personal</span>
                                <i class="fa-solid {{ $sortIcon('personal') }} {{ $sort === 'personal' ? 'text-blue-700' : 'text-slate-400' }}"
                                    aria-hidden="true"></i>
                            </a>
                        </th>
                        <th class="w-[17%] px-4 py-3">
                            <a href="{{ $sortUrl('usuario') }}"
                                class="inline-flex w-full items-center gap-2 text-left hover:text-blue-700"
                                title="Ordenar por usuario" aria-label="Ordenar por usuario">
                                <span class="min-w-0 flex-1">Usuario</span>
                                <i class="fa-solid {{ $sortIcon('usuario') }} {{ $sort === 'usuario' ? 'text-blue-700' : 'text-slate-400' }}"
                                    aria-hidden="true"></i>
                            </a>
                        </th>
                        <th class="w-[20%] px-4 py-3">
                            <a href="{{ $sortUrl('hospital') }}"
                                class="inline-flex w-full items-center gap-2 text-left hover:text-blue-700"
                                title="Ordenar por hospital" aria-label="Ordenar por hospital">
                                <span class="min-w-0 flex-1">Hospital</span>
                                <i class="fa-solid {{ $sortIcon('hospital') }} {{ $sort === 'hospital' ? 'text-blue-700' : 'text-slate-400' }}"
                                    aria-hidden="true"></i>
                            </a>
                        </th>
                        <th class="w-[16%] px-4 py-3">
                            <a href="{{ $sortUrl('rol') }}"
                                class="inline-flex w-full items-center gap-2 text-left hover:text-blue-700"
                                title="Ordenar por rol" aria-label="Ordenar por rol">
                                <span class="min-w-0 flex-1">Rol</span>
                                <i class="fa-solid {{ $sortIcon('rol') }} {{ $sort === 'rol' ? 'text-blue-700' : 'text-slate-400' }}"
                                    aria-hidden="true"></i>
                            </a>
                        </th>
                        <th class="w-[14%] px-4 py-3 text-center">Estatus laboral</th>
                        <th class="w-[8%] px-4 py-3 text-center">Editar</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        @php
                            $isCurrentUser = auth()->user()->is($user);
                            $isProtectedSuperAdministrator = $user->hasRole('Super Admin') && ! auth()->user()->hasRole('Super Admin');
                            $canDeactivate = $user->is_active && ! $isCurrentUser && ! $isProtectedSuperAdministrator;
                            $fullName = trim($user->name . ' ' . $user->lastname);
                        @endphp
                        <tr class="border-b border-slate-200 bg-white last:border-b-0">
                            <td class="px-5 py-4 font-medium text-slate-900">{{ $fullName }}</td>
                            <td class="px-4 py-4">{{ $user->username }}</td>
                            <td class="px-4 py-4">{{ $user->hospital?->name ?? 'Sin hospital' }}</td>
                            <td class="px-4 py-4">{{ $user->roles->pluck('name')->join(', ') ?: 'Sin rol' }}</td>
                            <td class="px-4 py-4 text-center">
                                @if ($canDeactivate)
                                    <div class="relative inline-block text-left" x-data="{ openStatusMenu: false }"
                                        @click.outside="openStatusMenu = false">
                                        <button type="button" @click="openStatusMenu = !openStatusMenu"
                                            class="inline-flex h-9 min-w-32 items-center justify-center gap-2 rounded-md border border-emerald-300 bg-emerald-50 px-3 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-300"
                                            :aria-expanded="openStatusMenu.toString()">
                                            <span class="size-2 rounded-full bg-emerald-500"></span>
                                            Contratado
                                            <i class="fa-solid fa-chevron-down text-[10px]" aria-hidden="true"></i>
                                        </button>

                                        <div x-cloak x-show="openStatusMenu" x-transition.origin.top
                                            class="absolute right-0 z-20 mt-1 w-36 rounded-md border border-slate-200 bg-white p-1 shadow-lg">
                                            <form action="{{ route('admin.users.deactivate', $user) }}" method="POST"
                                                data-deactivate-personnel-form data-personnel-name="{{ $fullName }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit"
                                                    class="flex w-full items-center rounded px-3 py-2 text-left text-sm font-medium text-red-700 hover:bg-red-50">
                                                    <i class="fa-solid fa-user-slash mr-2" aria-hidden="true"></i>
                                                    Dar de baja
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @elseif ($user->is_active)
                                    <span class="inline-flex h-9 min-w-32 items-center justify-center gap-2 rounded-md border border-emerald-200 bg-emerald-50 px-3 text-xs font-semibold text-emerald-700"
                                        title="{{ $isCurrentUser ? 'No puedes dar de baja tu propia cuenta' : 'Cuenta protegida' }}">
                                        <span class="size-2 rounded-full bg-emerald-500"></span>
                                        Contratado
                                    </span>
                                @else
                                    <span class="inline-flex h-9 min-w-32 items-center justify-center gap-2 rounded-md border border-red-200 bg-red-50 px-3 text-xs font-semibold text-red-700">
                                        <span class="size-2 rounded-full bg-red-500"></span>
                                        Baja
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center">
                                <x-table-action-link href="{{ route('admin.users.edit', $user) }}"
                                    icon="fa-solid fa-pen">
                                    Editar
                                </x-table-action-link>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-slate-500">
                                {{ $status === 'contratados' ? 'No hay personal contratado.' : 'No hay personal dado de baja.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $users->links() }}
            </div>
        @endif
    </section>

    @push('js')
        <script>
            (() => {
                const carousel = document.getElementById('personnel-central-carousel');
                const previous = document.getElementById('personnel-central-previous');
                const next = document.getElementById('personnel-central-next');
                const selectedCentral = carousel?.querySelector('[aria-current="true"]');

                if (!carousel || !previous || !next) return;

                const updateNavigation = () => {
                    const maximumScroll = Math.max(0, carousel.scrollWidth - carousel.clientWidth);
                    previous.disabled = carousel.scrollLeft <= 1;
                    next.disabled = carousel.scrollLeft >= maximumScroll - 1;
                };

                previous.addEventListener('click', () => carousel.scrollBy({ left: -280, behavior: 'smooth' }));
                next.addEventListener('click', () => carousel.scrollBy({ left: 280, behavior: 'smooth' }));
                carousel.addEventListener('scroll', updateNavigation, { passive: true });
                window.addEventListener('resize', updateNavigation);
                requestAnimationFrame(() => {
                    if (selectedCentral) {
                        const centeredPosition = selectedCentral.offsetLeft
                            - carousel.offsetLeft
                            - ((carousel.clientWidth - selectedCentral.offsetWidth) / 2);

                        carousel.scrollLeft = Math.max(0, centeredPosition);
                    }

                    updateNavigation();
                });
            })();

            document.querySelectorAll('[data-deactivate-personnel-form]').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();

                    const result = await Swal.fire({
                        title: '¿Estás seguro?',
                        text: `Se dará de baja a ${form.dataset.personnelName} y se bloquearán todos sus accesos.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí',
                        cancelButtonText: 'No',
                        confirmButtonColor: '#dc2626',
                        cancelButtonColor: '#475569',
                        reverseButtons: true,
                    });

                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>
