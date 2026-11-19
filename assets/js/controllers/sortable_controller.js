import { Controller } from '@hotwired/stimulus';
import Sortable from 'sortablejs';

/*
 * Sortable inline collection (option "sortable" of a one-to-many field; Sonata used jQuery UI): the rows of a table
 * or the items shown as tabs are moved by their handle (target "handle", rendered by the template), by drag and drop
 * or with the arrow keys on the focused handle. The position fields (named by the option) get the new order 1…n.
 */
export default class extends Controller {
    static values = { field: String, position: String };

    connect() {
        this.list = this.element.querySelector('tbody.sonata-ba-tbody, .sonata-ba-tabs');
        if (!this.list) {
            return;
        }
        this.sortable = Sortable.create(this.list, {
            handle: '.sonata-ba-sortable-handler',
            draggable: 'TBODY' === this.list.tagName ? 'tr' : '.adminlte4-inline-item',
            animation: 150,
            ghostClass: 'adminlte4-sortable-ghost',
            onEnd: () => this.renumber(),
        });
        this.renumber();
    }

    disconnect() {
        this.sortable?.destroy();
        this.sortable = undefined;
    }

    /* arrow keys on a handle move its item */
    move(event) {
        if (!['ArrowUp', 'ArrowDown'].includes(event.key)) {
            return;
        }
        const item = this.items().find((element) => element.contains(event.currentTarget));
        const sibling = 'ArrowUp' === event.key ? item?.previousElementSibling : item?.nextElementSibling;
        if (!item || !sibling) {
            return;
        }
        event.preventDefault();
        if ('ArrowUp' === event.key) {
            sibling.before(item);
        } else {
            sibling.after(item);
        }
        event.currentTarget.focus();
        this.renumber();
    }

    items() {
        return Array.from(this.list.children).filter((element) => 'TR' === element.tagName || element.classList.contains('adminlte4-inline-item'));
    }

    renumber() {
        const selector = `.sonata-ba-td-${this.fieldValue}-${this.positionValue} input, .sonata-ba-field-${this.fieldValue}-${this.positionValue} input`;
        this.items().forEach((item, index) => {
            const input = item.querySelector(selector);
            if (input) {
                input.value = String(index + 1);
            }
        });
    }
}
