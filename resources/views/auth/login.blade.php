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
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Acceso al portal PROMESA de mezclas de nutrición parenteral.">
    <title>Acceso al portal | PROMESA</title>
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
<body class="welcome-page login-page">
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

    <main class="welcome-main login-main">
        <div class="welcome-watermark" aria-hidden="true">
            <img src="{{ asset('img/promesa-home-reference.png') }}" alt="" width="1821" height="864">
        </div>
        <div class="welcome-visual login-visual">
            <div class="welcome-photo">
                <img src="{{ asset('img/promesa-home-reference.png') }}"
                    alt="Personal con equipo de protección preparando una mezcla en un área estéril"
                    width="1821" height="864" fetchpriority="high">
            </div>
            <div class="login-quality">
                <i data-welcome-icon="shield-check" aria-hidden="true"></i>
                <span>Calidad, seguridad<br>y trazabilidad</span>
            </div>
        </div>

        <div class="login-copy">
            <section class="login-panel" aria-labelledby="login-title">
                <p class="login-eyebrow">PORTAL DE NUTRICIÓN PARENTERAL</p>
                <h1 id="login-title">Acceso al portal</h1>
                <p class="login-description">Ingresa tus datos para solicitar y dar seguimiento a tus mezclas.</p>
                @if ($errors->any())
                    <div id="login-errors" class="login-message login-error" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if (session('status'))
                    <div class="login-message login-status" role="status">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="login-form">
                    @csrf
                    <div class="login-field">
                        <label for="username">Usuario</label>
                        <div class="login-input-wrap">
                            <i data-welcome-icon="user-round" class="login-input-icon" aria-hidden="true"></i>
                            <input id="username" type="text" name="username" value="{{ old('username') }}"
                                required autofocus autocomplete="username" autocapitalize="none" spellcheck="false"
                                @if ($errors->has('username')) aria-invalid="true" aria-describedby="login-errors" @endif>
                        </div>
                    </div>
                    <div class="login-field">
                        <label for="password">Contraseña</label>
                        <div class="login-input-wrap">
                            <i data-welcome-icon="lock-keyhole" class="login-input-icon" aria-hidden="true"></i>
                            <input id="password" type="password" name="password" required autocomplete="current-password"
                                @if ($errors->has('password')) aria-invalid="true" aria-describedby="login-errors" @endif>
                            <button type="button" class="login-password-toggle" data-password-toggle aria-controls="password"
                                aria-label="Mostrar contraseña" title="Mostrar contraseña" aria-pressed="false" hidden>
                                <i data-welcome-icon="eye" data-password-show aria-hidden="true"></i>
                                <i data-welcome-icon="eye-off" data-password-hide aria-hidden="true" hidden></i>
                            </button>
                        </div>
                    </div>
                    <button class="login-submit" type="submit">
                        Iniciar sesión <i data-welcome-icon="arrow-right" aria-hidden="true"></i>
                    </button>
                </form>
                <p class="login-assurance">
                    <i data-welcome-icon="lock-keyhole" aria-hidden="true"></i>
                    {{ $secureConnection ? 'Acceso seguro y protegido' : 'Acceso al sistema PROMESA' }}
                </p>
            </section>

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
