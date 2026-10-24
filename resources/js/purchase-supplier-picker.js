import { createElement, ChevronDown } from 'lucide';

const normalize = value => String(value ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es-MX').trim();

export function createSupplierPicker(input, toggle, suppliers, onSelect) {
    const catalog = suppliers.map(supplier => ({ ...supplier,
        searchText: normalize([supplier.name, supplier.commercial_name, supplier.rfc].filter(Boolean).join(' ')),
    }));
    const menu = document.createElement('div');
    menu.className = 'po-supplier-menu';
    menu.hidden = true;
    const list = document.createElement('div');
    list.id = 'po-supplier-options';
    list.setAttribute('role', 'listbox');
    list.setAttribute('aria-label', 'Proveedores registrados');
    const status = document.createElement('p');
    status.className = 'po-supplier-results';
    status.setAttribute('role', 'status');
    menu.append(list, status);
    // Keep the dropdown outside the horizontally scrollable purchase-order sheet.
    document.body.append(menu);
    toggle.append(createElement(ChevronDown, { width: 16, height: 16, 'aria-hidden': 'true' }));
    let results = [], activeIndex = -1;
    const exactMatches = () => catalog.filter(supplier => normalize(supplier.name) === normalize(input.value));
    const initialMatches = input.value.trim() ? exactMatches() : [];
    let selected = initialMatches.length === 1 ? initialMatches[0] : null;

    function close() {
        menu.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
    }
    function position() {
        if (menu.hidden) return;
        const rect = input.parentElement.getBoundingClientRect();
        const room = window.innerHeight - rect.bottom - 12;
        if (rect.bottom < 0 || rect.right < 0 || rect.left > window.innerWidth || room < 65) return close();
        const width = Math.min(Math.max(rect.width, 300), window.innerWidth - 16);
        menu.style.width = `${width}px`;
        menu.style.maxHeight = `${Math.min(320, room)}px`;
        menu.style.left = `${Math.max(8, Math.min(rect.left, window.innerWidth - width - 8))}px`;
        menu.style.top = `${rect.bottom + 3}px`;
    }
    function highlight(index) {
        activeIndex = index;
        [...list.children].forEach((option, i) => option.setAttribute('aria-selected', String(i === index)));
        const option = list.children[index];
        if (option) { input.setAttribute('aria-activedescendant', option.id); option.scrollIntoView({ block: 'nearest' }); }
        else input.removeAttribute('aria-activedescendant');
    }
    function select(supplier) {
        selected = supplier;
        input.value = supplier.name;
        onSelect(supplier);
        close();
        input.focus({ preventScroll: true });
    }
    function open(showAll = false) {
        if (input.disabled || input.readOnly) return;
        const terms = showAll ? [] : normalize(input.value).split(/\s+/).filter(Boolean);
        results = catalog.filter(supplier => terms.every(term => supplier.searchText.includes(term)));
        list.replaceChildren();
        results.forEach((supplier, index) => {
            const option = document.createElement('div');
            option.id = `po-supplier-option-${index}`;
            option.setAttribute('role', 'option');
            const name = document.createElement('strong'); name.textContent = supplier.name;
            const details = document.createElement('small');
            details.textContent = [supplier.rfc, supplier.commercial_name && supplier.commercial_name !== supplier.name ? supplier.commercial_name : '', supplier.category].filter(Boolean).join(' · ');
            option.append(name, details);
            option.addEventListener('pointerdown', event => event.preventDefault());
            option.addEventListener('click', () => select(supplier));
            list.append(option);
        });
        status.textContent = !catalog.length ? 'No hay proveedores disponibles para compras.' : !results.length ? 'Sin proveedores coincidentes. Busca por nombre o RFC.' : `${results.length} ${results.length === 1 ? 'proveedor disponible' : 'proveedores disponibles'}`;
        menu.hidden = false;
        input.setAttribute('aria-expanded', 'true'); toggle.setAttribute('aria-expanded', 'true');
        highlight(-1); list.scrollTop = 0;
        if (input.getBoundingClientRect().bottom > window.innerHeight - 150) input.scrollIntoView({ block: 'center', inline: 'nearest' });
        position();
    }
    input.addEventListener('input', () => {
        if (selected && normalize(input.value) !== normalize(selected.name)) { selected = null; onSelect(null); }
        open();
    });
    // Preserve exact-name completion when a user types a registered name and tabs away.
    input.addEventListener('change', () => {
        const matches = input.value.trim() ? exactMatches() : [];
        if (matches.length === 1 && selected !== matches[0]) {
            selected = matches[0]; onSelect(selected);
        }
    });
    input.addEventListener('click', () => { if (menu.hidden) open(); });
    toggle.addEventListener('click', () => {
        if (menu.hidden) { input.focus({ preventScroll: true }); open(true); } else close();
    });
    input.addEventListener('keydown', event => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (menu.hidden) open();
            if (results.length) highlight(event.key === 'ArrowDown' ? Math.min(activeIndex + 1, results.length - 1) : activeIndex < 0 ? results.length - 1 : Math.max(0, activeIndex - 1));
        } else if (event.key === 'Enter' && !menu.hidden) {
            event.preventDefault();
            const supplier = results[activeIndex < 0 ? 0 : activeIndex];
            if (supplier) select(supplier);
        } else if (event.key === 'Escape' && !menu.hidden) {
            event.preventDefault(); event.stopPropagation(); close();
        } else if (event.key === 'Tab') close();
    });
    for (const type of ['pointerdown', 'focusin']) document.addEventListener(type, event => {
        if (!menu.contains(event.target) && !input.parentElement.contains(event.target)) close();
    });
    window.addEventListener('resize', position);
    window.addEventListener('scroll', event => { if (!menu.contains(event.target)) position(); }, true);
    return { close };
}
