@php
    $secureConnection = request()->isSecure();
    $localConnection = in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1'], true);
    $connectionLabel = $secureConnection ? 'Conexión segura SSL' : ($localConnection ? 'Conexión local' : 'Conexión no cifrada');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="PROMESA, Central de Mezclas Estériles. Accede a la plataforma de gestión de operaciones.">
    <title>PROMESA | Central de Mezclas Estériles</title>
    <link rel="preload" href="{{ asset('fonts/figtree/figtree-latin-400-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="{{ asset('fonts/figtree/figtree-latin-700-normal.woff2') }}" as="font" type="font/woff2" crossorigin>
    <style>
        @foreach ([400, 500, 600, 700] as $fontWeight)
            @font-face {
                font-family: 'Figtree'; font-style: normal; font-weight: {{ $fontWeight }}; font-display: swap;
                src: url('{{ asset('fonts/figtree/figtree-latin-'.$fontWeight.'-normal.woff2') }}') format('woff2');
            }
        @endforeach
    </style>
    @vite('resources/js/welcome.js')
</head>
<body class="welcome-page">
    <header class="welcome-header">
        <a class="welcome-brand" href="{{ url('/') }}" aria-label="PROMESA, inicio">
            <span class="welcome-logo-art">
                <img src="{{ asset('img/promesa-logo.png') }}" alt="PROMESA - Prodifem Mezclas Estériles"
                    width="1448" height="1086" fetchpriority="high">
            </span>
        </a>
        <div class="welcome-support">
            <span class="welcome-support-icon" aria-hidden="true"><i data-welcome-icon="headset"></i></span>
            <div class="welcome-support-details">
                <strong>¿Necesitas ayuda?</strong>
                <a href="tel:+525591862620">55 9186 2620</a>
                <a href="mailto:contacto@prodifem.com.mx">contacto@prodifem.com.mx</a>
            </div>
        </div>
    </header>

    <main class="welcome-main">
        <div class="welcome-watermark" aria-hidden="true">
            <img src="{{ asset('img/promesa-home-reference.png') }}" alt="" width="1821" height="864">
        </div>
        <div class="welcome-visual">
            <div class="welcome-photo">
                <img src="{{ asset('img/promesa-home-reference.png') }}"
                    alt="Personal con equipo de protección preparando una mezcla en un área estéril"
                    width="1821" height="864" fetchpriority="high">
            </div>
        </div>
        <div class="welcome-copy">
            <h1>Central de mezclas estériles</h1>
            <p class="welcome-headline">Precisión y seguridad<br>en cada <span>mezcla</span></p>
            <p class="welcome-description">Accede a la plataforma PROMESA para gestionar<br class="welcome-desktop-break"> tus operaciones de forma segura.</p>
            <a class="welcome-access" href="{{ auth()->check() ? route('admin.dashboard') : route('login') }}">
                Acceder al sistema <i data-welcome-icon="arrow-right" aria-hidden="true"></i>
            </a>
            <ul class="welcome-benefits" aria-label="Compromisos de PROMESA">
                <li>
                    <span class="welcome-benefit-icon welcome-traceability-icon" aria-hidden="true">
                        <i data-welcome-icon="clipboard-list"></i>
                        <i data-welcome-icon="circle-check" class="welcome-icon-check"></i>
                    </span>
                    <span>Trazabilidad</span>
                </li>
                <li>
                    <span class="welcome-benefit-icon welcome-quality-icon" aria-hidden="true">
                        <i data-welcome-icon="award"></i>
                        <i data-welcome-icon="check" class="welcome-icon-check"></i>
                    </span>
                    <span>Control de calidad</span>
                </li>
                <li>
                    <i data-welcome-icon="shield-check" class="welcome-benefit-icon" aria-hidden="true"></i>
                    <span>Operación segura</span>
                </li>
            </ul>
        </div>
    </main>

    <footer class="welcome-footer">
        <p>© {{ now()->year }} PROMESA. Todos los derechos reservados.</p>
        <a class="welcome-privacy" href="{{ url('aviso-de-privacidad') }}">Aviso de privacidad</a>
        <span class="welcome-connection">
            <span class="welcome-connection-icon" aria-hidden="true">
                <i data-welcome-icon="{{ $secureConnection ? 'lock-keyhole' : ($localConnection ? 'monitor' : 'lock-keyhole-open') }}"></i>
            </span>
            {{ $connectionLabel }}
        </span>
    </footer>
</body>
</html>
