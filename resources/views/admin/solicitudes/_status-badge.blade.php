@php
    $normalizedStatus = Illuminate\Support\Str::lower(Illuminate\Support\Str::ascii($status ?? 'pendiente'));

    $statusKey = match (true) {
        str_contains($normalizedStatus, 'no_aprob') => 'no_aprobada',
        str_contains($normalizedStatus, 'cancel') => 'cancelada',
        str_contains($normalizedStatus, 'entreg'), str_contains($normalizedStatus, 'finaliz') => 'entregada',
        str_contains($normalizedStatus, 'revis'), str_contains($normalizedStatus, 'inspeccion') => 'revisada',
        str_contains($normalizedStatus, 'prepar'), str_contains($normalizedStatus, 'enproceso') => 'preparada',
        str_contains($normalizedStatus, 'aprob') => 'aprobada',
        default => 'pendiente',
    };

    $statusClass = match ($statusKey) {
        'aprobada' => 'bg-green-100 text-green-700',
        'preparada' => 'bg-blue-100 text-blue-700',
        'revisada' => 'bg-purple-100 text-purple-700',
        'entregada' => 'bg-gray-200 text-gray-700',
        'cancelada' => 'bg-red-100 text-red-700',
        'no_aprobada' => 'bg-red-200 text-red-800',
        default => 'bg-yellow-100 text-yellow-700',
    };

    $statusLabel = match ($statusKey) {
        'aprobada' => 'Aprobada',
        'preparada' => 'Preparada',
        'revisada' => 'Inspeccionada',
        'entregada' => 'Entregada',
        'cancelada' => 'Cancelada',
        'no_aprobada' => 'No aprobada',
        default => 'Pendiente',
    };
@endphp

<span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold leading-5 {{ $statusClass }}">
    {{ $statusLabel }}
</span>
