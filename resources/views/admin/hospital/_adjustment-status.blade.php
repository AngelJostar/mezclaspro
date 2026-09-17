<span class="ht-adjustment-status ht-adjustment-{{ $version->status }}" @if ($version->status === 'declined') title="Ajuste no autorizado por el hospital" @endif>{{ $version->log_label }}</span>
