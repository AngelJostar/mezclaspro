@props([
    'user',
    'field',
    'compact' => false,
    'displayValue' => null,
    'emptyLabel' => null,
    'emptyHint' => null,
])

@php
    $configuration = match ($field) {
        'username' => [
            'value' => (string) $user->username,
            'empty' => 'Sin usuario',
            'edit' => 'Editar usuario del software',
            'input' => 'Usuario del software',
            'route' => 'admin.users.username.update',
            'password' => false,
        ],
        'password' => [
            'value' => (string) ($displayValue ?? $user->credential_password ?? ''),
            'empty' => 'Sin contrasena',
            'edit' => 'Editar contrase&ntilde;a del software',
            'input' => 'Contrase&ntilde;a del software',
            'route' => 'admin.users.password.update',
            'password' => true,
        ],
        'training_username' => [
            'value' => (string) ($user->training_username ?? ''),
            'empty' => 'Sin usuario',
            'edit' => 'Editar usuario de capacitaci&oacute;n',
            'input' => 'Usuario de capacitaci&oacute;n',
            'route' => 'admin.users.training-username.update',
            'password' => false,
        ],
        'training_password' => [
            'value' => (string) ($displayValue ?? $user->training_credential_password ?? ''),
            'empty' => 'Sin contrasena',
            'edit' => 'Editar contrase&ntilde;a de capacitaci&oacute;n',
            'input' => 'Contrase&ntilde;a de capacitaci&oacute;n',
            'route' => 'admin.users.training-password.update',
            'password' => true,
        ],
        default => throw new InvalidArgumentException("Campo de credencial no compatible: {$field}"),
    };

    $currentValue = $displayValue !== null ? (string) $displayValue : $configuration['value'];
    $configuration['empty'] = $emptyLabel !== null ? (string) $emptyLabel : $configuration['empty'];
    $isPassword = $configuration['password'];
    $iconSize = $compact ? 'h-7 w-7' : 'h-8 w-8';
@endphp

<form method="POST" action="{{ route($configuration['route'], $user, false) }}"
    data-inline-credential-form data-field="{{ $field }}" data-is-password="{{ $isPassword ? 'true' : 'false' }}"
    data-original-value="{{ $currentValue }}" data-empty-label="{{ $configuration['empty'] }}"
    {{ $attributes->class('min-w-0') }}>
    @csrf
    @method('PATCH')

    <div class="flex min-w-0 items-center gap-1.5" data-inline-display>
        <span data-inline-value
            title="{{ filled($currentValue) ? '' : $emptyHint }}"
            class="{{ $isPassword && filled($currentValue) ? 'min-w-0 flex-1 truncate font-mono font-semibold text-gray-900' : ($isPassword ? 'min-w-0 flex-1 text-[11px] font-medium text-amber-700' : 'min-w-0 flex-1 truncate font-medium text-gray-800') }}">
            {{ filled($currentValue) ? $currentValue : $configuration['empty'] }}
        </span>

        <button type="button" data-inline-start
            class="inline-flex {{ $iconSize }} shrink-0 items-center justify-center rounded-md border border-blue-200 bg-white text-blue-800 shadow-sm transition hover:border-blue-400 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-300"
            title="{!! $configuration['edit'] !!}" aria-label="{!! $configuration['edit'] !!}">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                class="h-3.5 w-3.5" aria-hidden="true">
                <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
                <path d="m15 5 4 4" />
            </svg>
        </button>
    </div>

    <div class="min-w-0" data-inline-editor hidden>
        <div class="flex min-w-0 items-center gap-1.5">
            <input type="text" name="{{ $field }}" value="{{ $currentValue }}"
                data-inline-input aria-label="{!! $configuration['input'] !!}"
                autocomplete="{{ $isPassword ? 'new-password' : 'username' }}" required maxlength="255"
                class="h-8 min-w-28 flex-1 rounded-md border border-blue-300 px-2 text-xs text-gray-900 shadow-sm focus:border-blue-600 focus:ring-blue-600">

            <button type="submit" data-inline-submit
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-emerald-600 text-white transition hover:bg-emerald-700 disabled:cursor-wait disabled:opacity-60"
                title="Guardar" aria-label="Guardar">
                <svg data-inline-submit-icon xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                    stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4" aria-hidden="true">
                    <path d="m5 12 4 4L19 6" />
                </svg>
                <svg data-inline-submit-spinner xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 animate-spin" aria-hidden="true"
                    hidden>
                    <path d="M21 12a9 9 0 1 1-6.219-8.56" />
                </svg>
            </button>
            <button type="button" data-inline-cancel
                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md border border-red-600 bg-red-600 text-white transition hover:border-red-700 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-300"
                title="Cerrar" aria-label="Cerrar edici&oacute;n">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                    fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                    stroke-linejoin="round" class="h-4 w-4" aria-hidden="true">
                    <path d="M18 6 6 18" />
                    <path d="m6 6 12 12" />
                </svg>
            </button>
        </div>
        <p class="mt-1 text-[11px] font-medium text-red-600" data-inline-error role="alert" aria-live="polite"
            hidden></p>
    </div>
</form>
