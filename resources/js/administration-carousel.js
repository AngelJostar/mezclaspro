import { createIcons, ChevronLeft, ChevronRight } from 'lucide';

function initAdministrationCarousel() {
    const navigation = document.querySelector('[data-administration-carousel]');
    if (!navigation) return;

    createIcons({ icons: { ChevronLeft, ChevronRight }, nameAttr: 'data-administration-icon', root: navigation });
    const carousel = navigation.querySelector('#administration-carousel');
    const selected = carousel.querySelector('[aria-current="page"]');
    if (selected) {
        const viewport = carousel.getBoundingClientRect();
        const item = selected.getBoundingClientRect();
        // Leave visible tabs in place; only reveal the selected tab when it is clipped.
        const offset = item.left < viewport.left ? item.left - viewport.left
            : item.right > viewport.right ? item.right - viewport.right : 0;
        if (offset) carousel.scrollTo({ left: carousel.scrollLeft + offset, behavior: 'instant' });
    }

    navigation.querySelectorAll('[data-carousel-direction]').forEach(button => {
        button.addEventListener('click', () => carousel.scrollBy({
            left: Number(button.dataset.carouselDirection) * 240,
            behavior: 'smooth',
        }));
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdministrationCarousel, { once: true });
} else {
    initAdministrationCarousel();
}
