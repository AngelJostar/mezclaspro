@props([
    'column',
    'triggerClass',
    'align' => 'left',
    'compact' => false,
])

@php
    $alignmentClass = match ($align) {
        'center' => 'text-center',
        'right' => 'text-right',
        default => 'text-left',
    };
    $filterLabel = trim(strip_tags((string) $slot));
@endphp

<th {{ $attributes->class(['whitespace-nowrap font-bold uppercase', $compact ? 'px-2 py-1' : 'px-3 py-2']) }}>
    <div class="flex items-center gap-2">
        <span class="min-w-0 flex-1 {{ $alignmentClass }}">{{ $slot }}</span>
        <button type="button" data-column="{{ $column }}"
            class="{{ $triggerClass }} inline-flex h-6 w-6 shrink-0 items-center justify-center rounded border border-slate-300 bg-white text-slate-600 transition hover:bg-slate-200 hover:text-slate-800"
            title="Filtrar {{ $filterLabel }}" aria-label="Filtrar {{ $filterLabel }}" aria-expanded="false">
            <span aria-hidden="true" class="text-sm font-black leading-none text-slate-800">&#9660;</span>
        </button>
    </div>
</th>
