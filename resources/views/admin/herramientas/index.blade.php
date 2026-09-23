<x-admin-layout>
    @php
        $toolSections = ['reportes', 'conciliacion', 'facturacion', 'pagos'];
        $requestedSection = request()->query('seccion', 'reportes');
        $selectedSection = is_string($requestedSection) && in_array($requestedSection, $toolSections, true)
            ? $requestedSection : 'reportes';
        $toolLinks = [];
        foreach ($toolSections as $section) {
            $toolLinks[$section] = route('admin.herramientas.index', ['seccion' => $section]);
        }
    @endphp

    <h1 class="mb-4 text-2xl font-semibold">Herramientas</h1>

    @include('admin.instituciones.partials.administration-carousel', [
        'administrationSection' => $selectedSection,
        'administrationLinks' => $toolLinks,
        'navigationLabel' => 'Secciones de herramientas',
    ])
</x-admin-layout>
