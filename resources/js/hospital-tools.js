import { createIcons, CalendarDays, ClipboardList, FileText, FileSpreadsheet, Settings, ChevronLeft, ChevronRight, Check, X, Clock, CircleAlert, Info, Search, Send } from 'lucide';
import './hospital-invoices';
import './conciliation-review';
import { initializeConciliationSend } from './hospital-conciliation-send';
import '../css/hospital-tools.css';

function initializeHospitalTools() {
    createIcons({ icons: { CalendarDays, ClipboardList, FileText, FileSpreadsheet, Settings, ChevronLeft, ChevronRight, Check, X, Clock, CircleAlert, Info, Search, Send }, nameAttr: 'data-tools-icon' });
    const root = document.querySelector('[data-hospital-tools]');
    if (!root || root.dataset.ready) return;
    root.dataset.ready = 'true';
    const tabs = root.querySelector('.ht-tabs-scroll');
    const selected = tabs.querySelector('[aria-current]');
    if (selected) tabs.scrollLeft = selected.offsetLeft - tabs.offsetLeft;
    root.querySelectorAll('[data-tab-scroll]').forEach(button => button.addEventListener('click', () => {
        tabs.scrollBy({ left: Number(button.dataset.tabScroll) * 250, behavior: 'smooth' });
    }));
    const form = root.querySelector('[data-tools-filters]');
    root.querySelectorAll('[data-invoice-filter]').forEach(input => input.addEventListener('change', () => form.requestSubmit()));
    const from = form.elements.desde;
    const to = form.elements.hasta;
    const calendar = root.querySelector('.ht-calendar');
    const month = calendar.querySelector('[data-calendar-month]');
    const grid = calendar.querySelector('[data-calendar-days]');
    const summary = calendar.querySelector('[data-range-summary]');
    const apply = calendar.querySelector('[data-apply-range]');
    const allHistory = form.querySelector('[data-all-history]');
    const iso = date => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    const parse = value => new Date(`${value}T12:00:00`);
    const format = value => parse(value).toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
    let start = '', end = '', trigger;
    const normalize = () => {
        const period = form.elements.periodo.value;
        if (period === 'dia') return;
        if (from.value) {
            const date = parse(from.value);
            from.value = iso(new Date(date.getFullYear(), period === 'anio' ? 0 : date.getMonth(), 1));
        }
        if (to.value) {
            const date = parse(to.value);
            to.value = iso(new Date(date.getFullYear(), period === 'anio' ? 12 : date.getMonth() + 1, 0));
        }
    };
    const validate = () => to.setCustomValidity(from.value && to.value && to.value < from.value ? 'La fecha final debe ser igual o posterior a la inicial.' : '');
    form.querySelectorAll('input').forEach(input => input.addEventListener('change', () => { normalize(); validate(); }));
    allHistory?.addEventListener('change', () => {
        from.disabled = to.disabled = allHistory.checked;
        root.querySelectorAll('[data-open-range]').forEach(button => { button.disabled = allHistory.checked; });
        if (!allHistory.checked) {
            const today = new Date();
            if (!from.value) from.value = iso(new Date(today.getFullYear(), today.getMonth(), 1));
            if (!to.value) to.value = iso(today);
            normalize(); validate();
        }
    });
    form.addEventListener('submit', event => { normalize(); validate(); if (!form.reportValidity()) event.preventDefault(); });
    initializeConciliationSend(root, form, () => { normalize(); validate(); return form.reportValidity(); });
    const close = () => {
        calendar.hidden = true;
        trigger?.setAttribute('aria-expanded', 'false');
        trigger?.focus({ preventScroll: true });
    };
    const place = () => {
        if (calendar.hidden || !trigger) return;
        const rect = trigger.getBoundingClientRect();
        calendar.style.left = `${Math.max(12, Math.min(rect.right - calendar.offsetWidth, window.innerWidth - calendar.offsetWidth - 12))}px`;
        calendar.style.top = `${Math.max(12, Math.min(rect.bottom + 8, window.innerHeight - calendar.offsetHeight - 12))}px`;
    };
    const render = () => {
        const date = parse(`${month.value}-01`);
        if (Number.isNaN(date.getTime())) return;
        grid.replaceChildren();
        const offset = (date.getDay() + 6) % 7;
        for (let i = 0; i < offset; i++) grid.append(document.createElement('span'));
        const count = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();
        for (let day = 1; day <= count; day++) {
            const value = iso(new Date(date.getFullYear(), date.getMonth(), day));
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = day;
            button.dataset.date = value;
            button.setAttribute('aria-label', format(value));
            button.setAttribute('aria-pressed', String(value === start || value === end));
            if (start && end && value >= start && value <= end) button.classList.add('in-range');
            if (value === start || value === end) button.classList.add('range-edge');
            button.addEventListener('click', () => {
                if (!start || end) { start = value; end = ''; }
                else if (value < start) { end = start; start = value; }
                else end = value;
                render();
                grid.querySelector(`[data-date="${value}"]`)?.focus({ preventScroll: true });
            });
            grid.append(button);
        }
        summary.textContent = start ? `${format(start)}${end ? ` - ${format(end)}` : ''}` : '';
        apply.disabled = !start || !end;
        place();
    };
    root.querySelectorAll('[data-open-range]').forEach(button => button.addEventListener('click', () => {
        trigger?.setAttribute('aria-expanded', 'false');
        trigger = button;
        start = from.value; end = to.value;
        month.value = (start || end || iso(new Date())).slice(0, 7);
        calendar.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        render();
        (grid.querySelector('.range-edge') || grid.querySelector('button')).focus({ preventScroll: true });
    }));
    calendar.querySelectorAll('[data-calendar-step]').forEach(button => button.addEventListener('click', () => {
        const date = parse(`${month.value}-01`);
        date.setMonth(date.getMonth() + Number(button.dataset.calendarStep));
        month.value = iso(date).slice(0, 7); render();
    }));
    month.addEventListener('change', render);
    calendar.querySelector('[data-cancel-range]').addEventListener('click', close);
    apply.addEventListener('click', () => { from.value = start; to.value = end; normalize(); validate(); close(); });
    const outside = event => { if (!calendar.hidden && !calendar.contains(event.target) && !event.target.closest('[data-open-range]')) close(); };
    document.addEventListener('pointerdown', outside);
    calendar.addEventListener('keydown', event => {
        if (event.key === 'Escape') { event.preventDefault(); close(); }
        if (event.key === 'Tab') {
            const focusable = [...calendar.querySelectorAll('button:not(:disabled), input')];
            if (event.shiftKey && document.activeElement === focusable[0]) { event.preventDefault(); focusable.at(-1).focus(); }
            else if (!event.shiftKey && document.activeElement === focusable.at(-1)) { event.preventDefault(); focusable[0].focus(); }
        }
        const offset = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 }[event.key];
        if (offset && event.target.dataset.date) {
            event.preventDefault();
            const date = parse(event.target.dataset.date); date.setDate(date.getDate() + offset);
            month.value = iso(date).slice(0, 7); render();
            grid.querySelector(`[data-date="${iso(date)}"]`)?.focus();
        }
    });
    window.addEventListener('resize', place);
    window.addEventListener('scroll', place, true);
    document.addEventListener('livewire:navigating', () => {
        document.removeEventListener('pointerdown', outside);
        window.removeEventListener('resize', place);
        window.removeEventListener('scroll', place, true);
    }, { once: true });

    const feedback = root.querySelector('[data-tools-status]');
    root.querySelectorAll('[data-conciliable]').forEach(input => input.addEventListener('change', async () => {
        const checked = input.checked;
        input.disabled = true;
        feedback.hidden = true;
        try {
            const response = await fetch(input.dataset.url, {
                method: 'PATCH', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                body: JSON.stringify({ conciliable: checked, previous: input.dataset.previous || null }),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'No se pudo guardar el cambio.');
            input.dataset.previous = result.conciliable;
            input.closest('label').querySelector('output').textContent = checked ? 'S\u00ed' : 'No';
            input.closest('td').dataset.columnFilterValue = checked ? 'S\u00ed' : 'No';
            feedback.textContent = 'Estado conciliable guardado.';
            feedback.classList.remove('ht-error');
        } catch (error) {
            input.checked = !checked;
            feedback.textContent = error instanceof SyntaxError ? 'No se pudo guardar el cambio. Verifica tu sesi\u00f3n e intenta de nuevo.' : error.message;
            feedback.classList.add('ht-error');
        } finally { input.disabled = false; feedback.hidden = false; }
    }));
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeHospitalTools);
else initializeHospitalTools();
document.addEventListener('livewire:navigated', initializeHospitalTools);
