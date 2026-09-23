import { createIcons, ArrowUpDown, Check, ChevronDown, ChevronLeft, ChevronRight, X } from 'lucide';

function initRequestNavigation() {
    createIcons({
        icons: { ArrowUpDown, Check, ChevronDown, ChevronLeft, ChevronRight, X },
        nameAttr: 'data-request-navigation-icon',
    });

    document.querySelectorAll('[data-request-type-selector] .request-selector-scroll').forEach(carousel => {
        const selected = carousel.querySelector('[aria-current="page"]');
        if (!selected) return;

        const offset = selected.getBoundingClientRect().left - carousel.getBoundingClientRect().left;
        if (offset < 0 || offset + selected.offsetWidth > carousel.clientWidth) {
            carousel.scrollTo({ left: carousel.scrollLeft + offset - 4, behavior: 'instant' });
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRequestNavigation, { once: true });
} else {
    initRequestNavigation();
}
