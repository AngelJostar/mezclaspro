import { createIcons, X } from 'lucide';
import '../css/workflow-modal.css';

const messageType = 'mezclaspro:workflow-modal';

function initializeWorkflowModal() {
    const configElement = document.getElementById('workflow-page-config');
    if (!configElement || configElement.dataset.ready) return;
    configElement.dataset.ready = 'true';
    const config = JSON.parse(configElement.textContent);

    if (config.embedded) {
        const isEmbedded = window.parent !== window;
        const notifyParent = (action, extra = {}) => {
            if (isEmbedded) window.parent.postMessage({ type: messageType, action, ...extra }, window.location.origin);
        };
        notifyParent('ready', { title: document.querySelector('[data-workflow-heading]')?.textContent.replace(/\s+/g, ' ').trim() });
        document.addEventListener('click', (event) => {
            if (!isEmbedded || !event.target.closest('[data-workflow-popup-close]')) return;
            event.preventDefault();
            notifyParent('close');
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !window.Swal?.isVisible()) notifyParent('close');
        });

        if (config.completed) {
            let completed = false;
            const finish = () => {
                if (completed) return;
                completed = true;
                if (isEmbedded) {
                    notifyParent('complete');
                } else if (config.returnTo) {
                    const returnUrl = new URL(config.returnTo, window.location.origin);
                    if (returnUrl.origin === window.location.origin) window.location.assign(returnUrl.href);
                }
            };
            document.addEventListener('workflow-popup-dialog-closed', finish, { once: true });
            const finishWithoutDialog = () => {
                if (!config.waitForConfirmation || !window.Swal?.isVisible()) finish();
            };
            if (document.readyState === 'complete') finishWithoutDialog();
            else window.addEventListener('load', finishWithoutDialog, { once: true });
        }
        return;
    }

    const modal = document.querySelector('[data-workflow-modal]');
    if (!modal) return;
    const frame = modal.querySelector('[data-workflow-frame]');
    const title = modal.querySelector('#workflow-modal-title');
    const loading = modal.querySelector('[data-workflow-loading]');
    const error = modal.querySelector('[data-workflow-error]');
    const closeButton = modal.querySelector('[data-workflow-modal-close]');
    let trigger;
    let activeUrl;
    let timeout;
    let completed = false;
    createIcons({ icons: { X }, nameAttr: 'data-workflow-icon', root: modal });

    const stopLoading = () => {
        clearTimeout(timeout);
        loading.hidden = true;
        modal.removeAttribute('aria-busy');
    };
    const loadFrame = () => {
        clearTimeout(timeout);
        loading.hidden = false;
        error.hidden = true;
        modal.setAttribute('aria-busy', 'true');
        frame.src = activeUrl;
        timeout = window.setTimeout(() => {
            stopLoading();
            error.hidden = false;
        }, 30000);
    };
    frame.addEventListener('load', () => {
        if (!modal.open || frame.getAttribute('src') === 'about:blank') return;
        stopLoading();
    });
    modal.querySelector('[data-workflow-retry]').addEventListener('click', loadFrame);
    closeButton.addEventListener('click', () => modal.close());
    modal.addEventListener('cancel', (event) => {
        try {
            if (frame.contentWindow?.Swal?.isVisible()) event.preventDefault();
        } catch {
            // An expired session may redirect the frame outside the application.
        }
    });
    modal.addEventListener('close', () => {
        stopLoading();
        frame.src = 'about:blank';
        document.documentElement.classList.remove('workflow-modal-open');
        trigger?.focus({ preventScroll: true });
    });

    // Delegation also covers rows replaced by Livewire sorting and pagination.
    const openFromLink = (event) => {
        const link = event.target.closest('[data-approval-popup], [data-dispensing-popup]');
        if (!link) return;
        const url = new URL(link.href, window.location.href);
        if (url.origin !== window.location.origin) return;
        event.preventDefault();
        if (modal.open) return;
        trigger = link;
        activeUrl = url.href;
        completed = false;
        title.textContent = link.hasAttribute('data-dispensing-popup') ? 'Dispensar mezcla' : 'Aprobaci\u00f3n de mezcla';
        frame.title = title.textContent;
        modal.showModal();
        document.documentElement.classList.add('workflow-modal-open');
        closeButton.focus();
        loadFrame();
    };
    const receiveMessage = (event) => {
        if (!modal.open || event.origin !== window.location.origin || event.source !== frame.contentWindow
            || event.data?.type !== messageType) return;
        if (event.data.action === 'ready') {
            stopLoading();
            error.hidden = true;
            if (typeof event.data.title === 'string' && event.data.title) {
                title.textContent = event.data.title;
                frame.title = event.data.title;
            }
        } else if (event.data.action === 'close') {
            modal.close();
        } else if (event.data.action === 'complete' && !completed) {
            completed = true;
            modal.close();
            window.location.reload();
        }
    };
    document.addEventListener('click', openFromLink);
    window.addEventListener('message', receiveMessage);
    document.addEventListener('livewire:navigating', () => {
        if (modal.open) modal.close();
        clearTimeout(timeout);
        document.removeEventListener('click', openFromLink);
        window.removeEventListener('message', receiveMessage);
    }, { once: true });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeWorkflowModal);
else initializeWorkflowModal();
document.addEventListener('livewire:navigated', initializeWorkflowModal);
