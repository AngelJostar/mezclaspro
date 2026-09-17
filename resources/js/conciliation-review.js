import { createIcons, FlaskConical, CircleCheck, CircleX, Receipt, LockKeyhole, Search, Download, ChevronLeft, ChevronRight } from 'lucide';

const icons = () => createIcons({ icons: { FlaskConical, CircleCheck, CircleX, Receipt, LockKeyhole, Search, Download, ChevronLeft, ChevronRight }, nameAttr: 'data-review-icon' });

function initializeConciliationReview() {
    icons();
    const root = document.querySelector('[data-conciliation-inbox]');
    if (!root || root.dataset.reviewReady) return;
    root.dataset.reviewReady = 'true';
    const dialog = root.querySelector('[data-conciliation-review]');
    const content = dialog.querySelector('[data-review-content]');
    const loading = dialog.querySelector('[data-review-loading]');
    const error = dialog.querySelector('[data-review-error]');
    const title = dialog.querySelector('h2');
    let pending, trigger, currentUrl;

    async function load(url, focusTitle = true) {
        pending?.abort();
        const request = new AbortController();
        pending = request;
        currentUrl = url;
        loading.hidden = false; error.hidden = true;
        content.inert = true; content.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' }, cache: 'no-store', signal: request.signal });
            const result = await response.json();
            if (!response.ok) throw new Error(Object.values(result.errors || {}).flat()[0] || result.message || 'No se pudo cargar la conciliación.');
            if (request.signal.aborted || !dialog.open) return;
            // HTML is a server-rendered Blade fragment; all submitted values are escaped there.
            content.innerHTML = result.html;
            title.textContent = result.title;
            icons();
            content.inert = false;
            if (focusTitle) { dialog.querySelector('.ht-review-body').scrollTop = 0; title.focus({ preventScroll: true }); }
            else content.querySelector('input[type="search"]')?.focus({ preventScroll: true });
        } catch (failure) {
            if (request.signal.aborted) return;
            error.querySelector('p').textContent = failure instanceof TypeError || failure instanceof SyntaxError
                ? 'No se pudo cargar la conciliación. Revisa tu conexión o sesión e intenta nuevamente.' : failure.message;
            error.hidden = false;
        } finally {
            if (pending === request) { loading.hidden = true; content.inert = false; content.removeAttribute('aria-busy'); }
        }
    }

    root.addEventListener('click', event => {
        const open = event.target.closest('[data-open-conciliation]');
        if (!open || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
        event.preventDefault();
        trigger = open;
        content.replaceChildren(); title.textContent = 'Resumen de conciliación';
        dialog.showModal(); title.focus();
        load(open.href);
    });
    dialog.addEventListener('click', event => {
        if (event.target.closest('[data-close-review]')) dialog.close();
        if (event.target.closest('[data-review-retry]')) load(currentUrl);
        const page = event.target.closest('[data-review-page]');
        if (!page) return;
        event.preventDefault(); load(page.href);
    });
    dialog.addEventListener('submit', event => {
        const form = event.target.closest('[data-review-search]');
        if (!form) return;
        event.preventDefault();
        const url = new URL(form.action);
        url.search = new URLSearchParams(new FormData(form));
        load(url.toString(), false);
    });
    dialog.addEventListener('keydown', event => {
        if (event.key === 'Escape') { event.preventDefault(); dialog.close(); }
    });
    dialog.addEventListener('close', () => { pending?.abort(); content.replaceChildren(); trigger?.focus({ preventScroll: true }); });
    document.addEventListener('livewire:navigating', () => pending?.abort(), { once: true });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeConciliationReview);
else initializeConciliationReview();
document.addEventListener('livewire:navigated', initializeConciliationReview);
