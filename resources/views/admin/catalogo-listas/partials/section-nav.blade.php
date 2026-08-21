@props([
    'categories',
    'category',
    'mode' => null,
])

@php
    $categoryRoute = function ($key) use ($mode, $categories) {
        if ($mode === 'catalogo') {
            return route('admin.catalogo-listas.catalog', ['category' => $key]);
        }

        if ($mode === 'listas') {
            if (! ($categories[$key]['supports_lists'] ?? true)) {
                return route('admin.catalogo-listas.catalog', ['category' => $key]);
            }

            return route('admin.catalogo-listas.lists', ['category' => $key]);
        }

        return route('admin.catalogo-listas.index', ['category' => $key]);
    };
@endphp

<div class="mb-5">
    <div class="mb-4">
        <h1 class="text-2xl font-bold text-gray-900">Catalogo y listas de precios</h1>
        <p class="mt-1 text-sm text-gray-500">Selecciona una categoria y despues el area de trabajo.</p>
    </div>

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

        <div x-ref="categoryCarousel"
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

    <div class="mt-3 inline-flex flex-wrap items-center gap-2 rounded-md border border-gray-200 bg-gray-50 p-1"
        role="tablist" aria-label="Area de trabajo">
        <a href="{{ route('admin.catalogo-listas.catalog', ['category' => $category]) }}"
            role="tab" aria-selected="{{ $mode === 'catalogo' ? 'true' : 'false' }}"
            @if ($mode === 'catalogo') aria-current="page" @endif
            class="inline-flex h-9 items-center gap-2 rounded px-4 text-sm font-bold transition {{ $mode === 'catalogo' ? 'bg-blue-950 text-white shadow-sm ring-2 ring-blue-200' : 'border border-gray-300 bg-white text-gray-700 hover:border-blue-400 hover:text-blue-900' }}">
            @if ($mode === 'catalogo')
                <i class="fa-solid fa-check text-xs" aria-hidden="true"></i>
            @endif
            <span>Catalogo</span>
        </a>

        @if ($categories[$category]['supports_lists'] ?? true)
            <a href="{{ route('admin.catalogo-listas.lists', ['category' => $category]) }}"
                role="tab" aria-selected="{{ $mode === 'listas' ? 'true' : 'false' }}"
                @if ($mode === 'listas') aria-current="page" @endif
                class="inline-flex h-9 items-center gap-2 rounded px-4 text-sm font-bold transition {{ $mode === 'listas' ? 'bg-teal-700 text-white shadow-sm ring-2 ring-teal-200' : 'border border-gray-300 bg-white text-gray-700 hover:border-teal-400 hover:text-teal-800' }}">
                @if ($mode === 'listas')
                    <i class="fa-solid fa-check text-xs" aria-hidden="true"></i>
                @endif
                <span>Listas de precios</span>
            </a>
        @endif
    </div>
</div>

<style>
    .category-carousel::-webkit-scrollbar {
        display: none;
    }
</style>
