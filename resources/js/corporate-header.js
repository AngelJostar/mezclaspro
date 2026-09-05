import { createIcons, Bell, ChevronDown, Menu } from 'lucide';

function renderHeaderIcons() {
    const header = document.querySelector('.corporate-header');
    if (header) createIcons({ icons: { Bell, ChevronDown, Menu }, attrs: { 'stroke-width': 1.8 }, root: header });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderHeaderIcons);
} else {
    renderHeaderIcons();
}

document.addEventListener('livewire:navigated', renderHeaderIcons);
