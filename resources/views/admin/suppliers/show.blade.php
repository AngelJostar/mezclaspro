<x-admin-layout>
    @php
        $statusLabels = \App\Models\Supplier::statuses();
        $statusClasses = [
            'active' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            'review' => 'border-amber-200 bg-amber-50 text-amber-700',
            'inactive' => 'border-gray-200 bg-gray-100 text-gray-600',
        ];
    @endphp

    <div class="mx-auto max-w-7xl rounded-lg bg-white p-5 shadow-sm md:p-6">
        <header class="flex flex-col gap-4 border-b border-gray-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <nav class="mb-2 text-xs font-medium text-gray-500">
                    <a href="{{ route('admin.suppliers.index') }}" class="hover:text-blue-700">Proveedores</a>
                    <span class="mx-2 text-gray-300">/</span>
                    <span class="text-blue-700">Ficha</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-950">{{ $supplier->name }}</h1>
                    <span class="inline-flex rounded-md border px-2 py-1 text-xs font-semibold {{ $statusClasses[$supplier->status] ?? $statusClasses['inactive'] }}">
                        {{ $statusLabels[$supplier->status] ?? $supplier->status }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-500">Informacion registrada para ordenes de compra.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.suppliers.index') }}"
                    class="inline-flex h-10 items-center justify-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Catalogo</a>
                <a href="{{ route('admin.suppliers.edit', $supplier) }}"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-blue-800 px-4 text-sm font-semibold text-white hover:bg-blue-900">
                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                    Editar
                </a>
            </div>
        </header>

        <dl class="grid gap-x-8 gap-y-6 py-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['Nombre comercial', $supplier->commercial_name],
                ['RFC', $supplier->rfc],
                ['Giro', $supplier->category],
                ['Subcategoria', $supplier->subcategory],
                ['Comprador asignado', trim(($supplier->assignedBuyer?->name ?? '').' '.($supplier->assignedBuyer?->lastname ?? ''))],
                ['Contacto', $supplier->contact_name],
                ['Telefono', $supplier->phone],
                ['Correo', $supplier->email],
                ['Estado', $supplier->location],
                ['Municipio o alcaldia', $supplier->municipality],
                ['Codigo postal', $supplier->postal_code],
                ['Actualizacion', $supplier->updated_at?->format('d/m/Y H:i')],
                ['Registrado por', trim(($supplier->creator?->name ?? '').' '.($supplier->creator?->lastname ?? ''))],
            ] as [$label, $value])
                <div>
                    <dt class="text-xs font-semibold uppercase text-gray-500">{{ $label }}</dt>
                    <dd class="mt-1 text-sm font-medium text-gray-900">{{ filled($value) ? $value : '—' }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="grid gap-6 border-t border-gray-200 pt-6 md:grid-cols-2">
            <section>
                <h2 class="text-sm font-bold text-gray-950">Direccion</h2>
                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-gray-600">{{ $supplier->address ?: 'Sin direccion registrada.' }}</p>
            </section>
            <section>
                <h2 class="text-sm font-bold text-gray-950">Datos bancarios</h2>
                <dl class="mt-2 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                    @foreach ([
                        ['Banco', $supplier->bank_name],
                        ['Cuenta', $supplier->bank_account],
                        ['CLABE', $supplier->bank_clabe],
                        ['Referencia', $supplier->bank_reference],
                    ] as [$label, $value])
                        <div>
                            <dt class="text-xs font-semibold text-gray-500">{{ $label }}</dt>
                            <dd class="mt-0.5 text-gray-700">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        </div>

        <section class="mt-6 border-t border-gray-200 pt-6">
            <h2 class="text-sm font-bold text-gray-950">Documentacion</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                @foreach ([
                    ['Constancia de situacion fiscal', $supplier->tax_certificate_path],
                    ['Caratula bancaria', $supplier->bank_cover_path],
                    ['Documentacion adicional', $supplier->additional_document_path],
                ] as [$label, $path])
                    <div class="rounded-md border border-gray-200 bg-gray-50 p-3">
                        <p class="text-xs font-semibold text-gray-700">{{ $label }}</p>
                        <p class="mt-1 truncate text-xs text-gray-500">{{ $path ? basename($path) : 'Sin archivo' }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-admin-layout>
