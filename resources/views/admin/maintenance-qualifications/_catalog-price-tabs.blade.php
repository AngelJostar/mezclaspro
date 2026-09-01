@php
    $selectedLaboratoryId = (int) ($selectedLaboratoryId ?? 0);
    $tabQuery = array_filter(['laboratory_id' => $selectedLaboratoryId ?: null]);
    $activeTab = $activeTab ?? 'catalog';
@endphp

@once
    @push('css')
        <style>
            .maintenance-catalog-tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                margin-top: 1rem;
            }

            .maintenance-catalog-tabs a {
                display: inline-flex;
                min-height: 2.5rem;
                align-items: center;
                justify-content: center;
                border-radius: 0.375rem;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                color: #334155;
                padding: 0 1.25rem;
                font-size: 0.875rem;
                font-weight: 700;
                text-decoration: none;
                transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease, box-shadow 160ms ease;
            }

            .maintenance-catalog-tabs a:hover {
                border-color: #94a3b8;
                background: #f8fafc;
            }

            .maintenance-catalog-tabs a.is-active {
                border-color: #047857;
                background: #047857;
                color: #ffffff;
            }

            .maintenance-catalog-tabs a:focus {
                outline: none;
                box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.24);
            }
        </style>
    @endpush
@endonce

<nav class="maintenance-catalog-tabs" aria-label="Catalogo y cotizaciones">
    <a href="{{ route('admin.maintenance-qualifications.catalog', $tabQuery) }}"
        class="{{ $activeTab === 'catalog' ? 'is-active' : '' }}"
        @if ($activeTab === 'catalog') aria-current="page" @endif>
        Cat&aacute;logo
    </a>
    <a href="{{ route('admin.maintenance-qualifications.price-list', $tabQuery) }}"
        class="{{ $activeTab === 'prices' ? 'is-active' : '' }}"
        @if ($activeTab === 'prices') aria-current="page" @endif>
        Cotizaciones
    </a>
</nav>
