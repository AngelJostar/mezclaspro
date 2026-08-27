<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Lista de precios de respaldo</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/b023f039d3.js" crossorigin="anonymous"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    @livewireStyles
    @stack('css')
</head>

<body class="bg-white font-sans text-slate-900 antialiased">
    <main class="min-h-screen bg-white">
        {{ $slot }}
    </main>

    @stack('modals')
    @livewireScripts
    @stack('js')
</body>

</html>
