@if ($isSuperAdmin)
    <div class="fixed inset-0 z-[70] hidden items-center justify-center bg-gray-950/50 p-4"
        data-personnel-role-access-modal role="dialog" aria-modal="true"
        aria-labelledby="personnel-role-access-title">
        <div class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4">
                <div>
                    <h2 id="personnel-role-access-title" class="text-xl font-semibold text-gray-900">
                        Administrar rol y accesos
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Personal: <span class="font-semibold text-gray-700" data-personnel-role-access-user></span>
                    </p>
                </div>
                <button type="button"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    data-personnel-role-access-close aria-label="Cerrar">
                    <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                </button>
            </div>

            <form class="flex min-h-0 flex-1 flex-col" data-personnel-role-access-form>
                @csrf
                @method('PATCH')

                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                    <fieldset>
                        <legend class="text-sm font-semibold text-gray-900">Rol del usuario</legend>
                        <div class="mt-2 grid gap-2 sm:grid-cols-3">
                            @foreach ($roleOptions as $roleName => $roleLabel)
                                <label
                                    class="flex min-h-12 cursor-pointer items-center gap-3 rounded-md border border-gray-200 px-3 py-2 hover:border-blue-300 hover:bg-blue-50">
                                    <input type="radio" name="role" value="{{ $roleName }}"
                                        class="h-4 w-4 border-gray-300 text-blue-700 focus:ring-blue-500"
                                        data-personnel-role-option required>
                                    <span class="text-sm font-semibold text-gray-800">{{ $roleLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>

                    <fieldset class="mt-5" data-personnel-role-menu-fieldset>
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 pb-3">
                            <div>
                                <legend class="text-sm font-semibold text-gray-900">Accesos de Usuario general</legend>
                                <p class="mt-1 text-xs text-gray-500">
                                    Selecciona los menus y submenus disponibles para este usuario.
                                </p>
                            </div>
                            <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-blue-800">
                                <input type="checkbox"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-700 focus:ring-blue-500"
                                    data-personnel-role-select-all>
                                Seleccionar todo
                            </label>
                        </div>

                        <div class="mt-3 divide-y divide-gray-200 rounded-md border border-gray-200">
                            @foreach ($roleAccessTree as $menuItem)
                                @if (! empty($menuItem['children']))
                                    <details class="group" data-personnel-role-menu-group>
                                        <summary
                                            class="flex min-h-12 cursor-pointer list-none items-center gap-3 px-3 py-2 hover:bg-gray-50 [&::-webkit-details-marker]:hidden">
                                            <span
                                                class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-gray-200 text-base font-bold leading-none text-gray-600">
                                                <span class="group-open:hidden" aria-hidden="true">+</span>
                                                <span class="hidden group-open:inline" aria-hidden="true">-</span>
                                            </span>
                                            <i class="fa-solid {{ $menuItem['icon'] }} w-5 text-center text-blue-700"
                                                aria-hidden="true"></i>
                                            <span class="min-w-0 flex-1 text-sm font-semibold text-gray-800">
                                                {{ $menuItem['label'] }}
                                            </span>
                                            <input type="checkbox" name="menu_permissions[]"
                                                value="{{ $menuItem['permission'] }}"
                                                class="h-4 w-4 rounded border-gray-300 text-blue-700 focus:ring-blue-500"
                                                data-personnel-role-menu-parent
                                                aria-label="Dar acceso a {{ $menuItem['label'] }}"
                                                onclick="event.stopPropagation()">
                                        </summary>
                                        <div class="grid gap-1 bg-gray-50 px-4 py-3 sm:grid-cols-2">
                                            @foreach ($menuItem['children'] as $child)
                                                <label
                                                    class="flex min-h-9 cursor-pointer items-center gap-3 rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-white">
                                                    <input type="checkbox" name="menu_permissions[]"
                                                        value="{{ $child['permission'] }}"
                                                        class="h-4 w-4 rounded border-gray-300 text-blue-700 focus:ring-blue-500"
                                                        data-personnel-role-menu-child>
                                                    <span>{{ $child['label'] }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </details>
                                @else
                                    <label class="flex min-h-12 cursor-pointer items-center gap-3 px-3 py-2 hover:bg-gray-50">
                                        <span
                                            class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-gray-200 text-base font-bold leading-none text-gray-600"
                                            aria-hidden="true">
                                            +
                                        </span>
                                        <i class="fa-solid {{ $menuItem['icon'] }} w-5 text-center text-blue-700"
                                            aria-hidden="true"></i>
                                        <span class="min-w-0 flex-1 text-sm font-semibold text-gray-800">
                                            {{ $menuItem['label'] }}
                                        </span>
                                        <input type="checkbox" name="menu_permissions[]"
                                            value="{{ $menuItem['permission'] }}"
                                            class="h-4 w-4 rounded border-gray-300 text-blue-700 focus:ring-blue-500"
                                            data-personnel-role-menu-parent>
                                    </label>
                                @endif
                            @endforeach
                        </div>
                    </fieldset>

                    <p class="mt-3 hidden text-sm font-medium text-red-600"
                        data-personnel-role-access-error role="alert"></p>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-5 py-4">
                    <button type="button"
                        class="inline-flex h-10 items-center justify-center rounded-md border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-100"
                        data-personnel-role-access-close>
                        Cancelar
                    </button>
                    <button type="submit"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-azul-prodifem px-4 text-sm font-semibold text-white hover:bg-blue-900 disabled:cursor-not-allowed disabled:opacity-60"
                        data-personnel-role-access-save>
                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                        Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif

@push('js')
    <script>
        (function() {
            if (window.personnelUserManagementReady) {
                return;
            }

            window.personnelUserManagementReady = true;
            const editorSelector = '[data-inline-credential-form]';
            const roleModal = document.querySelector('[data-personnel-role-access-modal]');
            const roleForm = roleModal
                ? roleModal.querySelector('[data-personnel-role-access-form]')
                : null;
            const generalRole = @json(\App\Support\AdminMenuAccess::GENERAL_ROLE);
            let roleModalTrigger = null;

            function validationMessage(payload) {
                if (payload && payload.errors) {
                    const firstError = Object.values(payload.errors).flat()[0];

                    if (firstError) {
                        return firstError;
                    }
                }

                return payload && payload.message
                    ? payload.message
                    : 'No fue posible guardar el cambio.';
            }

            function closeEditor(form, restoreValue) {
                const display = form.querySelector('[data-inline-display]');
                const editor = form.querySelector('[data-inline-editor]');
                const input = form.querySelector('[data-inline-input]');
                const error = form.querySelector('[data-inline-error]');

                if (restoreValue && input) {
                    input.value = form.dataset.originalValue || '';
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
            }

            function openEditor(form) {
                document.querySelectorAll(editorSelector).forEach(function(otherForm) {
                    if (otherForm !== form) {
                        closeEditor(otherForm, true);
                    }
                });

                const display = form.querySelector('[data-inline-display]');
                const editor = form.querySelector('[data-inline-editor]');
                const input = form.querySelector('[data-inline-input]');

                if (display) {
                    display.hidden = true;
                }

                if (editor) {
                    editor.hidden = false;
                }

                input?.focus();
                input?.select();
            }

            function updateDisplayedValue(form, value) {
                const valueElement = form.querySelector('[data-inline-value]');

                if (!valueElement) {
                    return;
                }

                valueElement.textContent = value || form.dataset.emptyLabel || '';
                valueElement.className = form.dataset.isPassword === 'true' && value
                    ? 'min-w-0 flex-1 truncate font-mono font-semibold text-gray-900'
                    : (form.dataset.isPassword === 'true'
                        ? 'min-w-0 flex-1 text-[11px] font-medium text-amber-700'
                        : 'min-w-0 flex-1 truncate font-medium text-gray-800');
            }

            function roleMenuCheckboxes() {
                return roleForm
                    ? Array.from(roleForm.querySelectorAll(
                        '[data-personnel-role-menu-parent], [data-personnel-role-menu-child]'
                    ))
                    : [];
            }

            function updateRoleSelectAll() {
                if (!roleForm) {
                    return;
                }

                const selectAll = roleForm.querySelector('[data-personnel-role-select-all]');
                const checkboxes = roleMenuCheckboxes();
                const checkedCount = checkboxes.filter(function(checkbox) {
                    return checkbox.checked;
                }).length;

                if (selectAll) {
                    selectAll.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
                    selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
                }
            }

            function syncRoleMenuGroup(group) {
                const parent = group.querySelector('[data-personnel-role-menu-parent]');
                const children = Array.from(group.querySelectorAll('[data-personnel-role-menu-child]'));

                if (!parent || children.length === 0) {
                    return;
                }

                const checkedCount = children.filter(function(child) {
                    return child.checked;
                }).length;
                parent.checked = checkedCount > 0;
                parent.indeterminate = checkedCount > 0 && checkedCount < children.length;
            }

            function updateRoleMenuAvailability() {
                if (!roleForm) {
                    return;
                }

                const fieldset = roleForm.querySelector('[data-personnel-role-menu-fieldset]');
                const selectedRole = roleForm.querySelector('input[name="role"]:checked');
                const disabled = !selectedRole || selectedRole.value !== generalRole;

                if (fieldset) {
                    fieldset.disabled = disabled;
                    fieldset.classList.toggle('opacity-50', disabled);
                }
            }

            function setRoleMenuSelection(selectedPermissions) {
                const selected = new Set(selectedPermissions || []);

                roleMenuCheckboxes().forEach(function(checkbox) {
                    checkbox.checked = selected.has(checkbox.value);
                    checkbox.indeterminate = false;
                });

                roleForm?.querySelectorAll('[data-personnel-role-menu-group]').forEach(syncRoleMenuGroup);
                updateRoleSelectAll();
            }

            function openRoleModal(button) {
                if (!roleModal || !roleForm) {
                    return;
                }

                roleModalTrigger = button;
                roleForm.action = button.dataset.action || '';

                const roleRadios = Array.from(roleForm.querySelectorAll('[data-personnel-role-option]'));
                const currentRole = button.dataset.currentRole || '';
                const selectedRole = roleRadios.find(function(radio) {
                    return radio.value === currentRole;
                }) || roleRadios.find(function(radio) {
                    return radio.value === 'Admin';
                }) || roleRadios[0];

                roleRadios.forEach(function(radio) {
                    radio.checked = radio === selectedRole;
                });

                let permissions = [];

                try {
                    permissions = JSON.parse(button.dataset.menuPermissions || '[]');
                } catch (error) {
                    permissions = [];
                }

                setRoleMenuSelection(permissions);
                updateRoleMenuAvailability();

                const userLabel = roleModal.querySelector('[data-personnel-role-access-user]');
                const error = roleForm.querySelector('[data-personnel-role-access-error]');

                if (userLabel) {
                    userLabel.textContent = button.dataset.userName || '';
                }

                if (error) {
                    error.hidden = true;
                    error.textContent = '';
                }

                roleModal.classList.remove('hidden');
                roleModal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
                window.setTimeout(function() {
                    selectedRole?.focus();
                }, 0);
            }

            function closeRoleModal() {
                if (!roleModal) {
                    return;
                }

                roleModal.classList.add('hidden');
                roleModal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
                roleModalTrigger?.focus();
                roleModalTrigger = null;
            }

            function closeAccessStatusMenus(exceptMenu) {
                document.querySelectorAll('[data-access-status-menu]:not([hidden])').forEach(function(menu) {
                    if (menu === exceptMenu) {
                        return;
                    }

                    menu.hidden = true;
                    menu.style.removeProperty('left');
                    menu.style.removeProperty('top');
                    menu.style.removeProperty('visibility');

                    const trigger = document.querySelector(
                        `[data-access-status-menu-trigger][aria-controls="${menu.id}"]`
                    );
                    trigger?.setAttribute('aria-expanded', 'false');
                });
            }

            function toggleAccessStatusMenu(trigger) {
                const menu = document.getElementById(trigger.getAttribute('aria-controls'));

                if (!menu) {
                    return;
                }

                const shouldOpen = menu.hidden;
                closeAccessStatusMenus(menu);

                if (!shouldOpen) {
                    menu.hidden = true;
                    trigger.setAttribute('aria-expanded', 'false');
                    return;
                }

                menu.hidden = false;
                menu.style.visibility = 'hidden';

                const triggerRect = trigger.getBoundingClientRect();
                const menuRect = menu.getBoundingClientRect();
                const viewportPadding = 8;
                let left = triggerRect.right - menuRect.width;
                let top = triggerRect.bottom + 6;

                left = Math.max(viewportPadding, Math.min(left, window.innerWidth - menuRect.width - viewportPadding));

                if (top + menuRect.height > window.innerHeight - viewportPadding) {
                    top = Math.max(viewportPadding, triggerRect.top - menuRect.height - 6);
                }

                menu.style.left = `${Math.round(left)}px`;
                menu.style.top = `${Math.round(top)}px`;
                menu.style.visibility = 'visible';
                trigger.setAttribute('aria-expanded', 'true');
                menu.querySelector('button')?.focus();
            }

            document.addEventListener('click', function(event) {
                const statusMenuTrigger = event.target.closest('[data-access-status-menu-trigger]');

                if (statusMenuTrigger) {
                    event.preventDefault();
                    toggleAccessStatusMenu(statusMenuTrigger);
                    return;
                }

                if (!event.target.closest('[data-access-status-menu-root]')) {
                    closeAccessStatusMenus();
                }

                const roleButton = event.target.closest('[data-personnel-role-access-open]');

                if (roleButton) {
                    openRoleModal(roleButton);
                    return;
                }

                if (roleModal && (event.target === roleModal
                    || event.target.closest('[data-personnel-role-access-close]'))) {
                    closeRoleModal();
                    return;
                }

                const startButton = event.target.closest('[data-inline-start]');

                if (startButton) {
                    const form = startButton.closest(editorSelector);

                    if (form) {
                        openEditor(form);
                    }

                    return;
                }

                const cancelButton = event.target.closest('[data-inline-cancel]');

                if (cancelButton) {
                    const form = cancelButton.closest(editorSelector);

                    if (form) {
                        closeEditor(form, true);
                    }
                }
            });

            document.addEventListener('change', function(event) {
                if (!roleForm || !event.target.closest('[data-personnel-role-access-form]')) {
                    return;
                }

                if (event.target.matches('[data-personnel-role-option]')) {
                    updateRoleMenuAvailability();
                    return;
                }

                if (event.target.matches('[data-personnel-role-select-all]')) {
                    roleMenuCheckboxes().forEach(function(checkbox) {
                        checkbox.checked = event.target.checked;
                        checkbox.indeterminate = false;
                    });
                    roleForm.querySelectorAll('[data-personnel-role-menu-group]').forEach(syncRoleMenuGroup);
                    updateRoleSelectAll();
                    return;
                }

                if (event.target.matches('[data-personnel-role-menu-parent]')) {
                    const group = event.target.closest('[data-personnel-role-menu-group]');

                    group?.querySelectorAll('[data-personnel-role-menu-child]').forEach(function(child) {
                        child.checked = event.target.checked;
                    });
                    event.target.indeterminate = false;
                }

                if (event.target.matches('[data-personnel-role-menu-child]')) {
                    const group = event.target.closest('[data-personnel-role-menu-group]');

                    if (group) {
                        syncRoleMenuGroup(group);
                    }
                }

                updateRoleSelectAll();
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && document.querySelector('[data-access-status-menu]:not([hidden])')) {
                    event.preventDefault();
                    closeAccessStatusMenus();
                    return;
                }

                if (event.key === 'Escape' && roleModal && !roleModal.classList.contains('hidden')) {
                    event.preventDefault();
                    closeRoleModal();
                    return;
                }

                if (event.key === 'Escape' && event.target.matches('[data-inline-input]')) {
                    const form = event.target.closest(editorSelector);

                    if (form) {
                        event.preventDefault();
                        closeEditor(form, true);
                    }
                }
            });

            document.addEventListener('submit', async function(event) {
                const roleAccessForm = event.target.closest('[data-personnel-role-access-form]');

                if (roleAccessForm) {
                    event.preventDefault();

                    if (!roleAccessForm.reportValidity()) {
                        return;
                    }

                    const saveButton = roleAccessForm.querySelector('[data-personnel-role-access-save]');
                    const error = roleAccessForm.querySelector('[data-personnel-role-access-error]');

                    if (error) {
                        error.hidden = true;
                        error.textContent = '';
                    }

                    if (saveButton) {
                        saveButton.disabled = true;
                    }

                    try {
                        const response = await fetch(roleAccessForm.action, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new FormData(roleAccessForm),
                        });
                        const payload = await response.json().catch(function() {
                            return {};
                        });

                        if (!response.ok) {
                            throw new Error(validationMessage(payload));
                        }

                        closeRoleModal();
                        await window.Swal?.fire({
                            title: 'Accesos actualizados',
                            text: payload.message || 'El rol y los permisos se guardaron correctamente.',
                            icon: 'success',
                            confirmButtonText: 'Aceptar',
                        });
                        window.location.reload();
                    } catch (requestError) {
                        if (error) {
                            error.textContent = requestError.message;
                            error.hidden = false;
                        }
                    } finally {
                        if (saveButton) {
                            saveButton.disabled = false;
                        }
                    }

                    return;
                }

                const credentialForm = event.target.closest(editorSelector);

                if (credentialForm) {
                    event.preventDefault();

                    const input = credentialForm.querySelector('[data-inline-input]');

                    if (!input || !credentialForm.reportValidity()) {
                        return;
                    }

                    const submitButton = credentialForm.querySelector('[data-inline-submit]');
                    const cancelButton = credentialForm.querySelector('[data-inline-cancel]');
                    const submitIcon = credentialForm.querySelector('[data-inline-submit-icon]');
                    const spinner = credentialForm.querySelector('[data-inline-submit-spinner]');
                    const error = credentialForm.querySelector('[data-inline-error]');
                    const submittedValue = input.value;

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

                    if (submitIcon) {
                        submitIcon.hidden = true;
                    }

                    if (spinner) {
                        spinner.hidden = false;
                    }

                    try {
                        const response = await fetch(credentialForm.action, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new FormData(credentialForm),
                        });
                        const payload = await response.json().catch(function() {
                            return {};
                        });

                        if (!response.ok) {
                            throw new Error(validationMessage(payload));
                        }

                        const savedValue = payload.value || payload.username || submittedValue.trim();
                        credentialForm.dataset.originalValue = savedValue;
                        input.value = savedValue;
                        updateDisplayedValue(credentialForm, savedValue);
                        closeEditor(credentialForm, false);
                    } catch (requestError) {
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

                        if (submitIcon) {
                            submitIcon.hidden = false;
                        }

                        if (spinner) {
                            spinner.hidden = true;
                        }
                    }

                    return;
                }

                const statusForm = event.target.closest('[data-access-status-form]');

                if (!statusForm) {
                    return;
                }

                event.preventDefault();
                closeAccessStatusMenus();
                const button = statusForm.querySelector('button[type="submit"]');
                const willActivate = statusForm.querySelector('input[name="is_active"]')?.value === '1';
                const targetLabel = statusForm.dataset.targetLabel || 'usuario';
                let confirmed = false;

                if (window.Swal) {
                    const result = await Swal.fire({
                        title: 'Estas seguro?',
                        text: willActivate
                            ? `Se activara el acceso de este ${targetLabel}.`
                            : `Se bloqueara el acceso de este ${targetLabel}.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Si',
                        cancelButtonText: 'No',
                        confirmButtonColor: willActivate ? '#059669' : '#dc2626',
                    });
                    confirmed = result.isConfirmed;
                } else {
                    confirmed = window.confirm('Estas seguro?');
                }

                if (!confirmed) {
                    return;
                }

                if (button) {
                    button.disabled = true;
                }

                try {
                    const response = await fetch(statusForm.action, {
                        method: 'POST',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new FormData(statusForm),
                    });
                    const payload = await response.json().catch(function() {
                        return {};
                    });

                    if (!response.ok) {
                        throw new Error(validationMessage(payload));
                    }

                    await window.Swal?.fire({
                        title: willActivate ? 'Acceso activado' : 'Acceso bloqueado',
                        text: payload.message || 'El cambio se guardo correctamente.',
                        icon: 'success',
                        confirmButtonText: 'Aceptar',
                    });
                    window.location.reload();
                } catch (requestError) {
                    if (button) {
                        button.disabled = false;
                    }

                    if (window.Swal) {
                        await Swal.fire({
                            title: 'No se pudo guardar',
                            text: requestError.message,
                            icon: 'error',
                            confirmButtonText: 'Aceptar',
                        });
                    } else {
                        window.alert(requestError.message);
                    }
                }
            });
        })();
    </script>
@endpush
