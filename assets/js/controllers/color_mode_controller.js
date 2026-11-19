import { Controller } from '@hotwired/stimulus';
import { applyMode, getMode, storeMode } from '../lib/color-mode';

/*
 * Color mode toggle of the header: marks the current mode and switches to the chosen one.
 */
export default class extends Controller {
    static targets = ['icon', 'option'];

    connect() {
        this.render(getMode());
    }

    select(event) {
        const { mode } = event.currentTarget.dataset;
        storeMode(mode);
        applyMode(mode);
        this.render(mode);
    }

    render(mode) {
        this.optionTargets.forEach((option) => {
            const active = option.dataset.mode === mode;
            option.classList.toggle('active', active);
            option.setAttribute('aria-pressed', String(active));
            if (active && this.hasIconTarget) {
                this.iconTarget.className = `${option.dataset.icon} fa-fw`;
            }
        });
    }
}
