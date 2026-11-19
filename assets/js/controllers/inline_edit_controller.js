import { Controller } from '@hotwired/stimulus';
import { XHR_HEADERS } from '../lib/http';

/*
 * Inline editing of a list cell (option "editable"; Sonata used X-editable). Reads the same attributes:
 * data-type (text, textarea, email, number, url, select, checklist, date), data-value, data-source (the choices,
 * JSON), data-title, data-url. Sends the value to Sonata, which answers with the new cell.
 */
export default class extends Controller {
    connect() {
        this.element.classList.add('adminlte4-inline-edit');
        this.element.setAttribute('role', 'button');
        this.element.tabIndex = 0;
        this.element.title = this.element.dataset.title ?? '';
        this.open = this.open.bind(this);
        this.element.addEventListener('click', this.open);
        this.element.addEventListener('keydown', (event) => {
            if ('Enter' === event.key || ' ' === event.key) {
                this.open(event);
            }
        });
    }

    disconnect() {
        this.element.removeEventListener('click', this.open);
        this.form?.remove();
    }

    open(event) {
        event.preventDefault();
        if (this.form) {
            return;
        }
        this.form = this.buildForm();
        this.element.hidden = true;
        this.element.after(this.form);
        this.form.querySelector('input, select, textarea')?.focus();
    }

    close() {
        this.form?.remove();
        this.form = undefined;
        this.element.hidden = false;
        this.element.focus();
    }

    buildForm() {
        const { dataset } = this.element;
        const form = document.createElement('form');
        form.className = 'adminlte4-inline-edit-form d-flex flex-wrap align-items-start gap-1';
        form.noValidate = false;

        let control;
        if ('select' === dataset.type || 'checklist' === dataset.type) {
            control = document.createElement('select');
            control.className = 'form-select form-select-sm w-auto';
            control.multiple = 'checklist' === dataset.type;
            const current = this.currentValues();
            this.choices().forEach(({ value, text }) => {
                const option = new Option(text, String(value), false, current.includes(String(value)));
                control.append(option);
            });
        } else if ('textarea' === dataset.type) {
            control = document.createElement('textarea');
            control.className = 'form-control form-control-sm';
            control.rows = 3;
            control.value = dataset.value ?? '';
        } else {
            control = document.createElement('input');
            control.className = 'form-control form-control-sm w-auto';
            control.type = { email: 'email', number: 'number', url: 'url', date: 'date' }[dataset.type] ?? 'text';
            if ('number' === control.type) {
                control.step = 'any';
            }
            control.value = dataset.value ?? '';
        }
        control.name = 'value';
        control.setAttribute('aria-label', dataset.title ?? '');

        const save = document.createElement('button');
        save.type = 'submit';
        save.className = 'btn btn-sm btn-primary';
        save.innerHTML = '<i class="fas fa-check" aria-hidden="true"></i>';
        save.setAttribute('aria-label', 'OK');
        const cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'btn btn-sm btn-outline-secondary';
        cancel.innerHTML = '<i class="fas fa-times" aria-hidden="true"></i>';
        cancel.setAttribute('aria-label', 'Cancel');
        cancel.addEventListener('click', () => this.close());
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback d-block w-100';
        feedback.hidden = true;

        form.append(control, save, cancel, feedback);
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            this.save(control, feedback, save);
        });
        form.addEventListener('keydown', (event) => {
            if ('Escape' === event.key) {
                this.close();
            }
        });

        return form;
    }

    choices() {
        try {
            const source = JSON.parse(this.element.dataset.source ?? '[]');

            return Array.isArray(source) ? source : Object.entries(source).map(([value, text]) => ({ value, text }));
        } catch {
            return [];
        }
    }

    currentValues() {
        const { value } = this.element.dataset;
        try {
            const parsed = JSON.parse(value);

            return (Array.isArray(parsed) ? parsed : [parsed]).map(String);
        } catch {
            return [String(value ?? '')];
        }
    }

    async save(control, feedback, button) {
        const body = new FormData();
        if (control.multiple) {
            [...control.selectedOptions].forEach((option) => body.append('value[]', option.value));
        } else {
            body.append('value', control.value);
        }

        button.disabled = true;
        const response = await fetch(this.element.dataset.url, {
            method: 'POST',
            body,
            headers: { ...XHR_HEADERS, Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const json = await response.json().catch(() => '');
        button.disabled = false;

        if (!response.ok) {
            control.classList.add('is-invalid');
            feedback.textContent = 'string' === typeof json ? json : response.statusText;
            feedback.hidden = false;

            return;
        }

        // the answer is the new cell; its editable value connects a new controller
        const template = document.createElement('template');
        template.innerHTML = String(json).trim();
        const cell = this.element.closest('td');
        if (cell && template.content.firstElementChild) {
            cell.replaceWith(template.content.firstElementChild);
        } else {
            this.close();
        }
    }
}
