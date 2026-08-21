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
    <script>
        (function() {
            function syncStickyProxy(source, proxy, fromSource) {
                if (fromSource) {
                    if (Math.abs(proxy.scrollLeft - source.scrollLeft) > 1) {
                        proxy.scrollLeft = source.scrollLeft;
                    }
                    return;
                }

                if (Math.abs(source.scrollLeft - proxy.scrollLeft) > 1) {
                    source.scrollLeft = proxy.scrollLeft;
                }
            }

            function ensureStickyHorizontalScroll(root = document) {
                const selectors = [
                    '.admin-content .overflow-x-auto',
                    '.admin-content .billing-table-scroll',
                    '.admin-content [data-sticky-x-mode]'
                ];

                root.querySelectorAll(selectors.join(', ')).forEach((source) => {
                    if (source.dataset.stickyXReady === '1') {
                        if (typeof source._stickyXRefresh === 'function') {
                            source._stickyXRefresh();
                        }
                        return;
                    }

                    const parent = source.parentElement;
                    if (!parent) return;

                    const fixedToViewport = source.dataset.stickyXMode === 'fixed';

                    const proxy = document.createElement('div');
                    proxy.className = `sticky-x-proxy is-hidden${fixedToViewport ? ' is-fixed' : ''}`;
                    proxy.setAttribute('role', 'region');
                    proxy.setAttribute('aria-label', 'Desplazamiento horizontal de la tabla');
                    proxy.innerHTML = '<div class="sticky-x-proxy-track"></div>';

                    if (fixedToViewport) {
                        document.body.appendChild(proxy);
                    } else {
                        parent.insertBefore(proxy, source.nextSibling);
                    }

                    const track = proxy.firstElementChild;
                    source.dataset.stickyXReady = '1';
                    source.classList.add('sticky-x-source', 'is-sticky-x-managed');

                    let syncingFromSource = false;
                    let syncingFromProxy = false;

                    const refresh = () => {
                        const needsScroll = source.scrollWidth > source.clientWidth + 2;
                        let shouldShow = needsScroll;

                        if (fixedToViewport) {
                            const bounds = source.getBoundingClientRect();
                            const visibleLeft = Math.max(0, bounds.left);
                            const visibleRight = Math.min(window.innerWidth, bounds.right);
                            const visibleWidth = Math.max(0, visibleRight - visibleLeft);

                            proxy.style.left = `${visibleLeft}px`;
                            proxy.style.width = `${visibleWidth}px`;
                            shouldShow = needsScroll && visibleWidth > 40 && bounds.top < window.innerHeight && bounds.bottom > 0;
                        }

                        proxy.classList.toggle('is-hidden', !shouldShow);
                        track.style.width = `${source.scrollWidth}px`;

                        if (shouldShow) {
                            proxy.scrollLeft = source.scrollLeft;
                        }
                    };

                    source.addEventListener('scroll', () => {
                        if (syncingFromProxy) return;
                        syncingFromSource = true;
                        syncStickyProxy(source, proxy, true);
                        syncingFromSource = false;
                    }, {
                        passive: true
                    });

                    proxy.addEventListener('scroll', () => {
                        if (syncingFromSource) return;
                        syncingFromProxy = true;
                        syncStickyProxy(source, proxy, false);
                        syncingFromProxy = false;
                    }, {
                        passive: true
                    });

                    if (window.ResizeObserver) {
                        const observer = new ResizeObserver(() => refresh());
                        observer.observe(source);
                        if (source.firstElementChild) {
                            observer.observe(source.firstElementChild);
                        }
                    }

                    source._stickyXRefresh = refresh;

                    if (fixedToViewport) {
                        window.addEventListener('scroll', refresh, {
                            passive: true
                        });
                    }

                    refresh();
                });
            }

            document.addEventListener('DOMContentLoaded', () => {
                ensureStickyHorizontalScroll();
            });

            window.addEventListener('resize', () => {
                ensureStickyHorizontalScroll();
            }, {
                passive: true
            });

            document.addEventListener('livewire:init', () => {
                if (window.Livewire && typeof window.Livewire.hook === 'function') {
                    window.Livewire.hook('message.processed', () => {
                        ensureStickyHorizontalScroll();
                    });
                }
            });
        })();
    </script>
    <font></font>


</body>

</html>
