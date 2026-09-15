import { createIcons, LockKeyhole, X } from 'lucide';

function renderAdjustmentIcons() {
    createIcons({ icons: { LockKeyhole, X }, nameAttr: 'data-adjustment-icon' });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderAdjustmentIcons, { once: true });
} else {
    renderAdjustmentIcons();
}
document.addEventListener('livewire:navigated', renderAdjustmentIcons);
function registerAdjustmentMorphHook() {
    let scheduled = false;
    window.Livewire.hook('morph.updated', () => {
        if (scheduled) return;
        scheduled = true;
        queueMicrotask(() => {
            scheduled = false;
            renderAdjustmentIcons();
        });
    });
}
if (window.Livewire) registerAdjustmentMorphHook();
else document.addEventListener('livewire:init', registerAdjustmentMorphHook, { once: true });
