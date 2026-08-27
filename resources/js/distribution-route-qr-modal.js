const initializeDistributionRouteQrModal = () => {
    const modal = document.getElementById('route-qr-modal');
    if (!modal) return;

    const openButtons = document.querySelectorAll('[data-route-qr-open]');
    const closeButtons = modal.querySelectorAll('[data-route-qr-close]');
    const title = document.getElementById('route-qr-title');
    const routeName = document.getElementById('route-qr-name');
    const routeCode = document.getElementById('route-qr-code');
    const routeSchedule = document.getElementById('route-qr-schedule');
    const routeStops = document.getElementById('route-qr-stops');
    const image = document.getElementById('route-qr-image');
    const loading = document.getElementById('route-qr-loading');
    const error = document.getElementById('route-qr-error');
    const download = document.getElementById('route-qr-download');

    if (!title || !routeName || !routeCode || !routeSchedule || !routeStops
        || !image || !loading || !error || !download) {
        return;
    }

    let lastFocusedElement = null;

    const isOpen = () => !modal.classList.contains('hidden');

    const setLoadingState = () => {
        loading.classList.remove('hidden');
        loading.classList.add('flex');
        image.classList.add('hidden');
        error.classList.add('hidden');
    };

    const openModal = (button) => {
        const name = button.dataset.routeName || 'Ruta de distribuci\u00f3n';
        const code = button.dataset.routeCode || '';
        const schedule = button.dataset.routeSchedule || 'Sin horario';
        const stops = Number(button.dataset.routeStops || 0);
        const qrUrl = button.dataset.routeQrUrl || '';

        lastFocusedElement = document.activeElement;
        title.textContent = `C\u00f3digo QR de ${name}`;
        routeName.textContent = name;
        routeCode.textContent = code;
        routeSchedule.textContent = schedule;
        routeStops.textContent = `${stops} ${stops === 1 ? 'parada' : 'paradas'}`;
        image.alt = `C\u00f3digo QR de la ruta ${name}`;
        download.href = qrUrl;
        download.download = `${code || 'ruta'}.svg`;

        setLoadingState();
        image.removeAttribute('src');

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');

        window.requestAnimationFrame(() => {
            image.src = qrUrl;
            modal.querySelector('[data-route-qr-close]:not(.absolute)')?.focus();
        });
    };

    const closeModal = () => {
        if (!isOpen()) return;

        modal.classList.add('hidden');
        modal.classList.remove('flex');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        image.removeAttribute('src');
        lastFocusedElement?.focus?.();
    };

    image.addEventListener('load', () => {
        loading.classList.add('hidden');
        loading.classList.remove('flex');
        error.classList.add('hidden');
        image.classList.remove('hidden');
    });

    image.addEventListener('error', () => {
        loading.classList.add('hidden');
        loading.classList.remove('flex');
        image.classList.add('hidden');
        error.classList.remove('hidden');
    });

    openButtons.forEach((button) => button.addEventListener('click', () => openModal(button)));
    closeButtons.forEach((button) => button.addEventListener('click', closeModal));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen()) closeModal();
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeDistributionRouteQrModal, { once: true });
} else {
    initializeDistributionRouteQrModal();
}
