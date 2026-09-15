@forelse ($users as $accessUser)
    @php
        $credential = \App\Support\PersonnelCredentialDisplay::forUser($accessUser)['software'];
    @endphp
    <dl class="hospital-credential">
        <dt>Usuario</dt>
        <dd>{{ $accessUser->username ?: 'Sin usuario configurado' }}</dd>
        <dt>Contrase&ntilde;a</dt>
        <dd>{{ $credential['value'] !== '' ? $credential['value'] : $credential['empty_label'] }}</dd>
    </dl>
@empty
    <span class="text-gray-400">Sin usuario asignado</span>
@endforelse
