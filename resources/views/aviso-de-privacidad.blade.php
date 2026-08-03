<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>
    {{-- Font Awesome --}}
    <script src="https://kit.fontawesome.com/b023f039d3.js" crossorigin="anonymous"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Styles -->
    @livewireStyles
</head>

<body class="font-sans antialiased">
    <div class="flex flex-col md:flex-row justify-evenly items-center bg-azul-prodifem text-white py-4">
        <div class="w-[300px]">
            <img src="{{ asset('img/Logo_Prodifem.png') }}" alt="">
        </div>
        <p class="text-center font-semibold text-2xl mt-2 md:mt-0">Para cualquier pregunta, contáctenos.</p>
        <p class="text-center font-semibold text-2xl"><i class="fa-solid fa-phone" style="color: #ffffff;"></i>
            5591862620 <br> <i class="fa-brands fa-whatsapp"></i> 5591862620 <br> <i class="fa-solid fa-envelope"></i>
            contacto@prodifem.com.mx</p>
    </div>
    <div class="max-w-[800px] mx-auto px-4 py-4">
        <h1 class="text-2xl">Aviso de Privacidad</h1>
        <p>Última actualización: 10/12/2024</p>

        <p><strong>Responsable del tratamiento de los datos personales:</strong></p>
        <p>PRODIFEM  (en adelante, "el Responsable"), con domicilio en San Francisco 524, Colonia del Valle, Benito Juárez, 03100 Ciudad de México, CDMX, en cumplimiento con la Ley
            Federal de Protección de Datos Personales en Posesión de los Particulares (en adelante, "la Ley"), es
            responsable del tratamiento de los datos personales que sean recabados a través del sistema de administración de
            pedidos de mezclas nutricionales y oncológicas (en adelante, "el Sistema").</p>

        <h2 class="text-xl">1. Finalidad del tratamiento de los datos personales</h2>
        <p>Los datos personales recabados serán utilizados para las siguientes finalidades necesarias:</p>
        <ol class="list-decimal list-inside pl-4">
            <li>Registrar y gestionar solicitudes de mezclas nutricionales y oncológicas realizadas por los usuarios autorizados de hospitales.</li>
            <li>Analizar, evaluar y aprobar dichas solicitudes por parte de la química administradora o personal autorizado.</li>
            <li>Coordinar la preparación y entrega de las mezclas aprobadas.</li>
            <li>Llevar un control interno y generar reportes estadísticos (en forma anonimizada, cuando sea aplicable).</li>
            <li>Cumplir con obligaciones legales y regulatorias en materia de salud y servicios farmacéuticos.</li>
        </ol>
        <h2 class="text-xl mt-4">2. Datos personales recabados</h2>
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
            <li>Información clínica relevante para la formulación de las mezclas (como peso, edad, diagnóstico médico, indicaciones específicas, entre otros).</li></li>
        </ul>
        <h2 class="text-xl mt-4">3. Protección de los datos personales</h2>
        <p>El Responsable adopta medidas administrativas, técnicas y físicas para garantizar la seguridad de los datos
            personales y evitar su daño, pérdida, alteración, destrucción o uso no autorizado. El acceso a la información
            está restringido únicamente al personal autorizado.</p>
        <h2 class="text-xl">4. Transferencia de datos personales</h2>
        <p>Sus datos personales podrán ser transferidos a terceros únicamente en los siguientes casos:</p>
        <ol class="list-decimal list-inside pl-4">
            <li>Cuando sea necesario para el cumplimiento de obligaciones legales o regulatorias.</li>
            <li>Cuando sea solicitado por autoridades competentes en el ejercicio de sus funciones legales.</li>
            <li>En caso de ser necesario para la entrega de servicios contratados, previo consentimiento del titular.</li>
        </ol>
       <p>El Responsable se compromete a no transferir sus datos personales a terceros ajenos a los supuestos anteriores
        sin su consentimiento expreso.</p>
        <h2 class="text-xl">5. Derechos ARCO (Acceso, Rectificación, Cancelación y Oposición)</h2>
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
        <h2 class="text-xl mt-4">6. Uso de cookies y tecnologías similares</h2>
        <p>El Sistema puede emplear cookies u otras tecnologías para mejorar la experiencia del usuario. Estas tecnologías
            recaban información como el tipo de navegador, sistema operativo y la interacción con el Sistema. Puede
            desactivar el uso de cookies desde la configuración de su navegador.</p>
        <h2 class="text-xl">7. Cambios en el aviso de privacidad</h2>
        <p>Este aviso de privacidad puede ser modificado en cualquier momento para cumplir con cambios legislativos,
            regulatorios o por necesidades operativas del Sistema.</p>
        <h2 class="text-xl">8. Consentimiento</h2>
        <p>Al proporcionar sus datos personales, usted acepta que estos sean tratados conforme a los términos y condiciones
            de este aviso de privacidad.</p>
        <p><strong>Contacto:</strong></p>
        <p>PRODIFEM</p>
        <p>Domicilio: San Francisco 524, Colonia del Valle, Benito Juárez, 03100 Ciudad de México, CDMX</p>
        <p>Teléfono: 5591862620</p>
        <p>Correo electrónico: contacto@prodifem.com.mx</p>
    </div>
    <div class="h-32 w-full bg-azul-prodifem flex justify-center">
        <div class="flex items-center w-full">
            <div class="w-1/3 text-center"></div>
            <p class="text-white w-1/3 text-center text-xl"><a href="">Aviso de privacidad</a></p>
            <div class="w-1/3 flex justify-end">
                <img class="w-72 text-center" src="{{ asset('img/ssllogo.png') }}" alt="">
            </div>

        </div>
    </div>

    <style>
        p{
            font-size: 16px;
            padding: 1rem 0;
        }
    </style>

    @stack('modals')



    @livewireScripts
</body>

</html>
