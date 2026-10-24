<x-admin-layout>
    @if ($creationError)
        <div class="request-creation-result" role="alert">
            <h1>No se puede abrir la solicitud</h1>
            <p>{{ $creationError }}</p>
        </div>
    @else
        <div class="request-creation-result request-creation-result--success" role="status">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="m7 12 3 3 7-7"/></svg>
            <h1>Solicitud registrada correctamente</h1>
            <p>Puedes cerrar esta ventana con la cruz superior derecha para volver a la lista actualizada.</p>
        </div>
    @endif
</x-admin-layout>
