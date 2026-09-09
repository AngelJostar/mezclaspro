import { createIcons, Bot, ChevronLeft, ChevronRight, LayoutGrid, Plus, Pencil, X } from 'lucide';
import '../css/agent-center.css';

window.refreshAgentIcons = root => createIcons({
    icons: { Bot, ChevronLeft, ChevronRight, LayoutGrid, Plus, Pencil, X },
    nameAttr: 'data-agent-icon', root,
});
