<x-admin-layout>
    <nav class="text-xs text-slate-500" aria-label="Ruta de navegaci&oacute;n">
        <span>Distribuci&oacute;n</span>
        <span class="mx-1" aria-hidden="true">/</span>
        <span class="font-medium text-blue-700">Cat&aacute;logo de mensajeros</span>
    </nav>

    <div class="mt-1 flex flex-col gap-4 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Cat&aacute;logo de mensajeros</h1>
            <p class="mt-1 text-sm text-slate-500">Personal disponible para las rutas de distribuci&oacute;n.</p>
        </div>
        <a href="{{ route('admin.distribution.routes.index') }}"
            class="inline-flex h-10 items-center justify-center gap-2 self-start rounded-md border border-blue-600 bg-white px-4 text-sm font-semibold text-blue-700 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-300 sm:self-auto">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
            <span>Volver al cat&aacute;logo de rutas</span>
        </a>
    </div>

    <section class="mt-5" aria-labelledby="messengers-heading">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h2 id="messengers-heading" class="text-base font-semibold text-slate-900">Mensajeros registrados</h2>
                <p class="mt-1 text-xs text-slate-500">{{ $messengers->total() }} {{ $messengers->total() === 1 ? 'mensajero' : 'mensajeros' }}</p>
            </div>

            <form method="GET" action="{{ route('admin.distribution.messengers.index') }}" class="flex w-full gap-2 lg:w-[34rem]">
                <label for="messenger-search" class="sr-only">Buscar mensajero</label>
                <div class="relative min-w-0 flex-1">
                    <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"></i>
                    <input id="messenger-search" name="search" type="search" value="{{ $search }}"
                        placeholder="Buscar por nombre o usuario..."
                        class="h-10 w-full rounded-md border-slate-300 pl-9 pr-3 text-sm focus:border-blue-600 focus:ring-blue-600">
                </div>
                <button type="submit"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-blue-700 px-4 text-sm font-semibold text-white transition hover:bg-blue-800">
                    <i class="fa-solid fa-filter" aria-hidden="true"></i>
                    <span>Filtrar</span>
                </button>
                @if ($search !== '')
                    <a href="{{ route('admin.distribution.messengers.index') }}"
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-slate-300 bg-white text-slate-600 transition hover:bg-slate-50"
                        title="Limpiar filtro" aria-label="Limpiar filtro">
                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                    </a>
                @endif
            </form>
        </div>

        <div class="mt-3 overflow-x-auto border border-slate-200">
            <table class="min-w-[780px] w-full table-fixed text-left text-sm text-slate-600">
                <thead class="border-b border-slate-300 bg-slate-50 text-xs uppercase text-slate-700">
                    <tr>
                        <th scope="col" class="w-[30%] px-4 py-3">Mensajero</th>
                        <th scope="col" class="w-[20%] px-4 py-3">Usuario</th>
                        <th scope="col" class="w-[22%] px-4 py-3">Central</th>
                        <th scope="col" class="w-[18%] px-4 py-3">Almac&eacute;n</th>
                        <th scope="col" class="w-[10%] px-4 py-3 text-center">Estatus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($messengers as $messenger)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-bold text-blue-700">
                                        {{ mb_strtoupper(mb_substr($messenger->name, 0, 1).mb_substr($messenger->lastname ?? '', 0, 1)) }}
                                    </span>
                                    <span class="font-semibold text-slate-800">{{ trim($messenger->name.' '.$messenger->lastname) }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ $messenger->username }}</td>
                            <td class="px-4 py-3">{{ $messenger->warehouse?->laboratory?->nombre ?? 'Sin central asignada' }}</td>
                            <td class="px-4 py-3">{{ $messenger->warehouse?->name ?? 'Sin almac&eacute;n asignado' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $messenger->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $messenger->is_active ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                    {{ $messenger->is_active ? 'Activo' : 'Baja' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-14 text-center text-sm text-slate-500">
                                <i class="fa-solid fa-motorcycle text-2xl text-slate-300" aria-hidden="true"></i>
                                @if ($search !== '')
                                    <p class="mt-2">No hay mensajeros que coincidan con la b&uacute;squeda.</p>
                                @else
                                    <p class="mt-2">No hay personal con rol Mensajero registrado.</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($messengers->hasPages())
            <div class="mt-4">{{ $messengers->links() }}</div>
        @endif
    </section>
</x-admin-layout>
