<div class="corporate-notifications">
    <x-dropdown align="right" width="64" dropdownClasses="corporate-dropdown-panel">
        <x-slot name="trigger">
                <button type="button" wire:click="resetNotification"
                    class="corporate-control corporate-notifications__trigger"
                    x-bind:aria-expanded="open.toString()" aria-haspopup="true" aria-label="Notificaciones" title="Notificaciones">
                    <span class="corporate-notifications__icon" wire:ignore><i data-lucide="bell" aria-hidden="true"></i></span>
                    <span class="corporate-notifications__label">Notificaciones</span>
                    @if (auth()->user()->notification)
                        <span
                            class="corporate-notifications__badge">
                            {{ auth()->user()->notification }}
                        </span>
                    @endif
                </button>

        </x-slot>

        <x-slot name="content">
            <div class="corporate-notifications__list">
                @if ($this->notifications->count())
                    <ul class="divide-y">
                        @foreach ($this->notifications as $notification)
                            <li @class(['bg-gray-200' => !$notification->read_at]) wire:click="readNotification('{{ $notification->id }}')">
                                <x-dropdown-link href="{{ route('admin.nutricionales.solicitudes.index') }}">
                                    {{ $notification->data['message'] }}
                                    <br>
                                    <span class="text-xs font-semibold">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </span>
                                </x-dropdown-link>
                            </li>
                        @endforeach
                    </ul>
                    @if (auth()->user()->notifications->count() > $count)
                        <div class="px-4 pt-2 pb-1 flex justify-center">
                            <button wire:click="incrementCount()" class="text-sm text-blue-500 font-semibold">
                                ver más notificaciones
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
