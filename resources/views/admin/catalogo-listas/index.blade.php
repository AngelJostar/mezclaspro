<x-admin-layout>
    <div class="rounded-xl bg-white p-6 shadow-sm">
        @include('admin.catalogo-listas.partials.section-nav', [
            'categories' => $categories,
            'category' => $category,
            'mode' => $mode,
        ])
    </div>
</x-admin-layout>
