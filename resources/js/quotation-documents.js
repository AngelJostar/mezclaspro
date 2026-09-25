import { createIcons, Paperclip, Camera, X, Save, Download, RotateCw, Send } from 'lucide';
import '../css/quotation-documents.css';

function initQuotationDocuments() {
    const dialog = document.querySelector('[data-quotation-documents-dialog]');
    if (!dialog || dialog.dataset.initialized) return;
    dialog.dataset.initialized = 'true';
    const icons = () => createIcons({ icons: { Paperclip, Camera, X, Save, Download, RotateCw, Send }, nameAttr: 'data-quotation-document-icon' });
    const find = selector => dialog.querySelector(selector);
    const form = dialog.querySelector('form');
    const video = find('[data-doc-video]');
    let opener, selected, objectUrl, uploadKey, stream, loadController;
    let documents = [], canUpload = false, saving = false, loading = false, cameraPending = false, cameraVersion = 0;
    const bytes = size => `${(size / 1024 / 1024).toFixed(2)} MB`;
    function updateRow(count, preparationUrl) {
        opener.classList.toggle('quotation-row-pill--pending', count === 0);
        opener.classList.toggle('quotation-row-pill--success', count > 0);
        opener.title = `Solicitud de ${opener.dataset.folio} (Foto o Archivo): ${count} adjuntos`;
        const cell = opener.closest('[data-quotation-row]')?.querySelector('[data-quotation-preparation]');
        if (!cell) return;
        if (preparationUrl) {
            const link = document.createElement('a');
            link.href = preparationUrl; link.className = 'quotation-row-pill quotation-row-pill--success';
            link.setAttribute('aria-label', `Enviar ${opener.dataset.folio} a preparacion`);
            link.innerHTML = '<i data-quotation-document-icon="send" aria-hidden="true"></i>Enviar';
            cell.replaceChildren(link);
        } else if (cell.querySelector('a')) {
            const button = document.createElement('button');
            button.type = 'button'; button.disabled = true; button.textContent = 'Pendiente';
            button.className = 'quotation-row-pill quotation-row-pill--muted';
            button.title = 'Requiere autorizacion, una solicitud adjunta y permiso para enviar a preparacion';
            cell.replaceChildren(button);
        }
        icons();
    }
    function error(message = '') {
        const node = find('[data-doc-error]'); node.textContent = message; node.hidden = !message;
        if (message) node.focus();
    }
    function sync() {
        const unavailable = saving || loading || !canUpload;
        find('[data-doc-upload]').hidden = !canUpload;
        find('[data-doc-save]').hidden = !canUpload;
        find('[data-doc-save]').disabled = unavailable || !selected || cameraPending || !!stream;
        find('[data-doc-save-label]').textContent = saving ? 'Guardando...' : 'Guardar';
        find('[data-doc-choose]').disabled = unavailable || cameraPending || !!stream;
        find('[data-doc-camera]').disabled = unavailable || cameraPending || !!stream;
        find('[data-doc-capture]').disabled = unavailable || !stream || video.readyState < 2;
        find('[data-doc-clear]').disabled = saving;
        dialog.querySelectorAll('[data-doc-close]').forEach(button => { button.disabled = saving; });
        form.setAttribute('aria-busy', String(saving || loading));
    }
    function stopCamera() {
        cameraVersion += 1; cameraPending = false;
        stream?.getTracks().forEach(track => track.stop()); stream = null; video.srcObject = null;
        find('[data-doc-camera-panel]').hidden = true;
        find('[data-doc-preview]').hidden = !selected;
        sync();
    }
    function clearFile() {
        if (objectUrl) URL.revokeObjectURL(objectUrl); objectUrl = null; selected = null; uploadKey = null;
        find('[data-doc-file]').value = ''; find('[data-doc-camera-file]').value = '';
        find('[data-doc-image]').removeAttribute('src'); find('[data-doc-image]').hidden = true;
        find('[data-doc-preview]').hidden = true; sync();
    }
    function selectFile(file) {
        if (!file) return;
        clearFile();
        error(); find('[data-doc-status]').textContent = '';
        if (!['image/jpeg', 'image/png', 'image/webp', 'application/pdf'].includes(file.type)) {
            error('Selecciona un archivo JPG, PNG, WEBP o PDF.'); return;
        }
        if (!file.size || file.size > 5 * 1024 * 1024) { error('El archivo debe tener contenido y no superar 5 MB.'); return; }
        stopCamera(); selected = file; uploadKey = crypto.randomUUID();
        find('[data-doc-filename]').textContent = `${file.name} (${bytes(file.size)})`;
        if (file.type.startsWith('image/')) {
            objectUrl = URL.createObjectURL(file); find('[data-doc-image]').src = objectUrl; find('[data-doc-image]').hidden = false;
        }
        find('[data-doc-preview]').hidden = false; sync();
    }
    function renderDocuments() {
        const list = find('[data-doc-list]'); list.replaceChildren();
        find('[data-doc-empty]').hidden = loading || documents.length > 0;
        for (const document of documents) {
            const row = window.document.createElement('li'), details = window.document.createElement('div');
            const name = window.document.createElement('span'), date = window.document.createElement('small');
            name.textContent = document.name; date.textContent = `${document.created_at} · ${bytes(document.size)}`;
            details.append(name, date);
            const link = window.document.createElement('a'); link.href = document.url; link.className = 'quotation-icon-button';
            link.title = `Descargar ${document.name}`; link.setAttribute('aria-label', link.title);
            link.innerHTML = '<i data-quotation-document-icon="download"></i>'; row.append(details, link); list.append(row);
        }
        icons();
    }
    async function readResponse(response) {
        if (response.redirected || !response.headers.get('content-type')?.includes('application/json')) {
            throw new Error('No se pudo confirmar la operacion. Verifica tu sesion.');
        }
        const data = await response.json();
        if (!response.ok) throw new Error(Object.values(data.errors || {}).flat().join(' ')
            || (response.status === 413 ? 'El archivo supera el limite del servidor.' : response.status === 419 ? 'La sesion vencio. Recarga la pagina.'
                : response.status === 403 ? 'No tienes permiso para esta solicitud.' : 'No fue posible completar la operacion. Intenta de nuevo.'));
        return data;
    }
    async function loadDocuments() {
        loadController?.abort(); const current = new AbortController(); loadController = current;
        loading = true; error(); find('[data-doc-status]').textContent = 'Cargando soportes...'; find('[data-doc-retry]').hidden = true; sync();
        try {
            const response = await fetch(opener.dataset.url, { credentials: 'same-origin', cache: 'no-store', headers: { Accept: 'application/json' }, signal: current.signal });
            const data = await readResponse(response);
            if (!dialog.open || loadController !== current) return;
            documents = data.documents; canUpload = data.can_upload; find('[data-doc-status]').textContent = '';
            updateRow(documents.length, data.preparation_url);
        } catch (failure) {
            if (failure.name === 'AbortError' || loadController !== current) return;
            find('[data-doc-status]').textContent = ''; find('[data-doc-retry]').hidden = false;
            error(failure instanceof TypeError ? 'No se pudieron cargar los soportes. Revisa tu conexion.' : failure.message);
        } finally {
            if (loadController === current) { loading = false; renderDocuments(); sync(); }
        }
    }
    document.querySelectorAll('[data-quotation-documents]').forEach(button => button.addEventListener('click', () => {
        opener = button; canUpload = false; documents = []; saving = false; loading = false;
        stopCamera(); clearFile(); error(); renderDocuments();
        find('#quotation-documents-title').textContent = `Solicitud ${button.dataset.folio}`;
        dialog.showModal(); loadDocuments();
    }));
    dialog.querySelectorAll('[data-doc-close]').forEach(button => button.addEventListener('click', () => {
        if (!saving) { stopCamera(); dialog.close(); }
    }));
    dialog.addEventListener('cancel', event => { if (saving) event.preventDefault(); else stopCamera(); });
    dialog.addEventListener('close', () => { loadController?.abort(); loadController = null; stopCamera(); clearFile(); opener?.focus(); });
    window.addEventListener('pagehide', stopCamera);
    find('[data-doc-retry]').addEventListener('click', loadDocuments);
    find('[data-doc-choose]').addEventListener('click', () => find('[data-doc-file]').click());
    for (const selector of ['[data-doc-file]', '[data-doc-camera-file]']) {
        find(selector).addEventListener('change', event => { const file = event.target.files[0]; event.target.value = ''; selectFile(file); });
    }
    find('[data-doc-clear]').addEventListener('click', () => { clearFile(); error(); });
    find('[data-doc-stop-camera]').addEventListener('click', stopCamera);
    video.addEventListener('loadeddata', sync);
    find('[data-doc-camera]').addEventListener('click', async () => {
        error(); find('[data-doc-status]').textContent = '';
        if (!navigator.mediaDevices?.getUserMedia || window.matchMedia('(pointer: coarse)').matches) {
            find('[data-doc-camera-file]').click(); return;
        }
        const current = ++cameraVersion; cameraPending = true; sync();
        try {
            const media = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' }, width: { ideal: 1920 } }, audio: false });
            if (current !== cameraVersion || !dialog.open) { media.getTracks().forEach(track => track.stop()); return; }
            stream = media; video.srcObject = media;
            find('[data-doc-camera-panel]').hidden = false; find('[data-doc-preview]').hidden = true;
            await video.play();
        } catch (failure) {
            if (current !== cameraVersion || !dialog.open) return;
            stopCamera(); error(failure.name === 'NotAllowedError' ? 'No se autorizo el acceso a la camara. Puedes adjuntar un archivo.'
                : 'No se pudo abrir la camara. Puedes adjuntar un archivo.');
        } finally { if (current === cameraVersion) { cameraPending = false; sync(); } }
    });
    find('[data-doc-capture]').addEventListener('click', async () => {
        if (!stream || !video.videoWidth) return;
        const current = cameraVersion, canvas = document.createElement('canvas');
        const scale = Math.min(1, 1920 / Math.max(video.videoWidth, video.videoHeight));
        canvas.width = Math.round(video.videoWidth * scale); canvas.height = Math.round(video.videoHeight * scale);
        canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
        const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', .92));
        if (!dialog.open || current !== cameraVersion) return;
        if (!blob) { error('No fue posible capturar la foto. Intenta de nuevo.'); return; }
        selectFile(new File([blob], `Solicitud-${opener.dataset.folio}.jpg`, { type: 'image/jpeg' }));
    });
    form.addEventListener('submit', async event => {
        event.preventDefault(); if (saving || loading || !canUpload || !selected || stream || cameraPending) return;
        error(); saving = true; find('[data-doc-status]').textContent = ''; sync();
        const data = new FormData(); data.append('file', selected); data.append('upload_key', uploadKey);
        try {
            const response = await fetch(opener.dataset.url, { method: 'POST', credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': form.elements.namedItem('_token').value }, body: data });
            const result = await readResponse(response);
            documents = [result.document, ...documents.filter(document => document.id !== result.document.id)];
            updateRow(result.count, result.preparation_url);
            clearFile(); renderDocuments(); find('[data-doc-status]').textContent = result.message;
        } catch (failure) {
            error(failure instanceof TypeError ? 'No se pudo confirmar el guardado. Revisa tu conexion y vuelve a intentar.' : failure.message);
        } finally { saving = false; sync(); }
    });
    icons();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initQuotationDocuments);
else initQuotationDocuments();
