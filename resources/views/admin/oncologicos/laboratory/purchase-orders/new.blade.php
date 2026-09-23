<x-admin-layout>
    <div class="bg-white p-4 shadow-sm md:p-6">
        <h1 class="mb-5 text-2xl font-medium text-gray-900">Compras</h1>
        @include('admin.warehouses.partials.purchase-navigation', [
            'selectedLaboratory' => $laboratory,
            'section' => 'new',
        ])
    </div>
</x-admin-layout>
