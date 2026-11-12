import { createIcons, Bot, ChevronLeft, ChevronRight, LayoutGrid, Plus, Pencil, X, Play, Settings, ExternalLink, Eye, EyeOff, Send, FileText, Folder, MousePointer2, MessageCircle, UserRound, ShieldCheck, LockKeyhole, Info, Clock } from 'lucide';
import '../css/agent-center.css';

window.refreshAgentIcons = root => createIcons({
    icons: { Bot, ChevronLeft, ChevronRight, LayoutGrid, Plus, Pencil, X, Play, Settings, ExternalLink, Eye, EyeOff, Send, FileText, Folder, MousePointer2, MessageCircle, UserRound, ShieldCheck, LockKeyhole, Info, Clock },
    nameAttr: 'data-agent-icon', root,
});

window.agentCarouselOrder = wire => ({
    orderPointer: null, orderMoving: false, orderSaving: false, orderSuppressUntil: 0,
    orderCards() { return [...this.$refs.agentTrack.querySelectorAll('[data-agent-select]')]; },
    startOrder(event) {
        const card = event.target.closest('[data-agent-select]');
        if (!card || event.button !== 0 || this.orderSaving) return;
        this.orderPointer = { id: event.pointerId, card, x: event.clientX, y: event.clientY, originals: this.orderCards() };
    },
    moveOrder(event) {
        const pointer = this.orderPointer;
        if (!pointer || pointer.id !== event.pointerId) return;
        if (!this.orderMoving) {
            if (Math.abs(event.clientY - pointer.y) > 12 && Math.abs(event.clientX - pointer.x) < 8) { this.cancelOrder(); return; }
            if (Math.abs(event.clientX - pointer.x) < 8) return;
            this.orderMoving = true;
            pointer.card.classList.add('agent-dragging');
        }
        event.preventDefault();
        const track = this.$refs.agentTrack;
        const bounds = track.getBoundingClientRect();
        if (event.clientX < bounds.left + 45) track.scrollLeft -= 22;
        else if (event.clientX > bounds.right - 45) track.scrollLeft += 22;
        const target = this.orderCards().find(card => {
            const rect = card.getBoundingClientRect();
            return event.clientX >= rect.left && event.clientX <= rect.right;
        });
        if (target && target !== pointer.card) {
            const rect = target.getBoundingClientRect();
            track.insertBefore(pointer.card, event.clientX < rect.left + rect.width / 2 ? target : target.nextSibling);
        }
    },
    async finishOrder(event) {
        if (!this.orderPointer || this.orderPointer.id !== event.pointerId) return;
        const pointer = this.orderPointer;
        const moved = this.orderMoving;
        pointer.card.classList.remove('agent-dragging');
        this.orderPointer = null; this.orderMoving = false;
        if (!moved) return;
        this.orderSuppressUntil = Date.now() + 400;
        await this.persistOrder();
    },
    cancelOrder() {
        if (this.orderPointer) {
            const create = this.$refs.agentTrack.querySelector('.agent-create');
            this.orderPointer.originals.forEach(card => { card.classList.remove('agent-dragging'); this.$refs.agentTrack.insertBefore(card, create); });
        }
        this.orderPointer = null; this.orderMoving = false;
    },
    suppressOrderClick(event) {
        if (Date.now() < this.orderSuppressUntil || this.orderSaving) { event.preventDefault(); event.stopImmediatePropagation(); }
    },
    async keyOrder(event) {
        if (!event.altKey || !['ArrowLeft', 'ArrowRight'].includes(event.key) || this.orderSaving) return;
        const card = event.target.closest('[data-agent-select]');
        if (!card) return;
        event.preventDefault();
        const cards = this.orderCards(); const index = cards.indexOf(card);
        const other = cards[index + (event.key === 'ArrowLeft' ? -1 : 1)];
        if (!other) return;
        this.$refs.agentTrack.insertBefore(card, event.key === 'ArrowLeft' ? other : other.nextSibling);
        await this.persistOrder();
        this.$refs.agentTrack.querySelector(`[data-agent-select="${card.dataset.agentSelect}"]`)?.focus();
    },
    async persistOrder() {
        this.orderSaving = true;
        try { await wire.reorderAgents(this.orderCards().map(card => Number(card.dataset.agentSelect))); }
        finally { this.orderSaving = false; await wire.$refresh(); this.updateNavigation(); }
    },
});
