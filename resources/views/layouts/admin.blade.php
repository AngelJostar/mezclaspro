<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Central de Mezclas</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    {{-- Font Awesome --}}
    <script src="https://kit.fontawesome.com/b023f039d3.js" crossorigin="anonymous"></script>
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Scripts sweetalert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.8/css/dataTables.dataTables.css" />

    @livewireStyles

    @stack('css')

</head>

{{-- con x-data le digo que voy a trabajar con los alpine --}}

<body class="font-sans antialiased bg-slate-100 text-slate-900 sm:overflow-auto" :class="{ 'overflow-hidden': open }" x-data="{ open: false }">


    @include('layouts.includes.admin.nav')

    @include('layouts.includes.admin.aside')

    <main class="admin-page sm:ml-44">
        <div class="admin-content">
            {{ $slot }}
        </div>
    </main>
    <div x-show="open" x-on:click="open=false"
        style="display: none"class="bg-gray-900/50 dark:bg-gray-900/80 fixed inset-0 z-30 sm:hidden"></div>
    @stack('modals')

    @livewireScripts

    @if (session('swal'))
        <script>
            let swalConfig = @json(session('swal'));
            swalConfig = {
                ...swalConfig, // Extiende la configuración existente
                confirmButtonText: 'Aceptar',
                cancelButtonText: 'Cancelar',
                showCancelButton: true, // Habilita el botón de cancelar si es necesario
                customClass: {
                    confirmButton: 'swal-button-confirm',
                    cancelButton: 'swal-button-cancel'
                }
            };

            Swal.fire(swalConfig);
        </script>
    @endif
    @auth
        <script>
            window.onload = function() {
                Echo.private('App.Models.User.' + {{ auth()->id() }})
                    .notification((notification) => {
                        console.log(notification.type);
                    });
            }
        </script>

    @endauth
    @stack('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"
        integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdn.datatables.net/2.0.8/js/dataTables.js"></script>
    <font></font>


</body>

</html>
