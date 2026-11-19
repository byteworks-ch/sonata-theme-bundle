import { Controller } from '@hotwired/stimulus';

/*
 * Long content of a list cell on one line (class adminlte4-clamp, the content in .adminlte4-clamp-content): marks the
 * element when its content is cut (class is-clamped, then focusable), so the full content shows on hover and focus.
 * That layer is placed in the window (position: fixed) over the cell, so no scrolling container of the table cuts it
 * off; near the bottom of the window it opens upwards.
 */
export default class extends Controller {
    connect() {
        this.content = this.element.querySelector('.adminlte4-clamp-content') ?? this.element;
        this.observer = new ResizeObserver(() => this.measure());
        this.observer.observe(this.element);
        this.place = this.place.bind(this);
        this.element.addEventListener('pointerenter', this.place);
        this.element.addEventListener('focusin', this.place);
        this.measure();
    }

    disconnect() {
        this.observer.disconnect();
        this.element.removeEventListener('pointerenter', this.place);
        this.element.removeEventListener('focusin', this.place);
    }

    measure() {
        if (this.element.matches(':hover, :focus-within')) {
            return;
        }
        const clamped = this.content.scrollWidth > this.content.clientWidth + 1;
        this.element.classList.toggle('is-clamped', clamped);
        if (clamped) {
            this.element.tabIndex = 0;
        } else {
            this.element.removeAttribute('tabindex');
        }
    }

    place() {
        if (!this.element.classList.contains('is-clamped')) {
            return;
        }
        const cell = this.element.getBoundingClientRect();
        this.element.style.setProperty('--adminlte4-clamp-left', `${cell.left}px`);
        this.element.style.setProperty('--adminlte4-clamp-top', `${cell.top}px`);
        this.element.style.setProperty('--adminlte4-clamp-bottom', `${window.innerHeight - cell.bottom}px`);
        // after the layer is shown: open upwards when it would leave the window at the bottom
        requestAnimationFrame(() => {
            const layer = this.content.getBoundingClientRect();
            this.element.classList.toggle('is-clamped-up', layer.bottom > window.innerHeight && cell.top > layer.height);
        });
    }
}
