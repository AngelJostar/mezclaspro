import { createIcons, ChevronLeft, ChevronRight } from 'lucide';

function initAdministrationCarousel() {
    const navigation = document.querySelector('[data-administration-carousel]');
    if (!navigation) return;

    createIcons({ icons: { ChevronLeft, ChevronRight }, nameAttr: 'data-administration-icon', root: navigation });
    const carousel = navigation.querySelector('#administration-carousel');
    const selected = carousel.querySelector('[aria-current="page"]');
    if (selected) carousel.scrollLeft = selected.offsetLeft - carousel.offsetLeft;

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
