<x-admin-layout>
    <div class="mt-2 mb-4"><h1 class="text-2xl font-medium text-gray-800">Panel Administrativo</h1></div>
    @include('admin.instituciones.partials.administration-carousel', ['administrationSection' => 'conciliacion'])
    <section class="hospital-tools">
        <div class="ht-log-heading">
            <h2>Resumen de conciliación · {{ $submission->folio() }}</h2>
            <a class="ht-secondary" href="{{ route('admin.instituciones.reportes', ['seccion' => 'conciliacion']) }}">Volver</a>
        </div>
        @include('admin.instituciones.conciliacion._summary')
    </section>
</x-admin-layout>
