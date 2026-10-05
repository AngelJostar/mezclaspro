<x-admin-layout>
    <h1 class="text-2xl font-medium text-gray-800">Solicitudes de cotización de hospitales</h1>
    <a class="text-emerald-700" href="{{ route('admin.solicitudes.cotizacion.index') }}">Volver a cotizaciones</a>
    <div class="mt-4 space-y-4">
        @forelse ($requests as $request)
            <section class="rounded-lg border bg-white p-4">
                <h2 class="font-semibold">{{ $request->folio }} · {{ $request->hospital?->name }}</h2>
                <p class="text-sm text-gray-600">{{ $request->category }} · {{ $request->summary()['status'] }} · {{ $request->created_at->format('d/m/Y H:i') }}</p>
                <p class="text-sm">Ventas: {{ $request->summary()['seller'] }}</p>
                @if ($request->patient_name) <p>Paciente: {{ $request->patient_name }}</p> @endif
                <ul class="my-3 list-inside list-disc">@foreach ($request->items as $item)<li>{{ $item['name'] }} · {{ $item['quantity'] }} {{ $item['unit'] }} · {{ $item['presentation'] }}</li>@endforeach</ul>
                <p class="whitespace-pre-wrap">{{ $request->observations }}</p>
                <div class="mt-3 flex gap-3">
                    @if ($request->attachment_path)<a class="rounded border px-4 py-2 text-emerald-700" target="_blank" rel="noopener" href="{{ route('admin.solicitudes.cotizacion.hospital-requests.attachment', $request) }}">Ver receta adjunta</a>@endif
                    @if (!$request->quotation_id)<a class="rounded bg-emerald-600 px-4 py-2 text-white" href="{{ route('admin.solicitudes.cotizacion.index', ['hospital_request_id' => $request->id]) }}">Cotizar solicitud</a>@else<p>{{ $request->quotation?->folio }}</p>@endif
                </div>
            </section>
        @empty <p>No hay solicitudes de hospitales.</p> @endforelse
    </div>
    <div class="mt-4">{{ $requests->links() }}</div>
</x-admin-layout>
