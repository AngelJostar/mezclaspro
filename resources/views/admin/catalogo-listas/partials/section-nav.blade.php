@props([
    'categories',
    'category',
    'mode' => null,
    'categoryRouteName' => null,
    'categoryRouteQuery' => [],
    'embedded' => false,
])

@php
    $categoryRoute = function ($key) use ($mode, $categoryRouteName, $categoryRouteQuery) {
        if ($categoryRouteName) {
            return route($categoryRouteName, array_merge(
                ['category' => $key],
                $categoryRouteQuery
            ));
        }

        if ($mode === 'catalogo') {
            return route('admin.catalogo-listas.catalog', ['category' => $key]);
        }

        if ($mode === 'listas') {
            return route('admin.catalogo-listas.lists', ['category' => $key]);
        }

        return route('admin.catalogo-listas.index', ['category' => $key]);
    };
@endphp

<div class="mb-5">
    @unless ($embedded)
        <div class="mb-4">
            <h1 class="text-2xl font-bold text-gray-900">Catalogo y listas de precios</h1>
            <p class="mt-1 text-sm text-gray-500">Selecciona una categoria y despues el area de trabajo.</p>
        </div>
    @else
        <p class="mb-2 text-xs font-bold uppercase text-gray-500">Categoría de la lista de respaldo</p>
    @endunless

    <div x-data="{
        moveCategories(direction) {
            this.$refs.categoryCarousel.scrollBy({ left: direction * 210, behavior: 'smooth' });
        }
    }" class="flex max-w-4xl items-center gap-2">
        <button type="button" x-on:click="moveCategories(-1)" title="Categorias anteriores"
            aria-label="Categorias anteriores"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white text-blue-900 shadow-sm transition hover:bg-gray-50">
            <span aria-hidden="true" class="text-lg font-bold leading-none">&lsaquo;</span>
        </button>

        <div x-ref="categoryCarousel" data-disable-sticky-x
            class="category-carousel flex min-w-0 flex-1 justify-start gap-2 overflow-x-auto scroll-smooth py-1"
            style="scrollbar-width: none; -ms-overflow-style: none;">
            @foreach ($categories as $key => $meta)
                @php
                    $isActive = $category === $key;
                    $activeClass = $isActive
                        ? 'border-cyan-500 bg-cyan-50 text-gray-900 shadow-sm ring-1 ring-cyan-300'
                        : 'border-gray-200 bg-white text-gray-700 hover:border-cyan-300 hover:bg-gray-50';
                @endphp

                <a href="{{ $categoryRoute($key) }}"
                    class="flex h-16 w-48 shrink-0 items-center gap-3 rounded-lg border px-4 text-left transition {{ $activeClass }}">
                    <span
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-50 text-xs font-bold {{ $isActive ? 'text-cyan-700' : 'text-gray-500' }}">
                        {{ Str::upper(Str::substr($meta['label'], 0, 1)) }}
                    </span>

                    <span class="min-w-0">
                        <span class="block text-sm font-bold">{{ $meta['label'] }}</span>
                        @if ($isActive)
                            <span class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-cyan-700">
                                <span aria-hidden="true">&check;</span>
                                Seleccionada
                            </span>
                        @endif
                    </span>
                </a>
            @endforeach
        </div>

        <button type="button" x-on:click="moveCategories(1)" title="Categorias siguientes"
            aria-label="Categorias siguientes"
            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white text-blue-900 shadow-sm transition hover:bg-gray-50">
            <span aria-hidden="true" class="text-lg font-bold leading-none">&rsaquo;</span>
        </button>
    </div>

    @unless ($embedded)
        <div class="mt-3 flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.catalogo-listas.catalog', ['category' => $category]) }}"
                class="inline-flex h-9 items-center gap-2 rounded-md border px-4 text-sm font-bold transition {{ $mode === 'catalogo' ? 'border-blue-950 bg-blue-950 text-white shadow-sm' : 'border-blue-900 bg-blue-900 text-white hover:bg-blue-950' }}">
                <span>Catalogo</span>
            </a>

            <a href="{{ route('admin.catalogo-listas.lists', ['category' => $category]) }}"
                class="inline-flex h-9 items-center gap-2 rounded-md border px-4 text-sm font-bold transition {{ $mode === 'listas' ? 'border-teal-700 bg-teal-700 text-white shadow-sm' : 'border-teal-600 bg-teal-600 text-white hover:bg-teal-700' }}">
                <span>Listas de precios</span>
            </a>
        </div>
    @endunless
</div>

<style>
    .category-carousel::-webkit-scrollbar {
        display: none;
    }
</style>
