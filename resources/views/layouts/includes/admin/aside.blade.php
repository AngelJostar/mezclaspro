<aside id="logo-sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform duration-200 ease-in-out bg-white border-r border-gray-200 shadow-xl sm:w-44 sm:translate-x-0 sm:shadow-none dark:bg-gray-800 dark:border-gray-700"
    x-bind:class="open ? 'translate-x-0' : '-translate-x-full'"
    x-data="{ openMenu: '{{ request()->routeIs('admin.distribution.*') ? 'distribution' : (request()->routeIs('admin.instituciones.billing.*') ? 'facturacion' : (request()->routeIs('admin.instituciones.reportes') ? 'administracion' : '')) }}' || null }" aria-label="Sidebar">

    <div class="h-full px-3 pb-4 overflow-y-auto bg-white dark:bg-gray-800">
        <ul class="space-y-2 font-medium">

            @unless (auth()->user()?->hasRole('Capacitacion'))
            @unless (auth()->user()?->hasRole('Administracion y facturacion'))

            @if (auth()->user()?->can('nutricionales_solicitudes_index') || auth()->user()?->can('oncologicos_solicitudes_index'))
                <li class="rounded-lg border border-blue-200 bg-blue-50 p-1 dark:border-blue-700 dark:bg-blue-900/20">
                    <a href="{{ route('admin.solicitudes.index') }}"
                        x-on:click="open = false"
                        class="flex w-full items-center rounded-lg p-2 text-gray-900 hover:bg-blue-100 dark:text-white dark:hover:bg-blue-800/50 {{ request()->routeIs('admin.solicitudes.*') || request()->routeIs('admin.nutricionales.solicitudes.*') || request()->routeIs('admin.oncologicos.solicitudes.*') || request()->routeIs('admin.antibioticos.solicitudes.*') ? 'bg-blue-100 dark:bg-blue-800/50' : '' }}">
                        <i class="fa-solid fa-file-medical text-blue-600 dark:text-blue-300"></i>
                        <span class="ms-3 font-bold">Solicitudes</span>
                    </a>
                </li>
            @endif

            <!-- Catalogo y listas de precios -->
            @if (auth()->user()?->can('medicamentos_nutricionales') || auth()->user()?->can('nutricionales_listas') || auth()->user()?->can('medicamentos_oncologicos'))
                <li
                    class="rounded-lg border border-yellow-200 bg-yellow-50 p-1 dark:border-yellow-700 dark:bg-yellow-900/20">
                    <a href="{{ route('admin.catalogo-listas.index') }}"
                        x-on:click="open = false"
                        class="flex w-full items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-yellow-100 dark:hover:bg-yellow-800/50 {{ request()->routeIs('admin.catalogo-listas.*') || request()->routeIs('admin.nutricionales.medicines.*') || request()->routeIs('admin.nutricionales.inputs.*') || request()->routeIs('admin.nutricionales.nutri-medicine-lists.*') || request()->routeIs('admin.oncologicos.medicines.*') || request()->routeIs('admin.oncologicos.diluents.*') ? 'bg-yellow-100' : '' }}">
                        <i class="fa-solid fa-book-medical text-yellow-600 dark:text-yellow-300"></i>
                        <span class="ms-3 font-bold">Catalogo y listas de precios</span>
                    </a>

                    <ul x-show="openMenu === 'catalogo_listas'" class="pl-4 space-y-2">
                        @can('medicamentos_nutricionales')
                            <li>
                                <a href="{{ route('admin.nutricionales.medicines.index') }}"
                                    x-on:click="open = false"
                                    class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.nutricionales.medicines.*') ? 'bg-gray-100' : '' }}">
                                    <i class="fa-solid fa-capsules text-gray-500"></i>
                                    <span class="ms-3">Medicamentos nutricionales</span>
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('admin.nutricionales.inputs.index') }}"
                                    x-on:click="open = false"
                                    class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.nutricionales.inputs.*') ? 'bg-gray-100' : '' }}">
                                    <i class="fa-solid fa-sliders text-gray-500"></i>
                                    <span class="ms-3">Inputs nutricionales</span>
                                </a>
                            </li>
                        @endcan

                        @can('nutricionales_listas')
                            <li>
                                <a href="{{ route('admin.nutricionales.nutri-medicine-lists.index') }}"
                                    x-on:click="open = false"
                                    class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.nutricionales.nutri-medicine-lists.*') ? 'bg-gray-100' : '' }}">
                                    <i class="fa-solid fa-list text-gray-500"></i>
                                    <span class="ms-3">Listas nutricionales</span>
                                </a>
                            </li>
                        @endcan

                        @can('medicamentos_oncologicos')
                            <li>
                                <a href="{{ route('admin.oncologicos.medicines.catalog.index') }}"
                                    x-on:click="open = false"
                                    class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.oncologicos.medicines.catalog.*') ? 'bg-gray-100' : '' }}">
                                    <i class="fa-solid fa-capsules text-gray-500"></i>
                                    <span class="ms-3">Medicamentos oncológicos</span>
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('admin.oncologicos.diluents.index') }}"
                                    x-on:click="open = false"
                                    class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.oncologicos.diluents.*') ? 'bg-gray-100' : '' }}">
                                    <i class="fa-solid fa-flask text-gray-500"></i>
                                    <span class="ms-3">Diluyentes</span>
                                </a>
                            </li>

                            <li>
                                <a href="{{ route('admin.oncologicos.medicines.index') }}"
                                    x-on:click="open = false"
                                    class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.oncologicos.medicines.index') ? 'bg-gray-100' : '' }}">
                                    <i class="fa-solid fa-list text-gray-500"></i>
                                    <span class="ms-3">Listas oncológicas</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endif

            @can('menu.distribucion')
                <li class="rounded-lg border border-teal-200 bg-teal-50 p-1 dark:border-teal-700 dark:bg-teal-900/20">
                    <button type="button" @click="openMenu === 'distribution' ? openMenu = null : openMenu = 'distribution'"
                        class="flex w-full items-center rounded-lg p-2 text-gray-900 hover:bg-teal-100 dark:text-white dark:hover:bg-teal-800/50 {{ request()->routeIs('admin.distribution.*') ? 'bg-teal-100 dark:bg-teal-800/50' : '' }}"
                        :aria-expanded="(openMenu === 'distribution').toString()">
                        <i class="fa-solid fa-truck-fast text-teal-600 dark:text-teal-300" aria-hidden="true"></i>
                        <span class="ms-3 min-w-0 flex-1 text-left font-bold">Distribuci&oacute;n</span>
                        <i class="fa-solid fa-chevron-down text-xs text-teal-700 transition-transform"
                            :class="openMenu === 'distribution' ? 'rotate-180' : ''" aria-hidden="true"></i>
                    </button>

                    <ul x-cloak x-show="openMenu === 'distribution'" class="mt-1 space-y-1 pl-4">
                        <li>
                            <a href="{{ route('admin.distribution.routes.index') }}"
                                x-on:click="open = false"
                                class="flex items-center rounded-lg p-2 text-sm leading-4 text-gray-900 hover:bg-white/80 dark:text-white dark:hover:bg-gray-700 {{ request()->routeIs('admin.distribution.routes.*') ? 'bg-white/90 text-teal-800 shadow-sm' : '' }}">
                                <i class="fa-solid fa-route shrink-0 text-gray-500" aria-hidden="true"></i>
                                <span class="ms-2">Cat&aacute;logo de Rutas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.distribution.deliveries.index') }}"
                                x-on:click="open = false"
                                class="flex items-center rounded-lg p-2 text-sm leading-4 text-gray-900 hover:bg-white/80 dark:text-white dark:hover:bg-gray-700 {{ request()->routeIs('admin.distribution.deliveries.*') ? 'bg-white/90 text-teal-800 shadow-sm' : '' }}">
                                <i class="fa-solid fa-calendar-days shrink-0 text-gray-500" aria-hidden="true"></i>
                                <span class="ms-2">Programaci&oacute;n de Entregas</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endcan

            @can('laboratorios')
                <li
                    class="rounded-lg border border-cyan-200 bg-cyan-50 p-1 dark:border-cyan-700 dark:bg-cyan-900/20">
                    <a href="{{ route('admin.oncologicos.laboratory.index') }}"
                        x-on:click="open = false"
                        class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-cyan-100 dark:hover:bg-cyan-800/50 group {{ request()->routeIs('admin.oncologicos.laboratory.index', 'admin.oncologicos.laboratory.create', 'admin.oncologicos.laboratory.store', 'admin.oncologicos.laboratory.edit', 'admin.oncologicos.laboratory.update', 'admin.oncologicos.laboratory.destroy') ? 'bg-cyan-100' : '' }}">
                        <i class="fa-solid fa-flask-vial text-cyan-600 dark:text-cyan-300"></i>
                        <span class="ms-3 font-bold leading-5">Laboratorios</span>
                    </a>
                </li>

                <li
                    class="rounded-lg border border-sky-200 bg-sky-50 p-1 dark:border-sky-700 dark:bg-sky-900/20">
                    <a href="{{ route('admin.warehouses.index') }}"
                        x-on:click="open = false"
                        class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-sky-100 dark:hover:bg-sky-800/50 group {{ request()->routeIs('admin.warehouses.*', 'admin.oncologicos.inventory.*', 'admin.nutricionales.stocks.*', 'admin.oncologicos.laboratory.purchase-orders.*') ? 'bg-sky-100' : '' }}">
                        <i class="fa-solid fa-warehouse text-sky-600 dark:text-sky-300"></i>
                        <span class="ms-3 font-bold leading-5">Almacenes</span>
                    </a>
                </li>
            @endcan

            <!-- Instituciones -->
            @if (auth()->user()?->hasRole('Super Admin') || auth()->user()?->can('hospitales'))
                <li
                    class="rounded-lg border border-violet-200 bg-violet-50 p-1 dark:border-violet-700 dark:bg-violet-900/20">
                    <button @click="openMenu === 'instituciones' ? openMenu = null : openMenu = 'instituciones'"
                        class="flex w-full items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-violet-100 dark:hover:bg-violet-800/50 {{ request()->routeIs('admin.instituciones.index') || request()->routeIs('admin.instituciones.create') || request()->routeIs('admin.instituciones.edit') || request()->routeIs('admin.instituciones.hospitals') || request()->routeIs('admin.hospitals.*') ? 'bg-violet-100' : '' }}">
                        <i class="fa-solid fa-user-check text-violet-600 dark:text-violet-300"></i>
                        <span class="ms-3 font-bold">Instituciones</span>
                    </button>

                    <ul x-show="openMenu === 'instituciones'" class="pl-4 space-y-2">
                        @hasanyrole('Super Admin')
                            <li>
                                <a href="{{ route('admin.instituciones.index') }}"
                                    x-on:click="open = false"
                                    class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.instituciones.index') || request()->routeIs('admin.instituciones.create') || request()->routeIs('admin.instituciones.edit') || request()->routeIs('admin.instituciones.hospitals') ? 'bg-gray-100' : '' }}">
                                    <i class="fa-solid fa-list text-gray-500"></i>
                                    <span class="ms-3">Lista de instituciones</span>
                                </a>
                            </li>
                        @endhasanyrole

                        @can('hospitales')
                            <li>
                                <a href="{{ route('admin.hospitals.index') }}"
                                    x-on:click="open = false"
                                    class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.hospitals.*') ? 'bg-gray-100' : '' }}">
                                    <i class="fa-solid fa-hospital text-gray-500"></i>
                                    <span class="ms-3">Hospitales</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endif

            @endunless

            <!-- Administracion -->
            @if (auth()->user()?->hasAnyRole(['Super Admin', 'Administracion y facturacion']) || auth()->user()?->can('usuarios'))
                <li
                    class="rounded-lg border border-orange-200 bg-orange-50 p-1 dark:border-orange-700 dark:bg-orange-900/20">
                    <button @click="openMenu === 'administracion' ? openMenu = null : openMenu = 'administracion'"
                        class="flex w-full items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-orange-100 dark:hover:bg-orange-800/50 {{ request()->routeIs('admin.instituciones.reportes') || request()->routeIs('admin.users.*') ? 'bg-orange-100' : '' }}">
                        <i class="fa-solid fa-chart-column text-orange-600 dark:text-orange-300"></i>
                        <span class="ms-3 font-bold">Administracion</span>
                    </button>

                    <ul x-show="openMenu === 'administracion'" class="pl-4 space-y-2">
                        @hasanyrole('Super Admin|Administracion y facturacion')
                            <li>
                                <a href="{{ route('admin.instituciones.reportes') }}"
                                    x-on:click="open = false"
                                    class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.instituciones.reportes') ? 'bg-gray-100' : '' }}">
                                    <i class="fa-solid fa-file-lines text-gray-500"></i>
                                    <span class="ms-3">Reportes</span>
                                </a>
                            </li>
                        @endhasanyrole

                        @can('usuarios')
                            <li>
                                <a href="{{ route('admin.users.index') }}"
                                    x-on:click="open = false"
                                    class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.users.*') ? 'bg-gray-100' : '' }}">
                                    <i class="fa-solid fa-users text-gray-500"></i>
                                    <span class="ms-3">Personal</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endif

            <!-- Facturacion -->
            @hasanyrole('Super Admin|Administracion y facturacion')
                <li
                    class="rounded-lg border border-teal-200 bg-teal-50 p-1 dark:border-teal-700 dark:bg-teal-900/20">
                    <button @click="openMenu === 'facturacion' ? openMenu = null : openMenu = 'facturacion'"
                        class="flex w-full items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-teal-100 dark:hover:bg-teal-800/50 {{ request()->routeIs('admin.instituciones.billing.*') ? 'bg-teal-100' : '' }}">
                        <i class="fa-solid fa-file-invoice-dollar text-teal-600 dark:text-teal-300"></i>
                        <span class="ms-3 font-bold">Facturacion</span>
                    </button>

                    <ul x-show="openMenu === 'facturacion'" class="pl-4 space-y-2">
                        <li>
                            <a href="{{ route('admin.instituciones.billing.index') }}"
                                x-on:click="open = false"
                                class="flex items-center overflow-hidden p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.instituciones.billing.index') ? 'bg-gray-100' : '' }}">
                                <span class="grid w-full min-w-0 grid-cols-[minmax(0,1fr)_auto] items-center gap-1">
                                    <span class="truncate text-sm">Pendientes</span>
                                    <span class="flex min-w-0 shrink-0 items-center gap-0.5" aria-label="Resumen de vencimientos pendientes">
                                        <span
                                            class="inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full border border-amber-300 bg-amber-100 px-0.5 text-[10px] font-bold leading-none text-amber-800"
                                            title="{{ $billingDueCounts['yellow'] }} solicitudes con vencimiento amarillo"
                                            aria-label="{{ $billingDueCounts['yellow'] }} solicitudes con vencimiento amarillo">
                                            {{ $billingDueCounts['yellow'] }}
                                        </span>
                                        <span
                                            class="inline-flex h-[18px] min-w-[18px] items-center justify-center rounded-full border border-red-300 bg-red-100 px-0.5 text-[10px] font-bold leading-none text-red-700"
                                            title="{{ $billingDueCounts['red'] }} solicitudes con vencimiento rojo"
                                            aria-label="{{ $billingDueCounts['red'] }} solicitudes con vencimiento rojo">
                                            {{ $billingDueCounts['red'] }}
                                        </span>
                                    </span>
                                </span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.instituciones.billing.history') }}"
                                x-on:click="open = false"
                                class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.instituciones.billing.history') ? 'bg-gray-100' : '' }}">
                                <i class="fa-solid fa-clock-rotate-left text-gray-500"></i>
                                <span class="ms-3">Historial</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endhasanyrole

            @endunless

            <!-- Capacitaciones -->
            @unless (auth()->user()?->hasRole('Administracion y facturacion'))
            <li
                class="rounded-lg border border-blue-200 bg-blue-50 p-1 dark:border-blue-700 dark:bg-blue-900/20">
                <a href="{{ route('admin.capacitaciones.index') }}"
                    x-on:click="open = false"
                    class="flex w-full items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-blue-100 dark:hover:bg-blue-800/50 {{ request()->routeIs('admin.capacitaciones.*') ? 'bg-blue-100' : '' }}">
                    <i class="fa-solid fa-graduation-cap text-blue-600 dark:text-blue-300"></i>
                    <span class="ms-3 font-bold">Capacitaciones</span>
                </a>
            </li>
            @endunless

            @unless (auth()->user()?->hasAnyRole(['Capacitacion', 'Administracion y facturacion']))
            <!-- Roles y Permisos -->
            @if (auth()->user()?->can('roles') || auth()->user()?->can('permisos'))
                <li
                    class="rounded-lg border border-fuchsia-200 bg-fuchsia-50 p-1 dark:border-fuchsia-700 dark:bg-fuchsia-900/20">
                    <button @click="openMenu === 'roles_permisos' ? openMenu = null : openMenu = 'roles_permisos'"
                        class="flex w-full items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-fuchsia-100 dark:hover:bg-fuchsia-800/50 {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'bg-fuchsia-100' : '' }}">
                        <i class="fa-solid fa-user-shield text-fuchsia-600 dark:text-fuchsia-300"></i>
                        <span class="ms-3 font-bold">Roles y Permisos</span>
                    </button>

                    <ul x-show="openMenu === 'roles_permisos'" class="pl-4 space-y-2">
                        @can('roles')
                            <li>
                                <a href="{{ route('admin.roles.index') }}"
                                    x-on:click="open = false"
                                    class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.roles.*') ? 'bg-gray-100' : '' }}">
                                    <i class="fa-solid fa-user-tag text-gray-500"></i>
                                    <span class="ms-3">Roles</span>
                                </a>
                            </li>
                        @endcan

                        @can('permisos')
                            <li>
                                <a href="{{ route('admin.permissions.index') }}"
                                    x-on:click="open = false"
                                    class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.permissions.*') ? 'bg-gray-100' : '' }}">
                                    <i class="fa-solid fa-key text-gray-500"></i>
                                    <span class="ms-3">Permisos</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endif
            @endunless

            @hasanyrole('Super Admin')
                <li
                    class="rounded-lg border border-rose-200 bg-rose-50 p-1 dark:border-rose-700 dark:bg-rose-900/20">
                    <a href="{{ route('admin.superadministrator.index') }}"
                        aria-label="Superadministrador"
                        x-on:click="open = false"
                        class="flex w-full min-w-0 items-center rounded-lg p-2 text-gray-900 hover:bg-rose-100 dark:text-white dark:hover:bg-rose-800/50 {{ request()->routeIs('admin.superadministrator.*') ? 'bg-rose-100 dark:bg-rose-800/50' : '' }}">
                        <i class="fa-solid fa-crown shrink-0 text-rose-600 dark:text-rose-300" aria-hidden="true"></i>
                        <span class="ms-2 min-w-0 text-center text-xs font-bold leading-4" aria-hidden="true">
                            <span class="block">Super</span>
                            <span class="block">administrador</span>
                        </span>
                    </a>
                </li>
            @endhasanyrole

        </ul>
    </div>
</aside>
