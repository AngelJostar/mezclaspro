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
            @else
                <strong>Revisión sanitaria:</strong> puedes consultar todas las reglas y aprobar aquellas que estén pendientes de revisión. La creación, edición, desactivación y eliminación están reservadas al Super Admin.
            @endif
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif

        <nav class="flex flex-wrap gap-2" aria-label="Filtrar reglas">
            <a href="{{ route($indexRouteName) }}" class="rounded-full px-4 py-2 text-sm {{ !$engine ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-300' }}">Todas</a>
            <a href="{{ route($indexRouteName, ['engine' => 'composition']) }}" class="rounded-full px-4 py-2 text-sm {{ $engine === 'composition' ? 'bg-indigo-700 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-300' }}">Composición</a>
            <a href="{{ route($indexRouteName, ['engine' => 'mathematical']) }}" class="rounded-full px-4 py-2 text-sm {{ $engine === 'mathematical' ? 'bg-teal-700 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-300' }}">Matemáticas</a>
        </nav>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                        <tr><th class="px-4 py-3">Regla</th><th class="px-4 py-3">Motor</th><th class="px-4 py-3">Población</th><th class="px-4 py-3">Severidad</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3 text-right">Acciones</th></tr>
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
                                <td class="px-4 py-4"><div class="flex justify-end gap-2">
                                    @if ($canManage && !$rule->is_enforced)
                                        <a href="{{ route('admin.superadministrator.validation-rules.edit', $rule) }}" class="rounded-md border border-slate-300 px-3 py-1.5 font-medium text-slate-700 hover:bg-slate-50">Editar</a>
                                    @endif
                                    @if ($canApprove && $rule->status === 'review' && !$rule->is_enforced)
                                        <form method="POST" action="{{ route($approveRouteName, $rule) }}" onsubmit="return confirm('¿Confirmas que la regla fue revisada por el área química y deseas activarla?')">@csrf @method('PATCH')<button type="submit" class="rounded-md bg-emerald-700 px-3 py-1.5 font-medium text-white hover:bg-emerald-800">Aprobar y activar</button></form>
                                    @endif
                                    @if ($canManage && $rule->is_enforced)
                                        <form method="POST" action="{{ route('admin.superadministrator.validation-rules.deactivate', $rule) }}" onsubmit="return confirm('¿Desactivar esta regla? Dejará de aplicarse a nuevas validaciones.')">@csrf @method('PATCH')<button type="submit" class="rounded-md border border-amber-300 px-3 py-1.5 font-medium text-amber-800 hover:bg-amber-50">Desactivar</button></form>
                                    @elseif ($canManage && !$rule->approved_at)
                                        <form method="POST" action="{{ route('admin.superadministrator.validation-rules.destroy', $rule) }}" onsubmit="return confirm('¿Eliminar definitivamente esta regla? Esta acción no se puede deshacer.')">@csrf @method('DELETE')<button type="submit" class="rounded-md border border-red-200 px-3 py-1.5 font-medium text-red-700 hover:bg-red-50">Eliminar</button></form>
                                    @elseif ($canManage)
                                        <span class="self-center text-xs text-slate-500" title="Las reglas aprobadas deben conservarse como evidencia">Protegida</span>
                                    @elseif (!($rule->status === 'review' && !$rule->is_enforced))
                                        <span class="self-center text-xs text-slate-500">Solo consulta</span>
                                    @endif
                                </div></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">Aún no hay reglas. Crea el primer borrador para comenzar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($rules->hasPages()) <div class="border-t border-slate-200 px-4 py-3">{{ $rules->links() }}</div> @endif
        </div>
    </div>
</x-admin-layout>
