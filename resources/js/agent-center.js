import { createIcons, Bot, ChevronLeft, ChevronRight, LayoutGrid, Plus, Pencil, X, Play, Settings, ExternalLink, Eye, EyeOff, Send } from 'lucide';
import '../css/agent-center.css';

window.refreshAgentIcons = root => createIcons({
    icons: { Bot, ChevronLeft, ChevronRight, LayoutGrid, Plus, Pencil, X, Play, Settings, ExternalLink, Eye, EyeOff, Send },
    nameAttr: 'data-agent-icon', root,
});
