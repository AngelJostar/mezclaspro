import { createIcons, Phone, Send, X } from 'lucide';
import '../css/mixture-messages.css';

function initializeMixtureMessages() {
    window.cleanupMixtureMessages?.();
    const dialog = document.querySelector('[data-mixture-chat]');
    if (!dialog) return;
    const lifetime = new AbortController();
    const { signal } = lifetime;
    const scroll = dialog.querySelector('[data-chat-scroll]');
    const list = dialog.querySelector('[data-chat-messages]');
    const empty = dialog.querySelector('[data-chat-empty]');
    const older = dialog.querySelector('[data-chat-older]');
    const form = dialog.querySelector('form');
    const input = form.querySelector('textarea');
    const send = form.querySelector('[type=submit]');
    const error = dialog.querySelector('[data-chat-error]');
    const status = dialog.querySelector('[data-chat-status]');
    const drafts = new Map();
    const summaryCache = new Map();
    let current = null;
    let summaryBusy = false;
    let refreshTimer;
    const icons = () => createIcons({ icons: { Phone, Send, X }, nameAttr: 'data-mixture-chat-icon' });
    const cells = () => [...document.querySelectorAll('[data-mixture-message-key]')];

    async function request(url, body) {
        const response = await fetch(url, {
            method: body ? 'POST' : 'GET', credentials: 'same-origin', cache: 'no-store', signal,
            headers: { Accept: 'application/json', ...(body ? { 'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' } : {}) },
            ...(body ? { body: JSON.stringify(body) } : {}),
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok) {
            const message = response.status === 401 || response.status === 419
                ? 'La sesión terminó. Actualiza la página para continuar.'
                : Object.values(data.errors || {}).flat()[0] || data.message || 'No se pudo conectar. Intenta nuevamente.';
            throw new Error(message);
        }
        return data;
    }

    function updateCells() {
        for (const cell of cells()) {
            const summary = summaryCache.get(cell.dataset.mixtureMessageKey);
            const button = cell.querySelector('.mixture-message-history');
            const badge = cell.querySelector('.mixture-message-unread');
            button.disabled = !summary?.total;
            const label = summary?.total ? `Mensajes de mezcla #${cell.dataset.mixtureMessageKey.split(':')[1]}${summary.unread ? `: ${summary.unread} sin leer` : ''}` : 'Sin mensajes';
            button.title = label;
            button.setAttribute('aria-label', label);
            badge.hidden = !summary?.unread;
            badge.textContent = summary?.unread > 99 ? '99+' : String(summary?.unread || '');
            const compose = cell.querySelector('.mixture-message-compose');
            if (compose) compose.hidden = Boolean(summary?.total);
        }
        icons();
    }

    async function refreshSummaries() {
        if (summaryBusy || document.hidden || signal.aborted) return;
        const keys = [...new Set(cells().map(cell => cell.dataset.mixtureMessageKey))];
        if (!keys.length) return;
        summaryBusy = true;
        try {
            for (let offset = 0; offset < keys.length; offset += 50) {
                const batch = keys.slice(offset, offset + 50);
                const url = new URL(dialog.dataset.summaryUrl, location.href);
                batch.forEach(key => url.searchParams.append('targets[]', key));
                const data = await request(url);
                batch.forEach(key => summaryCache.set(key, data.summaries[key] || null));
            }
            updateCells();
        } catch (failure) {
            if (signal.aborted) return;
            for (const cell of cells()) cell.querySelector('.mixture-message-history').title = failure.message;
        } finally { summaryBusy = false; }
    }

    function setError(message = '') {
        error.textContent = message;
        error.hidden = !message;
    }

    function updateSend() {
        send.disabled = !current?.canSend || current?.sending || !input.value.trim();
    }

    function renderMessages(state) {
        const fragment = document.createDocumentFragment();
        const formatter = new Intl.DateTimeFormat('es-MX', { dateStyle: 'short', timeStyle: 'short' });
        for (const message of [...state.messages.values()].sort((a, b) => a.id - b.id)) {
            const item = document.createElement('li');
            item.className = `mixture-chat-message${message.side === state.side ? ' is-own' : ''}`;
            item.dataset.messageId = message.id;
            const avatar = document.createElement('span');
            avatar.className = 'mixture-chat-avatar';
            avatar.setAttribute('aria-hidden', 'true');
            avatar.textContent = message.author.trim().split(/\s+/).slice(0, 2).map(word => [...word][0]).join('').toUpperCase();
            const bubble = document.createElement('div');
            bubble.className = 'mixture-chat-bubble';
            const meta = document.createElement('div');
            meta.className = 'mixture-chat-meta';
            const author = document.createElement('strong');
            author.textContent = `${message.side === 'hospital' ? 'Hospital' : 'Central de Mezclas'} · ${message.author}`;
            const time = document.createElement('time');
            time.dateTime = message.sent_at;
            time.textContent = formatter.format(new Date(message.sent_at));
            const text = document.createElement('p');
            text.textContent = message.body;
            meta.append(author, time);
            bubble.append(meta, text);
            item.append(avatar, bubble);
            fragment.append(item);
        }
        list.replaceChildren(fragment);
        empty.hidden = state.messages.size > 0;
        empty.textContent = 'Sin mensajes';
    }

    const atBottom = () => scroll.scrollHeight - scroll.scrollTop - scroll.clientHeight < 40;

    async function acknowledge(state) {
        if (current !== state || !dialog.open || document.hidden || !atBottom() || state.reading || !state.messages.size) return;
        const latest = Math.max(...state.messages.keys());
        if (latest <= state.readThrough) return;
        state.reading = true;
        try {
            await request(`${state.url}/leidos`, { through_id: latest });
            state.readThrough = latest;
            await refreshSummaries();
        } catch { /* Keep unread counts until the server confirms the read. */ }
        finally { state.reading = false; }
    }

    async function loadMessages(state, mode = 'new') {
        if (current !== state || state.loading || !dialog.open || document.hidden) return;
        state.loading = true;
        older.disabled = true;
        const wasAtBottom = atBottom();
        const previousHeight = scroll.scrollHeight;
        const previousTop = scroll.scrollTop;
        try {
            const url = new URL(state.url, location.href);
            if (state.messages.size) url.searchParams.set(mode === 'older' ? 'before_id' : 'after_id',
                mode === 'older' ? Math.min(...state.messages.keys()) : Math.max(...state.messages.keys()));
            const data = await request(url);
            if (current !== state || !dialog.open) return;
            const initial = !state.messages.size;
            state.side = data.side;
            state.canSend = data.can_send;
            document.getElementById('mixture-chat-title').textContent = `Mensajes · Mezcla #${data.target.id}`;
            dialog.querySelector('[data-chat-hospital]').textContent = `Hospital: ${data.target.hospital}`;
            dialog.querySelector('[data-chat-patient]').textContent = `Paciente: ${data.target.patient}`;
            for (const message of data.messages) state.messages.set(message.id, message);
            if (initial || mode === 'older') older.hidden = !data.has_older;
            renderMessages(state);
            if (mode === 'older') scroll.scrollTop = previousTop + scroll.scrollHeight - previousHeight;
            else if (initial || wasAtBottom) scroll.scrollTop = scroll.scrollHeight;
            input.disabled = !state.canSend;
            updateSend();
            await acknowledge(state);
        } catch (failure) {
            if (current === state && !signal.aborted) { setError(failure.message); empty.hidden = true; }
        } finally { state.loading = false; older.disabled = false; }
    }

    function openConversation(cell) {
        if (dialog.open) return;
        const key = cell.dataset.mixtureMessageKey;
        current = { key, url: cell.dataset.mixtureMessageUrl.replace(/\/$/, ''), messages: new Map(),
            canSend: false, readThrough: 0, opener: document.activeElement };
        input.value = drafts.get(key)?.body || '';
        input.disabled = true;
        input.readOnly = false;
        list.replaceChildren();
        older.hidden = true;
        empty.hidden = false;
        empty.textContent = 'Cargando mensajes...';
        document.getElementById('mixture-chat-title').textContent = `Mensajes · Mezcla #${key.split(':')[1]}`;
        dialog.querySelector('[data-chat-hospital]').textContent = '';
        dialog.querySelector('[data-chat-patient]').textContent = '';
        status.textContent = '';
        setError();
        updateSend();
        dialog.showModal();
        loadMessages(current);
    }

    document.addEventListener('click', event => {
        const button = event.target.closest('.mixture-message-history,.mixture-message-compose');
        if (!button || button.disabled) return;
        const cell = button.closest('[data-mixture-message-key]');
        if (cell) openConversation(cell);
    }, { signal });
    dialog.querySelector('[data-chat-close]').addEventListener('click', () => dialog.close(), { signal });
    dialog.addEventListener('cancel', event => event.stopPropagation(), { signal });
    dialog.addEventListener('close', () => {
        if (current) {
            drafts.set(current.key, { ...drafts.get(current.key), body: input.value });
            current.opener?.focus();
        }
        current = null;
    }, { signal });
    input.addEventListener('input', updateSend, { signal });
    scroll.addEventListener('scroll', () => { if (current) acknowledge(current); }, { signal });
    older.addEventListener('click', () => { if (current) loadMessages(current, 'older'); }, { signal });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const state = current;
        const body = input.value.trim();
        if (!state?.canSend || state.sending || !body || !form.reportValidity()) return;
        const previous = drafts.get(state.key);
        const token = previous?.sentBody === body ? previous.token : crypto.randomUUID();
        drafts.set(state.key, { body: input.value, sentBody: body, token });
        state.sending = true;
        input.readOnly = true;
        updateSend();
        setError();
        status.textContent = 'Enviando...';
        try {
            await request(state.url, { body, client_token: token });
            drafts.delete(state.key);
            if (current === state) {
                input.value = '';
                status.textContent = 'Mensaje enviado';
                await loadMessages(state);
            }
            await refreshSummaries();
        } catch (failure) {
            if (current === state && !signal.aborted) { status.textContent = ''; setError(failure.message); }
        } finally {
            state.sending = false;
            if (current === state) { input.readOnly = false; updateSend(); }
        }
    }, { signal });
    const poll = () => {
        refreshSummaries();
        if (current) loadMessages(current);
    };
    const timer = setInterval(poll, 10000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) poll(); }, { signal });
    document.addEventListener('mixture-messages:refresh', () => {
        updateCells();
        clearTimeout(refreshTimer);
        refreshTimer = setTimeout(refreshSummaries, 100);
    }, { signal });
    window.cleanupMixtureMessages = () => {
        lifetime.abort(); clearInterval(timer); clearTimeout(refreshTimer);
        if (dialog.open) dialog.close();
    };
    icons();
    refreshSummaries();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeMixtureMessages, { once: true });
else initializeMixtureMessages();
document.addEventListener('livewire:navigated', initializeMixtureMessages);
function registerMessageMorphHook() {
    window.Livewire.hook('morph.updated', () => document.dispatchEvent(new Event('mixture-messages:refresh')));
}
if (window.Livewire) registerMessageMorphHook();
else document.addEventListener('livewire:init', registerMessageMorphHook, { once: true });
