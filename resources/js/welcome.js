import { createIcons, Headset, ArrowRight, ClipboardList, CircleCheck, Award, Check, ShieldCheck, LockKeyhole, LockKeyholeOpen, Monitor } from 'lucide';
import '../css/welcome.css';

createIcons({
    icons: { Headset, ArrowRight, ClipboardList, CircleCheck, Award, Check, ShieldCheck, LockKeyhole, LockKeyholeOpen, Monitor },
    nameAttr: 'data-welcome-icon',
    root: document.body,
});
