@include('admin.instituciones.conciliacion._period-detail', ['clientReview' => true])
@if ($responseStatus)
    <p class="cp-note">Respuesta del hospital: <strong>{{ $responseStatus }}</strong> · {{ $responseBy }} · {{ $responseAt }}.</p>
@elseif (!$canRespond)
    <p class="cp-error">Esta conciliación está disponible para consulta. Solicita a PROMESA Prodifem el envío del periodo actualizado para registrar tu respuesta.</p>
@endif
