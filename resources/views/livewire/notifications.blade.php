<div class="corporate-notifications" wire:poll.10s>
    <x-dropdown align="right" width="64" dropdownClasses="corporate-dropdown-panel">
        <x-slot name="trigger">
                <button type="button"
                    class="corporate-control corporate-notifications__trigger"
                    x-bind:aria-expanded="open.toString()" aria-haspopup="true" aria-label="Notificaciones" title="Notificaciones">
                    <span class="corporate-notifications__icon" wire:ignore><i data-lucide="bell" aria-hidden="true"></i></span>
                    <span class="corporate-notifications__label">Notificaciones</span>
                    @if ($this->unreadCount)
                        <span class="corporate-notifications__badge">
                            {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
                        </span>
                    @endif
                </button>

        </x-slot>

        <x-slot name="content">
            <div class="corporate-notifications__list">
                @if ($this->notifications->count())
                    <ul class="divide-y">
                        @foreach ($this->notifications as $notification)
                            <li @class([
                                'bg-blue-50' => !$notification->read_at && ($notification->data['severity'] ?? null) !== 'error',
                                'bg-red-50' => !$notification->read_at && ($notification->data['severity'] ?? null) === 'error',
                            ]) wire:click="readNotification('{{ $notification->id }}')">
                                <x-dropdown-link href="{{ $notification->data['url'] ?? route('admin.nutricionales.solicitudes.index') }}">
                                    <span class="flex items-start gap-2">
                                        <span class="mt-1.5 h-2 w-2 shrink-0 rounded-full {{ ($notification->data['severity'] ?? null) === 'error' ? 'bg-red-500' : (($notification->data['severity'] ?? null) === 'success' ? 'bg-emerald-500' : 'bg-blue-500') }}"></span>
                                        <span>
                                            @if (filled($notification->data['title'] ?? null))
                                                <strong class="block text-gray-800">{{ $notification->data['title'] }}</strong>
                                            @endif
                                            {{ $notification->data['message'] ?? 'Notificación actualizada.' }}
                                        </span>
                                    </span>
                                    <br>
                                    <span class="text-xs font-semibold">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </span>
                                </x-dropdown-link>
                            </li>
                        @endforeach
                    </ul>
                    @if ($notifications->count() > $count)
                        <div class="px-4 pt-2 pb-1 flex justify-center">
                            <button wire:click="incrementCount()" class="text-sm text-blue-500 font-semibold">
                                ver más notificaciones
                            </button>
                        </div>
                    @endif
                    @if ($this->unreadCount)
                        <div class="border-t px-4 py-2 text-center">
                            <button type="button" wire:click="resetNotification" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                Marcar todas como atendidas
                            </button>
                        </div>
                    @endif
                @else
                    <div class="px-4 py-2">
                        No tienes notificaciones
                    </div>

                @endif
            </div>


        </x-slot>
    </x-dropdown>
</div>
