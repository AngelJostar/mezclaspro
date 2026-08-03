@props(['align' => 'right'])

@php
    $menuId = 'row-actions-' . uniqid();
@endphp

<div class="inline-flex justify-center">
    <button type="button"
        class="js-row-actions-toggle cbta-row-actions-button inline-flex h-9 w-9 items-center justify-center rounded-full bg-white text-slate-950 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-1"
        aria-haspopup="true" aria-expanded="false" data-menu-id="{{ $menuId }}" title="Acciones">
        <span class="sr-only">Acciones</span>
        <span class="cbta-row-actions-dots" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
        </span>
    </button>

    <div id="{{ $menuId }}"
        class="js-row-actions-menu cbta-row-actions-menu fixed z-[9999] hidden min-w-40 rounded-lg border border-slate-200 bg-white p-1 text-left shadow-xl"
        data-align="{{ $align }}">
        {{ $slot }}
    </div>
</div>

@once
    <style>
        .cbta-row-actions-button {
            width: 36px !important;
            height: 36px !important;
            min-width: 36px !important;
            min-height: 36px !important;
            padding: 0 !important;
            border: 2px solid #020617 !important;
            border-radius: 9999px !important;
            background: #ffffff !important;
            color: #020617 !important;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.12);
            line-height: 1 !important;
        }

        .cbta-row-actions-dots {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 3px;
        }

        .cbta-row-actions-dots span {
            display: block;
            width: 4px;
            height: 4px;
            border-radius: 9999px;
            background: #020617;
        }

        .cbta-row-actions-button:hover .cbta-row-actions-dots span,
        .cbta-row-actions-button[aria-expanded="true"] .cbta-row-actions-dots span {
            background: #020617;
        }

        .cbta-row-actions-menu>a,
        .cbta-row-actions-menu>button,
        .cbta-row-actions-menu form>button {
            display: flex !important;
            width: 100% !important;
            align-items: center;
            gap: 0.45rem;
            border-radius: 0.375rem;
            padding: 0.55rem 0.85rem !important;
            background: transparent !important;
            color: #334155 !important;
            font-size: 0.8125rem !important;
            font-weight: 600;
            line-height: 1.1;
            text-align: left;
            white-space: nowrap;
        }

        .cbta-row-actions-menu>a:hover,
        .cbta-row-actions-menu>button:hover,
        .cbta-row-actions-menu form>button:hover {
            background: #f1f5f9 !important;
            color: #0f172a !important;
        }

        .cbta-row-actions-menu .action-danger,
        .cbta-row-actions-menu form.action-danger>button,
        .cbta-row-actions-menu form>button.action-danger {
            color: #dc2626 !important;
        }

        .cbta-row-actions-menu .action-danger:hover,
        .cbta-row-actions-menu form.action-danger>button:hover,
        .cbta-row-actions-menu form>button.action-danger:hover {
            background: #fee2e2 !important;
            color: #b91c1c !important;
        }

        .cbta-row-actions-menu form {
            margin: 0;
        }
    </style>

    <script>
        (() => {
            if (window.__cbtaRowActionsReady) return;
            window.__cbtaRowActionsReady = true;

            const closeMenus = () => {
                document.querySelectorAll('.js-row-actions-menu').forEach((menu) => {
                    menu.classList.add('hidden');
                });
                document.querySelectorAll('.js-row-actions-toggle[aria-expanded="true"]').forEach((button) => {
                    button.setAttribute('aria-expanded', 'false');
                });
            };

            const positionMenu = (button, menu) => {
                menu.classList.remove('hidden');

                const rect = button.getBoundingClientRect();
                const menuRect = menu.getBoundingClientRect();
                const gap = 6;
                const align = menu.dataset.align || 'right';

                let top = rect.bottom + gap;
                let left = align === 'left' ? rect.left : rect.right - menuRect.width;

                if (left + menuRect.width > window.innerWidth - 8) {
                    left = window.innerWidth - menuRect.width - 8;
                }
                if (left < 8) {
                    left = 8;
                }
                if (top + menuRect.height > window.innerHeight - 8) {
                    top = rect.top - menuRect.height - gap;
                }
                if (top < 8) {
                    top = 8;
                }

                menu.style.top = `${top}px`;
                menu.style.left = `${left}px`;
            };

            document.addEventListener('click', (event) => {
                const button = event.target.closest('.js-row-actions-toggle');
                const menu = event.target.closest('.js-row-actions-menu');

                if (button) {
                    event.preventDefault();
                    event.stopPropagation();

                    const targetMenu = document.getElementById(button.dataset.menuId);
                    const wasOpen = targetMenu && !targetMenu.classList.contains('hidden');

                    closeMenus();

                    if (targetMenu && !wasOpen) {
                        button.setAttribute('aria-expanded', 'true');
                        positionMenu(button, targetMenu);
                    }

                    return;
                }

                if (!menu) {
                    closeMenus();
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    closeMenus();
                }
            });

            window.addEventListener('resize', closeMenus);
            window.addEventListener('scroll', closeMenus, true);
        })();
    </script>
@endonce
