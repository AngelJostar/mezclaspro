<div class="quotation-brand" aria-label="Datos de PROMESA">
    <div class="quotation-brand-logo-cell">
        <div class="quotation-brand-logo">
            <img src="{{ $issuer['logo'] }}" alt="Logotipo de PROMESA" width="1448" height="1086">
        </div>
    </div>
    <div class="quotation-brand-details">
        <strong>{{ $issuer['name'] }}</strong>
        <p>
            {{ $issuer['legal_name'] }}
            @if ($issuer['rfc']) &middot; RFC: {{ $issuer['rfc'] }} @endif
        </p>
        @if ($issuer['address'])
            <p>{{ $issuer['address'] }}</p>
        @endif
        <p>
            @if ($issuer['phone']) Tel. {{ $issuer['phone'] }} @endif
            @if ($issuer['phone'] && $issuer['email']) &middot; @endif
            {{ $issuer['email'] }}
        </p>
    </div>
</div>
