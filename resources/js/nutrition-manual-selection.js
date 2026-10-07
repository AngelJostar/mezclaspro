function initializeNutritionManualSelection() {
    document.querySelectorAll('[data-npt-manual-selector]').forEach(root => {
        if (root.dataset.ready) return;
        root.dataset.ready = 'true';
        const select = root.querySelector('[name="npt"]');
        const description = root.querySelector('[data-npt-manual-description]');
        const update = () => {
            const label = select.selectedOptions[0]?.dataset.manualLabel;
            description.textContent = label ? `Manual de soporte: ${label}.` : 'Selecciona NPT para definir el manual de soporte médico.';
        };
        select.addEventListener('change', update);
        window.addEventListener('pageshow', update);
        update();
    });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializeNutritionManualSelection);
else initializeNutritionManualSelection();
document.addEventListener('livewire:navigated', initializeNutritionManualSelection);
