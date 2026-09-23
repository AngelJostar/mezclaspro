import { createIcons, ArrowUpDown, ArrowUp, ArrowDown, Filter, FilterX, ChevronLeft, ChevronRight } from 'lucide';

function renderPersonnelIcons() {
    const root = document.querySelector('.training-personnel-screen');
    if (!root) return;
    root.querySelectorAll('.js-personnel-sort [data-sort-icon]').forEach(icon => {
        if (!icon.dataset.personnelTableIcon) icon.dataset.personnelTableIcon = 'arrow-up-down';
        icon.setAttribute('class', 'h-3.5 w-3.5');
    });
    createIcons({
        icons: { ArrowUpDown, ArrowUp, ArrowDown, Filter, FilterX, ChevronLeft, ChevronRight },
        nameAttr: 'data-personnel-table-icon',
        root,
    });
}

document.addEventListener('personnel-table-sorted', renderPersonnelIcons);
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', renderPersonnelIcons, { once: true });
else renderPersonnelIcons();
