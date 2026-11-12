<!doctype html><html lang="es"><head><meta charset="utf-8"></head><body style="font-family:Arial,sans-serif;color:#17365d;line-height:1.6">
<h2>Nueva solicitud de conciliación</h2>
<p>Se envió la conciliación <strong>{{ $submission->folio() }}</strong> al hospital <strong>{{ $submission->hospital_name }}</strong>.</p>
<p>Periodo: {{ $submission->periodLabel() }}.</p>
<p>Ingresa a Mezclas Pro con tu cuenta para revisar las remisiones y responder a la solicitud.</p>
<p><a href="{{ url('/login') }}">Abrir Mezclas Pro</a></p>
<p>PROMESA · Central de Mezclas Estériles</p>
</body></html>
