@props(['status' => 'pendiente'])

@php
    $status = $status ?: 'pendiente';

    $classes = match ($status) {
        'pendiente' => 'bg-yellow-100 text-yellow-700',
        'aprobada' => 'bg-green-100 text-green-700',
        'dispensada', 'enproceso', 'preparada', 'revisada' => 'bg-blue-100 text-blue-700',
        'finalizada', 'entregada' => 'bg-gray-200 text-gray-700',
        'cancelada' => 'bg-red-100 text-red-700',
        'no_aprobada', 'no-aprobada' => 'bg-red-200 text-red-800',
        default => 'bg-gray-100 text-gray-700',
    };

    $label = match ($status) {
        'pendiente' => 'Pendiente',
        'aprobada' => 'Aprobada',
        'dispensada' => 'Dispensada',
        'enproceso', 'preparada' => 'Preparada',
        'revisada' => 'Inspeccionada',
        'finalizada', 'entregada' => 'Entregada',
        'cancelada' => 'Cancelada',
        'no_aprobada', 'no-aprobada' => 'No Aprobada',
        default => ucfirst($status),
    };
@endphp

<span {{ $attributes->class("inline-flex rounded-full px-2 py-1 text-xs font-semibold leading-5 {$classes}") }}>
    {{ $label }}
</span>
