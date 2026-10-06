import { createIcons, Bot, ChevronLeft, ChevronRight, LayoutGrid, Plus, Pencil, X, Play, Settings, ExternalLink, Eye, EyeOff, Send, FileText, Folder, MousePointer2, MessageCircle, UserRound, ShieldCheck, LockKeyhole, Info, Clock } from 'lucide';
import '../css/agent-center.css';

window.refreshAgentIcons = root => createIcons({
    icons: { Bot, ChevronLeft, ChevronRight, LayoutGrid, Plus, Pencil, X, Play, Settings, ExternalLink, Eye, EyeOff, Send, FileText, Folder, MousePointer2, MessageCircle, UserRound, ShieldCheck, LockKeyhole, Info, Clock },
    nameAttr: 'data-agent-icon', root,
});
