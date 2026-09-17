@php
    $sidebarUser = auth()->user();
    $menuAllows = fn (string $permission, bool $legacyAccess = true) =>
        \App\Support\AdminMenuAccess::allows($sidebarUser, $permission, $legacyAccess);
    $isRequestsSection = request()->routeIs('admin.solicitudes.*', 'admin.nutricionales.solicitudes.*', 'admin.oncologicos.solicitudes.*', 'admin.antibioticos.solicitudes.*', 'admin.oncologicos.mezclas.*');
    $isValidationsSection = request()->routeIs('admin.solicitudes.validaciones.*');
    $isAdministrationSection = request()->routeIs('admin.instituciones.reportes', 'admin.instituciones.billing.*');
    $canViewAdministrationReports = \App\Support\AdministrationNavigation::canViewReports($sidebarUser);
    $administrationBillingSections = \App\Support\AdministrationNavigation::billingSections($sidebarUser);
    $administrationEntryRoute = $canViewAdministrationReports ? 'admin.instituciones.reportes' : (reset($administrationBillingSections)['route'] ?? null);
    $initialOpenMenu = match (true) {
        $isRequestsSection => 'solicitudes',
        $isAdministrationSection => null,
        request()->routeIs('admin.users.*', 'admin.roles.*', 'admin.permissions.*') => 'usuarios_permisos',
        request()->routeIs('admin.capacitaciones.*') => 'capacitaciones',
        request()->routeIs('admin.maintenance-qualifications.*') => 'maintenance_qualifications',
        request()->routeIs('admin.purchases.*', 'admin.warehouses.purchase-orders.*', 'admin.oncologicos.laboratory.purchase-orders.*', 'admin.suppliers.*') => 'compras',
        request()->routeIs('admin.catalogo-listas.*', 'admin.nutricionales.medicines.*', 'admin.nutricionales.inputs.*', 'admin.nutricionales.nutri-medicine-lists.*', 'admin.oncologicos.medicines.*', 'admin.oncologicos.diluents.*') => 'catalogo_listas',
        request()->routeIs('admin.distribution.*') => 'distribution',
        request()->routeIs('admin.instituciones.*', 'admin.hospitals.*') => 'instituciones',
        default => null,
    };
@endphp

<aside id="logo-sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-screen transition-transform duration-200 ease-in-out bg-white border-r border-gray-200 shadow-xl sm:w-44 sm:translate-x-0 sm:shadow-none dark:bg-gray-800 dark:border-gray-700"
    x-bind:class="open ? 'translate-x-0' : '-translate-x-full'"
    x-data="{ openMenu: @js($initialOpenMenu) }" aria-label="Sidebar">

    <div class="h-full px-3 pb-4 overflow-y-auto bg-white dark:bg-gray-800">
        <ul class="space-y-2 font-medium">

            @unless (auth()->user()?->hasRole('Capacitacion'))
            @unless (auth()->user()?->hasRole('Administracion y facturacion'))

            @if ($menuAllows('menu.solicitudes', $sidebarUser?->can('nutricionales_solicitudes_index') || $sidebarUser?->can('oncologicos_solicitudes_index')))
                <li class="rounded-lg border border-blue-200 bg-blue-50 p-1 dark:border-blue-700 dark:bg-blue-900/20">
                    <button type="button"
                        @click="openMenu = openMenu === 'solicitudes' ? null : 'solicitudes'"
                        :aria-expanded="(openMenu === 'solicitudes').toString()" aria-controls="solicitudes-submenu"
                        class="flex w-full items-center gap-1 rounded-md px-2 py-2 text-gray-900 hover:bg-blue-100 dark:text-white dark:hover:bg-blue-800/50 {{ $isRequestsSection ? 'bg-blue-100 dark:bg-blue-800/50' : '' }}">
                            <span class="min-w-0 flex-1 text-left font-bold">Solicitudes</span>
                            <i data-request-navigation-icon="chevron-down" class="h-3 w-3 shrink-0 transition-transform"
                                :class="openMenu === 'solicitudes' ? 'rotate-180' : ''" aria-hidden="true"></i>
                            <span
                                class="inline-flex h-[18px] min-w-[18px] shrink-0 items-center justify-center rounded-full border border-red-300 bg-red-100 px-0.5 font-bold leading-none text-red-700"
                                style="font-size: 10px"
                                title="{{ $pendingSolicitudesCount }} solicitudes pendientes"
                                aria-label="{{ $pendingSolicitudesCount }} solicitudes pendientes">
                                {{ $pendingSolicitudesCount > 99 ? '99+' : $pendingSolicitudesCount }}
                            </span>
                    </button>
                    <ul id="solicitudes-submenu" x-cloak x-show="openMenu === 'solicitudes'" class="space-y-1 pt-1">
                        <li>
                            <a href="{{ route('admin.solicitudes.index') }}" @click="open = false"
                                @if ($isRequestsSection && !$isValidationsSection) aria-current="page" @endif
                                class="block rounded-md px-2 py-2 text-sm {{ $isRequestsSection && !$isValidationsSection ? 'bg-blue-100 font-semibold text-blue-800' : 'text-gray-900 hover:bg-blue-100' }}">
                                Listado
                            </a>
                        </li>
                    </ul>
                </li>
            @endif

            @if ($sidebarUser?->hasAnyRole(['Cliente', 'Institucion']))
                <li class="rounded-lg border border-cyan-200 bg-cyan-50 p-1 dark:border-cyan-700 dark:bg-cyan-900/20">
                    <a href="{{ route('admin.hospital.herramientas') }}" x-on:click="open = false"
                        @if (request()->routeIs('admin.hospital.herramientas')) aria-current="page" @endif
                        class="flex w-full items-center rounded-lg p-2 text-gray-900 hover:bg-cyan-100 dark:text-white dark:hover:bg-cyan-800/50 {{ request()->routeIs('admin.hospital.herramientas') ? 'bg-cyan-100' : '' }}">
                        <span class="min-w-0 flex-1 text-left font-bold">Herramientas</span>
                    </a>
                </li>
            @endif

            <!-- Catalogo y listas de precios -->
            @if ($menuAllows('menu.catalogo', $sidebarUser?->can('medicamentos_nutricionales') || $sidebarUser?->can('nutricionales_listas') || $sidebarUser?->can('medicamentos_oncologicos')))
                <li
                    class="rounded-lg border border-yellow-200 bg-yellow-50 p-1 dark:border-yellow-700 dark:bg-yellow-900/20">
                    <a href="{{ route('admin.catalogo-listas.index') }}" x-on:click="open = false"
                        class="flex w-full items-center rounded-lg p-2 text-gray-900 hover:bg-yellow-100 dark:text-white dark:hover:bg-yellow-800/50 {{ request()->routeIs('admin.catalogo-listas.*') ? 'bg-yellow-100' : '' }}">
                        <span class="min-w-0 flex-1 text-left font-bold">Catalogo y listas de precios</span>
                    </a>
                </li>
            @endif

            <!-- Compras -->
            @php
                $canAccessPurchases = $menuAllows('menu.compras', $sidebarUser?->can('oncologicos_laboratory_index'));
                $canAccessSuppliers = $menuAllows('menu.proveedores', $sidebarUser?->can('oncologicos_laboratory_index'));
            @endphp
            @if ($canAccessPurchases || $canAccessSuppliers)
                @php
                    $purchaseSection = (string) request()->query('section', 'mine');
                    $purchaseListRoute = request()->routeIs('admin.warehouses.purchase-orders.*');
                @endphp
                <li class="rounded-lg border border-indigo-200 bg-indigo-50 p-1 dark:border-indigo-700 dark:bg-indigo-900/20">
                    <button type="button"
                        @click="openMenu === 'compras' ? openMenu = null : openMenu = 'compras'"
                        :aria-expanded="(openMenu === 'compras').toString()" aria-controls="compras-submenu"
                        class="flex w-full items-center rounded-lg p-2 text-gray-900 hover:bg-indigo-100 dark:text-white dark:hover:bg-indigo-800/50 {{ request()->routeIs('admin.purchases.*', 'admin.warehouses.purchase-orders.*', 'admin.oncologicos.laboratory.purchase-orders.*', 'admin.suppliers.*') ? 'bg-indigo-100' : '' }}">
                        <span class="min-w-0 flex-1 text-left font-bold">Compras</span>
                        <i data-request-navigation-icon="chevron-down" class="ms-1 h-3 w-3 shrink-0 transition-transform"
                            :class="openMenu === 'compras' ? 'rotate-180' : ''" aria-hidden="true"></i>
                    </button>

                    <ul id="compras-submenu" x-cloak x-show="openMenu === 'compras'" class="space-y-1 pt-1">
                        @if ($menuAllows('menu.compras.mine'))
                        <li>
                            <a href="{{ route('admin.warehouses.purchase-orders.index', ['section' => 'mine']) }}"
                                x-on:click="open = false"
                                class="flex items-center rounded-lg p-2 text-gray-900 hover:bg-white dark:text-white dark:hover:bg-gray-700 {{ $purchaseListRoute && $purchaseSection === 'mine' ? 'bg-white shadow-sm' : '' }}">
                                <span class="flex min-w-0 flex-1 items-center justify-between gap-2">
                                    <span class="truncate">Mis Ordenes</span>
                                    <span class="inline-flex h-[18px] min-w-[18px] shrink-0 items-center justify-center rounded-full border border-red-300 bg-red-100 px-1 text-[10px] font-bold leading-none text-red-700"
                                        title="{{ $myPurchaseOrdersCount }} ordenes creadas por ti"
                                        aria-label="{{ $myPurchaseOrdersCount }} ordenes creadas por ti">
                                        {{ $myPurchaseOrdersCount > 99 ? '99+' : $myPurchaseOrdersCount }}
                                    </span>
                                </span>
                            </a>
                        </li>
                        @endif
                        @if ($menuAllows('menu.compras.new'))
                        <li>
                            <a href="{{ route('admin.purchases.create', array_filter(['laboratory_id' => request()->integer('laboratory_id') ?: null])) }}"
                                x-on:click="open = false"
                                class="flex items-center rounded-lg p-2 text-gray-900 hover:bg-white dark:text-white dark:hover:bg-gray-700 {{ request()->routeIs('admin.purchases.create', 'admin.oncologicos.laboratory.purchase-orders.create') ? 'bg-white shadow-sm' : '' }}">
                                <span>Nueva OC</span>
                            </a>
                        </li>
                        @endif
                        @if ($menuAllows('menu.compras.paid'))
                        <li>
                            <a href="{{ route('admin.warehouses.purchase-orders.index', ['section' => 'paid']) }}"
                                x-on:click="open = false"
                                class="flex items-center rounded-lg p-2 text-gray-900 hover:bg-white dark:text-white dark:hover:bg-gray-700 {{ $purchaseListRoute && $purchaseSection === 'paid' ? 'bg-white shadow-sm' : '' }}">
                                <span>Pagadas</span>
                            </a>
                        </li>
                        @endif
                        @if ($menuAllows('menu.compras.pending'))
                        <li>
                            <a href="{{ route('admin.warehouses.purchase-orders.index', ['section' => 'pending']) }}"
                                x-on:click="open = false"
                                class="flex items-center rounded-lg p-2 text-gray-900 hover:bg-white dark:text-white dark:hover:bg-gray-700 {{ $purchaseListRoute && $purchaseSection === 'pending' ? 'bg-white shadow-sm' : '' }}">
                                <span>Pendientes de Pago</span>
                            </a>
                        </li>
                        @endif
                        @if ($menuAllows('menu.compras.rejected'))
                        <li>
                            <a href="{{ route('admin.warehouses.purchase-orders.index', ['section' => 'rejected']) }}"
                                x-on:click="open = false"
                                class="flex items-center rounded-lg p-2 text-gray-900 hover:bg-white dark:text-white dark:hover:bg-gray-700 {{ $purchaseListRoute && $purchaseSection === 'rejected' ? 'bg-white shadow-sm' : '' }}">
                                <span>Rechazadas</span>
                            </a>
                        </li>
                        @endif
                        @if ($canAccessSuppliers)
                        <li>
                            <a href="{{ route('admin.suppliers.index') }}"
                                x-on:click="open = false"
                                class="flex items-center rounded-lg p-2 text-gray-900 hover:bg-white dark:text-white dark:hover:bg-gray-700 {{ request()->routeIs('admin.suppliers.*') ? 'bg-white shadow-sm' : '' }}">
                                <span>Proveedores</span>
                            </a>
                        </li>
                        @endif
                    </ul>
                </li>
            @endif

            @if ($menuAllows('menu.distribucion', $sidebarUser?->can('laboratorios')))
                <li class="rounded-lg border border-cyan-200 bg-cyan-50 p-1 dark:border-cyan-700 dark:bg-cyan-900/20">
                    <button type="button" @click="openMenu === 'distribution' ? openMenu = null : openMenu = 'distribution'"
                        class="flex w-full items-center rounded-lg p-2 text-gray-900 hover:bg-cyan-100 dark:text-white dark:hover:bg-cyan-800/50 {{ request()->routeIs('admin.distribution.*') ? 'bg-cyan-100 dark:bg-cyan-800/50' : '' }}"
                        :aria-expanded="(openMenu === 'distribution').toString()" aria-controls="distribution-submenu">
                        <span class="min-w-0 flex-1 text-left font-bold">Distribución</span>
                        <i data-request-navigation-icon="chevron-down" class="ms-1 h-3 w-3 shrink-0 transition-transform"
                            :class="openMenu === 'distribution' ? 'rotate-180' : ''" aria-hidden="true"></i>
                    </button>

                    <ul id="distribution-submenu" x-cloak x-show="openMenu === 'distribution'" class="mt-1 space-y-1">
                        <li>
                            <a href="{{ route('admin.distribution.routes.index') }}" x-on:click="open = false"
                                class="flex items-center rounded-lg p-2 text-sm leading-4 text-gray-900 hover:bg-white/80 dark:text-white dark:hover:bg-gray-700 {{ request()->routeIs('admin.distribution.routes.*') ? 'bg-white/90 text-cyan-800 shadow-sm' : '' }}">
                                <span>Catálogo de Rutas</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.distribution.deliveries.index') }}" x-on:click="open = false"
                                class="flex items-center rounded-lg p-2 text-sm leading-4 text-gray-900 hover:bg-white/80 dark:text-white dark:hover:bg-gray-700 {{ request()->routeIs('admin.distribution.deliveries.*') ? 'bg-white/90 text-cyan-800 shadow-sm' : '' }}">
                                <span>Programación de Entregas</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endif

            @if ($menuAllows('menu.warehouses', $sidebarUser?->can('laboratorios')))
                <li
                    class="rounded-lg border border-sky-200 bg-sky-50 p-1 dark:border-sky-700 dark:bg-sky-900/20">
                    <a href="{{ route('admin.warehouses.index') }}"
                        x-on:click="open = false"
                        class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-sky-100 dark:hover:bg-sky-800/50 group {{ ((request()->routeIs('admin.warehouses.*') && ! request()->routeIs('admin.warehouses.purchase-orders.*')) || request()->routeIs('admin.oncologicos.inventory.*', 'admin.nutricionales.stocks.*', 'admin.oncologicos.laboratory.*')) && ! request()->routeIs('admin.oncologicos.laboratory.purchase-orders.*') ? 'bg-sky-100' : '' }}">
                        <span class="min-w-0 font-bold leading-5">Centrales y Almacenes</span>
                    </a>
                </li>
            @endif

            <!-- Instituciones -->
            @if ($menuAllows('menu.instituciones', $sidebarUser?->hasRole('Super Admin') || $sidebarUser?->can('hospitales')))
                <li
                    class="rounded-lg border border-violet-200 bg-violet-50 p-1 dark:border-violet-700 dark:bg-violet-900/20">
                    <button type="button" @click="openMenu === 'instituciones' ? openMenu = null : openMenu = 'instituciones'"
                        :aria-expanded="(openMenu === 'instituciones').toString()" aria-controls="instituciones-submenu"
                        class="flex w-full items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-violet-100 dark:hover:bg-violet-800/50 {{ request()->routeIs('admin.instituciones.index') || request()->routeIs('admin.instituciones.create') || request()->routeIs('admin.instituciones.edit') || request()->routeIs('admin.instituciones.hospitals') || request()->routeIs('admin.hospitals.*') ? 'bg-violet-100' : '' }}">
                        <span class="min-w-0 flex-1 text-left font-bold">Instituciones</span>
                        <i data-request-navigation-icon="chevron-down" class="ms-1 h-3 w-3 shrink-0 transition-transform"
                            :class="openMenu === 'instituciones' ? 'rotate-180' : ''" aria-hidden="true"></i>
                    </button>

                    <ul id="instituciones-submenu" x-cloak x-show="openMenu === 'instituciones'" class="space-y-2">
                        @if ($menuAllows('menu.instituciones.list', $sidebarUser?->hasRole('Super Admin')))
                            <li>
                                <a href="{{ route('admin.instituciones.index') }}"
                                    x-on:click="open = false"
                                    class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.instituciones.index') || request()->routeIs('admin.instituciones.create') || request()->routeIs('admin.instituciones.edit') || request()->routeIs('admin.instituciones.hospitals') ? 'bg-gray-100' : '' }}">
                                    <span>Instituciones</span>
                                </a>
                            </li>
                        @endif

                        @if ($menuAllows('menu.instituciones.hospitals', $sidebarUser?->can('hospitales')))
                            <li>
                                <a href="{{ route('admin.hospitals.index') }}"
                                    x-on:click="open = false"
                                    class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.hospitals.*') ? 'bg-gray-100' : '' }}">
                                    <span>Hospitales</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            @endunless

            <!-- Administracion -->
            @if ($administrationEntryRoute)
                <li
                    class="rounded-lg border border-orange-200 bg-orange-50 p-1 dark:border-orange-700 dark:bg-orange-900/20">
                    <a href="{{ route($administrationEntryRoute) }}" x-on:click="open = false"
                        @if ($isAdministrationSection) aria-current="page" @endif
                        class="flex w-full items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-orange-100 dark:hover:bg-orange-800/50 {{ $isAdministrationSection ? 'bg-orange-100' : '' }}">
                        <span class="min-w-0 font-bold">Administracion</span>
                    </a>
                </li>
            @endif

            @endunless

            <!-- Personal y Capacitaciones -->
            @unless (auth()->user()?->hasRole('Administracion y facturacion'))
            @if ($menuAllows('menu.capacitaciones'))
            <li
                class="rounded-lg border border-blue-200 bg-blue-50 p-1 dark:border-blue-700 dark:bg-blue-900/20">
                <button type="button"
                    @click="openMenu === 'capacitaciones' ? openMenu = null : openMenu = 'capacitaciones'"
                    :aria-expanded="(openMenu === 'capacitaciones').toString()" aria-controls="capacitaciones-submenu"
                    class="flex w-full items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-blue-100 dark:hover:bg-blue-800/50 {{ request()->routeIs('admin.capacitaciones.*') ? 'bg-blue-100' : '' }}">
                    <span class="min-w-0 flex-1 text-left font-bold">Personal y Capacitaciones</span>
                    <i data-request-navigation-icon="chevron-down" class="ms-1 h-3 w-3 shrink-0 transition-transform"
                        :class="openMenu === 'capacitaciones' ? 'rotate-180' : ''" aria-hidden="true"></i>
                </button>

                <ul id="capacitaciones-submenu" x-cloak x-show="openMenu === 'capacitaciones'" class="space-y-1 pt-1">
                    @if ($menuAllows('menu.capacitaciones.personal'))
                    <li>
                        <a href="{{ route('admin.capacitaciones.personal') }}"
                            x-on:click="open = false"
                            class="flex items-center rounded-lg p-2 text-gray-900 hover:bg-white dark:text-white dark:hover:bg-gray-700 {{ request()->routeIs('admin.capacitaciones.personal') ? 'bg-white shadow-sm' : '' }}">
                            <span>Personal</span>
                        </a>
                    </li>
                    @endif
                    @if ($menuAllows('menu.capacitaciones.programas'))
                    <li>
                        <a href="{{ route('admin.capacitaciones.programas') }}"
                            x-on:click="open = false"
                            class="flex items-center rounded-lg p-2 text-gray-900 hover:bg-white dark:text-white dark:hover:bg-gray-700 {{ request()->routeIs('admin.capacitaciones.index', 'admin.capacitaciones.programas') ? 'bg-white shadow-sm' : '' }}">
                            <span>Programas</span>
                        </a>
                    </li>
                    @endif
                    @if ($menuAllows('menu.capacitaciones.alumnos'))
                    <li>
                        <a href="{{ route('admin.capacitaciones.alumnos') }}"
                            x-on:click="open = false"
                            class="flex items-center rounded-lg p-2 text-gray-900 hover:bg-white dark:text-white dark:hover:bg-gray-700 {{ request()->routeIs('admin.capacitaciones.alumnos') ? 'bg-white shadow-sm' : '' }}">
                            <span>Alumnos</span>
                        </a>
                    </li>
                    @endif
                </ul>
            </li>
            @endif
            @endunless

            @if ($menuAllows('menu.maintenance-qualifications', $sidebarUser?->hasRole('Super Admin') ?? false))
                <li class="rounded-lg border border-emerald-200 bg-emerald-50 p-1 dark:border-emerald-700 dark:bg-emerald-900/20">
                    <a href="{{ route('admin.maintenance-qualifications.index') }}" aria-label="Mantenimiento y Calificaciones"
                        x-on:click="open = false"
                        class="flex w-full min-w-0 items-center rounded-lg p-2 text-gray-900 hover:bg-emerald-100 dark:text-white dark:hover:bg-emerald-800/50 {{ request()->routeIs('admin.maintenance-qualifications.*') ? 'bg-emerald-100 dark:bg-emerald-800/50' : '' }}">
                        <span class="min-w-0 text-left text-xs font-bold leading-4">
                            <span class="block">Mantenimiento y</span>
                            <span class="block">Calificaciones</span>
                        </span>
                    </a>
                </li>
            @endif

            @unless (auth()->user()?->hasRole('Capacitacion'))
            <!-- Roles y Permisos -->
            @if ($menuAllows('menu.users', $sidebarUser?->can('roles') || $sidebarUser?->can('permisos')))
                <li
                    class="rounded-lg border border-fuchsia-200 bg-fuchsia-50 p-1 dark:border-fuchsia-700 dark:bg-fuchsia-900/20">
                    <button type="button" @click="openMenu === 'usuarios_permisos' ? openMenu = null : openMenu = 'usuarios_permisos'"
                        :aria-expanded="(openMenu === 'usuarios_permisos').toString()" aria-controls="usuarios-permisos-submenu"
                        class="flex w-full items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-fuchsia-100 dark:hover:bg-fuchsia-800/50 {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'bg-fuchsia-100' : '' }}">
                        <span class="min-w-0 flex-1 text-left font-bold">Roles y Permisos</span>
                        <i data-request-navigation-icon="chevron-down" class="ms-1 h-3 w-3 shrink-0 transition-transform"
                            :class="openMenu === 'usuarios_permisos' ? 'rotate-180' : ''" aria-hidden="true"></i>
                    </button>

                    <ul id="usuarios-permisos-submenu" x-cloak x-show="openMenu === 'usuarios_permisos'" class="space-y-2">
                        @if ($menuAllows('menu.users.roles', $sidebarUser?->can('roles')))
                            <li>
                                <a href="{{ route('admin.roles.index') }}"
                                    x-on:click="open = false"
                                    class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.roles.*') ? 'bg-gray-100' : '' }}">
                                    <span>Roles</span>
                                </a>
                            </li>
                        @endif

                        @if ($menuAllows('menu.users.permissions', $sidebarUser?->can('permisos')))
                            <li>
                                <a href="{{ route('admin.permissions.index') }}"
                                    x-on:click="open = false"
                                    class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.permissions.*') ? 'bg-gray-100' : '' }}">
                                    <span>Permisos</span>
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @endunless

            @hasanyrole('Super Admin')
                <li class="rounded-lg border border-rose-200 bg-rose-50 p-1 dark:border-rose-700 dark:bg-rose-900/20">
                    <a href="{{ route('admin.superadministrator.index') }}" aria-label="Superadministrador"
                        x-on:click="open = false"
                        class="flex w-full min-w-0 items-center rounded-lg p-2 text-gray-900 hover:bg-rose-100 dark:text-white dark:hover:bg-rose-800/50 {{ request()->routeIs('admin.superadministrator.*') ? 'bg-rose-100 dark:bg-rose-800/50' : '' }}">
                        <span class="min-w-0 text-left text-xs font-bold leading-4">
                            <span class="block">Super</span>
                            <span class="block">administrador</span>
                        </span>
                    </a>
                </li>
            @endhasanyrole

        </ul>
    </div>
</aside>
