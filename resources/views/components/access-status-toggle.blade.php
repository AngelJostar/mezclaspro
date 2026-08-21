@props([
    'type' => 'user',
    'target',
    'isActive' => false,
    'disabled' => false,
    'compact' => false,
    'menu' => false,
])

@php
    $routeName = match ($type) {
        'hospital' => 'admin.users.hospitals.status.update',
        'institution' => 'admin.users.institutions.status.update',
        default => 'admin.users.status.update',
    };
    $nextStatus = ! $isActive;
    $label = $disabled ? 'Sin acceso' : ($isActive ? 'Activo' : 'Bloqueado');
    $targetLabel = match ($type) {
        'hospital' => 'hospital',
        'institution' => 'institucion',
        default => 'usuario',
    };
    $size = $compact ? 'h-7 px-2 text-[11px]' : 'h-8 px-2.5 text-xs';
    $actionLabel = $isActive ? 'Bloquear' : 'Activar';
    $menuId = 'access-status-menu-'.$type.'-'.$target->getKey();
@endphp

@if ($menu)
    <div data-access-status-menu-root {{ $attributes->class('relative inline-flex') }}>
        <button type="button" data-access-status-menu-trigger aria-controls="{{ $menuId }}" aria-expanded="false"
            aria-haspopup="menu" @disabled($disabled)
            class="inline-flex {{ $size }} items-center justify-center gap-1.5 whitespace-nowrap rounded-md border font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-1
                {{ $disabled
                    ? 'cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400'
                    : ($isActive
                        ? 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 focus:ring-emerald-300'
                        : 'border-red-300 bg-red-50 text-red-700 hover:bg-red-100 focus:ring-red-300') }}"
            title="{{ $disabled ? 'No hay accesos registrados' : 'Mostrar opciones de acceso' }}"
            aria-label="{{ $disabled ? 'Sin acceso registrado' : 'Mostrar opciones para '.$label }}">
            <i class="fa-solid {{ $isActive && ! $disabled ? 'fa-lock-open' : 'fa-lock' }}" aria-hidden="true"></i>
            <span>{{ $label }}</span>
            @unless ($disabled)
                <i class="fa-solid fa-chevron-down text-[9px]" aria-hidden="true"></i>
            @endunless
        </button>

        @unless ($disabled)
            <div id="{{ $menuId }}" data-access-status-menu role="menu" hidden
                class="fixed z-[100] min-w-[118px] rounded-md border border-gray-200 bg-white p-1 shadow-lg">
                <form method="POST" action="{{ route($routeName, $target, false) }}" data-access-status-form
                    data-current-active="{{ $isActive ? 'true' : 'false' }}" data-target-label="{{ $targetLabel }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="is_active" value="{{ $nextStatus ? 1 : 0 }}">

                    <button type="submit" role="menuitem"
                        class="inline-flex h-8 w-full items-center justify-start gap-2 whitespace-nowrap rounded px-2.5 text-xs font-semibold transition focus:outline-none focus:ring-2
                            {{ $isActive
                                ? 'text-red-700 hover:bg-red-50 focus:ring-red-200'
                                : 'text-emerald-700 hover:bg-emerald-50 focus:ring-emerald-200' }}">
                        <i class="fa-solid {{ $isActive ? 'fa-lock' : 'fa-lock-open' }}" aria-hidden="true"></i>
                        <span>{{ $actionLabel }}</span>
                    </button>
                </form>
            </div>
        @endunless
    </div>
@else
    <form method="POST" action="{{ route($routeName, $target, false) }}" data-access-status-form
        data-current-active="{{ $isActive ? 'true' : 'false' }}" data-target-label="{{ $targetLabel }}"
        {{ $attributes->class('inline-flex') }}>
        @csrf
        @method('PATCH')
        <input type="hidden" name="is_active" value="{{ $nextStatus ? 1 : 0 }}">

        <button type="submit" @disabled($disabled)
            class="inline-flex {{ $size }} items-center justify-center gap-1.5 whitespace-nowrap rounded-md border font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-1
                {{ $disabled
                    ? 'cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400'
                    : ($isActive
                        ? 'border-emerald-300 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 focus:ring-emerald-300'
                        : 'border-red-300 bg-red-50 text-red-700 hover:bg-red-100 focus:ring-red-300') }}"
            title="{{ $disabled ? 'No hay accesos registrados' : ($isActive ? 'Bloquear acceso' : 'Activar acceso') }}"
            aria-label="{{ $disabled ? 'Sin acceso registrado' : ($isActive ? 'Bloquear acceso' : 'Activar acceso') }}">
            <i class="fa-solid {{ $isActive && ! $disabled ? 'fa-lock-open' : 'fa-lock' }}" aria-hidden="true"></i>
            <span>{{ $label }}</span>
        </button>
    </form>
@endif
