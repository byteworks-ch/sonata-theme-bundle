import { Controller } from '@hotwired/stimulus';

/*
 * Remembers on wide screens whether the sidebar is collapsed (cookie "sonata_sidebar_hide", read by the server, so
 * the page renders in the right state without flickering). On narrow screens the sidebar is an off-canvas panel
 * and its state is not kept.
 */
export default class extends Controller {
    static values = { breakpoint: { type: Number, default: 992 } };

    connect() {
        this.collapsed = document.body.classList.contains('sidebar-collapse');
        this.observer = new MutationObserver(() => this.update());
        this.observer.observe(document.body, { attributes: true, attributeFilter: ['class'] });
    }

    disconnect() {
        this.observer.disconnect();
    }

    update() {
        const collapsed = document.body.classList.contains('sidebar-collapse');
        if (collapsed === this.collapsed || window.innerWidth < this.breakpointValue) {
            return;
        }
        this.collapsed = collapsed;
        document.cookie = `sonata_sidebar_hide=${collapsed ? 1 : 0}; path=/; max-age=31536000; SameSite=Lax`;
    }
}
