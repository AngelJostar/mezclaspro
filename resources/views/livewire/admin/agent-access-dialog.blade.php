@if ($accessAgentId)
<div class="agent-modal-overlay" wire:key="agent-access-{{ $accessAgentId }}">
    <section class="agent-modal agent-access-modal" role="dialog" aria-modal="true" aria-labelledby="agent-access-title"
        x-data="{ tab: 'user', search: '' }" x-trap.inert.noscroll="true" x-init="$nextTick(() => $refs.search.focus())"
        @keydown.escape.prevent.stop="$wire.closeAccess()">
        <header class="agent-modal-header">
            <div><h3 id="agent-access-title">Autorizaciones</h3><p>{{ \App\Models\AiAgent::find($accessAgentId)?->name }}</p></div>
            <button type="button" class="agent-edit" wire:click="closeAccess" aria-label="Cerrar autorizaciones">×</button>
        </header>
        <form wire:submit="saveAccess">
            <div class="agent-access-body">
                <p>Define quién puede consultar, ejecutar o editar este agente. Ejecutar y editar requieren consultar.</p>
                <p class="agent-access-help">Los permisos por rol y puesto se suman. Una autorización individual los sustituye. Para bloquear a un usuario, activa su autorización individual y deja los tres permisos vacíos.</p>
                <p class="agent-access-help">Editar permite cambiar la información e instrucciones. La configuración, activación y administración de accesos quedan a cargo del superadministrador.</p>
                <div class="agent-access-tabs" role="tablist" aria-label="Tipo de autorización">
                    @foreach (['user' => 'Usuarios', 'role' => 'Roles', 'position' => 'Puestos'] as $type => $label)
                        <button type="button" role="tab" :aria-selected="tab === '{{ $type }}'" @click="tab = '{{ $type }}'; search = ''">{{ $label }}</button>
                    @endforeach
                </div>
                <label for="agent-access-search">Buscar usuario, rol o puesto</label>
                <input id="agent-access-search" type="search" x-ref="search" x-model="search" placeholder="Escribe un nombre" autocomplete="off">
                @error('accessRows')<p class="agent-error" role="alert">{{ $message }}</p>@enderror
                @if ($errors->has('rows*'))<p class="agent-error" role="alert">Revisa los permisos seleccionados.</p>@endif
                <div class="agent-access-table-wrap">
                    <table class="agent-access-table">
                        <thead><tr><th>Asignar</th><th>Usuario / Rol / Puesto</th><th>Consultar</th><th>Ejecutar</th><th>Editar</th></tr></thead>
                        <tbody>
                        @foreach ($accessRows as $index => $row)
                            <tr wire:key="access-row-{{ $index }}"
                                x-show="tab === @js($row['subject_type']) && @js(mb_strtolower($row['label'])).normalize('NFD').replace(/[\u0300-\u036f]/g, '').includes(search.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, ''))">
                                <td data-label="Asignar"><input type="checkbox" wire:model="accessRows.{{ $index }}.assigned" aria-label="Asignar autorización a {{ $row['label'] }}"></td>
                                <th scope="row">{{ $row['label'] }}</th>
                                @foreach (\App\Services\Agents\AgentAccess::LABELS as $permission => $label)
                                    <td data-label="{{ $label }}"><input type="checkbox" wire:model="accessRows.{{ $index }}.{{ $permission }}" aria-label="{{ $label }} · {{ $row['label'] }}"
                                        @change="if ($event.target.checked) { $wire.set('accessRows.{{ $index }}.assigned', true, false); $wire.set('accessRows.{{ $index }}.can_view', true, false); } @if($permission === 'can_view') else { $wire.set('accessRows.{{ $index }}.can_run', false, false); $wire.set('accessRows.{{ $index }}.can_edit', false, false); } @endif"></td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <p class="agent-access-help">El superadministrador conserva acceso completo. Los cambios se aplican al guardar.</p>
            </div>
            <footer class="agent-modal-actions">
                <button type="button" class="agent-cancel" wire:click="closeAccess">Cancelar</button>
                <button type="submit" class="agent-save" wire:loading.attr="disabled" wire:target="saveAccess">Guardar autorizaciones</button>
            </footer>
        </form>
    </section>
</div>
@endif
