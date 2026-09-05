import { createIcons, ExternalLink } from 'lucide';

function initializeHospitalEditor() {
    const form = document.querySelector('[data-hospital-edit-form]');
    if (!form || form.dataset.ready) return;
    form.dataset.ready = 'true';
    createIcons({ icons: { ExternalLink }, nameAttr: 'data-hospital-icon', root: form });

    const active = form.querySelector('#hospital_is_active');
    const badge = form.querySelector('[data-hospital-active-label]');
    const updateStatus = () => {
        badge.classList.toggle('is-active', active.checked);
        badge.querySelector('[data-hospital-active-text]').textContent = active.checked ? 'Activo' : 'Inactivo';
    };
    active.addEventListener('change', updateStatus);
    updateStatus();

    const mapsInput = form.querySelector('#google_maps_url');
    const mapsLink = form.querySelector('[data-hospital-map-link]');
    const updateMapLink = () => {
        let url;
        try {
            const candidate = new URL(mapsInput.value.trim());
            if (['http:', 'https:'].includes(candidate.protocol)) url = candidate.href;
        } catch {
            // An empty or incomplete address is not navigable.
        }
        if (url) mapsLink.href = url;
        else mapsLink.removeAttribute('href');
        mapsLink.setAttribute('aria-disabled', String(!url));
        mapsLink.tabIndex = url ? 0 : -1;
    };
    mapsInput.addEventListener('input', updateMapLink);
    updateMapLink();
}

document.addEventListener('DOMContentLoaded', initializeHospitalEditor);
document.addEventListener('livewire:navigated', initializeHospitalEditor);
