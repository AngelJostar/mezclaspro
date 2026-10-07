import { createIcons, ChevronLeft, ChevronRight } from 'lucide';
import '../css/fixed-table-scrollbar.css';

const entries = new Map();
let frame;
let sequence = 0;
let observer;

function attach(source) {
    const native = source.hasAttribute('data-sticky-x-native');
    const bar = document.createElement('div');
    bar.className = `fixed-table-scrollbar${native ? ' fixed-table-scrollbar--native' : ''}`;
    bar.hidden = true;
    bar.setAttribute('role', 'group');
    bar.setAttribute('aria-label', 'Desplazamiento horizontal de la tabla');
    bar.innerHTML = native ? `
        <div class="fixed-table-scrollbar-native" tabindex="0" role="region" aria-label="Desplazar columnas de la tabla">
            <div class="fixed-table-scrollbar-spacer"></div>
        </div>` : `
        <button type="button" data-scroll-left aria-label="Desplazar columnas a la izquierda" title="Desplazar a la izquierda"><i data-table-scroll-icon="chevron-left" aria-hidden="true"></i></button>
        <input type="range" min="0" max="0" step="1" value="0" aria-label="Desplazar columnas de la tabla">
        <button type="button" data-scroll-right aria-label="Desplazar columnas a la derecha" title="Desplazar a la derecha"><i data-table-scroll-icon="chevron-right" aria-hidden="true"></i></button>`;
    document.body.append(bar);
    if (!native) createIcons({ icons: { ChevronLeft, ChevronRight }, nameAttr: 'data-table-scroll-icon', root: bar });
    source.classList.add('sticky-x-source', 'is-sticky-x-managed');
    const fallbackId = `fixed-table-scroll-source-${++sequence}`;
    const range = bar.querySelector('input');
    const nativeScroll = bar.querySelector('.fixed-table-scrollbar-native');
    const spacer = bar.querySelector('.fixed-table-scrollbar-spacer');
    const control = nativeScroll || range;
    const left = bar.querySelector('[data-scroll-left]');
    const right = bar.querySelector('[data-scroll-right]');
    const events = new AbortController();
    let table;

    function sync() {
        const maximum = Math.max(0, source.scrollWidth - source.clientWidth);
        const position = Math.max(0, Math.min(maximum, source.scrollLeft));
        if (native) {
            nativeScroll.scrollLeft = position;
            nativeScroll.setAttribute('aria-disabled', String(maximum === 0));
            return;
        }
        range.max = String(maximum);
        range.value = String(position);
        left.disabled = position <= 1;
        right.disabled = position >= maximum - 1;
    }

    function refresh() {
        const currentTable = source.querySelector('table');
        if (currentTable !== table) {
            if (table) resizeObserver?.unobserve(table);
            table = currentTable;
            if (table) resizeObserver?.observe(table);
        }
        const rect = source.getBoundingClientRect();
        const leftEdge = Math.max(0, rect.left);
        const rightEdge = Math.min(document.documentElement.clientWidth, rect.right);
        const width = Math.max(0, rightEdge - leftEdge);
        const visible = (source.hasAttribute('data-sticky-x-always-visible') || source.scrollWidth > source.clientWidth + 2) && source.getClientRects().length > 0
            && rect.top < window.innerHeight && rect.bottom > 0 && width > 96;
        bar.hidden = !visible;
        if (!visible) return;
        source.classList.add('sticky-x-source', 'is-sticky-x-managed');
        if (!source.id) source.id = fallbackId;
        control.setAttribute('aria-controls', source.id);
        bar.style.left = `${leftEdge}px`;
        bar.style.width = `${width}px`;
        if (native) {
            spacer.style.width = `${nativeScroll.clientWidth + Math.max(0, source.scrollWidth - source.clientWidth)}px`;
        } else {
            const thumbWidth = Math.max(28, range.clientWidth * source.clientWidth / source.scrollWidth);
            range.style.setProperty('--scroll-thumb-width', `${Math.min(range.clientWidth, thumbWidth)}px`);
        }
        sync();
    }

    function moveTo(position) {
        source.scrollLeft = position;
        sync();
    }

    source.addEventListener('scroll', sync, { passive: true, signal: events.signal });
    if (native) nativeScroll.addEventListener('scroll', () => {
        if (Math.abs(source.scrollLeft - nativeScroll.scrollLeft) > 1) moveTo(nativeScroll.scrollLeft);
    }, { passive: true, signal: events.signal });
    else range.addEventListener('input', () => moveTo(Number(range.value)), { signal: events.signal });
    control.addEventListener('keydown', event => {
        const maximum = source.scrollWidth - source.clientWidth;
        const targets = {
            ArrowLeft: source.scrollLeft - 80, ArrowRight: source.scrollLeft + 80,
            PageUp: source.scrollLeft - source.clientWidth * 0.8, PageDown: source.scrollLeft + source.clientWidth * 0.8,
            Home: 0, End: maximum,
        };
        if (!(event.key in targets)) return;
        event.preventDefault();
        moveTo(targets[event.key]);
    }, { signal: events.signal });
    left?.addEventListener('click', () => moveTo(source.scrollLeft - source.clientWidth * 0.8), { signal: events.signal });
    right?.addEventListener('click', () => moveTo(source.scrollLeft + source.clientWidth * 0.8), { signal: events.signal });

    const resizeObserver = window.ResizeObserver ? new ResizeObserver(schedule) : null;
    resizeObserver?.observe(source);
    return {
        bar, refresh,
        destroy() {
            events.abort();
            resizeObserver?.disconnect();
            bar.remove();
            source.classList.remove('sticky-x-source', 'is-sticky-x-managed');
        },
    };
}

function reconcile() {
    const sources = new Set(document.querySelectorAll('.admin-content [data-sticky-x-position="viewport"]:not([data-disable-sticky-x])'));
    for (const [source, entry] of entries) {
        if (!sources.has(source) || !entry.bar.isConnected) {
            entry.destroy();
            entries.delete(source);
        }
    }
    for (const source of sources) {
        if (!entries.has(source)) entries.set(source, attach(source));
        entries.get(source).refresh();
    }
    document.querySelectorAll('.admin-page').forEach(page => {
        page.classList.toggle('has-fixed-table-scrollbar', [...entries].some(([source, entry]) => page.contains(source) && !entry.bar.hidden));
    });
}

function schedule() {
    cancelAnimationFrame(frame);
    frame = requestAnimationFrame(reconcile);
}

function initialize() {
    observer?.disconnect();
    observer = new MutationObserver(schedule);
    observer.observe(document.body, { childList: true, subtree: true });
    schedule();
}

window.addEventListener('resize', schedule, { passive: true });
window.addEventListener('scroll', schedule, { passive: true });
document.addEventListener('livewire:navigating', () => {
    observer?.disconnect();
    cancelAnimationFrame(frame);
    entries.forEach(entry => entry.destroy());
    entries.clear();
});
document.addEventListener('livewire:navigated', initialize);
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
else initialize();
