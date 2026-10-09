<x-admin-layout>
    <div class="mx-auto max-w-5xl py-4">
        <h1 class="text-2xl font-semibold text-slate-900">Editar regla</h1>
        <p class="mt-1 text-sm text-slate-600">Cada guardado aumenta la versión. La regla seguirá sin afectar solicitudes.</p>
        <form method="POST" action="{{ route('admin.superadministrator.validation-rules.update', $validationRule) }}" class="mt-6">@csrf @method('PUT')
            @include('admin.validation-rules._form', ['rule' => $validationRule, 'engine' => $validationRule->engine])
        </form>
    </div>
</x-admin-layout>
