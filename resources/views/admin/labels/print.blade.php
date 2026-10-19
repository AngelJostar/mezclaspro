<x-admin-layout>
    <div class="mx-auto mt-8 max-w-2xl rounded-xl bg-white p-6 shadow-lg">
        <h1 class="text-2xl font-semibold text-gray-900">Impresion de etiquetas</h1>
        <p class="mt-2 text-sm text-gray-600">{{ $documentLabel }}</p>

        <div class="mt-6 grid gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm sm:grid-cols-2">
            <div>
                <span class="block text-gray-500">Etiquetas registradas</span>
                <strong class="text-xl text-gray-900">{{ $summary['copies'] }}</strong>
            </div>
            <div>
                <span class="block text-gray-500">Siguiente impresion</span>
                <strong class="text-xl text-gray-900">{{ $summary['has_prints'] ? '1 etiqueta' : '3 etiquetas' }}</strong>
            </div>
        </div>

        @if (! $summary['has_prints'])
            <p class="mt-5 text-sm text-gray-700">
                Esta es la primera impresion. Se generara un PDF con 3 etiquetas iguales.
            </p>
            <form method="POST" action="{{ $printRoute }}" class="mt-5">
                @csrf
                <button type="submit"
                    class="rounded-lg bg-azul-prodifem px-5 py-3 text-sm font-semibold text-white hover:bg-blue-800">
                    Imprimir 3 etiquetas
                </button>
            </form>
        @elseif ($canReprint)
            <div class="mt-5 rounded-lg border border-amber-300 bg-amber-50 p-4">
                <h2 class="font-semibold text-amber-900">Reimpresion protegida</h2>
                <p class="mt-1 text-sm text-amber-800">
                    Confirma tu contrasena de Super administrador para generar 1 etiqueta adicional.
                </p>
            </div>

            <form method="POST" action="{{ $printRoute }}" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label for="label-print-password" class="block text-sm font-semibold text-gray-800">
                        Contrasena
                    </label>
                    <input id="label-print-password" name="password" type="password" required
                        autocomplete="current-password" autofocus
                        class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-600 focus:ring-blue-600">
                    @error('password')
                        <p class="mt-2 text-sm font-semibold text-red-600" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit"
                    class="rounded-lg bg-amber-600 px-5 py-3 text-sm font-semibold text-white hover:bg-amber-700">
                    Autorizar e imprimir 1 etiqueta
                </button>
            </form>
        @else
            <div class="mt-5 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                Estas etiquetas ya fueron impresas. Solicita a un Super administrador que inicie sesion y autorice la reimpresion.
            </div>
        @endif

        @if ($summary['history']->isNotEmpty())
            <div class="mt-8 border-t border-gray-200 pt-6">
                <h2 class="text-lg font-semibold text-gray-900">Historial de impresiones</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-gray-100 text-xs uppercase text-gray-600">
                            <tr>
                                <th class="px-3 py-2">Fecha</th>
                                <th class="px-3 py-2">Tipo</th>
                                <th class="px-3 py-2">Cantidad</th>
                                <th class="px-3 py-2">Usuario</th>
                                <th class="px-3 py-2">Autorizo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($summary['history'] as $print)
                                @php
                                    $requesterName = trim(($print->requester?->name ?? '').' '.($print->requester?->lastname ?? ''));
                                    $authorizerName = trim(($print->authorizer?->name ?? '').' '.($print->authorizer?->lastname ?? ''));
                                @endphp
                                <tr class="border-b border-gray-100">
                                    <td class="whitespace-nowrap px-3 py-2">{{ $print->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2">{{ $print->print_type === 'initial' ? 'Inicial' : 'Reimpresion' }}</td>
                                    <td class="px-3 py-2">{{ $print->copies }}</td>
                                    <td class="px-3 py-2">{{ $requesterName ?: ($print->requester?->username ?? 'Usuario no disponible') }}</td>
                                    <td class="px-3 py-2">{{ $print->print_type === 'initial' ? 'No requerida' : ($authorizerName ?: ($print->authorizer?->username ?? 'Usuario no disponible')) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-admin-layout>
