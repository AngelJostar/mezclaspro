import { createIcons, UserRound, Bed, CirclePlus } from 'lucide';
import '../css/mixture-workflow.css';

function initializePatientIcons() {
    const root = document.querySelector('[data-dispensing-patient]');
    if (root) createIcons({ icons: { UserRound, Bed, CirclePlus }, nameAttr: 'data-patient-icon', root });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initializePatientIcons);
else initializePatientIcons();
document.addEventListener('livewire:navigated', initializePatientIcons);
