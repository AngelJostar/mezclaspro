<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <h1 class="text-xl font-semibold mb-4">Restablecer contrase&ntilde;a</h1>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('password.update') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="block">
                <x-label for="username" value="Usuario" />
                <x-input id="username" class="block mt-1 w-full" type="text" name="username" :value="old('username') ?: $request->query('username')" required autocomplete="username" autocapitalize="none" spellcheck="false" />
            </div>

            <div class="mt-4">
                <x-label for="password">Nueva contrase&ntilde;a</x-label>
                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autofocus autocomplete="new-password" />
            </div>

            <div class="mt-4">
                <x-label for="password_confirmation">Confirmar contrase&ntilde;a</x-label>
                <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-button>
                    Restablecer contrase&ntilde;a
                </x-button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>
