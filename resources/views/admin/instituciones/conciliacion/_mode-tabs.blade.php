<nav class="conciliation-modes" aria-label="Modalidad de conciliación">
    @foreach (['periodo' => 'Por periodo', 'remision' => 'Por remisión'] as $mode => $label)
        <a href="{{ route('admin.instituciones.reportes', ['seccion' => 'conciliacion', 'modalidad' => $mode] + request()->only(['institucion_id', 'hospital_id'])) }}"
            @if ($mode === ($conciliationMode ?? 'remision')) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
