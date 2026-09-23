<x-admin-layout>
    <div x-data="{ quotation: null }" @quotation-detail.window="quotation = $event.detail; $refs.detail.showModal()">
        <div class="mt-2 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0">
                <h1 class="text-2xl font-medium text-gray-800">Lista de Cotizaciones</h1>
                @include('admin.solicitudes._type-selector', [
                    'typeSelectorRoute' => 'admin.solicitudes.cotizacion.index',
                    'typeSelectorQuery' => Illuminate\Support\Arr::except($filterQuery, ['tipo', 'page']),
                ])
            </div>
        </div>

        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
        <nav class="flex max-w-full flex-wrap gap-2 pb-1" aria-label="Estado de las cotizaciones">
            @foreach (App\Models\RequestQuotation::STATUS_FILTERS as $key => $label)
                <a href="{{ route('admin.solicitudes.cotizacion.index', array_merge($filterQuery, ['estado' => $key])) }}"
                    @if ($statusFilter === $key) aria-current="page" @endif
                    @class([
                        'flex h-8 shrink-0 items-center justify-center whitespace-nowrap rounded-md border px-4 text-xs font-semibold transition',
                        'border-emerald-600 bg-emerald-600 text-white shadow-sm' => $statusFilter === $key,
                        'border-gray-200 bg-white text-gray-700 hover:border-emerald-300 hover:bg-emerald-50' => $statusFilter !== $key,
                    ])>{{ $label }}</a>
            @endforeach
        </nav>
        <div class="ml-auto flex flex-wrap justify-end gap-2">
            @if (count($createTypes))
                <button type="button" data-quotation-new @disabled($selectedType === 'antibioticos' || ($selectedType !== 'todas' && !in_array($selectedType, $createTypes)))
                    title="{{ $selectedType === 'antibioticos' ? 'Formato de antibioticos pendiente' : 'Nueva cotizacion' }}"
                    class="inline-flex items-center gap-2 rounded-full bg-azul-prodifem px-5 py-2.5 text-xs font-semibold text-white disabled:cursor-not-allowed disabled:opacity-50">
                    <i data-quotation-icon="plus" class="h-4 w-4" aria-hidden="true"></i>Nueva Cotizacion
                </button>
            @endif
            <a href="{{ route('admin.solicitudes.cotizacion.export', $filterQuery) }}" class="inline-flex items-center gap-2 rounded-full bg-green-600 px-5 py-2.5 text-xs font-semibold text-white hover:bg-green-700">
                <i data-quotation-icon="file-spreadsheet" class="h-4 w-4" aria-hidden="true"></i>Exportar a Excel
            </a>
        </div>
        </div>

        <form method="GET" action="{{ route('admin.solicitudes.cotizacion.index') }}"
            class="mt-4 grid grid-cols-1 items-end gap-3 bg-gray-50 p-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-12"
            aria-label="Filtros de cotizaciones" x-data="{ institution: @js((string) ($filters['institucion_id'] ?? '')) }">
            <input type="hidden" name="tipo" value="{{ $selectedType }}">
            <input type="hidden" name="estado" value="{{ $statusFilter }}">
            <input type="hidden" name="orden" value="{{ $sort }}">
            <input type="hidden" name="direccion" value="{{ $sortDirection }}">
            <div class="min-w-0 2xl:col-span-2">
                <label for="quotation-institution" class="mb-1 block text-xs font-medium text-gray-700">Instituci&oacute;n</label>
                <select id="quotation-institution" name="institucion_id" x-model="institution" @change="$refs.hospital.value = ''"
                    class="h-10 w-full min-w-0 rounded-md border-gray-300 text-xs text-gray-700">
                    <option value="">Todas las instituciones</option>
                    @foreach ($institutions as $institution)
                        <option value="{{ $institution->id }}" @selected((string) $filters['institucion_id'] === (string) $institution->id)>{{ $institution->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0 2xl:col-span-2">
                <label for="quotation-hospital" class="mb-1 block text-xs font-medium text-gray-700">Hospital</label>
                <select id="quotation-hospital" name="hospital_id" x-ref="hospital"
                    class="h-10 w-full min-w-0 rounded-md border-gray-300 text-xs text-gray-700">
                    <option value="">Todos los hospitales</option>
                    @foreach ($hospitals as $hospital)
                        <option value="{{ $hospital->id }}" @selected((string) $filters['hospital_id'] === (string) $hospital->id)
                            x-bind:hidden="institution !== '' && !@js($hospital->instituciones->modelKeys()).map(String).includes(institution)"
                            x-bind:disabled="institution !== '' && !@js($hospital->instituciones->modelKeys()).map(String).includes(institution)">{{ $hospital->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid min-w-0 grid-cols-2 gap-2 2xl:col-span-3">
                <div class="min-w-0">
                    <label for="quotation-from" class="mb-1 block text-xs font-medium text-gray-700">Desde</label>
                    <input id="quotation-from" type="date" name="desde" value="{{ $filters['desde'] }}"
                        class="h-10 w-full min-w-0 rounded-md border-gray-300 text-xs text-gray-700">
                </div>
                <div class="min-w-0">
                    <label for="quotation-to" class="mb-1 block text-xs font-medium text-gray-700">Hasta</label>
                    <input id="quotation-to" type="date" name="hasta" value="{{ $filters['hasta'] }}"
                        class="h-10 w-full min-w-0 rounded-md border-gray-300 text-xs text-gray-700">
                </div>
            </div>
            <div class="min-w-0 2xl:col-span-3">
                <label for="quotation-search" class="mb-1 block text-xs font-medium text-gray-700">Buscar</label>
                <input id="quotation-search" type="search" name="buscar" value="{{ $filters['buscar'] }}" maxlength="200"
                    placeholder="Buscar folio o paciente..." class="h-10 w-full min-w-0 rounded-md border-gray-300 text-xs text-gray-700">
            </div>
            <div class="flex min-w-0 flex-wrap items-center gap-3 2xl:col-span-2">
                <button type="submit" class="inline-flex h-10 items-center justify-center rounded-md bg-azul-prodifem px-4 text-xs font-semibold text-white hover:bg-blue-800">Aplicar filtros</button>
                <a href="{{ route('admin.solicitudes.cotizacion.index', ['tipo' => $selectedType]) }}" class="text-xs text-gray-600 underline">Limpiar</a>
            </div>
        </form>
        @if ($errors->any())
            <div role="alert" class="mt-3 text-sm text-red-700">{{ $errors->first() }}</div>
        @endif
        @if (session('quotation_status'))
            <p role="status" class="mt-3 text-sm text-emerald-700">{{ session('quotation_status') }}</p>
        @endif

        <div class="mt-4 overflow-x-auto" data-sticky-x-position="viewport" tabindex="0" role="region" aria-label="Tabla de cotizaciones">
            <table id="request-quotations-table" class="w-full text-left text-sm text-gray-500">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                    <tr>
                        @foreach (['Folio', 'Fecha', 'Instituci&oacute;n', 'Hospital', 'Paciente', 'Vendedor', 'Lista de precios'] as $column)
                            <th scope="col" class="px-2 py-3 text-center" data-force-column-filter>{!! $column !!}</th>
                        @endforeach
                        <th scope="col" class="px-2 py-3 text-center whitespace-nowrap" data-command-column
                            aria-sort="{{ $sort === 'total' ? ($sortDirection === 'asc' ? 'ascending' : 'descending') : 'none' }}">
                            <a class="inline-flex items-center gap-1" href="{{ route('admin.solicitudes.cotizacion.index', array_merge($filterQuery, ['orden' => 'total', 'direccion' => $sort === 'total' && $sortDirection === 'asc' ? 'desc' : 'asc'])) }}"
                                title="Ordenar por total">Total MXN <i data-request-navigation-icon="arrow-up-down" class="h-3 w-3 shrink-0" aria-hidden="true"></i></a>
                        </th>
                        <th scope="col" class="px-2 py-3 text-center" data-force-column-filter>Estado</th>
                        <th scope="col" class="px-2 py-3 text-center" data-command-column>Enviar</th>
                        <th scope="col" class="px-2 py-3 text-center" data-force-column-filter>Autorizaci&oacute;n</th>
                        <th scope="col" class="px-2 py-3 text-center" data-command-column>Detalle</th>
                        <th scope="col" class="px-2 py-3 text-center whitespace-nowrap" data-command-column>Agregar a solicitudes</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($quotations as $quotation)
                        @php
                            $detail = [
                                'folio' => $quotation->folio, 'date' => $quotation->created_at?->format('d/m/Y H:i'),
                                'institution' => $quotation->institution?->nombre, 'hospital' => $quotation->hospital?->name,
                                'patient' => $quotation->patient_name, 'list' => $quotation->price_list_name,
                                'seller' => $quotation->seller_name,
                                'total' => $quotation->total === null ? 'Sin registrar' : '$'.number_format((float) $quotation->total, 2).' MXN',
                                'status' => $quotation->status_label,
                                'clinical' => $quotation->clinical_data,
                                'pricing' => $quotation->pricing_snapshot,
                                'attachment' => $quotation->attachment_path ? route('admin.solicitudes.cotizacion.attachment', $quotation) : null,
                                'authorization' => $quotation->authorized_at
                                    ? trim($quotation->authorizer?->name.' '.$quotation->authorizer?->lastname).' · '.$quotation->authorized_at->format('d/m/Y H:i')
                                    : 'Pendiente',
                            ];
                            $requestUrl = $quotation->request_id ? route($quotation->category === 'nutricionales'
                                ? 'admin.nutricionales.solicitudes.edit' : 'admin.oncologicos.solicitudes.edit', $quotation->request_id) : null;
                        @endphp
                        <tr class="border-b hover:bg-gray-50" data-quotation-row>
                            <td class="px-2 py-3 text-center whitespace-nowrap font-medium text-gray-700">{{ $quotation->folio }}</td>
                            <td class="px-2 py-3 text-center whitespace-nowrap">{{ $quotation->created_at?->format('d/m/Y') }}</td>
                            <td class="min-w-[10rem] max-w-xs break-words px-2 py-3 text-center">{{ $quotation->institution?->nombre ?? 'Sin institucion' }}</td>
                            <td class="min-w-[9rem] max-w-xs break-words px-2 py-3 text-center">{{ $quotation->hospital?->name ?? 'Sin hospital' }}</td>
                            <td class="min-w-[10rem] max-w-xs break-words px-2 py-3 text-center">{{ $quotation->patient_name }}</td>
                            <td class="min-w-[9rem] max-w-xs break-words px-2 py-3 text-center">{{ $quotation->seller_name }}</td>
                            <td class="min-w-[9rem] max-w-xs break-words px-2 py-3 text-center">{{ $quotation->price_list_name }}</td>
                            <td class="px-2 py-3 text-right whitespace-nowrap font-medium text-gray-700">{{ $quotation->total === null ? 'Sin registrar' : '$'.number_format((float) $quotation->total, 2) }}</td>
                            <td class="px-2 py-3 text-center">
                                <span @class([
                                    'inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-3 py-1.5 text-xs font-semibold',
                                    'bg-gray-100 text-gray-600' => $quotation->status === 'borrador',
                                    'bg-amber-100 text-amber-800' => $quotation->status === 'enviada',
                                    'bg-emerald-100 text-emerald-700' => $quotation->status === 'autorizada',
                                    'bg-blue-100 text-blue-700' => $quotation->status === 'preparacion',
                                ])><span aria-hidden="true" class="h-1.5 w-1.5 rounded-full bg-current"></span>{{ $quotation->status_label }}</span>
                            </td>
                            <td class="px-2 py-3 text-center">
                                <button type="button" data-quotation-send="{{ json_encode([
                                    'folio' => $quotation->folio,
                                    'summary' => App\Support\QuotationMessage::summary($quotation),
                                    'url' => route('admin.solicitudes.cotizacion.email', $quotation),
                                ], JSON_THROW_ON_ERROR) }}" aria-label="Enviar {{ $quotation->folio }}"
                                    class="inline-flex items-center gap-1.5 rounded-md border border-emerald-300 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">
                                    <i data-quotation-icon="send" class="h-3.5 w-3.5" aria-hidden="true"></i>Enviar
                                </button>
                            </td>
                            <td class="min-w-[10rem] px-2 py-3 text-center">
                                @if ($quotation->authorized_at)
                                    <span class="block">{{ trim($quotation->authorizer?->name.' '.$quotation->authorizer?->lastname) }}</span>
                                    <span class="mt-1 block text-xs">{{ $quotation->authorized_at->format('d/m/Y H:i') }}</span>
                                @else
                                    <span class="block">Pendiente</span>
                                    @if ($quotation->status === 'enviada' && $quotation->canBeAuthorizedBy(auth()->user()))
                                        <form method="POST" action="{{ route('admin.solicitudes.cotizacion.authorize', $quotation) }}"
                                            class="mt-1" onsubmit="return confirm('¿Autorizar esta cotizacion con el importe mostrado?')">
                                            @csrf
                                            <button type="submit" class="rounded-md border border-emerald-300 px-3 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Autorizar</button>
                                        </form>
                                    @endif
                                @endif
                            </td>
                            <td class="px-2 py-3 text-center">
                                <button type="button" @click="$dispatch('quotation-detail', {{ Illuminate\Support\Js::from($detail) }})"
                                    aria-label="Ver {{ $quotation->folio }}" class="inline-flex rounded-full bg-azul-prodifem px-4 py-2 text-xs font-semibold text-white hover:bg-blue-800">Ver</button>
                                @if ($quotation->clinical_data && $quotation->canBeEditedBy(auth()->user()))
                                    <button type="button" data-quotation-edit="{{ route('admin.solicitudes.cotizacion.show', $quotation) }}"
                                        class="mt-1 rounded-md border px-3 py-1 text-xs font-semibold" aria-label="Editar {{ $quotation->folio }}">Editar</button>
                                @endif
                            </td>
                            <td class="px-2 py-3 text-center">
                                @if ($requestUrl)
                                    <span class="block text-xs text-gray-500">Agregada</span>
                                    <a href="{{ $requestUrl }}" class="mt-1 inline-block text-xs font-semibold text-blue-700 underline">SOL-{{ str_pad((string) $quotation->request_id, 5, '0', STR_PAD_LEFT) }}</a>
                                @else
                                    <span class="text-xs text-gray-500">{{ $quotation->status === 'autorizada' ? 'Por agregar' : 'Pendiente' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($quotations->isEmpty())
            <p role="status" class="px-4 py-10 text-center text-sm text-gray-400">No hay cotizaciones para los filtros seleccionados.</p>
        @endif
        <p class="mt-4 text-xs text-gray-500" role="status">{{ $quotations->count() }} {{ $quotations->count() === 1 ? 'cotizacion' : 'cotizaciones' }}</p>

        <dialog x-ref="detail" aria-labelledby="quotation-detail-title" @click="if ($event.target === $refs.detail) $refs.detail.close()"
            class="m-auto max-h-[90vh] w-[calc(100%-2rem)] max-w-2xl overflow-y-auto rounded-lg border border-gray-200 bg-white p-0 text-gray-800 shadow-xl backdrop:bg-gray-900/50">
            <div class="flex items-center justify-between gap-3 border-b px-5 py-4">
                <h2 id="quotation-detail-title" class="text-lg font-semibold" x-text="'Cotizacion · ' + (quotation?.folio || '')"></h2>
                <button type="button" @click="$refs.detail.close()" aria-label="Cerrar detalle" title="Cerrar" class="grid h-8 w-8 shrink-0 place-items-center rounded-md border border-gray-200 hover:bg-gray-50"><i data-request-navigation-icon="x" class="h-4 w-4" aria-hidden="true"></i></button>
            </div>
            <dl class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2">
                @foreach (['institution' => 'Instituci&oacute;n', 'hospital' => 'Hospital', 'patient' => 'Paciente', 'seller' => 'Vendedor', 'date' => 'Fecha', 'list' => 'Lista de precios', 'total' => 'Total MXN', 'status' => 'Estado', 'authorization' => 'Autorizaci&oacute;n'] as $key => $label)
                    <div class="min-w-0"><dt class="text-xs text-gray-500">{!! $label !!}</dt><dd class="mt-1 break-words text-sm font-medium" x-text="quotation?.{{ $key }} || 'Sin registrar'"></dd></div>
                @endforeach
            </dl>
            <section class="border-t p-5" x-show="Boolean(quotation?.clinical)">
                <h3 class="mb-3 font-semibold">Datos de la mezcla</h3>
                <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    @foreach (['scheduled_date' => 'Programacion', 'service' => 'Servicio', 'floor' => 'Piso', 'bed' => 'Cama', 'record_number' => 'Registro', 'sex' => 'Sexo', 'birth_date' => 'Nacimiento', 'weight_kg' => 'Peso (kg)', 'height_cm' => 'Talla (cm)', 'body_surface_m2' => 'Superficie corporal (m2)', 'diagnosis' => 'Diagnostico', 'administration_route' => 'Via', 'infusion_hours' => 'Infusion (h)', 'infusion_rate' => 'Velocidad (ml/h)', 'overfill_ml' => 'Sobrellenado (ml)', 'volume_total_ml' => 'Volumen total (ml)', 'npt' => 'NPT', 'delivery_at' => 'Entrega', 'delivery_method' => 'Manera de entrega', 'doctor_name' => 'Medico', 'doctor_license' => 'Cedula profesional', 'observations' => 'Observaciones'] as $key => $label)
                        <div x-show="quotation?.clinical?.{{ $key }} != null && quotation?.clinical?.{{ $key }} !== ''" class="min-w-0">
                            <dt class="text-xs text-gray-500">{{ $label }}</dt><dd class="break-words text-sm" x-text="quotation?.clinical?.{{ $key }}"></dd>
                        </div>
                    @endforeach
                </dl>
                <template x-for="(row, index) in (quotation?.clinical?.rows || [])" :key="index">
                    <div class="mt-3 border-t pt-3 text-sm">
                        <p class="font-semibold" x-text="row.product_name + ' · ' + row.presentation_name"></p>
                        <p x-text="'Dosis: ' + row.dose_mg + ' mg · Diluyente: ' + (row.diluent_name || 'Sin diluyente') + ' · Dilucion: ' + row.dilution_ml + ' ml'"></p>
                        <p x-text="'Bolos/dia: ' + row.boluses_per_day + ' · Infusion: ' + row.infusion_minutes + ' min'"></p>
                        <p x-text="'Entregas: ' + row.deliveries.join(', ')"></p>
                    </div>
                </template>
                <template x-for="(item, index) in (quotation?.clinical?.components || [])" :key="index">
                    <p class="mt-2 text-sm" x-text="item.product_name + ' · ' + item.presentation_name + ': ' + item.volume_ml + ' ml'"></p>
                </template>
                <a x-show="Boolean(quotation?.attachment)" :href="quotation?.attachment" class="mt-3 inline-block text-sm text-blue-700 underline">Descargar firma y cedula</a>
            </section>
            <section class="border-t p-5" x-show="Boolean(quotation?.pricing)">
                <h3 class="mb-3 font-semibold">Importes cotizados (MXN)</h3>
                <template x-for="(line, index) in (quotation?.pricing?.lines || [])" :key="index">
                    <div class="flex items-start justify-between gap-4 border-b py-2 text-sm">
                        <div class="min-w-0 break-words"><p x-text="line.description + ' ' + line.presentation"></p>
                            <p class="text-xs text-gray-500" x-text="line.quantity + ' ' + line.unit + ' × ' + line.mixtures + ' · $' + Number(line.unit_price).toFixed(4) + ' · IVA $' + Number(line.vat).toFixed(2)"></p></div>
                        <span class="shrink-0 font-semibold" x-text="'$' + Number(line.total).toFixed(2)"></span>
                    </div>
                </template>
            </section>
            <div class="flex justify-end border-t px-5 py-4"><button type="button" @click="$refs.detail.close()" class="rounded-md bg-azul-prodifem px-5 py-2 text-sm font-semibold text-white hover:bg-blue-800">Cerrar</button></div>
        </dialog>
        @include('admin.solicitudes.quotations._send-modal')
        @if (count($createTypes))
            @include('admin.solicitudes.quotations._capture-modal')
        @endif
    </div>
</x-admin-layout>
