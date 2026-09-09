@props([
    'type' => 'button',
    'variant' => 'primary',
    'icon' => null,
])

@php
    $variants = [
        'primary' => 'bg-azul-prodifem text-white hover:bg-blue-800 focus:ring-blue-300',
        'blue' => 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-300',
        'green' => 'bg-green-600 text-white hover:bg-green-700 focus:ring-green-300',
        'red' => 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-300',
        'yellow' => 'bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-yellow-300',
        'warning' => 'bg-yellow-400 text-gray-900 hover:bg-yellow-500 focus:ring-yellow-300',
        'gray' => 'bg-gray-100 text-gray-700 hover:bg-gray-200 focus:ring-gray-300',
    ];
@endphp

<button type="{{ $type }}"
    {{ $attributes->merge([
        'class' => 'inline-flex items-center justify-center whitespace-nowrap rounded-full px-3 py-2 text-xs font-semibold transition focus:outline-none focus:ring-4 ' . ($variants[$variant] ?? $variants['primary']),
    ]) }}>
    @if ($icon)
        <i class="{{ $icon }} pr-1"></i>
    @endif
    {{ $slot }}
</button>
