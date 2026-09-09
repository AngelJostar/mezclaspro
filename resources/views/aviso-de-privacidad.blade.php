@php
    $secureConnection = request()->isSecure();
    $localConnection = in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1'], true);
    $connectionLabel = $secureConnection ? 'Conexión segura SSL' : ($localConnection ? 'Conexión local' : 'Conexión no cifrada');
    $contents = [
        'responsable' => 'Responsable',
        'finalidad' => 'Finalidad del tratamiento',
        'datos-personales' => 'Datos personales recabados',
        'proteccion' => 'Protección de datos',
        'transferencia' => 'Transferencia de datos',
        'derechos-arco' => 'Derechos ARCO',
        'cookies' => 'Cookies y tecnologías',
        'cambios' => 'Cambios al aviso',
        'consentimiento' => 'Consentimiento',
        'contacto' => 'Contacto',
    ];
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Aviso de privacidad de PROMESA. Conoce cómo protegemos y tratamos tus datos personales.">
    <title>Aviso de privacidad | PROMESA</title>
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
<body class="privacy-page">
    <a class="privacy-skip" href="#privacy-document">Saltar al aviso</a>
    <header class="welcome-header privacy-header">
        <a class="welcome-brand" href="{{ url('/') }}" aria-label="PROMESA, inicio">
            <span class="welcome-logo-art">
                <img src="{{ asset('img/promesa-logo.png') }}" alt="PROMESA - Prodifem Mezclas Estériles" width="1448" height="1086" fetchpriority="high">
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
    <main>
        <div class="privacy-intro">
            <div class="privacy-pattern" aria-hidden="true">
                <img src="{{ asset('img/promesa-home-reference.png') }}" alt="" width="1821" height="864">
            </div>
            <div class="privacy-intro-inner">
                <nav class="privacy-breadcrumb" aria-label="Ruta de navegación">
                    <a href="{{ url('/') }}">Inicio</a><span aria-hidden="true">/</span><span aria-current="page">Aviso de privacidad</span>
                </nav>
                <div class="privacy-title-row">
                    <span class="privacy-title-icon" aria-hidden="true">
                        <i data-welcome-icon="shield"></i><i data-welcome-icon="lock-keyhole"></i>
                    </span>
                    <div class="privacy-title-copy">
                        <h1>Aviso de privacidad</h1>
                        <p>Conoce cómo protegemos y tratamos tus datos personales.</p>
                    </div>
                    <span class="privacy-updated"><i data-welcome-icon="calendar-days" aria-hidden="true"></i><span>Última actualización: <time datetime="2024-12-10">10/12/2024</time></span></span>
                </div>
            </div>
        </div>
        <div class="privacy-layout">
            <aside class="privacy-sidebar">
                <details class="privacy-index" open>
                    <summary>Contenido<i data-welcome-icon="chevron-down" aria-hidden="true"></i></summary>
                    <nav aria-label="Contenido del aviso">
                        <ol>
                            @foreach ($contents as $id => $label)
                                <li><a href="#{{ $id }}" @if ($loop->first) aria-current="location" @endif><span aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>{{ $label }}</a></li>
                            @endforeach
                        </ol>
                    </nav>
                    <p class="privacy-sidebar-note"><i data-welcome-icon="lock-keyhole" aria-hidden="true"></i>Privacidad de tus datos</p>
                </details>
            </aside>
            <article id="privacy-document" class="privacy-document" aria-label="Aviso de privacidad" tabindex="-1">
                <section id="responsable" class="privacy-section" aria-labelledby="responsable-title">
                    <span class="privacy-number" aria-hidden="true">01</span>
                    <div class="privacy-section-content">
                        <h2 id="responsable-title" tabindex="-1"><i data-welcome-icon="user-round" aria-hidden="true"></i>Responsable del tratamiento de los datos personales</h2>

                        <p>PRODIFEM  (en adelante, "el Responsable"), con domicilio en San Francisco 524, Colonia del Valle, Benito Juárez, 03100 Ciudad de México, CDMX, en cumplimiento con la Ley
                            Federal de Protección de Datos Personales en Posesión de los Particulares (en adelante, "la Ley"), es
                            responsable del tratamiento de los datos personales que sean recabados a través del sistema de administración de
                            pedidos de mezclas nutricionales y oncológicas (en adelante, "el Sistema").</p>

                    </div>
                </section>
                <section id="finalidad" class="privacy-section" aria-labelledby="finalidad-title">
                    <span class="privacy-number" aria-hidden="true">02</span>
                    <div class="privacy-section-content">
                        <h2 id="finalidad-title" tabindex="-1"><i data-welcome-icon="file-text" aria-hidden="true"></i>1. Finalidad del tratamiento de los datos personales</h2>
                        <p>Los datos personales recabados serán utilizados para las siguientes finalidades necesarias:</p>
                        <ol class="list-decimal list-inside pl-4">
                            <li>Registrar y gestionar solicitudes de mezclas nutricionales y oncológicas realizadas por los usuarios autorizados de hospitales.</li>
                            <li>Analizar, evaluar y aprobar dichas solicitudes por parte de la química administradora o personal autorizado.</li>
                            <li>Coordinar la preparación y entrega de las mezclas aprobadas.</li>
                            <li>Llevar un control interno y generar reportes estadísticos (en forma anonimizada, cuando sea aplicable).</li>
                            <li>Cumplir con obligaciones legales y regulatorias en materia de salud y servicios farmacéuticos.</li>
                        </ol>
                    </div>
                </section>
                <section id="datos-personales" class="privacy-section" aria-labelledby="datos-personales-title">
                    <span class="privacy-number" aria-hidden="true">03</span>
                    <div class="privacy-section-content">
                        <h2 id="datos-personales-title" tabindex="-1"><i data-welcome-icon="database" aria-hidden="true"></i>2. Datos personales recabados</h2>
                        <p>Para cumplir con las finalidades antes descritas, el Responsable podrá recabar los siguientes datos:</p>
                        <p><strong>De los usuarios hospitalarios:</strong></p>
                        <ul class="list-disc list-inside pl-4">
                            <li>Nombre completo</li>
                            <li>Cargo, hospital de adscripción y unidad médica</li>
                            <li>Correo electrónico y teléfono de contacto</li>
                        </ul>
                        <p><strong>De los pacientes (si aplica):</strong></p>
                        <ul class="list-disc list-inside pl-4">
                            <li>Nombre o identificador del paciente (número de expediente)</li>
                            <li>Información clínica relevante para la formulación de las mezclas (como peso, edad, diagnóstico médico, indicaciones específicas, entre otros).</li>
                        </ul>
                    </div>
                </section>
                <section id="proteccion" class="privacy-section" aria-labelledby="proteccion-title">
                    <span class="privacy-number" aria-hidden="true">04</span>
                    <div class="privacy-section-content">
                        <h2 id="proteccion-title" tabindex="-1"><i data-welcome-icon="shield-check" aria-hidden="true"></i>3. Protección de los datos personales</h2>
                        <p>El Responsable adopta medidas administrativas, técnicas y físicas para garantizar la seguridad de los datos
                            personales y evitar su daño, pérdida, alteración, destrucción o uso no autorizado. El acceso a la información
                            está restringido únicamente al personal autorizado.</p>
                    </div>
                </section>
                <section id="transferencia" class="privacy-section" aria-labelledby="transferencia-title">
                    <span class="privacy-number" aria-hidden="true">05</span>
                    <div class="privacy-section-content">
                        <h2 id="transferencia-title" tabindex="-1"><i data-welcome-icon="arrow-right-left" aria-hidden="true"></i>4. Transferencia de datos personales</h2>
                        <p>Sus datos personales podrán ser transferidos a terceros únicamente en los siguientes casos:</p>
                        <ol class="list-decimal list-inside pl-4">
                            <li>Cuando sea necesario para el cumplimiento de obligaciones legales o regulatorias.</li>
                            <li>Cuando sea solicitado por autoridades competentes en el ejercicio de sus funciones legales.</li>
                            <li>En caso de ser necesario para la entrega de servicios contratados, previo consentimiento del titular.</li>
                        </ol>
                        <p>El Responsable se compromete a no transferir sus datos personales a terceros ajenos a los supuestos anteriores
                            sin su consentimiento expreso.</p>
                    </div>
                </section>
                <section id="derechos-arco" class="privacy-section" aria-labelledby="derechos-arco-title">
                    <span class="privacy-number" aria-hidden="true">06</span>
                    <div class="privacy-section-content">
                        <h2 id="derechos-arco-title" tabindex="-1"><i data-welcome-icon="badge-check" aria-hidden="true"></i>5. Derechos ARCO (Acceso, Rectificación, Cancelación y Oposición)</h2>
                        <p>Como titular de los datos personales, usted tiene derecho a:</p>
                        <ul class="list-disc list-inside pl-4">
                            <li><strong>Acceso:</strong> Conocer qué datos personales posee el Responsable y para qué fines se utilizan.</li>
                            <li><strong>Rectificación:</strong> Solicitar la corrección de datos incorrectos, inexactos o incompletos.</li>
                            <li><strong>Cancelación:</strong> Solicitar que sus datos sean eliminados de nuestros registros cuando ya no sean necesarios para las finalidades mencionadas.</li>
                            <li><strong>Oposición:</strong> Negarse al tratamiento de sus datos personales en circunstancias específicas.</li>
                        </ul>
                        <p>Para ejercer sus derechos ARCO, puede comunicarse con el Responsable a través del correo electrónico contacto@prodifem.com.mx o al teléfono 5591862620, presentando una solicitud que incluya:</p>

                        <ol class="list-decimal list-inside pl-4">
                            <li>Su nombre completo y datos de contacto.</li>
                            <li>Copia de una identificación oficial.</li>
                            <li>Descripción clara del derecho que desea ejercer.</li>
                            <li>Cualquier documento que facilite la localización de los datos.</li>
                        </ol>
                    </div>
                </section>
                <section id="cookies" class="privacy-section" aria-labelledby="cookies-title">
                    <span class="privacy-number" aria-hidden="true">07</span>
                    <div class="privacy-section-content">
                        <h2 id="cookies-title" tabindex="-1"><i data-welcome-icon="cookie" aria-hidden="true"></i>6. Uso de cookies y tecnologías similares</h2>
                        <p>El Sistema puede emplear cookies u otras tecnologías para mejorar la experiencia del usuario. Estas tecnologías
                            recaban información como el tipo de navegador, sistema operativo y la interacción con el Sistema. Puede
                            desactivar el uso de cookies desde la configuración de su navegador.</p>
                    </div>
                </section>
                <section id="cambios" class="privacy-section" aria-labelledby="cambios-title">
                    <span class="privacy-number" aria-hidden="true">08</span>
                    <div class="privacy-section-content">
                        <h2 id="cambios-title" tabindex="-1"><i data-welcome-icon="refresh-cw" aria-hidden="true"></i>7. Cambios en el aviso de privacidad</h2>
                        <p>Este aviso de privacidad puede ser modificado en cualquier momento para cumplir con cambios legislativos,
                            regulatorios o por necesidades operativas del Sistema.</p>
                    </div>
                </section>
                <section id="consentimiento" class="privacy-section" aria-labelledby="consentimiento-title">
                    <span class="privacy-number" aria-hidden="true">09</span>
                    <div class="privacy-section-content">
                        <h2 id="consentimiento-title" tabindex="-1"><i data-welcome-icon="circle-check" aria-hidden="true"></i>8. Consentimiento</h2>
                        <p>Al proporcionar sus datos personales, usted acepta que estos sean tratados conforme a los términos y condiciones
                            de este aviso de privacidad.</p>
                    </div>
                </section>
                <section id="contacto" class="privacy-section" aria-labelledby="contacto-title">
                    <span class="privacy-number" aria-hidden="true">10</span>
                    <div class="privacy-section-content">
                        <h2 id="contacto-title" tabindex="-1"><i data-welcome-icon="mail" aria-hidden="true"></i>Contacto</h2>
                        <p>PRODIFEM</p>
                        <p>Domicilio: San Francisco 524, Colonia del Valle, Benito Juárez, 03100 Ciudad de México, CDMX</p>
                        <p>Teléfono: <a href="tel:+525591862620">5591862620</a></p>
                        <p>Correo electrónico: <a href="mailto:contacto@prodifem.com.mx">contacto@prodifem.com.mx</a></p>
                    </div>
                </section>
            </article>
        </div>
    </main>
    <footer class="privacy-footer">
        <div class="privacy-footer-inner">
            <a class="welcome-brand privacy-footer-brand" href="{{ url('/') }}" aria-label="PROMESA, inicio">
                <span class="welcome-logo-art"><img src="{{ asset('img/promesa-logo.png') }}" alt="PROMESA - Prodifem Mezclas Estériles" width="1448" height="1086"></span>
            </a>
            <nav class="privacy-footer-links" aria-label="Enlaces del pie">
                <a href="#privacy-document">Aviso de privacidad</a><a href="#contacto">Contacto</a>
            </nav>
            <span class="welcome-connection"><span class="welcome-connection-icon" aria-hidden="true"><i data-welcome-icon="{{ $secureConnection ? 'lock-keyhole' : ($localConnection ? 'monitor' : 'lock-keyhole-open') }}"></i></span>{{ $connectionLabel }}</span>
            <p>© {{ now()->year }} PROMESA. Todos los derechos reservados.</p>
        </div>
    </footer>
</body>
</html>
