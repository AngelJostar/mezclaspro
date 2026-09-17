import { createIcons, Sparkles, X, FileText, ChartNoAxesColumnIncreasing, Play, ClipboardCheck } from 'lucide';
import '../css/conciliation-agent.css';

const icons = () => createIcons({ icons: { Sparkles, X, FileText, ChartNoAxesColumnIncreasing, Play, ClipboardCheck }, nameAttr: 'data-ca-icon' });

function initialize() {
    const root = document.querySelector('[data-conciliation-agent]');
    if (!root || root.dataset.ready) return;
    root.dataset.ready = 'true';
    icons();
    const dialog = root.querySelector('[data-agent-dialog]');
    const content = root.querySelector('[data-agent-content]');
    const loading = root.querySelector('[data-agent-loading]');
    const error = root.querySelector('[data-agent-error]');
    const retry = root.querySelector('[data-agent-retry]');
    const opener = root.querySelector('[data-agent-open]');
    let pending, running = false;
    async function responseHtml(response) {
        const result = await response.json();
        if (!response.ok) throw new Error(Object.values(result.errors || {}).flat().join(' ') || result.message || 'No se pudo completar el proceso.');
        return result.html;
    }
    function showError(failure) {
        error.textContent = failure instanceof TypeError || failure instanceof SyntaxError
            ? 'No se pudo conectar. Revisa la sesión y la conexión. Si estabas ejecutando, consulta el historial antes de reintentarlo.' : failure.message;
        error.hidden = false;
    }
    async function load() {
        pending?.abort(); pending = new AbortController();
        loading.hidden = false; error.hidden = true; retry.hidden = true; content.replaceChildren();
        try {
            const html = await responseHtml(await fetch(opener.dataset.url, { headers: { Accept: 'application/json' }, cache: 'no-store', signal: pending.signal }));
            if (!dialog.open) return;
            content.innerHTML = html; icons();
        } catch (failure) { if (failure.name !== 'AbortError') { showError(failure); retry.hidden = false; } }
        finally { loading.hidden = true; }
    }
    opener.addEventListener('click', () => { dialog.showModal(); dialog.querySelector('h2').focus(); load(); });
    retry.addEventListener('click', load);
    dialog.addEventListener('click', event => { if (event.target.closest('[data-agent-close]') && !running) dialog.close(); });
    dialog.addEventListener('cancel', event => { if (running) event.preventDefault(); });
    dialog.addEventListener('close', () => { pending?.abort(); content.replaceChildren(); opener.focus({ preventScroll: true }); });
    dialog.addEventListener('submit', async event => {
        const form = event.target.closest('[data-agent-run]');
        if (!form) return;
        event.preventDefault();
        if (running) return;
        const data = new FormData(form);
        const filters = {};
        for (const [key, value] of data) { const match = key.match(/^filters\[(\w+)\]$/); if (match) filters[match[1]] = value; }
        running = true; error.hidden = true;
        dialog.querySelectorAll('button').forEach(button => { button.disabled = true; });
        form.querySelector('[data-agent-progress]').textContent = 'Ejecutando revisión...';
        try {
            const html = await responseHtml(await fetch(form.action, { method: 'POST', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': data.get('_token') }, body: JSON.stringify({ filters, instructions: data.get('instructions') }) }));
            content.innerHTML = html; icons(); dialog.querySelector('.ca-body').scrollTop = 0; dialog.querySelector('h2').focus();
        } catch (failure) { showError(failure); form.querySelector('[data-agent-progress]').textContent = 'Revisa el resultado o error antes de ejecutar nuevamente.'; }
        finally { running = false; dialog.querySelectorAll('button').forEach(button => { button.disabled = false; }); }
    });
    document.addEventListener('livewire:navigating', () => pending?.abort(), { once: true });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
else initialize();
document.addEventListener('livewire:navigated', initialize);
