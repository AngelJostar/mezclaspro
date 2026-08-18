<nav class="fixed top-0 z-50 w-full bg-azul-prodifem border-b border-blue-950/10 shadow-sm dark:bg-gray-800 dark:border-gray-700">
    <div class="px-3 py-2.5 lg:px-5 lg:pl-3">
        <div class="flex items-center justify-between">
            <div class="flex items-center justify-start rtl:justify-end">
                <button x-on:click="open = !open"
                    x-bind:aria-expanded="open.toString()"
                    aria-controls="logo-sidebar" type="button"
                    class="inline-flex items-center p-2 text-sm text-white rounded-lg sm:hidden hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-white/50">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="w-6 h-6" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path clip-rule="evenodd" fill-rule="evenodd"
                            d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 10.5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10z">
                        </path>
                    </svg>
                </button>
                <a href="/" class="flex min-w-0 items-center gap-3 ms-2 md:me-8">
                    <img class="h-9 w-auto md:h-11 object-contain" src="{{ asset('img/Logo_Prodifem.png') }}"
                        alt="PRODIFEM">
                    <div class="hidden min-w-0 leading-tight text-white lg:block">
                        <p class="truncate text-sm font-semibold">
                            Central de Mezclas Estériles PRODIFEM
                        </p>
                        <p class="truncate text-xs text-white/85">
                            5591862620 · contacto@prodifem.com.mx
                        </p>
                    </div>
                </a>
            </div>
            <div class="flex min-w-0 items-center gap-2">
                @livewire('notifications')
                <!-- Settings Dropdown - SOLO APARECERA SI ESTAMOS LOGEADOS-->
                <div class="ms-3 relative">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                                <button
                                    class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                    <img class="h-8 w-8 rounded-full object-cover"
                                        src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                                </button>
                            @else
                                <span class="inline-flex rounded-md">
                                    <button type="button"
                                    class="inline-flex max-w-[8.5rem] items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-600 bg-white hover:text-gray-800 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150 md:max-w-none">
                                        <span class="truncate">{{ Auth::user()->name }}</span>

                                        <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                            fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                </span>
                            @endif
                        </x-slot>

                        <x-slot name="content">
                            <!-- Account Management -->
                            <div class="block px-4 py-2 text-xs text-gray-400">
                                {{ __('Manage Account') }}
                            </div>

                            {{-- <x-dropdown-link href="{{ route('profile.show') }}">
                                {{ __('Profile') }}
                            </x-dropdown-link> --}}

                            <div class="border-t border-gray-200"></div>

                            <!-- Authentication -->
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
    </div>
</nav>
