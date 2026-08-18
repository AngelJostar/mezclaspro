@php
    $hasHospitals = $institucion->hospitals->isNotEmpty();
@endphp

<div data-report-hospital-control data-institution-id="{{ $institucion->id }}"
    data-download-base="{{ $downloadUrl }}" class="flex min-w-[185px] flex-col gap-1.5">
    <button type="button" data-hospital-trigger @disabled(!$hasHospitals)
        aria-expanded="false" aria-controls="hospital-report-selector-popover"
        class="flex h-9 w-full items-center justify-between gap-2 rounded-md border border-slate-300 bg-white px-2.5 text-left text-xs text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
        <span data-hospital-trigger-label class="truncate">
            {{ $hasHospitals ? $institucion->hospitals->count().' hospitales seleccionados' : 'Sin hospitales disponibles' }}
        </span>
        <i class="fa-solid fa-chevron-down shrink-0 text-[10px]"></i>
    </button>

    @if ($hasHospitals)
        <a href="{{ $downloadUrl }}" data-hospital-download
            class="inline-flex w-fit items-center gap-1 font-medium text-blue-700 hover:text-blue-800">
            <i class="fa-solid fa-download text-[11px]"></i>
            Descargar
        </a>
    @else
        <span class="inline-flex items-center gap-1 font-medium text-slate-400" aria-disabled="true">
            <i class="fa-solid fa-download text-[11px]"></i>
            Descargar
        </span>
    @endif
</div>
