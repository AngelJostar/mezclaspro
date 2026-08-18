<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Central de Mezclas</title>
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
    <div class="h-screen">
        <div class="flex flex-col h-full justify-between">
            <div class="flex flex-col md:flex-row justify-evenly items-center bg-azul-prodifem text-white py-4">
                <div class="w-[300px]">
                    <img src="{{ asset('img/Logo_Prodifem.png') }}" alt="">
                </div>
                <p class="text-center font-semibold text-2xl mt-2 md:mt-0">Para cualquier pregunta, contáctenos.</p>
                <p class="text-center font-semibold text-2xl"><i class="fa-solid fa-phone" style="color: #ffffff;"></i>
                    5591862620 <br> <i class="fa-brands fa-whatsapp"></i> 5591862620 <br> <i
                        class="fa-solid fa-envelope"></i>
                    contacto@prodifem.com.mx</p>
            </div>
            <div class="max-w-[600px] mx-auto text-center">
                <a class="inline-flex items-center justify-center text-white bg-azul-prodifem rounded-xl px-12 py-4 text-2xl font-semibold hover:bg-blue-900 transition"
                    href="{{ auth()->check() ? route('admin.dashboard') : route('login') }}">
                    Acceder al sistema
                </a>
            </div>

            <div class="h-32 w-full bg-azul-prodifem flex justify-center">
                <div class="flex items-center w-full">
                    <div class="w-1/3 text-center"></div>
                    <p class="text-white w-1/3 text-center text-xl"><a href="{{ url('aviso-de-privacidad') }}">Aviso de
                            privacidad</a></p>
                    <div class="w-1/3 flex justify-end">
                        <img class="w-72 text-center" src="{{ asset('img/ssllogo.png') }}" alt="">
                    </div>
                </div>
            </div>
        </div>
    </div>

    @stack('modals')

    @livewireScripts
</body>

</html>
