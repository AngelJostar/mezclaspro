<x-admin-layout>
    @php
        $selectedLaboratoryId = (int) ($selectedLaboratory?->id ?? 0);
        $backUrl = route('admin.maintenance-qualifications.index', array_filter([
            'laboratory_id' => $selectedLaboratoryId ?: null,
        ]));
        $currency = fn ($amount): string => $amount === null ? '-' : '$'.number_format((float) $amount, 2);
        $storeQuoteUrl = route('admin.maintenance-qualifications.price-list.quotes.store', ['maintenanceService' => '__SERVICE__']);
    @endphp

    @push('css')
        <style>
            .maintenance-price-page {
                color: #0f172a;
            }

            .maintenance-price-breadcrumb {
                color: #64748b;
                font-size: 0.75rem;
            }

            .maintenance-price-breadcrumb a {
                color: #047857;
                font-weight: 600;
                text-decoration: none;
            }

            .maintenance-price-header {
                display: flex;
                flex-direction: column;
                gap: 1rem;
                margin-top: 0.5rem;
                padding-bottom: 1rem;
                border-bottom: 1px solid #e2e8f0;
            }

            .maintenance-price-heading {
                display: flex;
                min-width: 0;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .maintenance-price-back-button,
            .maintenance-price-icon-button,
            .maintenance-price-close-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: none;
                border-radius: 0.375rem;
                transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease, box-shadow 160ms ease;
            }

            .maintenance-price-back-button {
                width: 2.5rem;
                height: 2.5rem;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                color: #334155;
            }

            .maintenance-price-back-button:hover,
            .maintenance-price-close-button:hover {
                background: #f8fafc;
                color: #0f172a;
            }

            .maintenance-price-title {
                margin: 0;
                font-size: 1.5rem;
                font-weight: 700;
                line-height: 1.15;
            }

            .maintenance-price-subtitle {
                margin-top: 0.25rem;
                color: #64748b;
                font-size: 0.875rem;
            }

            .maintenance-price-section {
                margin-top: 1rem;
            }

            .maintenance-price-table-wrap {
                overflow-x: auto;
                border: 1px solid #e2e8f0;
                border-radius: 0.5rem;
                background: #ffffff;
                box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
            }

            .maintenance-price-table {
                min-width: 1380px;
                width: 100%;
                border-collapse: collapse;
                color: #334155;
                font-size: 0.75rem;
            }

            .maintenance-price-table thead {
                background: #f8fafc;
                border-bottom: 1px solid #cbd5e1;
                color: #0f172a;
                font-size: 0.6875rem;
                text-transform: uppercase;
            }

            .maintenance-price-table th,
            .maintenance-price-table td {
                padding: 0.75rem;
                vertical-align: top;
                border-bottom: 1px solid #e2e8f0;
            }

            .maintenance-price-table tbody tr:hover {
                background: #f8fafc;
            }

            .maintenance-price-service {
                color: #0f172a;
                font-weight: 700;
            }

            .maintenance-price-muted {
                color: #64748b;
            }

            .maintenance-price-quotes {
                display: grid;
                gap: 0.5rem;
            }

            .maintenance-price-quote {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                border: 1px solid #e2e8f0;
                border-radius: 0.375rem;
                background: #ffffff;
                padding: 0.5rem;
            }

            .maintenance-price-quote.is-lowest {
                border-color: #86efac;
                background: #ecfdf5;
            }

            .maintenance-price-quote-main {
                min-width: 0;
            }

            .maintenance-price-quote-main strong,
            .maintenance-price-selected-provider {
                display: block;
                color: #0f172a;
                font-weight: 700;
            }

            .maintenance-price-quote-main span,
            .maintenance-price-selected-price {
                display: block;
                color: #047857;
                font-weight: 800;
            }

            .maintenance-price-quote-main em {
                display: inline-flex;
                margin-top: 0.25rem;
                border-radius: 999px;
                background: #bbf7d0;
                color: #166534;
                padding: 0.125rem 0.5rem;
                font-size: 0.6875rem;
                font-style: normal;
                font-weight: 800;
            }

            .maintenance-price-quote-actions {
                display: flex;
                flex: none;
                gap: 0.25rem;
            }

            .maintenance-price-button {
                display: inline-flex;
                min-height: 2.25rem;
                align-items: center;
                justify-content: center;
                border-radius: 0.375rem;
                border: 1px solid #047857;
                background: #047857;
                color: #ffffff;
                padding: 0 0.75rem;
                font-size: 0.75rem;
                font-weight: 800;
                line-height: 1;
                white-space: nowrap;
            }

            .maintenance-price-button:hover {
                border-color: #065f46;
                background: #065f46;
            }

            .maintenance-price-secondary-button {
                border-color: #cbd5e1;
                background: #ffffff;
                color: #334155;
            }

            .maintenance-price-secondary-button:hover {
                background: #f8fafc;
                border-color: #94a3b8;
            }

            .maintenance-price-icon-button {
                width: 2rem;
                height: 2rem;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                color: #334155;
            }

            .maintenance-price-icon-button.is-edit {
                border-color: #bfdbfe;
                background: #eff6ff;
                color: #1d4ed8;
            }

            .maintenance-price-icon-button.is-edit:hover {
                background: #dbeafe;
                border-color: #93c5fd;
            }

            .maintenance-price-icon-button.is-delete {
                border-color: #fecaca;
                background: #fef2f2;
                color: #dc2626;
            }

            .maintenance-price-icon-button.is-delete:hover {
                background: #fee2e2;
                border-color: #fca5a5;
            }

            .maintenance-price-empty {
                display: inline-flex;
                border-radius: 999px;
                background: #f1f5f9;
                color: #64748b;
                padding: 0.25rem 0.625rem;
                font-size: 0.75rem;
                font-weight: 700;
            }

            .maintenance-quote-modal {
                position: fixed;
                inset: 0;
                z-index: 90;
                display: none;
                align-items: center;
                justify-content: center;
                background: rgba(15, 23, 42, 0.5);
                padding: 1rem;
            }

            .maintenance-quote-modal.is-open {
                display: flex;
            }

            .maintenance-quote-dialog {
                width: 100%;
                max-width: 34rem;
                border-radius: 0.5rem;
                background: #ffffff;
                box-shadow: 0 22px 55px rgba(15, 23, 42, 0.22);
            }

            .maintenance-quote-modal-header,
            .maintenance-quote-modal-footer {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                padding: 1rem 1.25rem;
                border-bottom: 1px solid #e2e8f0;
            }

            .maintenance-quote-modal-footer {
                justify-content: flex-end;
                border-top: 1px solid #e2e8f0;
                border-bottom: 0;
            }

            .maintenance-price-close-button {
                width: 2.25rem;
                height: 2.25rem;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                color: #334155;
            }

            .maintenance-quote-modal-body {
                display: grid;
                gap: 1rem;
                padding: 1rem 1.25rem;
            }

            .maintenance-quote-modal-body label {
                display: grid;
                gap: 0.25rem;
                color: #475569;
                font-size: 0.75rem;
                font-weight: 700;
            }

            .maintenance-quote-modal-body input {
                width: 100%;
                border: 1px solid #cbd5e1;
                border-radius: 0.375rem;
                padding: 0.625rem 0.75rem;
                color: #0f172a;
                font-size: 0.875rem;
            }

            .maintenance-price-back-button:focus,
            .maintenance-price-button:focus,
            .maintenance-price-icon-button:focus,
            .maintenance-price-close-button:focus {
                outline: none;
                box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.24);
            }

            .maintenance-price-icon {
                display: block;
                width: 1rem;
                height: 1rem;
                fill: none;
                stroke: currentColor;
                stroke-linecap: round;
                stroke-linejoin: round;
                stroke-width: 2;
            }
        </style>
    @endpush

    <div class="maintenance-price-page">
        <nav class="maintenance-price-breadcrumb" aria-label="Ruta de navegaci&oacute;n">
            <span>Administraci&oacute;n</span>
            <span aria-hidden="true">/</span>
            <a href="{{ route('admin.maintenance-qualifications.index') }}">Mantenimiento y Calificaciones</a>
            <span aria-hidden="true">/</span>
            <span>Cotizaciones</span>
        </nav>

        <div class="maintenance-price-header">
            <div class="maintenance-price-heading">
                <a href="{{ $backUrl }}" class="maintenance-price-back-button" title="Atras" aria-label="Atras">
                    <svg class="maintenance-price-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M19 12H5"></path>
                        <path d="M12 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="maintenance-price-title">Cat&aacute;logo y cotizaciones</h1>
                    <p class="maintenance-price-subtitle">
                        Cotizaciones de servicios y mantenimientos.
                        @if ($selectedLaboratory)
                            <strong>{{ $selectedLaboratory->nombre }}</strong>
                        @else
                            <strong>Todas las centrales</strong>
                        @endif
                    </p>
                </div>
            </div>

            @include('admin.maintenance-qualifications._catalog-price-tabs', [
                'activeTab' => 'prices',
                'selectedLaboratoryId' => $selectedLaboratoryId,
            ])
        </div>

        @if ($calendar['error'])
            <div class="mt-4 border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
                {{ $calendar['error'] }}
            </div>
        @endif

        <section class="maintenance-price-section" aria-labelledby="maintenance-price-list-heading">
            <div class="maintenance-price-table-wrap">
                <table class="maintenance-price-table">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 4rem;">ID</th>
                            <th scope="col" style="width: 18rem;">Servicio</th>
                            <th scope="col" style="width: 8rem;">Frecuencia</th>
                            <th scope="col" style="width: 12rem;">Areas</th>
                            <th scope="col" style="width: 20rem;">Identificacion</th>
                            <th scope="col" style="width: 23rem;">Cotizaciones</th>
                            <th scope="col" style="width: 12rem;">Proveedor seleccionado</th>
                            <th scope="col" style="width: 10rem; text-align: right;">Precio seleccionado</th>
                            <th scope="col" style="width: 10rem; text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($services as $service)
                            @php
                                $quotes = $service->quotes;
                                $lowestQuote = $quotes->sortBy(fn ($quote) => (float) $quote->price)->first();
                            @endphp
                            <tr>
                                <td class="maintenance-price-service">{{ $service->id }}</td>
                                <td>
                                    <span class="maintenance-price-service">{{ $service->service }}</span>
                                </td>
                                <td>{{ $service->frequency ?: '-' }}</td>
                                <td>{{ $service->areas ?: '-' }}</td>
                                <td class="maintenance-price-muted">{{ $service->identification ?: '-' }}</td>
                                <td>
                                    <div class="maintenance-price-quotes">
                                        @forelse ($quotes as $quote)
                                            @php
                                                $isLowest = $lowestQuote && abs((float) $quote->price - (float) $lowestQuote->price) < 0.01;
                                            @endphp
                                            <div class="maintenance-price-quote {{ $isLowest ? 'is-lowest' : '' }}">
                                                <div class="maintenance-price-quote-main">
                                                    <strong>{{ $quote->supplier_name }}</strong>
                                                    <span>{{ $currency($quote->price) }}</span>
                                                    @if ($isLowest)
                                                        <em>Seleccionado</em>
                                                    @endif
                                                </div>
                                                <div class="maintenance-price-quote-actions">
                                                    <button type="button" data-quote-edit
                                                        data-action="{{ route('admin.maintenance-qualifications.price-list.quotes.update', $quote) }}"
                                                        data-service-name="{{ $service->service }}"
                                                        data-supplier-name="{{ $quote->supplier_name }}"
                                                        data-price="{{ $quote->price }}"
                                                        class="maintenance-price-icon-button is-edit"
                                                        title="Editar cotizacion" aria-label="Editar cotizacion">
                                                        <svg class="maintenance-price-icon" viewBox="0 0 24 24" aria-hidden="true">
                                                            <path d="M12 20h9"></path>
                                                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                                        </svg>
                                                    </button>
                                                    <form method="POST" action="{{ route('admin.maintenance-qualifications.price-list.quotes.destroy', $quote) }}"
                                                        onsubmit="return confirm('Eliminar esta cotizacion?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <input type="hidden" name="current_laboratory_id" value="{{ $selectedLaboratoryId ?: '' }}">
                                                        <button type="submit" class="maintenance-price-icon-button is-delete"
                                                            title="Eliminar cotizacion" aria-label="Eliminar cotizacion">
                                                            <svg class="maintenance-price-icon" viewBox="0 0 24 24" aria-hidden="true">
                                                                <path d="M3 6h18"></path>
                                                                <path d="M8 6V4h8v2"></path>
                                                                <path d="M19 6l-1 14H6L5 6"></path>
                                                                <path d="M10 11v5"></path>
                                                                <path d="M14 11v5"></path>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @empty
                                            <span class="maintenance-price-empty">Sin cotizaciones</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    @if ($lowestQuote)
                                        <span class="maintenance-price-selected-provider">{{ $lowestQuote->supplier_name }}</span>
                                    @else
                                        <span class="maintenance-price-muted">-</span>
                                    @endif
                                </td>
                                <td style="text-align: right;">
                                    @if ($lowestQuote)
                                        <span class="maintenance-price-selected-price">{{ $currency($lowestQuote->price) }}</span>
                                    @else
                                        <span class="maintenance-price-muted">-</span>
                                    @endif
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" data-quote-create
                                        data-service-id="{{ $service->id }}"
                                        data-service-name="{{ $service->service }}"
                                        class="maintenance-price-button">
                                        Agregar cotizaci&oacute;n
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" style="padding: 2rem; text-align: center; color: #64748b;">
                                    No hay servicios registrados en el cat&aacute;logo.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </section>
    </div>

    <div id="maintenance-quote-modal" class="maintenance-quote-modal" aria-hidden="true">
        <div class="maintenance-quote-dialog">
            <form id="maintenance-quote-form" method="POST" action="">
                @csrf
                <input id="maintenance-quote-method" type="hidden" name="_method" value="PATCH" disabled>
                <input type="hidden" name="current_laboratory_id" value="{{ $selectedLaboratoryId ?: '' }}">

                <div class="maintenance-quote-modal-header">
                    <div>
                        <h2 id="maintenance-quote-title" class="maintenance-price-title" style="font-size: 1.125rem;">Nueva cotizaci&oacute;n</h2>
                        <p id="maintenance-quote-service-name" class="maintenance-price-subtitle"></p>
                    </div>
                    <button type="button" data-quote-close class="maintenance-price-close-button" title="Cerrar" aria-label="Cerrar">
                        <svg class="maintenance-price-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M18 6 6 18"></path>
                            <path d="M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="maintenance-quote-modal-body">
                    <label>
                        Proveedor
                        <input id="maintenance-quote-supplier" name="supplier_name" required type="text" maxlength="255">
                    </label>

                    <label>
                        Precio
                        <input id="maintenance-quote-price" name="price" required type="number" min="0" step="0.01" inputmode="decimal">
                    </label>
                </div>

                <div class="maintenance-quote-modal-footer">
                    <button type="button" data-quote-close class="maintenance-price-button maintenance-price-secondary-button">
                        Cancelar
                    </button>
                    <button type="submit" class="maintenance-price-button">
                        Guardar cotizaci&oacute;n
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById('maintenance-quote-modal');
                const form = document.getElementById('maintenance-quote-form');
                const method = document.getElementById('maintenance-quote-method');
                const title = document.getElementById('maintenance-quote-title');
                const serviceName = document.getElementById('maintenance-quote-service-name');
                const supplier = document.getElementById('maintenance-quote-supplier');
                const price = document.getElementById('maintenance-quote-price');
                const storeQuoteUrl = @json($storeQuoteUrl);

                const openModal = () => {
                    modal.classList.add('is-open');
                    modal.setAttribute('aria-hidden', 'false');
                    supplier.focus();
                };

                const closeModal = () => {
                    modal.classList.remove('is-open');
                    modal.setAttribute('aria-hidden', 'true');
                };

                document.querySelectorAll('[data-quote-create]').forEach((button) => {
                    button.addEventListener('click', () => {
                        form.reset();
                        form.action = storeQuoteUrl.replace('__SERVICE__', button.dataset.serviceId);
                        method.disabled = true;
                        title.textContent = 'Nueva cotizacion';
                        serviceName.textContent = button.dataset.serviceName || '';
                        openModal();
                    });
                });

                document.querySelectorAll('[data-quote-edit]').forEach((button) => {
                    button.addEventListener('click', () => {
                        form.reset();
                        form.action = button.dataset.action;
                        method.disabled = false;
                        title.textContent = 'Editar cotizacion';
                        serviceName.textContent = button.dataset.serviceName || '';
                        supplier.value = button.dataset.supplierName || '';
                        price.value = button.dataset.price || '';
                        openModal();
                    });
                });

                document.querySelectorAll('[data-quote-close]').forEach((button) => {
                    button.addEventListener('click', closeModal);
                });

                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal();
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                        closeModal();
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>
