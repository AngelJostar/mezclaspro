<nav class="corporate-header" aria-label="Barra superior">
    <div class="corporate-header__inner">
        <div class="corporate-header__identity">
            @if ($hasSidebar ?? true)
                <button x-on:click="open = !open" x-bind:aria-expanded="open.toString()"
                    aria-controls="logo-sidebar" type="button" class="corporate-control corporate-sidebar-toggle"
                    aria-label="Abrir o cerrar men&uacute;" title="Men&uacute;">
                    <i data-lucide="menu" aria-hidden="true"></i>
                </button>
            @endif
            <x-corporate-brand />
        </div>
        <div class="corporate-header__actions">
            @livewire('notifications')
            <div class="corporate-account">
                <x-dropdown align="right" width="48" dropdownClasses="corporate-dropdown-panel">
                    <x-slot name="trigger">
                        <button type="button" class="corporate-control corporate-account__trigger"
                            x-bind:aria-expanded="open.toString()" aria-haspopup="true"
                            aria-label="{{ Auth::user()->name }}" title="{{ Auth::user()->name }}">
                            @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                                <img class="corporate-avatar" src="{{ Auth::user()->profile_photo_url }}" alt="" />
                            @else
                                <span class="corporate-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(trim(Auth::user()->name), 0, 1)) }}</span>
                            @endif
                            <span class="corporate-account__name">{{ Auth::user()->name }}</span>
                            <i data-lucide="chevron-down" class="corporate-account__chevron" aria-hidden="true"></i>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="block px-4 py-2 text-xs text-gray-400">{{ __('Manage Account') }}</div>
                        @if ($showProfileLink ?? false)
                            <x-dropdown-link href="{{ route('admin.nutricionales.solicitudes.index') }}">Solicitudes</x-dropdown-link>
                            <x-dropdown-link href="{{ route('profile.show') }}">{{ __('Profile') }}</x-dropdown-link>
                        @endif
                        <div class="border-t border-gray-200"></div>
                        <form method="POST" action="{{ route('logout') }}" x-data>
                            @csrf
                            <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </div>
</nav>
