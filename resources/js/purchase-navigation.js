import { createIcons, Building2, Check, ChevronLeft, ChevronRight } from 'lucide';

function initPurchaseNavigation() {
    document.querySelectorAll('[data-purchase-navigation]').forEach(navigation => {
        createIcons({ icons: { Building2, Check, ChevronLeft, ChevronRight }, nameAttr: 'data-purchase-icon', root: navigation });
        const carousel = navigation.querySelector('[data-purchase-carousel]');
        if (!carousel) return;

        const previous = navigation.querySelector('[data-purchase-direction="-1"]');
        const next = navigation.querySelector('[data-purchase-direction="1"]');
        const update = () => {
            previous.disabled = carousel.scrollLeft <= 1;
            next.disabled = carousel.scrollLeft + carousel.clientWidth >= carousel.scrollWidth - 1;
        };
        const selected = carousel.querySelector('[aria-current="page"]');
        if (selected) {
            const offset = selected.getBoundingClientRect().left - carousel.getBoundingClientRect().left;
            if (offset < 0 || offset + selected.offsetWidth > carousel.clientWidth) carousel.scrollLeft += offset;
        }
        navigation.querySelectorAll('[data-purchase-direction]').forEach(button => {
            button.addEventListener('click', () => carousel.scrollBy({
                left: Number(button.dataset.purchaseDirection) * carousel.clientWidth,
                behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
            }));
        });
        carousel.addEventListener('scroll', update, { passive: true });
        new ResizeObserver(update).observe(carousel);
        update();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPurchaseNavigation, { once: true });
} else {
    initPurchaseNavigation();
}
