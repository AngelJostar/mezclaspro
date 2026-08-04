<aside id="logo-sidebar"
    class="fixed top-0 left-0 z-40 w-64 h-screen pt-20 transition-transform duration-200 ease-in-out bg-white border-r border-gray-200 shadow-xl sm:w-44 sm:translate-x-0 sm:shadow-none dark:bg-gray-800 dark:border-gray-700"
    x-bind:class="open ? 'translate-x-0' : '-translate-x-full'"
    x-data="{ openMenu: null }" aria-label="Sidebar">

    <div class="h-full px-3 pb-4 overflow-y-auto bg-white dark:bg-gray-800">
        <ul class="space-y-2 font-medium">

            <!-- Nutricionales -->
            <li
                class="rounded-lg border border-emerald-200 bg-emerald-50 p-1 dark:border-emerald-700 dark:bg-emerald-900/20">
                <button @click="openMenu === 'nutricionales' ? openMenu = null : openMenu = 'nutricionales'"
                    class="flex w-full items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-emerald-100 dark:hover:bg-emerald-800/50">
                    <i class="fas fa-vial text-emerald-600 dark:text-emerald-300"></i>
                    <span class="ms-3 font-bold">Nutricionales</span>
                </button>
                <ul x-show="openMenu === 'nutricionales'" class="pl-4 space-y-2">
                    @can('nutricionales_solicitudes_index')
                        <li>
                            <a href="{{ route('admin.nutricionales.solicitudes.index') }}"
                                x-on:click="open = false"
                                class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.nutricionales.solicitudes.*') ? 'bg-gray-100' : '' }}">
                                <i class="fa-solid fa-file-import text-gray-500"></i>
                                <span class="ms-3">Solicitudes</span>
                            </a>
                        </li>
                    @endcan
                    @can('medicamentos_nutricionales')
                        <li>
                            <a href="{{ route('admin.nutricionales.medicines.index') }}"
                                x-on:click="open = false"
                                class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.nutricionales.solicitudes.*') ? 'bg-gray-100' : '' }}">
                                <i class="fa-solid fa-file-import text-gray-500"></i>
                                <span class="ms-3">Medicamentos</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.nutricionales.inputs.index') }}"
                                x-on:click="open = false"
                                class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.nutricionales.inputs.*') ? 'bg-gray-100' : '' }}">
                                <i class="fa-solid fa-sliders text-gray-500"></i>
                                <span class="ms-3">Inputs</span>
                            </a>
                        </li>
                    @endcan
                    @can('nutricionales_listas')
                        <li>
                            <a href="{{ route('admin.nutricionales.nutri-medicine-lists.index') }}"
                                x-on:click="open = false"
                                class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.nutricionales.nutri-medicine-lists.*') ? 'bg-gray-100' : '' }}">
                                <i class="fa-solid fa-list text-gray-500"></i>
                                <span class="ms-3">Listas de precios</span>
                            </a>
                        </li>
                    @endcan
                    @can('nutricionales_stock')
                        <li>
                            <a href="{{ route('admin.nutricionales.stocks.index') }}"
                                x-on:click="open = false"
                                class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.nutricionales.stocks.*') ? 'bg-gray-100' : '' }}">
                                <i class="fa-solid fa-boxes-stacked text-gray-500"></i>
                                <span class="ms-3">Inventario</span>
                            </a>
                        </li>
                    @endcan
                </ul>
            </li>

            <!-- Oncológicas -->
            <li
                class="rounded-lg border border-indigo-200 bg-indigo-50 p-1 dark:border-indigo-700 dark:bg-indigo-900/20">
                <button @click="openMenu === 'oncologicas' ? openMenu = null : openMenu = 'oncologicas'"
                    class="flex w-full items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-indigo-100 dark:hover:bg-indigo-800/50">
                    <i class="fas fa-vial text-indigo-600 dark:text-indigo-300"></i>
                    <span class="ms-3 font-bold">Oncológicas</span>
                </button>

                <ul x-show="openMenu === 'oncologicas'" class="pl-4 space-y-2">
                    @can('nutricionales_solicitudes_index')
                        <li>
                            <a href="{{ route('admin.oncologicos.solicitudes.index') }}"
                                x-on:click="open = false"
                                class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.oncologicos.solicitudes.*') ? 'bg-gray-100' : '' }}">
                                <i class="fa-solid fa-file-import text-gray-500"></i>
                                <span class="ms-3">Solicitudes</span>
                            </a>
                        </li>
                    @endcan
                    @can('medicamentos_oncologicos')
                        <li>
                            <a href="{{ route('admin.oncologicos.medicines.catalog.index') }}"
                                x-on:click="open = false"
                                class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                                <i class="fa-solid fa-file-import text-gray-500"></i>
                                <span class="ms-3">Medicamentos</span>
                            </a>
                        </li>
                    @endcan

                    {{-- NUEVO BOTÓN PARA DILUYENTES --}}
                    @can('medicamentos_oncologicos')
                        <li>
                            <a href="{{ route('admin.oncologicos.diluents.index') }}"
                                x-on:click="open = false"
                                class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('oncologicos.diluents.*') ? 'bg-gray-100' : '' }}">
                                <i class="fa-solid fa-flask text-gray-500"></i>
                                <span class="ms-3">Diluyentes</span>
                            </a>
                        </li>
                    @endcan

                    @can('medicamentos_oncologicos')
                        <li>
                            <a href="{{ route('admin.oncologicos.medicines.index') }}"
                                x-on:click="open = false"
                                class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group">
                                <i class="fa-solid fa-list text-gray-500"></i>
                                <span class="ms-3">Listas de precio</span>
                            </a>
                        </li>
                    @endcan

                    @can('medicamentos_oncologicos')
                        <li>
                            <a href="{{ route('admin.oncologicos.inventory.index') }}"
                                x-on:click="open = false"
                                class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.oncologicos.inventory.*') ? 'bg-gray-100' : '' }}">
                                <i class="fa-solid fa-boxes-stacked text-gray-500"></i>
                                <span class="ms-3">Inventario</span>
                            </a>
                        </li>
                    @endcan



                </ul>
            </li>

            <!-- Usuarios -->
            @can('usuarios')
                <li>
                    <a href="{{ route('admin.users.index') }}"
                        x-on:click="open = false"
                        class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.users.*') ? 'bg-gray-100' : '' }}">
                        <i class="fa-solid fa-users text-gray-500"></i>
                        <span class="ms-3">Usuarios</span>
                    </a>
                </li>
            @endcan

            <!-- Instituciones -->
            @hasanyrole('Super Admin')
                <li>
                    <button @click="openMenu === 'instituciones' ? openMenu = null : openMenu = 'instituciones'"
                        class="flex w-full items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 {{ request()->routeIs('admin.instituciones.*') ? 'bg-gray-100' : '' }}">
                        <i class="fa-solid fa-user-check text-gray-500"></i>
                        <span class="ms-3">Instituciones</span>
                    </button>

                    <ul x-show="openMenu === 'instituciones'" class="pl-4 space-y-2">
                        <li>
                            <a href="{{ route('admin.instituciones.index') }}"
                                x-on:click="open = false"
                                class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.instituciones.index') || request()->routeIs('admin.instituciones.create') || request()->routeIs('admin.instituciones.edit') || request()->routeIs('admin.instituciones.hospitals') ? 'bg-gray-100' : '' }}">
                                <i class="fa-solid fa-list text-gray-500"></i>
                                <span class="ms-3">Lista de instituciones</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('admin.instituciones.reportes') }}"
                                x-on:click="open = false"
                                class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.instituciones.reportes') ? 'bg-gray-100' : '' }}">
                                <i class="fa-solid fa-chart-column text-gray-500"></i>
                                <span class="ms-3">Reportes</span>
                            </a>
                        </li>

                        <li>
                            <a href="{{ route('admin.instituciones.billing.index') }}"
                                x-on:click="open = false"
                                class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.instituciones.billing.*') ? 'bg-gray-100' : '' }}">
                                <i class="fa-solid fa-file-invoice-dollar text-gray-500"></i>
                                <span class="ms-3">Facturacion / Solicitudes</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endhasanyrole

            <!-- Roles -->
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

            <!-- Permisos -->
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

            @can('laboratorios')
                <li>
                    <a href="{{ route('admin.oncologicos.laboratory.index') }}"
                        x-on:click="open = false"
                        class="flex items-center p-2 text-gray-900 rounded-lg dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700 group {{ request()->routeIs('admin.oncologicos.laboratory.*') ? 'bg-gray-100' : '' }}">
                        <i class="fa-solid fa-boxes-stacked text-gray-500"></i>
                        <span class="ms-3">Laboratorio</span>
                    </a>
                </li>
            @endcan

        </ul>
    </div>
</aside>
