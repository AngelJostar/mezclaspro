<x-admin-layout>
    <div class="mt-2 mb-4">
        <h1 class="text-2xl font-medium text-gray-800">Facturacion / Solicitudes</h1>
        <p class="text-sm text-gray-500 mt-1">
            Administra datos de facturacion por institucion y hospital para mezclas oncologicas y solicitudes nutricionales en un solo listado.
        </p>
    </div>

    <div id="billing-toast"
        class="pointer-events-none hidden"
        style="position:fixed; right:20px; bottom:20px; z-index:9999; min-width:260px; max-width:360px; border-radius:14px; border:1px solid #334155; background:#020617; color:#ffffff; padding:14px 16px; box-shadow:0 12px 30px rgba(15,23,42,.32); opacity:0; transform:translateY(8px); transition:opacity .25s ease, transform .25s ease;">
        <div class="flex items-start gap-3">
            <div id="billing-toast-icon" class="mt-0.5 text-sm"></div>
            <div>
                <p id="billing-toast-title" class="text-sm font-semibold"></p>
                <p id="billing-toast-message" class="mt-1 text-sm"></p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5"
        x-data="billingModule(@js($instituciones->mapWithKeys(fn($institucion) => [
            $institucion->id => $institucion->hospitals->map(fn($hospital) => [
                'id' => $hospital->id,
                'name' => $hospital->name,
            ])->values(),
        ])), @js($institucionId), @js($hospitalId))">
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4 mb-6">
            <div class="rounded-2xl border border-slate-100 bg-slate-50 px-5 py-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Total registros</p>
                <p class="mt-2 text-3xl font-semibold text-slate-800">{{ $summary['total'] }}</p>
            </div>

            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 px-5 py-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Con facturacion</p>
                <p class="mt-2 text-3xl font-semibold text-emerald-700">{{ $summary['with_billing'] }}</p>
            </div>

            <div class="rounded-2xl border border-amber-100 bg-amber-50 px-5 py-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">Sin facturacion</p>
                <p class="mt-2 text-3xl font-semibold text-amber-700">{{ $summary['without_billing'] }}</p>
            </div>

            <div class="rounded-2xl border border-blue-100 bg-blue-50 px-5 py-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Conciliables</p>
                <p class="mt-2 text-3xl font-semibold text-blue-700">{{ $summary['conciliables'] }}</p>
            </div>

            <div class="rounded-2xl border border-rose-100 bg-rose-50 px-5 py-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-rose-600">Pendientes</p>
                <p class="mt-2 text-3xl font-semibold text-rose-700">{{ $summary['pendientes'] }}</p>
            </div>
        </div>

        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-end mb-5">
            <a href="{{ route('admin.instituciones.billing.export', array_filter(['institucion_id' => $institucionId, 'hospital_id' => $hospitalId, 'search' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'billing_status' => $billingStatus, 'facturacion_status' => $facturacionStatus, 'conciliable_filter' => $conciliableFilter])) }}"
                class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700">
                <i class="fa-solid fa-file-excel mr-2"></i>
                Exportar a Excel
            </a>
        </div>

        <form method="GET" action="{{ route('admin.instituciones.billing.index') }}"
            class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-9 gap-4 mb-5">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Institucion</label>
                <select name="institucion_id" x-model="selectedInstitucion" @change="handleInstitucionChange()"
                    class="w-full rounded-lg border-slate-200">
                    <option value="">Todas</option>
                    @foreach ($instituciones as $institucion)
                        <option value="{{ $institucion->id }}" @selected((string) $institucionId === (string) $institucion->id)>
                            {{ $institucion->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Hospital</label>
                <select name="hospital_id" x-model="selectedHospital" class="w-full rounded-lg border-slate-200">
                    <option value="">Todos</option>
                    <template x-for="hospital in availableHospitals()" :key="hospital.id">
                        <option :value="String(hospital.id)" x-text="hospital.name"></option>
                    </template>
                </select>
            </div>

            <div class="xl:col-span-2">
                <label class="block text-sm font-medium text-slate-700 mb-1">Buscar</label>
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Paciente, lote o folio..."
                    class="w-full rounded-lg border-slate-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Desde</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                    class="w-full rounded-lg border-slate-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Hasta</label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                    class="w-full rounded-lg border-slate-200">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Facturacion</label>
                <select name="billing_status" class="w-full rounded-lg border-slate-200">
                    <option value="">Todas</option>
                    <option value="con" @selected($billingStatus === 'con')>Con facturacion</option>
                    <option value="sin" @selected($billingStatus === 'sin')>Sin facturacion</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Estatus facturacion</label>
                <select name="facturacion_status" class="w-full rounded-lg border-slate-200">
                    <option value="">Todos</option>
                    <option value="Pendiente" @selected($facturacionStatus === 'Pendiente')>Pendiente</option>
                    <option value="Por facturar" @selected($facturacionStatus === 'Por facturar')>Por facturar</option>
                    <option value="En revisión" @selected($facturacionStatus === 'En revisión')>En revisión</option>
                    <option value="Facturado" @selected($facturacionStatus === 'Facturado')>Facturado</option>
                    <option value="Pagado" @selected($facturacionStatus === 'Pagado')>Pagado</option>
                    <option value="Completado" @selected($facturacionStatus === 'Completado')>Completado</option>
                    <option value="Cancelado" @selected($facturacionStatus === 'Cancelado')>Cancelado</option>
                    <option value="Rechazado" @selected($facturacionStatus === 'Rechazado')>Rechazado</option>
                    <option value="No conciliado" @selected($facturacionStatus === 'No conciliado')>No conciliado</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Conciliable</label>
                <select name="conciliable_filter" class="w-full rounded-lg border-slate-200">
                    <option value="">Todos</option>
                    <option value="Si" @selected($conciliableFilter === 'Si')>Si</option>
                    <option value="No" @selected($conciliableFilter === 'No')>No</option>
                    <option value="Conciliado" @selected($conciliableFilter === 'Conciliado')>Conciliado</option>
                    <option value="No conciliable" @selected($conciliableFilter === 'No conciliable')>No conciliable</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                    class="inline-flex w-full items-center justify-center rounded-lg bg-azul-prodifem px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-800">
                    <i class="fa-solid fa-magnifying-glass mr-2"></i>
                    Filtrar
                </button>
            </div>

            <div class="flex items-end gap-2">
                <a href="{{ route('admin.instituciones.billing.index') }}"
                    class="inline-flex w-full items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    <i class="fa-solid fa-rotate-left mr-2"></i>
                    Limpiar
                </a>
            </div>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-[3200px] w-full text-sm text-left text-slate-600">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-4 py-4">Institucion</th>
                        <th class="px-4 py-4">Unidad</th>
                        <th class="px-4 py-4">Nombre del Medico</th>
                        <th class="px-4 py-4">Nombre del Paciente</th>
                        <th class="px-4 py-4">No. de remision</th>
                        <th class="px-4 py-4">Fecha de Remision</th>
                        <th class="px-4 py-4">Cantidad</th>
                        <th class="px-4 py-4">Descripcion</th>
                        <th class="px-4 py-4">P.V. unitario IVA Incluido</th>
                        <th class="px-4 py-4">P.V. total IVA Incluido</th>
                        <th class="px-4 py-4">Empresa</th>
                        <th class="px-4 py-4">Precio Total</th>
                        <th class="px-4 py-4">Coinciliable</th>
                        <th class="px-4 py-4">Folio Factura UUID</th>
                        <th class="px-4 py-4">Folio Factura Interno</th>
                        <th class="px-4 py-4">Fecha Facturacion</th>
                        <th class="px-4 py-4">Numero Carta Factura</th>
                        <th class="px-4 py-4">Fecha Carta Factura</th>
                        <th class="px-4 py-4">Guardar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $item)
                        @php
                            $record = $item['record'];
                            $hospital = $item['hospital'];
                            $institucionActual = $item['institucion'];
                            $patientName = $item['patient_name'];
                            $fecha = $item['fecha'];
                            $origenTipo = $item['origen_tipo'];
                            $billing = $item['billing'];
                            $formId = 'billing-form-' . $origenTipo . '-' . $record->id;
                        @endphp
                        <tr class="bg-white align-top">
                            <td class="px-4 py-4 min-w-[260px]">
                                <div class="font-medium text-slate-800">{{ $institucionActual?->nombre ?: '—' }}</div>
                                @if ($institucionActual?->razon_social)
                                    <div class="text-xs text-slate-400 mt-1">{{ $institucionActual->razon_social }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-4 min-w-[220px]">{{ $hospital?->name ?: '—' }}</td>
                            <td class="px-4 py-4 min-w-[240px]">{{ $item['medico'] }}</td>
                            <td class="px-4 py-4 min-w-[240px]">{{ $patientName }}</td>
                            <td class="px-4 py-4 min-w-[140px]">{{ $record->remision ?: '—' }}</td>
                            <td class="px-4 py-4 whitespace-nowrap min-w-[180px]">{{ $fecha }}</td>
                            <td class="px-4 py-4 min-w-[120px]">1</td>
                            <td class="px-4 py-4 min-w-[280px]">
                                <div class="font-medium text-slate-700">{{ $item['tipo_texto'] }}</div>
                                <div class="mt-1 text-xs text-slate-400">
                                    ID {{ $record->id }}@if ($record->lote) | Lote: {{ $record->lote }}@endif
                                </div>
                                <div class="mt-2">
                                    <a href="{{ $item['view_route'] }}" class="text-xs font-medium text-blue-600 hover:text-blue-700">
                                        {{ $item['view_label'] }}
                                    </a>
                                </div>
                            </td>
                            <td class="px-4 py-4 min-w-[180px]">{{ $billing?->precio_total ?: '—' }}</td>
                            <td class="px-4 py-4 min-w-[180px]">{{ $billing?->precio_total ?: '—' }}</td>
                            <td class="px-4 py-4 min-w-[240px]">{{ $item['empresa'] }}</td>
                            <td class="px-4 py-3 min-w-[160px]">
                                <input type="text" form="{{ $formId }}" name="precio_total" value="{{ $billing?->precio_total }}"
                                    class="w-full rounded-lg border-slate-200 text-sm">
                            </td>
                            <td class="px-4 py-3 min-w-[210px]">
                                <select form="{{ $formId }}" name="conciliable" class="w-full rounded-lg border-slate-200 text-sm">
                                    <option value="">Selecciona una opcion</option>
                                    <option value="Si" @selected(($billing?->conciliable ?? '') === 'Si')>Si</option>
                                    <option value="No" @selected(($billing?->conciliable ?? '') === 'No')>No</option>
                                    <option value="Conciliado" @selected(($billing?->conciliable ?? '') === 'Conciliado')>Conciliado</option>
                                    <option value="No conciliable" @selected(($billing?->conciliable ?? '') === 'No conciliable')>No conciliable</option>
                                </select>
                            </td>
                            <td class="px-4 py-3 min-w-[240px]">
                                <input type="text" form="{{ $formId }}" name="folio_factura_uuid" value="{{ $billing?->folio_factura_uuid }}"
                                    class="w-full rounded-lg border-slate-200 text-sm">
                            </td>
                            <td class="px-4 py-3 min-w-[200px]">
                                <input type="text" form="{{ $formId }}" name="folio_interno" value="{{ $billing?->folio_interno }}"
                                    class="w-full rounded-lg border-slate-200 text-sm">
                            </td>
                            <td class="px-4 py-3 min-w-[200px]">
                                <input type="text" form="{{ $formId }}" name="fecha_facturacion" value="{{ $billing?->fecha_facturacion }}"
                                    class="w-full rounded-lg border-slate-200 text-sm">
                            </td>
                            <td class="px-4 py-3 min-w-[220px]">
                                <input type="text" form="{{ $formId }}" name="numero_carta_factura" value="{{ $billing?->numero_carta_factura }}"
                                    class="w-full rounded-lg border-slate-200 text-sm">
                            </td>
                            <td class="px-4 py-3 min-w-[200px]">
                                <input type="text" form="{{ $formId }}" name="fecha_carta_factura" value="{{ $billing?->fecha_carta_factura }}"
                                    class="w-full rounded-lg border-slate-200 text-sm">

                                <form id="{{ $formId }}" method="POST" action="{{ route('admin.instituciones.billing.store') }}" class="js-inline-billing-form hidden">
                                    @csrf
                                    <input type="hidden" name="institucion_filter" value="{{ $institucionId }}">
                                    <input type="hidden" name="hospital_filter" value="{{ $hospitalId }}">
                                    <input type="hidden" name="search_filter" value="{{ $search }}">
                                    <input type="hidden" name="date_from_filter" value="{{ $dateFrom }}">
                                    <input type="hidden" name="date_to_filter" value="{{ $dateTo }}">
                                    <input type="hidden" name="billing_status_filter" value="{{ $billingStatus }}">
                                    <input type="hidden" name="facturacion_status_filter" value="{{ $facturacionStatus }}">
                                    <input type="hidden" name="conciliable_filter_value" value="{{ $conciliableFilter }}">
                                    <input type="hidden" name="institucion_id" value="{{ $institucionActual?->id }}">
                                    <input type="hidden" name="hospital_id" value="{{ $hospital?->id }}">
                                    <input type="hidden" name="origen_tipo" value="{{ $origenTipo }}">
                                    <input type="hidden" name="origen_id" value="{{ $record->id }}">
                                    <input type="hidden" name="estatus_facturacion" value="{{ $billing?->estatus_facturacion }}">
                                </form>
                            </td>
                            <td class="px-4 py-4 min-w-[140px]">
                                <button type="button"
                                    class="inline-flex items-center rounded-lg bg-azul-prodifem px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
                                    onclick="submitInlineBilling(document.getElementById('{{ $formId }}'), this)">
                                    <i class="fa-solid fa-floppy-disk mr-2"></i>Guardar
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="19" class="px-5 py-10 text-center text-slate-400">
                                No se encontraron registros para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5 flex flex-col gap-3 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
            <p>
                Mostrando {{ $records->firstItem() ?? 0 }} a {{ $records->lastItem() ?? 0 }} de {{ $records->total() }} registros
            </p>

            <div>
                {{ $records->links() }}
            </div>
        </div>
    </div>

    <script>
        function billingModule(hospitalMap, initialInstitucion, initialHospital) {
            return {
                hospitalMap: hospitalMap || {},
                selectedInstitucion: initialInstitucion ? String(initialInstitucion) : '',
                selectedHospital: initialHospital ? String(initialHospital) : '',
                availableHospitals() {
                    if (!this.selectedInstitucion) {
                        const allHospitals = Object.values(this.hospitalMap || {}).flat();
                        const unique = [];
                        const seen = new Set();

                        allHospitals.forEach((hospital) => {
                            const key = String(hospital.id);
                            if (!seen.has(key)) {
                                seen.add(key);
                                unique.push(hospital);
                            }
                        });

                        return unique.sort((a, b) => a.name.localeCompare(b.name, 'es'));
                    }

                    return this.hospitalMap[this.selectedInstitucion] || [];
                },
                handleInstitucionChange() {
                    const availableIds = this.availableHospitals().map((hospital) => String(hospital.id));
                    if (this.selectedHospital && !availableIds.includes(this.selectedHospital)) {
                        this.selectedHospital = '';
                    }
                },
                init() {
                    this.handleInstitucionChange();
                }
            }
        }

        let billingToastTimer = null;

        function showBillingToast(type, message) {
            const toast = document.getElementById('billing-toast');
            const title = document.getElementById('billing-toast-title');
            const body = document.getElementById('billing-toast-message');
            const icon = document.getElementById('billing-toast-icon');

            if (!toast || !title || !body || !icon) return;

            if (billingToastTimer) {
                window.clearTimeout(billingToastTimer);
            }

            const isSuccess = type === 'success';

            toast.className = 'pointer-events-none';
            toast.style.position = 'fixed';
            toast.style.right = '20px';
            toast.style.bottom = '20px';
            toast.style.top = 'auto';
            toast.style.left = 'auto';
            toast.style.zIndex = '9999';
            toast.style.minWidth = '260px';
            toast.style.maxWidth = '360px';
            toast.style.borderRadius = '14px';
            toast.style.border = '1px solid #334155';
            toast.style.background = '#020617';
            toast.style.color = '#ffffff';
            toast.style.padding = '14px 16px';
            toast.style.boxShadow = '0 12px 30px rgba(15,23,42,.32)';
            toast.style.transition = 'opacity .25s ease, transform .25s ease';

            icon.innerHTML = isSuccess
                ? '<i class="fa-solid fa-circle-check text-emerald-400"></i>'
                : '<i class="fa-solid fa-circle-exclamation text-rose-400"></i>';
            title.textContent = isSuccess ? 'Guardado' : 'No se pudo guardar';
            body.textContent = message;

            toast.classList.remove('hidden');
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';

            billingToastTimer = window.setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(8px)';

                window.setTimeout(() => {
                    toast.classList.add('hidden');
                }, 250);
            }, 2600);
        }

        function submitInlineBilling(form, trigger = null) {
            if (!form || form.dataset.submitting === '1') return;

            const formData = new FormData(form);
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            form.dataset.submitting = '1';

            if (trigger) {
                trigger.disabled = true;
                trigger.classList.add('opacity-70', 'cursor-not-allowed');
            }

            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: formData,
            })
                .then(async (response) => {
                    if (!response.ok) {
                        const payload = await response.json().catch(() => ({}));
                        throw new Error(payload.message || 'No se pudo guardar el cambio.');
                    }

                    return response.json().catch(() => ({}));
                })
                .then(() => {
                    const row = form.closest('tr');
                    if (row) {
                        row.classList.add('bg-emerald-50');
                        window.setTimeout(() => row.classList.remove('bg-emerald-50'), 1000);
                    }

                    showBillingToast('success', 'Los datos de facturacion se guardaron correctamente.');
                })
                .catch((error) => {
                    const row = form.closest('tr');
                    if (row) {
                        row.classList.add('bg-rose-50');
                        window.setTimeout(() => row.classList.remove('bg-rose-50'), 1200);
                    }

                    console.error(error);
                    showBillingToast('error', error.message || 'Ocurrio un error al guardar.');
                })
                .finally(() => {
                    form.dataset.submitting = '0';

                    if (trigger) {
                        trigger.disabled = false;
                        trigger.classList.remove('opacity-70', 'cursor-not-allowed');
                    }
                });
        }
    </script>
</x-admin-layout>
