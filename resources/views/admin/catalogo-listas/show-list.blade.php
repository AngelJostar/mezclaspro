<x-admin-layout>
    @php $usesMilligrams = in_array($category, ['oncologicos', 'antibioticos'], true); @endphp

    <div class="rounded-xl bg-white p-5 shadow-sm">
        @include('admin.catalogo-listas.partials.section-nav', [
            'categories' => $categories,
            'category' => $category,
            'mode' => $mode,
        ])

        <div class="mt-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-900">{{ $list->name }}</h2>
                <p class="text-sm text-gray-500">Vista de precios por presentacion.</p>
            </div>

            <a href="{{ route('admin.catalogo-listas.lists', ['category' => $category]) }}"
                class="text-sm font-semibold text-blue-700 hover:text-blue-900">
                Volver a listas
            </a>
        </div>

        <dl class="mt-4 grid gap-px overflow-hidden rounded-md border border-gray-200 bg-gray-200 sm:grid-cols-3">
            <div class="bg-white px-4 py-3">
                <dt class="text-[11px] font-bold uppercase text-gray-500">Tipo de lista</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900">
                    {{ $list->is_backup ? 'Almacén de respaldo' : 'Almacén principal' }}
                </dd>
            </div>
            <div class="bg-white px-4 py-3">
                <dt class="text-[11px] font-bold uppercase text-gray-500">Central de mezclas</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $list->laboratory?->nombre ?? '-' }}</dd>
            </div>
            <div class="bg-white px-4 py-3">
                <dt class="text-[11px] font-bold uppercase text-gray-500">Almacén surtidor</dt>
                <dd class="mt-1 text-sm font-semibold text-gray-900">{{ $list->warehouse?->name ?? '-' }}</dd>
                @if ($list->is_backup && $list->primaryWarehouse)
                    <dd class="mt-0.5 text-xs text-gray-500">Principal: {{ $list->primaryWarehouse->name }}</dd>
                @elseif ($list->backup_enabled && $list->backupWarehouse)
                    <dd class="mt-0.5 text-xs text-gray-500">Respaldo: {{ $list->backupWarehouse->name }}</dd>
                @endif
            </div>
        </dl>

        <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200 text-xs">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="whitespace-nowrap px-3 py-2 text-left font-bold uppercase">Producto</th>
                        <th class="whitespace-nowrap px-3 py-2 text-left font-bold uppercase">Presentacion</th>
                        <th class="whitespace-nowrap px-3 py-2 text-right font-bold uppercase">Precio por frasco</th>
                        <th class="whitespace-nowrap px-3 py-2 text-right font-bold uppercase">Precio por {{ $usesMilligrams ? 'miligramo' : 'mililitro' }}</th>
                        @if ($usesMilligrams)
                            <th class="whitespace-nowrap px-3 py-2 text-center font-bold uppercase">Cobrar por</th>
                            <th class="whitespace-nowrap px-3 py-2 text-center font-bold uppercase">IVA desglosado</th>
                        @endif
                    </tr>
                </thead>

                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($items as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="max-w-xs px-3 py-2 font-semibold text-gray-900">
                                {{ $item->product }}
                            </td>
                            <td class="max-w-xs px-3 py-2 text-gray-700">
                                {{ $item->presentation ?: '-' }}
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-700">
                                {{ $item->price_bottle !== null ? '$' . number_format((float) $item->price_bottle, 2) : '-' }}
                            </td>
                            <td class="px-3 py-2 text-right tabular-nums text-gray-700">
                                {{ $item->unit_price !== null ? '$' . number_format((float) $item->unit_price, 4) : '-' }}
                            </td>
                            @if ($usesMilligrams)
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-1 font-semibold text-emerald-700">
                                        {{ $item->charge_by === 'mg' ? 'Miligramo' : 'Frasco' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-center">
                                    <span class="inline-flex rounded-full px-2 py-1 font-semibold {{ $item->vat_breakdown ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                                        {{ $item->vat_breakdown ? 'Si' : 'No' }}
                                    </span>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $usesMilligrams ? 6 : 4 }}" class="px-3 py-8 text-center text-sm text-gray-500">
                                Esta lista no tiene productos capturados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>
