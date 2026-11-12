<x-admin-layout>
    <div class="mx-auto max-w-7xl space-y-6 py-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">Motores de validación</h1>
                <p class="mt-1 text-sm text-slate-600">Reglas deterministas para validar composición y cálculos de mezclas.</p>
            </div>
            @if ($canManage)
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.superadministrator.validation-rules.create', ['engine' => 'composition']) }}" class="rounded-lg bg-indigo-700 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-800">Nueva regla de composición</a>
                <a href="{{ route('admin.superadministrator.validation-rules.create', ['engine' => 'mathematical']) }}" class="rounded-lg bg-teal-700 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-800">Nueva regla matemática</a>
            </div>
            @endif
        </div>

        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            @if ($canManage)
                <strong>Flujo de aprobación:</strong> prepara la regla como borrador, envíala a revisión química y actívala únicamente después de aprobar su contenido.
            @elseif ($canEdit)
                <strong>Edición autorizada:</strong> puedes desactivar una regla activa, editarla y volver a aprobarla cuando quede en revisión. La eliminación permanece reservada al Super Admin.
            @elseif ($canApprove)
                <strong>Revisión sanitaria:</strong> puedes consultar todas las reglas y aprobar aquellas que estén pendientes de revisión. La creación, edición, desactivación y eliminación están reservadas al Super Admin.
            @else
                <strong>Consulta:</strong> puedes revisar la formulación, el estado y el historial de las reglas. La edición, aprobación y administración requieren autorización.
            @endif
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:items-end">
            <nav class="flex flex-wrap gap-2" aria-label="Filtrar reglas">
                <a href="{{ route($indexRouteName, array_filter(['search' => $search])) }}" class="rounded-full px-4 py-2 text-sm {{ !$engine ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-300' }}">Todas</a>
                <a href="{{ route($indexRouteName, array_filter(['engine' => 'composition', 'search' => $search])) }}" class="rounded-full px-4 py-2 text-sm {{ $engine === 'composition' ? 'bg-indigo-700 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-300' }}">Composición</a>
                <a href="{{ route($indexRouteName, array_filter(['engine' => 'mathematical', 'search' => $search])) }}" class="rounded-full px-4 py-2 text-sm {{ $engine === 'mathematical' ? 'bg-teal-700 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-300' }}">Matemáticas</a>
            </nav>

            <form method="GET" action="{{ route($indexRouteName) }}" class="flex w-full gap-2" role="search">
                @if ($engine)
                    <input type="hidden" name="engine" value="{{ $engine }}">
                @endif
                <label for="validation-rule-search" class="sr-only">Buscar reglas</label>
                <div class="relative min-w-0 flex-1">
                    <svg aria-hidden="true" class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.578 3.579a.75.75 0 1 1-1.06 1.06l-3.579-3.578A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                    </svg>
                    <input id="validation-rule-search" name="search" type="search" value="{{ $search }}" placeholder="Buscar regla o código..." class="w-full rounded-lg border border-slate-300 py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                </div>
                <button type="submit" class="rounded-lg bg-slate-900 px-3.5 py-2 text-sm font-semibold text-white hover:bg-slate-800">Buscar</button>
                @if ($search !== '')
                    <a href="{{ route($indexRouteName, array_filter(['engine' => $engine])) }}" class="self-center text-sm font-medium text-slate-600 underline hover:text-slate-900">Limpiar</a>
                @endif
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="px-4 py-3">Regla</th>
                            <th class="px-4 py-3">Motor</th>
                            <th class="px-4 py-3">Población</th>
                            <th class="px-4 py-3">Severidad</th>
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-3 py-3 text-center">Ver</th>
                            @if ($canEdit)<th class="px-3 py-3 text-center">Editar</th>@endif
                            @if ($canApprove)<th class="px-3 py-3 text-center">Aprobar</th>@endif
                            @if ($canEdit)<th class="px-3 py-3 text-center">Desactivar</th>@endif
                            @if ($canManage)<th class="px-3 py-3 text-center">Eliminar</th>@endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rules as $rule)
                            @php
                                $labels = ['composition' => 'Composición', 'mathematical' => 'Matemático', 'adult' => 'Adulto', 'pediatric' => 'Pediátrico', 'both' => 'Ambos', 'blocking' => 'Bloqueo', 'authorization' => 'Autorización', 'advisory' => 'Advertencia', 'information' => 'Información', 'draft' => 'Borrador', 'review' => 'En revisión', 'active' => 'Activa', 'inactive' => 'Inactiva'];
                            @endphp
                            <tr>
                                <td class="px-4 py-4"><div class="font-semibold text-slate-900">{{ $rule->name }}</div><div class="text-xs text-slate-500">{{ $rule->code }} · v{{ $rule->version }}</div></td>
                                <td class="px-4 py-4">{{ $labels[$rule->engine] ?? $rule->engine }}</td>
                                <td class="px-4 py-4">{{ $labels[$rule->population] ?? $rule->population }}</td>
                                <td class="px-4 py-4">{{ $labels[$rule->severity] ?? $rule->severity }}</td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $rule->is_enforced ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">{{ $labels[$rule->status] ?? $rule->status }}</span>
                                    @if ($rule->approved_at)
                                        <div class="mt-2 text-xs text-slate-500" title="{{ $rule->approved_at->format('d/m/Y H:i') }}">
                                            Aprobada por {{ trim(($rule->approver?->name ?? '') . ' ' . ($rule->approver?->lastname ?? '')) ?: 'usuario eliminado' }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-4 text-center">
                                    <a href="{{ route('admin.validation-rules.show', $rule) }}" class="inline-flex rounded-md border border-indigo-200 px-3 py-1.5 font-medium text-indigo-700 hover:bg-indigo-50">Ver</a>
                                </td>
                                @if ($canEdit)
                                    <td class="px-3 py-4 text-center">
                                        @if (!$rule->is_enforced)
                                            <a href="{{ route($editRouteName, $rule) }}" class="inline-flex rounded-md border border-sky-300 bg-sky-50 px-3 py-1.5 font-medium text-sky-800 hover:bg-sky-100">Editar</a>
                                        @else
                                            <button type="button" disabled title="Desactiva la regla para poder editarla" class="cursor-not-allowed rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 font-medium text-slate-400">Editar</button>
                                        @endif
                                    </td>
                                @endif
                                @if ($canApprove)
                                    <td class="px-3 py-4 text-center">
                                        @if ($rule->status === 'review' && !$rule->is_enforced)
                                            <form method="POST" action="{{ route($approveRouteName, $rule) }}" onsubmit="return confirm('¿Confirmas que la regla fue revisada por el área química y deseas activarla?')">@csrf @method('PATCH')<button type="submit" class="rounded-md bg-emerald-700 px-3 py-1.5 font-medium text-white hover:bg-emerald-800">Aprobar</button></form>
                                        @else
                                            <button type="button" disabled title="Solo se pueden aprobar reglas en revisión" class="cursor-not-allowed rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 font-medium text-slate-400">Aprobar</button>
                                        @endif
                                    </td>
                                @endif
                                @if ($canEdit)
                                    <td class="px-3 py-4 text-center">
                                        @if ($rule->is_enforced)
                                            <form method="POST" action="{{ route($deactivateRouteName, $rule) }}" onsubmit="return confirm('¿Desactivar esta regla? Dejará de aplicarse a nuevas validaciones y podrá editarse.')">@csrf @method('PATCH')<button type="submit" class="rounded-md border border-amber-300 bg-amber-50 px-3 py-1.5 font-medium text-amber-800 hover:bg-amber-100">Desactivar</button></form>
                                        @else
                                            <button type="button" disabled title="La regla ya se encuentra inactiva" class="cursor-not-allowed rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 font-medium text-slate-400">Desactivar</button>
                                        @endif
                                    </td>
                                @endif
                                @if ($canManage)
                                    <td class="px-3 py-4 text-center">
                                        @if (!$rule->approved_at)
                                            <form method="POST" action="{{ route('admin.superadministrator.validation-rules.destroy', $rule) }}" onsubmit="return confirm('¿Eliminar definitivamente esta regla? Esta acción no se puede deshacer.')">@csrf @method('DELETE')<button type="submit" class="rounded-md border border-red-300 bg-red-50 px-3 py-1.5 font-medium text-red-700 hover:bg-red-100">Eliminar</button></form>
                                        @else
                                            <button type="button" disabled title="Las reglas aprobadas se conservan como evidencia" class="cursor-not-allowed rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 font-medium text-slate-400">Eliminar</button>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <tr><td colspan="{{ 6 + ($canEdit ? 2 : 0) + ($canManage ? 1 : 0) + ($canApprove ? 1 : 0) }}" class="px-4 py-12 text-center text-slate-500">
                                @if ($search !== '')
                                    No se encontraron reglas para “{{ $search }}”.
                                @else
                                    Aún no hay reglas. Crea el primer borrador para comenzar.
                                @endif
                            </td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($rules->hasPages()) <div class="border-t border-slate-200 px-4 py-3">{{ $rules->links() }}</div> @endif
        </div>
    </div>
</x-admin-layout>
