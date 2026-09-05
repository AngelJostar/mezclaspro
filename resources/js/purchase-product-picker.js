import { createElement, ChevronDown, Search } from 'lucide';

const normalize = (value) => String(value).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('es-MX');

export function createProductPicker(slots, onChange) {
    const menu = document.createElement('div');
    menu.id = 'po-product-menu';
    menu.className = 'po-product-menu';
    menu.hidden = true;
    const searchWrap = document.createElement('div');
    searchWrap.className = 'po-product-search';
    searchWrap.append(createElement(Search, { width: 18, height: 18, 'aria-hidden': 'true' }));
    const search = document.createElement('input');
    search.type = 'search';
    search.placeholder = 'Buscar producto';
    search.autocomplete = 'off';
    search.setAttribute('aria-label', 'Buscar producto en el catálogo');
    search.setAttribute('role', 'combobox');
    search.setAttribute('aria-autocomplete', 'list');
    search.setAttribute('aria-controls', 'po-product-options');
    search.setAttribute('aria-expanded', 'false');
    searchWrap.append(search);
    const list = document.createElement('div');
    list.id = 'po-product-options';
    list.setAttribute('role', 'listbox');
    list.setAttribute('aria-label', 'Productos del almacén');
    const status = document.createElement('p');
    status.className = 'po-product-results';
    status.setAttribute('role', 'status');
    menu.append(searchWrap, list, status);
    // The original purchase-order sheet clips overflow; keep the search menu outside it.
    document.body.append(menu);

    let products = [];
    let activeSlot = null;
    let results = [];
    let activeIndex = -1;

    function close() {
        menu.hidden = true;
        search.setAttribute('aria-expanded', 'false');
        search.removeAttribute('aria-activedescendant');
        slots.forEach(({ description, toggle }) => {
            description.setAttribute('aria-expanded', 'false');
            description.removeAttribute('aria-activedescendant');
            toggle.setAttribute('aria-expanded', 'false');
        });
        activeSlot = null;
    }

    function position() {
        if (!activeSlot) return;
        const rect = activeSlot.description.parentElement.getBoundingClientRect();
        if (rect.bottom < 0 || rect.top > window.innerHeight || rect.right < 0 || rect.left > window.innerWidth) {
            close();
            return;
        }
        const width = Math.min(Math.max(rect.width, 320), window.innerWidth - 16);
        const below = window.innerHeight - rect.bottom - 12;
        const above = rect.top - 12;
        const openAbove = below < 260 && above > below;
        const height = Math.min(340, Math.max(140, openAbove ? above : below));
        menu.style.width = `${width}px`;
        menu.style.maxHeight = `${height}px`;
        menu.style.left = `${Math.max(8, Math.min(rect.left, window.innerWidth - width - 8))}px`;
        menu.style.top = `${openAbove ? Math.max(8, rect.top - menu.offsetHeight - 4) : rect.bottom + 4}px`;
    }

    function highlight(index) {
        activeIndex = index;
        [...list.children].forEach((option, optionIndex) => {
            option.setAttribute('aria-selected', String(optionIndex === index));
        });
        const selected = list.children[index];
        for (const input of [search, activeSlot?.description].filter(Boolean)) {
            if (selected) input.setAttribute('aria-activedescendant', selected.id);
            else input.removeAttribute('aria-activedescendant');
        }
        selected?.scrollIntoView({ block: 'nearest' });
    }

    function select(product) {
        const slot = activeSlot;
        if (!slot) return;
        slot.description.value = product.description;
        onChange(slot.slot, product);
        slot.description.focus({ preventScroll: true });
        close();
    }

    function renderResults(query) {
        const terms = normalize(query).trim().split(/\s+/).filter(Boolean);
        results = products.filter((product) => terms.every((term) => product.searchText.includes(term)));
        list.replaceChildren();
        results.forEach((product, index) => {
            const option = document.createElement('div');
            option.id = `po-product-option-${index}`;
            option.setAttribute('role', 'option');
            option.textContent = product.description;
            option.addEventListener('pointerdown', (event) => event.preventDefault());
            option.addEventListener('click', () => select(product));
            list.append(option);
        });
        status.textContent = results.length ? `${results.length} ${results.length === 1 ? 'producto' : 'productos'}` : 'Sin coincidencias';
        highlight(-1);
        list.scrollTop = 0;
        position();
    }

    function open(slot, focusSearch = false) {
        if (slot.description.disabled) return;
        close();
        activeSlot = slot;
        menu.hidden = false;
        slot.description.setAttribute('aria-expanded', 'true');
        slot.toggle.setAttribute('aria-expanded', 'true');
        search.setAttribute('aria-expanded', 'true');
        search.value = focusSearch ? '' : slot.description.value;
        renderResults(search.value);
        if (focusSearch) search.focus({ preventScroll: true });
    }

    function keydown(event, slot) {
        if (event.key === 'Escape' && activeSlot) {
            event.preventDefault();
            event.stopPropagation();
            activeSlot.description.focus({ preventScroll: true });
            close();
        } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            if (!activeSlot) open(slot);
            if (results.length) {
                highlight(event.key === 'ArrowDown'
                    ? Math.min(activeIndex + 1, results.length - 1)
                    : (activeIndex < 0 ? results.length - 1 : Math.max(0, activeIndex - 1)));
            }
        } else if (event.key === 'Enter' && activeSlot) {
            event.preventDefault();
            const product = results[activeIndex < 0 ? 0 : activeIndex];
            if (product) select(product);
        } else if (event.key === 'Tab') {
            if (event.target === search && activeSlot) {
                event.preventDefault();
                activeSlot.description.focus({ preventScroll: true });
            }
            close();
        }
    }

    slots.forEach((slot) => {
        slot.toggle.append(createElement(ChevronDown, { width: 16, height: 16, 'aria-hidden': 'true' }));
        slot.toggle.addEventListener('click', () => activeSlot === slot ? close() : open(slot, true));
        slot.description.addEventListener('click', () => { if (!activeSlot) open(slot); });
        slot.description.addEventListener('input', () => {
            onChange(slot.slot, null);
            open(slot);
        });
        slot.description.addEventListener('keydown', (event) => keydown(event, slot));
    });
    search.addEventListener('input', () => {
        if (!activeSlot) return;
        renderResults(search.value);
    });
    search.addEventListener('keydown', (event) => keydown(event, activeSlot));
    document.addEventListener('pointerdown', (event) => {
        if (activeSlot && !menu.contains(event.target) && !activeSlot.description.parentElement.contains(event.target)) close();
    });
    document.addEventListener('focusin', (event) => {
        if (activeSlot && !menu.contains(event.target) && !activeSlot.description.parentElement.contains(event.target)) close();
    });
    window.addEventListener('resize', position);
    window.addEventListener('scroll', (event) => { if (!menu.contains(event.target)) position(); }, true);

    return {
        close,
        setProducts(value) {
            close();
            products = value.map((product) => ({ ...product, searchText: normalize(product.description) }));
        },
    };
}
