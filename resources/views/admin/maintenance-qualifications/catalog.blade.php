<x-admin-layout>
    @php
        $selectedLaboratoryId = (int) ($selectedLaboratory?->id ?? 0);
        $backUrl = route('admin.maintenance-qualifications.index', array_filter([
            'laboratory_id' => $selectedLaboratoryId ?: null,
        ]));
        $storeUrl = route('admin.maintenance-qualifications.catalog.store');
    @endphp

    @push('css')
        <style>
            .service-catalog-back-button,
            .service-catalog-icon-button,
            .service-catalog-close-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex: none;
                border-radius: 0.375rem;
                transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease, box-shadow 160ms ease;
            }

            .service-catalog-back-button {
                width: 2.5rem;
                height: 2.5rem;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                color: #334155;
            }

            .service-catalog-back-button:hover,
            .service-catalog-close-button:hover {
                background: #f8fafc;
                color: #0f172a;
            }

            .service-catalog-action-button,
            .service-catalog-primary-button,
            .service-catalog-secondary-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 2.5rem;
                border-radius: 0.375rem;
                padding: 0 1rem;
                font-size: 0.875rem;
                font-weight: 700;
                line-height: 1;
                transition: background-color 160ms ease, border-color 160ms ease, color 160ms ease, box-shadow 160ms ease;
            }

            .service-catalog-action-button,
            .service-catalog-primary-button {
                border: 1px solid #047857;
                background: #047857;
                color: #ffffff;
            }

            .service-catalog-action-button:hover,
            .service-catalog-primary-button:hover {
                border-color: #065f46;
                background: #065f46;
            }

            .service-catalog-secondary-button {
                border: 1px solid #cbd5e1;
                background: #ffffff;
                color: #334155;
            }

            .service-catalog-secondary-button:hover {
                background: #f8fafc;
            }

            .service-catalog-icon-button,
            .service-catalog-close-button {
                width: 2rem;
                height: 2rem;
                border: 1px solid #cbd5e1;
                background: #ffffff;
            }

            .service-catalog-close-button {
                width: 2.25rem;
                height: 2.25rem;
                color: #334155;
            }

            .service-catalog-icon-button.is-edit {
                border-color: #bfdbfe;
                background: #eff6ff;
                color: #1d4ed8;
            }

            .service-catalog-icon-button.is-edit:hover {
                background: #dbeafe;
                border-color: #93c5fd;
            }

            .service-catalog-icon-button.is-delete {
                border-color: #fecaca;
                background: #fef2f2;
                color: #dc2626;
            }

            .service-catalog-icon-button.is-delete:hover {
                background: #fee2e2;
                border-color: #fca5a5;
            }

            .service-catalog-back-button:focus,
            .service-catalog-action-button:focus,
            .service-catalog-primary-button:focus,
            .service-catalog-secondary-button:focus,
            .service-catalog-icon-button:focus,
            .service-catalog-close-button:focus {
                outline: none;
                box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.24);
            }

            .service-catalog-button-icon {
                width: 1rem;
                height: 1rem;
                display: block;
                stroke: currentColor;
                stroke-width: 2;
                stroke-linecap: round;
                stroke-linejoin: round;
                fill: none;
            }
        </style>
    @endpush

    <nav class="text-xs text-slate-500" aria-label="Ruta de navegaci&oacute;n">
        <span>Administraci&oacute;n</span>
        <span class="mx-1" aria-hidden="true">/</span>
        <a href="{{ $backUrl }}" class="font-medium text-emerald-700 hover:text-emerald-800">
            Mantenimiento y Calificaciones
        </a>
        <span class="mx-1" aria-hidden="true">/</span>
        <span class="font-medium text-slate-700">Catalogo</span>
    </nav>

    <div class="mt-2 flex flex-col gap-4 border-b border-slate-200 pb-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex min-w-0 items-start gap-3">
            <a href="{{ $backUrl }}"
                class="service-catalog-back-button"
                title="Atras" aria-label="Atras">
                <svg class="service-catalog-button-icon" viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M19 12H5"></path>
                    <path d="M12 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div class="min-w-0">
                <h1 class="text-2xl font-semibold text-slate-950">Cat&aacute;logo y cotizaciones</h1>
                <p class="mt-1 max-w-3xl text-sm text-slate-500">
                    Servicios que se deben realizar a la central de mezclas.
                    @if ($selectedLaboratory)
                        <span class="font-semibold text-cyan-800">{{ $selectedLaboratory->nombre }}</span>
                    @else
                        <span class="font-semibold text-cyan-800">Todas las centrales</span>
                    @endif
                </p>
            </div>
        </div>

        <button type="button" data-service-create
            class="service-catalog-action-button gap-2">
            <svg class="service-catalog-button-icon" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 5v14"></path>
                <path d="M5 12h14"></path>
            </svg>
            <span>Agregar nuevo servicio</span>
        </button>
    </div>

    @include('admin.maintenance-qualifications._catalog-price-tabs', [
        'activeTab' => 'catalog',
        'selectedLaboratoryId' => $selectedLaboratoryId,
    ])

    @if ($calendar['error'])
        <div class="mt-4 border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium text-amber-800">
            {{ $calendar['error'] }}
        </div>
    @endif

    <section class="mt-4" aria-labelledby="service-catalog-heading">
        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm"
            data-sticky-x-position="viewport">
            <table class="min-w-[1420px] table-fixed text-left text-xs text-slate-700">
                <thead class="border-b border-slate-300 bg-slate-50 text-[11px] uppercase text-slate-700">
                    <tr>
                        <th scope="col" class="w-16 px-3 py-3">ID</th>
                        <th scope="col" class="w-72 px-3 py-3">Servicio</th>
                        <th scope="col" class="w-44 px-3 py-3">Etapas de calificacion</th>
                        <th scope="col" class="w-36 px-3 py-3">Tipo</th>
                        <th scope="col" class="w-48 px-3 py-3">Areas</th>
                        <th scope="col" class="w-36 px-3 py-3">Frecuencia</th>
                        <th scope="col" class="w-72 px-3 py-3">Identificacion</th>
                        <th scope="col" class="w-40 px-3 py-3">Proveedores</th>
                        <th scope="col" class="w-24 px-3 py-3 text-center">Editar</th>
                        <th scope="col" class="w-24 px-3 py-3 text-center">Eliminar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($services as $service)
                        <tr class="align-top hover:bg-slate-50">
                            <td class="px-3 py-3 font-semibold text-slate-950">{{ $service->id }}</td>
                            <td class="px-3 py-3 font-semibold text-slate-950">{{ $service->service }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $service->qualification_stages ?: '-' }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex rounded border border-emerald-200 bg-emerald-50 px-2 py-1 text-[11px] font-semibold text-emerald-700">
                                    {{ $service->type ?: 'Servicio' }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-slate-600">{{ $service->areas ?: '-' }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $service->frequency ?: '-' }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $service->identification ?: '-' }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $service->providers ?: '-' }}</td>
                            <td class="px-3 py-3 text-center">
                                <button type="button" data-service-edit
                                    data-action="{{ route('admin.maintenance-qualifications.catalog.update', $service) }}"
                                    data-laboratory-id="{{ $service->laboratory_id }}"
                                    data-service="{{ $service->service }}"
                                    data-qualification-stages="{{ $service->qualification_stages }}"
                                    data-type="{{ $service->type }}"
                                    data-areas="{{ $service->areas }}"
                                    data-frequency="{{ $service->frequency }}"
                                    data-identification="{{ $service->identification }}"
                                    data-providers="{{ $service->providers }}"
                                    class="service-catalog-icon-button is-edit"
                                    title="Editar servicio" aria-label="Editar servicio">
                                    <svg class="service-catalog-button-icon" viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M12 20h9"></path>
                                        <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                    </svg>
                                </button>
                            </td>
                            <td class="px-3 py-3 text-center">
                                <form method="POST" action="{{ route('admin.maintenance-qualifications.catalog.destroy', $service) }}"
                                    onsubmit="return confirm('Eliminar este servicio del catalogo?');">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="current_laboratory_id" value="{{ $selectedLaboratoryId ?: '' }}">
                                    <button type="submit"
                                        class="service-catalog-icon-button is-delete"
                                        title="Eliminar servicio" aria-label="Eliminar servicio">
                                        <svg class="service-catalog-button-icon" viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M3 6h18"></path>
                                            <path d="M8 6V4h8v2"></path>
                                            <path d="M19 6l-1 14H6L5 6"></path>
                                            <path d="M10 11v5"></path>
                                            <path d="M14 11v5"></path>
                                        </svg>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-8 text-center text-sm text-slate-500">
                                No hay servicios registrados en el catalogo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

    </section>

    <div id="service-catalog-modal" class="fixed inset-0 z-[80] hidden items-center justify-center bg-slate-950/50 p-4">
        <div class="w-full max-w-3xl rounded-lg bg-white shadow-2xl">
            <form id="service-catalog-form" method="POST" action="{{ $storeUrl }}">
                @csrf
                <input id="service-catalog-method" type="hidden" name="_method" value="PATCH" disabled>
                <input type="hidden" name="current_laboratory_id" value="{{ $selectedLaboratoryId ?: '' }}">

                <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                    <div>
                        <h2 id="service-catalog-modal-title" class="text-lg font-semibold text-slate-950">Nuevo servicio</h2>
                        <p class="text-sm text-slate-500">Completa la informacion del servicio o mantenimiento.</p>
                    </div>
                    <button type="button" data-service-close
                        class="service-catalog-close-button"
                        title="Cerrar" aria-label="Cerrar">
                        <svg class="service-catalog-button-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M18 6 6 18"></path>
                            <path d="M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="grid gap-4 px-5 py-4 md:grid-cols-2">
                    <label class="md:col-span-2">
                        <span class="text-xs font-semibold text-slate-600">Servicio</span>
                        <input id="service-field-service" name="service" required type="text"
                            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </label>

                    <label>
                        <span class="text-xs font-semibold text-slate-600">Central</span>
                        <select id="service-field-laboratory" name="laboratory_id"
                            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                            <option value="">Todas las centrales</option>
                            @foreach ($laboratories as $laboratory)
                                <option value="{{ $laboratory->id }}" @selected($selectedLaboratoryId === (int) $laboratory->id)>
                                    {{ $laboratory->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span class="text-xs font-semibold text-slate-600">Etapas de calificacion</span>
                        <input id="service-field-qualification-stages" name="qualification_stages" type="text"
                            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </label>

                    <label>
                        <span class="text-xs font-semibold text-slate-600">Tipo</span>
                        <input id="service-field-type" name="type" type="text"
                            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </label>

                    <label>
                        <span class="text-xs font-semibold text-slate-600">Areas</span>
                        <input id="service-field-areas" name="areas" type="text"
                            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </label>

                    <label>
                        <span class="text-xs font-semibold text-slate-600">Frecuencia</span>
                        <input id="service-field-frequency" name="frequency" type="text"
                            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </label>

                    <label>
                        <span class="text-xs font-semibold text-slate-600">Proveedores</span>
                        <input id="service-field-providers" name="providers" type="text"
                            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">
                    </label>

                    <label class="md:col-span-2">
                        <span class="text-xs font-semibold text-slate-600">Identificacion</span>
                        <textarea id="service-field-identification" name="identification" rows="3"
                            class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4">
                    <button type="button" data-service-close
                        class="service-catalog-secondary-button">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="service-catalog-primary-button">
                        Guardar servicio
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById('service-catalog-modal');
                const form = document.getElementById('service-catalog-form');
                const method = document.getElementById('service-catalog-method');
                const title = document.getElementById('service-catalog-modal-title');
                const storeUrl = @json($storeUrl);
                const selectedLaboratoryId = @json((string) ($selectedLaboratoryId ?: ''));
                const fields = {
                    laboratory: document.getElementById('service-field-laboratory'),
                    service: document.getElementById('service-field-service'),
                    qualificationStages: document.getElementById('service-field-qualification-stages'),
                    type: document.getElementById('service-field-type'),
                    areas: document.getElementById('service-field-areas'),
                    frequency: document.getElementById('service-field-frequency'),
                    identification: document.getElementById('service-field-identification'),
                    providers: document.getElementById('service-field-providers'),
                };

                const openModal = () => {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    fields.service.focus();
                };

                const closeModal = () => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                };

                document.querySelector('[data-service-create]')?.addEventListener('click', () => {
                    form.reset();
                    form.action = storeUrl;
                    method.disabled = true;
                    title.textContent = 'Nuevo servicio';
                    fields.laboratory.value = selectedLaboratoryId;
                    openModal();
                });

                document.querySelectorAll('[data-service-edit]').forEach((button) => {
                    button.addEventListener('click', () => {
                        form.reset();
                        form.action = button.dataset.action;
                        method.disabled = false;
                        title.textContent = 'Editar servicio';
                        fields.laboratory.value = button.dataset.laboratoryId || '';
                        fields.service.value = button.dataset.service || '';
                        fields.qualificationStages.value = button.dataset.qualificationStages || '';
                        fields.type.value = button.dataset.type || '';
                        fields.areas.value = button.dataset.areas || '';
                        fields.frequency.value = button.dataset.frequency || '';
                        fields.identification.value = button.dataset.identification || '';
                        fields.providers.value = button.dataset.providers || '';
                        openModal();
                    });
                });

                document.querySelectorAll('[data-service-close]').forEach((button) => {
                    button.addEventListener('click', closeModal);
                });

                modal.addEventListener('click', (event) => {
                    if (event.target === modal) {
                        closeModal();
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                        closeModal();
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>
