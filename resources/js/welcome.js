import { createIcons, Headset, ArrowRight, ClipboardList, CircleCheck, Award, Check, ShieldCheck, LockKeyhole, LockKeyholeOpen, Monitor, UserRound, Eye, EyeOff, Shield, CalendarDays, ChevronDown, FileText, Database, ArrowRightLeft, BadgeCheck, Cookie, RefreshCw, Mail } from 'lucide';
import '../css/welcome.css';
import '../css/login.css';
import '../css/privacy.css';
import './privacy-navigation';

createIcons({
    icons: { Headset, ArrowRight, ClipboardList, CircleCheck, Award, Check, ShieldCheck, LockKeyhole, LockKeyholeOpen, Monitor, UserRound, Eye, EyeOff, Shield, CalendarDays, ChevronDown, FileText, Database, ArrowRightLeft, BadgeCheck, Cookie, RefreshCw, Mail },
    nameAttr: 'data-welcome-icon',
    root: document.body,
});

const passwordToggle = document.querySelector('[data-password-toggle]');
const passwordInput = document.getElementById('password');
if (passwordToggle && passwordInput) {
    passwordToggle.hidden = false;
    passwordToggle.addEventListener('click', () => {
        const visible = passwordInput.type === 'password';
        passwordInput.type = visible ? 'text' : 'password';
        const label = visible ? 'Ocultar contraseña' : 'Mostrar contraseña';
        passwordToggle.setAttribute('aria-label', label);
        passwordToggle.title = label;
        passwordToggle.setAttribute('aria-pressed', String(visible));
        passwordToggle.querySelector('[data-password-show]').toggleAttribute('hidden', visible);
        passwordToggle.querySelector('[data-password-hide]').toggleAttribute('hidden', !visible);
    });
}
