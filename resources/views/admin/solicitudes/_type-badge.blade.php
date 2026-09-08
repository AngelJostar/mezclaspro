@php
    $normalizedType = match ($type ?? null) {
        'nutricionales' => 'nutricionales',
        'antibioticos' => 'antibioticos',
        default => 'oncologicos',
    };

    $typeLabel = match ($normalizedType) {
        'nutricionales' => 'Nutricional',
        'antibioticos' => 'Antibiotico',
        default => 'Oncologica',
    };

    $typeClass = match ($normalizedType) {
        'nutricionales' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'antibioticos' => 'bg-rose-50 text-rose-700 ring-rose-200',
        default => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
    };
@endphp

<span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset {{ $typeClass }}">
    {{ $typeLabel }}
</span>
