import { createIcons, UserRound, Bed, CirclePlus, CircleCheck, X, Syringe } from 'lucide';
import '../css/mixture-workflow.css';
import { calculateDispensingProposal, optimizeDispensingSelection, evaluateDispensingSelection, dispensingBrandKey } from './dispensing-proposal';

window.calculateDispensingProposal = calculateDispensingProposal;
window.optimizeDispensingSelection = optimizeDispensingSelection;
window.evaluateDispensingSelection = evaluateDispensingSelection;
window.dispensingBrandKey = dispensingBrandKey;
window.refreshDispensingIcons = (root) => createIcons({ icons: { CircleCheck }, nameAttr: 'data-dispensing-icon', root });
window.refreshInspectionIcons = (root) => createIcons({ icons: { X, UserRound, Syringe }, nameAttr: 'data-inspection-icon', root });

function initializePatientIcons() {
    const root = document.querySelector('[data-dispensing-patient]');
    if (root) createIcons({ icons: { UserRound, Bed, CirclePlus }, nameAttr: 'data-patient-icon', root });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializePatientIcons);
else initializePatientIcons();
document.addEventListener('livewire:navigated', initializePatientIcons);
