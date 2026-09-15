import { createIcons, Bot, ChevronLeft, ChevronRight, LayoutGrid, Plus, Pencil, X, Play, Settings, ExternalLink } from 'lucide';
import '../css/agent-center.css';

window.refreshAgentIcons = root => createIcons({
    icons: { Bot, ChevronLeft, ChevronRight, LayoutGrid, Plus, Pencil, X, Play, Settings, ExternalLink },
    nameAttr: 'data-agent-icon', root,
});
