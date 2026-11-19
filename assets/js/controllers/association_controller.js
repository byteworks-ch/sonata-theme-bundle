import { Controller } from '@hotwired/stimulus';
import { createModal, spinner } from '../lib/modal';
import { XHR_HEADERS, getHtml, htmlToElement, postForm } from '../lib/http';

/*
 * Association fields of Sonata (replaces the jQuery code of Sonata's association templates), on the field container
 * "#field_container_<id>". Modes:
 * - "inline": a collection edited inline; "append" adds a row (the server renders the field anew).
 * - "list": the id of the selected object in a hidden input; a list in a modal to pick one, a form in a modal to
 *   create or edit one, "remove", and a short description of the selected object.
 * - "model": a select; a form in a modal creates an object, then the field is rendered anew with it selected.
 * - "autocomplete": the "add" button of an autocomplete field; the created object goes to the field as the event
 *   "sonata-autocomplete:created" (detail: id, label).
 * Events as in Sonata, as native events that bubble: "sonata.add_element" and "sonata-admin-append-form-element".
 */
export default class extends Controller {
    static targets = ['input', 'description', 'editButton'];

    static values = {
        field: String,
        mode: String,
        appendUrl: String,
        retrieveUrl: String,
        shortUrl: String,
        editUrl: String,
        placeholder: String,
        loading: { type: String, default: 'Loading…' },
    };

    /* inline collection: a new row */
    async append(event) {
        event.preventDefault();
        const button = event.currentTarget;
        button.classList.add('disabled');
        try {
            const response = await postForm(this.appendUrlValue, this.element.closest('form'));
            const html = await response.text();
            if ('' !== html.trim()) {
                this.replaceContainer(html);
            }
        } finally {
            button.classList.remove('disabled');
        }
    }

    /* row of an inline table marked for deletion: its fields are no longer required */
    toggleDeleted(event) {
        const checkbox = event.currentTarget;
        const row = checkbox.closest('tr') ?? checkbox.closest('.adminlte4-inline-item') ?? checkbox.closest('.sonata-ba-collapsed-fields');
        if (!row) {
            return;
        }
        const deleted = checkbox.checked;
        row.querySelectorAll('input, select, textarea').forEach((field) => {
            if (field === checkbox) {
                return;
            }
            if (deleted && field.required) {
                field.required = false;
                field.dataset.required = 'true';
            } else if (!deleted && 'true' === field.dataset.required) {
                field.required = true;
                delete field.dataset.required;
            }
        });
        row.querySelectorAll('.sonata-ba-field-error-messages').forEach((messages) => {
            messages.hidden = deleted;
        });
        row.classList.toggle('adminlte4-row-deleted', deleted);
    }

    openList(event) {
        event.preventDefault();
        this.openModal(event.currentTarget, 'list');
    }

    openAdd(event) {
        event.preventDefault();
        this.openModal(event.currentTarget, 'form');
    }

    openEdit(event) {
        event.preventDefault();
        this.openModal(event.currentTarget, 'form');
    }

    remove(event) {
        event.preventDefault();
        this.setValue('');
    }

    /* the hidden input changed: show the short description of the selected object */
    async refresh() {
        const id = this.inputTarget.value;
        if (this.hasEditButtonTarget) {
            this.editButtonTarget.classList.toggle('d-none', '' === id || '' === this.editUrlValue);
            if ('' !== id && '' !== this.editUrlValue) {
                this.editButtonTarget.href = this.editUrlValue.replace('__OBJECT_ID__', encodeURIComponent(id));
            }
        }
        if (!this.hasDescriptionTarget) {
            return;
        }
        if ('' === id) {
            const placeholder = document.createElement('span');
            placeholder.className = 'inner-field-short-description text-body-secondary';
            placeholder.textContent = this.placeholderValue;
            this.descriptionTarget.replaceChildren(placeholder);

            return;
        }
        this.descriptionTarget.replaceChildren(spinner(this.loadingValue));
        this.descriptionTarget.innerHTML = await getHtml(this.shortUrlValue.replace('__OBJECT_ID__', encodeURIComponent(id)));
    }

    openModal(link, purpose) {
        this.purpose = purpose;
        this.modal = createModal({ title: (link.getAttribute('title') || link.textContent).trim() });
        this.modal.element.addEventListener('click', (event) => this.modalClick(event));
        this.modal.element.addEventListener('submit', (event) => this.modalSubmit(event));
        this.modal.show();
        this.load(link.href);
    }

    async load(url) {
        this.modal.body.replaceChildren(spinner(this.loadingValue));
        this.modal.body.innerHTML = await getHtml(url);
    }

    modalClick(event) {
        const link = event.target.closest('a');
        if (!link || event.defaultPrevented) {
            return;
        }
        const href = link.getAttribute('href') ?? '';
        if ('' === href || href.startsWith('#') || href.startsWith('javascript:')
            || link.classList.contains('sonata-ba-action') || link.hasAttribute('data-bs-toggle')) {
            return;
        }

        event.preventDefault();
        // a link in a row of the list (identifier or "select" button) picks that object
        const cell = link.closest('td[objectid]');
        if ('list' === this.purpose && cell) {
            this.setValue(cell.getAttribute('objectid'));
            this.modal.hide();

            return;
        }
        this.load(link.href);
    }

    async modalSubmit(event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
            return;
        }
        event.preventDefault();

        if ('get' === form.method.toLowerCase()) {
            const url = new URL(form.action, window.location.href);
            url.search = new URLSearchParams(new FormData(form, event.submitter)).toString();
            this.load(url.toString());

            return;
        }

        const response = await postForm(form.action, form, event.submitter, 'application/json');
        if ((response.headers.get('Content-Type') ?? '').includes('json')) {
            const json = await response.json();
            if (response.ok && 'ok' === json.result) {
                this.created(json.objectId, json.objectName);
            } else {
                this.showViolations(form, json);
            }

            return;
        }
        this.modal.body.innerHTML = await response.text();
    }

    async created(objectId, objectName) {
        this.modal.hide();
        if ('list' === this.modeValue) {
            this.setValue(objectId);

            return;
        }
        if ('autocomplete' === this.modeValue) {
            // the name comes HTML-escaped
            const label = new DOMParser().parseFromString(objectName ?? '', 'text/html').documentElement.textContent;
            document.getElementById(`${this.fieldValue}_autocomplete_input`)?.dispatchEvent(new CustomEvent('sonata-autocomplete:created', {
                bubbles: true,
                detail: { id: objectId, label },
            }));

            return;
        }

        const response = await postForm(this.retrieveUrlValue, this.element.closest('form'));
        const fresh = this.replaceContainer(await response.text());
        // select the new object before the controllers of the new markup connect
        fresh.querySelectorAll(`#${CSS.escape(this.fieldValue)} option, input[name^="${CSS.escape(this.inputName(fresh))}"]`).forEach((option) => {
            if (String(objectId) === option.value) {
                if (option instanceof HTMLOptionElement) {
                    option.selected = true;
                    option.setAttribute('selected', 'selected');
                } else {
                    option.checked = true;
                }
            }
        });
        fresh.dispatchEvent(new CustomEvent('sonata-admin-append-form-element', { bubbles: true }));
    }

    inputName(container) {
        const field = container.querySelector(`#${CSS.escape(this.fieldValue)}`);

        return field?.getAttribute('name') ?? field?.querySelector('[name]')?.getAttribute('name')?.replace(/\[\]$/, '') ?? this.fieldValue;
    }

    setValue(value) {
        this.inputTarget.value = value;
        this.inputTarget.dispatchEvent(new Event('change', { bubbles: true }));
    }

    replaceContainer(html) {
        const fresh = htmlToElement(html);
        // keep files the user selected
        this.element.querySelectorAll('input[type="file"]').forEach((input) => {
            const twin = input.id ? fresh.querySelector(`#${CSS.escape(input.id)}`) : null;
            twin?.replaceWith(input);
        });
        const id = this.fieldValue;
        this.element.replaceWith(fresh);
        const form = fresh.closest('form');
        if (form && form.querySelector('input[type="file"]')) {
            form.enctype = 'multipart/form-data';
        }
        [document.getElementById(`sonata-ba-field-container-${id}`), fresh].forEach((element) => {
            element?.dispatchEvent(new CustomEvent('sonata.add_element', { bubbles: true }));
        });

        return fresh;
    }

    /* violations of a form sent from a modal (Sonata answers with JSON, the field names as property paths) */
    showViolations(form, json) {
        form.querySelectorAll('.adminlte4-violations, .adminlte4-violation').forEach((element) => element.remove());
        form.querySelectorAll('.adminlte4-invalid').forEach((field) => field.classList.remove('is-invalid', 'adminlte4-invalid'));

        const violations = Array.isArray(json.violations) ? json.violations : [];
        const alert = document.createElement('div');
        alert.className = 'alert alert-danger adminlte4-violations';
        alert.setAttribute('role', 'alert');
        const title = document.createElement('div');
        title.className = 'fw-semibold';
        title.textContent = json.title ?? json.detail ?? '';
        alert.append(title);
        const list = document.createElement('ul');
        list.className = 'mb-0';
        violations.forEach((violation) => {
            const item = document.createElement('li');
            item.textContent = violation.title ?? violation.message ?? '';
            list.append(item);

            const field = violation.propertyPath ? form.querySelector(`[name="${CSS.escape(violation.propertyPath)}"]`) : null;
            if (field) {
                field.classList.add('is-invalid', 'adminlte4-invalid');
                const feedback = document.createElement('div');
                feedback.className = 'invalid-feedback d-block adminlte4-violation';
                feedback.textContent = item.textContent;
                (field.closest('.input-group') ?? field).after(feedback);
            }
        });
        alert.append(list);
        form.prepend(alert);
        form.querySelectorAll('button').forEach((button) => {
            button.disabled = false;
        });
    }
}

