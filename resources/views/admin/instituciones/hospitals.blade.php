<x-admin-layout>
    <section class="rounded-lg bg-white p-5 shadow-lg">
        <div class="flex flex-col gap-3 border-b border-gray-100 pb-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Hospitales de la instituci&oacute;n</h1>
                <p class="mt-1 text-sm text-blue-600">{{ $institucion->nombre }}</p>
            </div>

            <a href="{{ route('admin.instituciones.index') }}"
                class="text-sm font-medium text-blue-600 hover:text-blue-800">
                &larr; Volver a instituciones
            </a>
        </div>

        <div class="mt-4 flex flex-col gap-4 rounded-lg border border-gray-200 p-4 md:flex-row md:items-center md:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-800">
                    <span class="text-sm font-bold" aria-hidden="true">I</span>
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-gray-900">
                        Instituci&oacute;n: {{ $institucion->nombre }}
                    </p>
                    <p class="mt-1 flex items-center gap-2 text-xs text-gray-600">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Activa
                    </p>
                </div>
            </div>

            <a href="{{ route('admin.instituciones.hospitals.create', $institucion) }}"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-azul-prodifem px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-4 focus:ring-blue-200">
                <span class="text-base leading-none" aria-hidden="true">+</span>
                Crear hospital
            </a>
        </div>

        <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
            <div class="border-b border-gray-200 px-4 py-3">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Hospitales relacionados</h2>
                        <p class="text-xs text-gray-500">
                            {{ $totalHospitals }} {{ $totalHospitals === 1 ? 'hospital' : 'hospitales' }}
                        </p>
                    </div>
                    <a href="{{ route('admin.instituciones.hospitals.export', $institucion) }}"
                        class="inline-flex min-h-9 items-center justify-center gap-2 rounded-lg border border-emerald-600 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-300">
                        <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
                        Descargar Excel
                    </a>
                </div>

                <form method="GET" action="{{ route('admin.instituciones.hospitals', $institucion) }}"
                    class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-12">
                    <label class="relative md:col-span-4">
                        <span class="sr-only">Buscar hospital</span>
                        <input type="search" name="search" value="{{ $search }}"
                            placeholder="Buscar por nombre, clave, municipio o usuario..."
                            class="h-10 w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>

                    <label class="md:col-span-2">
                        <span class="sr-only">Tipo de unidad</span>
                        <select name="unit_type"
                            class="h-10 w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="all">Tipo de unidad: Todos</option>
                            @foreach ($unitTypes as $type)
                                <option value="{{ $type }}" @selected($unitType === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="md:col-span-2">
                        <span class="sr-only">Estatus</span>
                        <select name="status"
                            class="h-10 w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="all" @selected($status === 'all')>Estatus: Todos</option>
                            <option value="active" @selected($status === 'active')>Activos</option>
                            <option value="inactive" @selected($status === 'inactive')>Inactivos</option>
                        </select>
                    </label>

                    <label class="md:col-span-2">
                        <span class="sr-only">L&iacute;nea de servicio</span>
                        <select name="service"
                            class="h-10 w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="all" @selected($service === 'all')>Servicio: Todos</option>
                            <option value="oncology" @selected($service === 'oncology')>Oncol&oacute;gicos</option>
                            <option value="antibiotics" @selected($service === 'antibiotics')>Antibi&oacute;ticos</option>
                            <option value="nutrition" @selected($service === 'nutrition')>Nutricionales</option>
                        </select>
                    </label>

                    <div class="flex gap-2 md:col-span-2">
                        <button type="submit"
                            class="inline-flex h-10 flex-1 items-center justify-center rounded-lg bg-azul-prodifem px-3 text-sm font-semibold text-white hover:bg-blue-900">
                            Filtrar
                        </button>
                        <a href="{{ route('admin.instituciones.hospitals', $institucion) }}"
                            class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 px-3 text-xs font-medium text-gray-700 hover:bg-gray-50">
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1500px] text-left text-xs text-gray-600">
                    <thead class="bg-gray-50 text-[11px] uppercase text-gray-700">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Hospital</th>
                            <th class="px-4 py-3 font-semibold">Usuario</th>
                            <th class="px-4 py-3 font-semibold">Contrase&ntilde;a</th>
                            <th class="px-4 py-3 font-semibold">Clave</th>
                            <th class="px-4 py-3 font-semibold">Tipo de unidad</th>
                            <th class="px-4 py-3 font-semibold">Municipio</th>
                            <th class="px-4 py-3 font-semibold">L&iacute;neas de servicio</th>
                            <th class="px-4 py-3 font-semibold">Estatus</th>
                            <th class="px-4 py-3 text-center font-semibold">Bloqueo</th>
                            <th class="px-4 py-3 text-center font-semibold">Editar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse ($hospitals as $hospital)
                            @php
                                $hasOncology = $hospital->service_oncology || $hospital->onco_medicine_list_id;
                                $hasNutrition = $hospital->service_nutrition || $hospital->nutri_medicine_list_id;
                                $hasServices = $hasOncology || $hospital->service_antibiotics || $hasNutrition;
                            @endphp
                            <tr class="hover:bg-gray-50" data-hospital-credential-group>
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $hospital->name }}</td>
                                <td class="min-w-64 px-4 py-3 align-top">
                                    @forelse ($hospital->users as $accessUser)
                                        <div class="{{ ! $loop->first ? 'mt-2 border-t border-gray-100 pt-2' : '' }}">
                                            @can('usuarios')
                                                <x-inline-user-credential-editor :user="$accessUser" field="username" compact />
                                            @else
                                                <span class="font-semibold text-gray-900">{{ $accessUser->username }}</span>
                                            @endcan
                                            @unless ($accessUser->is_active)
                                                <span class="mt-1 inline-flex rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-500">
                                                    Inactivo
                                                </span>
                                            @endunless
                                        </div>
                                    @empty
                                        <span class="block text-gray-400">Sin usuario registrado</span>
                                        @can('usuarios')
                                            <a href="{{ route('admin.users.create', ['hospital_id' => $hospital->id, 'role' => 'Institucion']) }}"
                                                class="mt-2 inline-flex h-7 items-center justify-center gap-1 rounded border border-blue-800 px-2.5 text-[11px] font-semibold text-blue-800 hover:bg-blue-50">
                                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                                Crear usuario
                                            </a>
                                        @endcan
                                    @endforelse
                                </td>
                                <td class="min-w-72 px-4 py-3 align-top">
                                    @forelse ($hospital->users as $accessUser)
                                        @php
                                            $accessPassword = (string) ($accessUser->credential_password
                                                ?: $accessUser->training_credential_password
                                                ?: '');
                                        @endphp
                                        <div class="{{ ! $loop->first ? 'mt-2 border-t border-gray-100 pt-2' : '' }}">
                                            @can('usuarios')
                                                <x-inline-user-credential-editor :user="$accessUser" field="password" compact
                                                    :display-value="$accessPassword" empty-label="Sin contrasena" />
                                            @else
                                                <span aria-label="Contrase&ntilde;a protegida" class="text-gray-500">
                                                    &bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;
                                                </span>
                                            @endcan
                                        </div>
                                    @empty
                                        <span class="block text-gray-400">Sin contrase&ntilde;a registrada</span>
                                        @can('usuarios')
                                            <a href="{{ route('admin.users.create', ['hospital_id' => $hospital->id, 'role' => 'Institucion']) }}"
                                                class="mt-2 inline-flex h-7 items-center justify-center gap-1 rounded border border-blue-800 px-2.5 text-[11px] font-semibold text-blue-800 hover:bg-blue-50">
                                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                                Crear contrase&ntilde;a
                                            </a>
                                        @endcan
                                    @endforelse
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $hospital->internal_key ?: '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $hospital->unit_type ?: '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $hospital->municipality ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @if ($hasOncology)
                                            <span class="rounded border border-emerald-300 bg-emerald-50 px-2 py-1 text-[10px] font-medium text-emerald-700">Oncol&oacute;gicos</span>
                                        @endif
                                        @if ($hospital->service_antibiotics)
                                            <span class="rounded border border-red-300 bg-red-50 px-2 py-1 text-[10px] font-medium text-red-700">Antibi&oacute;ticos</span>
                                        @endif
                                        @if ($hasNutrition)
                                            <span class="rounded border border-blue-300 bg-blue-50 px-2 py-1 text-[10px] font-medium text-blue-700">Nutricionales</span>
                                        @endif
                                        @unless ($hasServices)
                                            <span class="text-gray-400">Sin definir</span>
                                        @endunless
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-2 font-medium {{ $hospital->is_active ? 'text-emerald-700' : 'text-gray-500' }}">
                                        <span class="h-2 w-2 rounded-full {{ $hospital->is_active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                        {{ $hospital->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <form method="POST"
                                        action="{{ route('admin.users.hospitals.status.update', $hospital) }}"
                                        data-hospital-access-form
                                        data-hospital-name="{{ $hospital->name }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_active" value="{{ $hospital->access_is_active ? 0 : 1 }}">

                                        <button type="submit"
                                            class="inline-flex min-w-24 items-center justify-center rounded-full px-3 py-2 text-xs font-semibold text-white transition focus:outline-none focus:ring-4 {{ $hospital->access_is_active
                                                ? 'bg-green-600 hover:bg-green-700 focus:ring-green-300'
                                                : 'bg-red-600 hover:bg-red-700 focus:ring-red-300' }}">
                                            <i class="fa-solid {{ $hospital->access_is_active ? 'fa-lock-open' : 'fa-lock' }} pr-1"
                                                aria-hidden="true"></i>
                                            <span>{{ $hospital->access_is_active ? 'Bloquear' : 'Bloqueado' }}</span>
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @can('usuarios')
                                        @if ($hospital->users->isNotEmpty())
                                            <button type="button" data-inline-group-start
                                                class="inline-flex min-h-8 items-center justify-center gap-1.5 rounded-full bg-azul-prodifem px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-300"
                                                title="Editar usuario y contrase&ntilde;a">
                                                <i class="fa-solid fa-pen" aria-hidden="true"></i>
                                                Editar
                                            </button>
                                        @else
                                            <a href="{{ route('admin.users.create', ['hospital_id' => $hospital->id, 'role' => 'Institucion']) }}"
                                                class="inline-flex min-h-8 items-center justify-center gap-1.5 rounded-full bg-azul-prodifem px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-300">
                                                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                                Crear acceso
                                            </a>
                                        @endif
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-10 text-center text-sm text-gray-500">
                                    No hay hospitales relacionados con estos filtros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($hospitals->hasPages())
                <div class="border-t border-gray-200 px-4 py-3">
                    {{ $hospitals->links() }}
                </div>
            @endif
        </div>
    </section>

    @include('admin.instituciones.partials.hospital-map')

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const credentialEditorSelector = '[data-inline-credential-form]';

                function isPasswordEditor(form) {
                    return form.dataset.isPassword === 'true';
                }

                function setPasswordVisibility(form, visible) {
                    const valueElement = form.querySelector('[data-inline-value]');
                    const revealButton = form.querySelector('[data-inline-reveal]');
                    const icon = revealButton?.querySelector('i');

                    if (!valueElement || !revealButton) {
                        return;
                    }

                    valueElement.style.webkitTextSecurity = visible ? 'none' : 'disc';
                    revealButton.dataset.visible = visible ? 'true' : 'false';
                    revealButton.title = visible ? 'Ocultar contraseña' : 'Mostrar contraseña';
                    revealButton.setAttribute('aria-label', revealButton.title);

                    if (icon) {
                        icon.className = visible ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
                    }
                }

                function closeCredentialEditor(form, restoreValue = true) {
                    const display = form.querySelector('[data-inline-display]');
                    const editor = form.querySelector('[data-inline-editor]');
                    const input = form.querySelector('[data-inline-input]');
                    const error = form.querySelector('[data-inline-error]');

                    if (restoreValue && input) {
                        input.value = form.dataset.originalValue || '';
                    }

                    if (input && isPasswordEditor(form)) {
                        input.type = 'password';
                    }

                    if (display) {
                        display.hidden = false;
                    }

                    if (editor) {
                        editor.hidden = true;
                    }

                    if (error) {
                        error.hidden = true;
                        error.textContent = '';
                    }

                    if (isPasswordEditor(form)) {
                        setPasswordVisibility(form, false);
                    }
                }

                function openCredentialEditor(form) {
                    const display = form.querySelector('[data-inline-display]');
                    const editor = form.querySelector('[data-inline-editor]');
                    const input = form.querySelector('[data-inline-input]');

                    if (display) {
                        display.hidden = true;
                    }

                    if (editor) {
                        editor.hidden = false;
                    }

                    if (input) {
                        input.focus();
                        input.select();
                    }
                }

                function closeCredentialEditorsExcept(formsToKeep) {
                    document.querySelectorAll(credentialEditorSelector).forEach(function(form) {
                        if (!formsToKeep.has(form)) {
                            closeCredentialEditor(form);
                        }
                    });
                }

                function updateCredentialDisplay(form, value) {
                    const valueElement = form.querySelector('[data-inline-value]');
                    const revealButton = form.querySelector('[data-inline-reveal]');
                    const emptyLabel = form.dataset.emptyLabel || '';

                    if (!valueElement) {
                        return;
                    }

                    valueElement.textContent = value || emptyLabel;

                    if (isPasswordEditor(form)) {
                        valueElement.className = value
                            ? 'min-w-0 flex-1 truncate font-mono font-semibold text-gray-900'
                            : 'min-w-0 flex-1 text-[11px] font-medium text-amber-700';

                        if (revealButton) {
                            revealButton.hidden = !value;
                        }

                        setPasswordVisibility(form, false);
                    } else {
                        valueElement.className = 'min-w-0 flex-1 truncate font-medium text-gray-800';
                    }
                }

                function credentialValidationMessage(payload) {
                    const firstError = payload?.errors ? Object.values(payload.errors).flat()[0] : null;

                    return firstError || payload?.message || 'No fue posible guardar el cambio.';
                }

                document.addEventListener('click', function(event) {
                    const groupButton = event.target.closest('[data-inline-group-start]');

                    if (groupButton) {
                        const group = groupButton.closest('[data-hospital-credential-group]');
                        const forms = new Set(group?.querySelectorAll(credentialEditorSelector) || []);

                        closeCredentialEditorsExcept(forms);
                        forms.forEach(openCredentialEditor);

                        const firstInput = group?.querySelector('[data-inline-input]');
                        firstInput?.focus();
                        firstInput?.select();
                        return;
                    }

                    const revealButton = event.target.closest('[data-inline-reveal]');

                    if (revealButton) {
                        const form = revealButton.closest(credentialEditorSelector);

                        if (form) {
                            setPasswordVisibility(form, revealButton.dataset.visible !== 'true');
                        }

                        return;
                    }

                    const inputRevealButton = event.target.closest('[data-inline-input-reveal]');

                    if (inputRevealButton) {
                        const form = inputRevealButton.closest(credentialEditorSelector);
                        const input = form?.querySelector('[data-inline-input]');
                        const icon = inputRevealButton.querySelector('i');

                        if (input) {
                            const showing = input.type === 'text';
                            input.type = showing ? 'password' : 'text';
                            inputRevealButton.title = showing ? 'Mostrar contraseña' : 'Ocultar contraseña';
                            inputRevealButton.setAttribute('aria-label', inputRevealButton.title);

                            if (icon) {
                                icon.className = showing ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
                            }
                        }

                        return;
                    }

                    const startButton = event.target.closest('[data-inline-start]');

                    if (startButton) {
                        const form = startButton.closest(credentialEditorSelector);

                        if (form) {
                            closeCredentialEditorsExcept(new Set([form]));
                            openCredentialEditor(form);
                        }

                        return;
                    }

                    const cancelButton = event.target.closest('[data-inline-cancel]');

                    if (cancelButton) {
                        const form = cancelButton.closest(credentialEditorSelector);

                        if (form) {
                            closeCredentialEditor(form);
                        }
                    }
                });

                document.addEventListener('keydown', function(event) {
                    if (event.key !== 'Escape' || !event.target.matches('[data-inline-input]')) {
                        return;
                    }

                    const group = event.target.closest('[data-hospital-credential-group]');
                    group?.querySelectorAll(credentialEditorSelector).forEach(function(form) {
                        closeCredentialEditor(form);
                    });
                });

                document.addEventListener('submit', async function(event) {
                    const form = event.target.closest(credentialEditorSelector);

                    if (!form) {
                        return;
                    }

                    event.preventDefault();
                    const submitButton = form.querySelector('[data-inline-submit]');
                    const cancelButton = form.querySelector('[data-inline-cancel]');
                    const input = form.querySelector('[data-inline-input]');
                    const error = form.querySelector('[data-inline-error]');

                    if (!input || !form.reportValidity()) {
                        return;
                    }

                    const icon = submitButton?.querySelector('i');
                    const originalIconClass = icon?.className || '';
                    const submittedValue = input.value;
                    const formData = new FormData(form);
                    let requestFailed = false;

                    if (error) {
                        error.hidden = true;
                        error.textContent = '';
                    }

                    input.disabled = true;
                    [submitButton, cancelButton].forEach(function(button) {
                        if (button) {
                            button.disabled = true;
                        }
                    });

                    if (icon) {
                        icon.className = 'fa-solid fa-spinner fa-spin';
                    }

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: formData,
                        });
                        const payload = await response.json().catch(function() {
                            return {};
                        });

                        if (!response.ok) {
                            throw new Error(credentialValidationMessage(payload));
                        }

                        const savedValue = payload.value || payload.username || submittedValue.trim();
                        form.dataset.originalValue = savedValue;
                        input.value = savedValue;
                        updateCredentialDisplay(form, savedValue);
                        closeCredentialEditor(form, false);
                    } catch (requestError) {
                        requestFailed = true;

                        if (error) {
                            error.textContent = requestError.message;
                            error.hidden = false;
                        }
                    } finally {
                        input.disabled = false;
                        [submitButton, cancelButton].forEach(function(button) {
                            if (button) {
                                button.disabled = false;
                            }
                        });

                        if (icon) {
                            icon.className = originalIconClass;
                        }

                        if (requestFailed) {
                            input.focus();
                        }
                    }
                });

                document.addEventListener('submit', async function(event) {
                    const form = event.target.closest('[data-hospital-access-form]');

                    if (!form) {
                        return;
                    }

                    event.preventDefault();
                    const statusInput = form.querySelector('input[name="is_active"]');
                    const button = form.querySelector('button[type="submit"]');
                    const willActivate = statusInput?.value === '1';
                    const hospitalName = form.dataset.hospitalName || 'este hospital';
                    const confirmation = await Swal.fire({
                        title: 'Estas seguro?',
                        text: willActivate
                            ? `Se reactivara el acceso de los usuarios de ${hospitalName}.`
                            : `Se bloqueara el acceso de los usuarios de ${hospitalName}.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Si',
                        cancelButtonText: 'No',
                        confirmButtonColor: willActivate ? '#16a34a' : '#dc2626',
                    });

                    if (!confirmation.isConfirmed) {
                        return;
                    }

                    button.disabled = true;
                    button.setAttribute('aria-busy', 'true');

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new FormData(form),
                        });
                        const payload = await response.json().catch(function() {
                            return {};
                        });

                        if (!response.ok) {
                            const validationMessage = Object.values(payload.errors || {})
                                .flat()
                                .find(Boolean);
                            throw new Error(validationMessage || payload.message || 'No se pudo cambiar el acceso.');
                        }

                        await Swal.fire({
                            title: willActivate ? 'Acceso reactivado' : 'Acceso bloqueado',
                            text: payload.message || 'El cambio se guardo correctamente.',
                            icon: 'success',
                            confirmButtonText: 'Aceptar',
                        });
                        window.location.reload();
                    } catch (requestError) {
                        await Swal.fire({
                            title: 'No se pudo guardar',
                            text: requestError.message,
                            icon: 'error',
                            confirmButtonText: 'Aceptar',
                        });
                        button.disabled = false;
                        button.removeAttribute('aria-busy');
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>
