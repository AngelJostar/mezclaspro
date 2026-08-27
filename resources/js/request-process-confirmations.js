function initializeRequestProcessConfirmations() {
    document.querySelectorAll('[data-request-process-form]').forEach((form) => {
        if (form.dataset.confirmationReady === '1') return;

        form.dataset.confirmationReady = '1';
        form.addEventListener('submit', (event) => {
            event.preventDefault();

            if (!window.Swal) {
                form.submit();
                return;
            }

            window.Swal.fire({
                title: form.dataset.confirmTitle || '¿Continuar con el proceso?',
                text: form.dataset.confirmText || 'Se actualizará el estado operativo.',
                icon: form.dataset.confirmIcon || 'question',
                showCancelButton: true,
                confirmButtonColor: form.dataset.confirmColor || '#16a34a',
                cancelButtonColor: '#9CA3AF',
                confirmButtonText: form.dataset.confirmButton || 'Sí, continuar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true,
                background: '#ffffff',
                customClass: {
                    popup: 'rounded-2xl shadow-2xl',
                    confirmButton: 'px-5 py-2 rounded-lg',
                    cancelButton: 'px-5 py-2 rounded-lg',
                },
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        });
    });
}

document.addEventListener('DOMContentLoaded', initializeRequestProcessConfirmations);
document.addEventListener('livewire:navigated', initializeRequestProcessConfirmations);
document.addEventListener('livewire:update', initializeRequestProcessConfirmations);
document.addEventListener('livewire:init', () => {
    if (window.Livewire?.hook) {
        window.Livewire.hook('morph.updated', initializeRequestProcessConfirmations);
    }
});
