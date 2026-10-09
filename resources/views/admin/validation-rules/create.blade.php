<x-admin-layout>
    <div class="mx-auto max-w-5xl py-4">
        <h1 class="text-2xl font-semibold text-slate-900">Nueva regla de {{ $engine === 'mathematical' ? 'cálculo' : 'composición' }}</h1>
        <p class="mt-1 text-sm text-slate-600">Se guardará sin activarse en las solicitudes.</p>
        <form method="POST" action="{{ route('admin.superadministrator.validation-rules.store') }}" class="mt-6">@csrf
            @include('admin.validation-rules._form', ['rule' => null])
        </form>
    </div>
</x-admin-layout>
