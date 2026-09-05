import { createIcons, X } from 'lucide';

function initializeIdentification() {
    const dialog = document.getElementById('maintenance-identification-dialog');
    if (!dialog || dialog.dataset.initialized) return;
    dialog.dataset.initialized = 'true';
    createIcons({ icons: { X }, nameAttr: 'data-identification-icon', root: dialog });
    let trigger = null;

    document.querySelectorAll('[data-view-identification]').forEach(button => {
        button.addEventListener('click', () => {
            trigger = button;
            const identification = button.closest('td').dataset.columnFilterValue;
            dialog.querySelector('[data-identification-service]').textContent = button.dataset.service;
            dialog.querySelector('[data-identification-content]').textContent =
                identification === '-' ? 'Sin identificacion registrada.' : identification;
            dialog.showModal();
            dialog.querySelector('.identification-body').scrollTop = 0;
        });
    });

    dialog.querySelector('[data-close-identification]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
        const bounds = dialog.getBoundingClientRect();
        if (event.target === dialog && (event.clientX < bounds.left || event.clientX > bounds.right
            || event.clientY < bounds.top || event.clientY > bounds.bottom)) dialog.close();
    });
    dialog.addEventListener('close', () => trigger?.focus({ preventScroll: true }));
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeIdentification);
} else {
    initializeIdentification();
}
document.addEventListener('livewire:navigated', initializeIdentification);
