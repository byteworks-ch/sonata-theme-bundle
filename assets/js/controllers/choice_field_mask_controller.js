import { Controller } from '@hotwired/stimulus';

/*
 * Choice field mask (ChoiceFieldMaskType; replaces Sonata's inline script): only the fields mapped to the selected
 * choice are shown. Hidden fields are not required (marked with data-required="required" to restore it, as in
 * Sonata); in an inline table, the cell and its column header are hidden too.
 */
export default class extends Controller {
    static values = { form: String, allFields: Array, map: Object, inlineTable: Boolean };

    connect() {
        this.update();
    }

    update() {
        const shown = new Set(this.mapValue[this.selected()] ?? []);
        this.allFieldsValue.forEach((field) => this.toggle(field, shown.has(field)));
    }

    selected() {
        const radios = this.element.querySelectorAll('input[type="radio"]');
        if (radios.length > 0) {
            return Array.from(radios).find((radio) => radio.checked)?.value ?? '';
        }

        return this.element.querySelector('select')?.value ?? '';
    }

    /* the container of a field, found as Sonata finds it */
    container(field) {
        const form = this.formValue;
        const ids = [
            `sonata-ba-field-container-${form}_${field}`,
            // keys of an immutable array are joined with a dash
            `sonata-ba-field-container-${form}-${field}`,
            // fields rendered without a row
            `${form}_${field}`,
            `${form}_${field}_autocomplete_input`,
        ];

        return ids.map((id) => document.getElementById(id)).find((element) => null !== element) ?? null;
    }

    toggle(field, visible) {
        const container = this.container(field);
        if (!container) {
            return;
        }
        container.hidden = !visible;

        if (this.inlineTableValue) {
            const cell = container.closest('td');
            const header = cell?.closest('table')?.querySelector('thead tr')?.children[cell.cellIndex];
            [cell, header].forEach((element) => {
                if (element) {
                    element.hidden = !visible;
                }
            });
        }

        if (visible) {
            container.querySelectorAll('[data-required="required"]').forEach((input) => {
                input.required = true;
                input.removeAttribute('data-required');
            });
        } else {
            container.querySelectorAll('[required]').forEach((input) => {
                input.required = false;
                input.setAttribute('data-required', 'required');
            });
        }
    }
}
