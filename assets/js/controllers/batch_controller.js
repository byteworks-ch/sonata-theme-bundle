import { Controller } from '@hotwired/stimulus';

const ROW_CHECKBOXES = 'td.sonata-ba-list-field-batch input[type="checkbox"], div.sonata-ba-list-field-batch input[type="checkbox"]';

/*
 * Batch selection of a list (replaces Sonata's inline script): the checkbox in the header selects all rows, a
 * selected row is highlighted, and a click with the shift key selects the range from the last clicked row.
 */
export default class extends Controller {
    static targets = ['all'];

    connect() {
        this.last = null;
        this.onChange = (event) => {
            if (event.target.matches(ROW_CHECKBOXES)) {
                this.highlight(event.target);
                this.syncAll();
            }
        };
        this.onClick = (event) => {
            if (!event.target.matches(ROW_CHECKBOXES)) {
                return;
            }
            const boxes = this.checkboxes;
            const index = boxes.indexOf(event.target);
            if (event.shiftKey && null !== this.last && this.last !== index) {
                const [from, to] = [Math.min(this.last, index), Math.max(this.last, index)];
                boxes.slice(from, to + 1).forEach((box) => {
                    box.checked = event.target.checked;
                    this.highlight(box);
                });
                this.syncAll();
            }
            this.last = index;
        };
        this.element.addEventListener('change', this.onChange);
        this.element.addEventListener('click', this.onClick);
        this.checkboxes.forEach((box) => this.highlight(box));
        this.syncAll();
    }

    disconnect() {
        this.element.removeEventListener('change', this.onChange);
        this.element.removeEventListener('click', this.onClick);
    }

    toggleAll(event) {
        this.checkboxes.forEach((box) => {
            box.checked = event.currentTarget.checked;
            this.highlight(box);
        });
    }

    highlight(box) {
        box.closest('tr, div.sonata-ba-list-field-batch')?.classList.toggle('sonata-ba-list-row-selected', box.checked);
    }

    syncAll() {
        if (!this.hasAllTarget) {
            return;
        }
        const boxes = this.checkboxes;
        const checked = boxes.filter((box) => box.checked).length;
        this.allTarget.checked = boxes.length > 0 && checked === boxes.length;
        this.allTarget.indeterminate = checked > 0 && checked < boxes.length;
    }

    get checkboxes() {
        return [...this.element.querySelectorAll(ROW_CHECKBOXES)];
    }
}
