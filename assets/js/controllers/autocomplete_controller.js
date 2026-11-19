import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

/*
 * Autocomplete field (ModelAutocompleteType; Sonata used Select2 with AJAX): Tom Select loads the options from the
 * admin's autocomplete action, the next page while scrolling. The selected ids are kept in hidden inputs (target
 * "inputs"), as in Sonata. Before Tom Select starts, the event "sonata-autocomplete:options" (detail: options, params)
 * lets a project change the Tom Select options and the request parameters. An object created through the "add"
 * button arrives as "sonata-autocomplete:created" (detail: id, label) and is selected.
 */
export default class extends Controller {
    static targets = ['select', 'inputs'];

    static values = {
        url: String,
        params: Object,
        searchParam: String,
        pageParam: String,
        name: String,
        multiple: Boolean,
        disabled: Boolean,
        placeholder: String,
        allowClear: Boolean,
        minimumInputLength: Number,
        delay: Number,
        cache: Boolean,
        safeLabel: Boolean,
        itemClass: String,
        dropdownClass: String,
        containerClass: String,
        noResults: String,
        tooShort: String,
        loadingMore: String,
    };

    connect() {
        const select = this.selectTarget;
        if (select.tomselect) {
            return;
        }

        const controller = this;
        const label = (data, escape) => (this.safeLabelValue ? String(data.label) : escape(String(data.label)));
        const plugins = { virtual_scroll: {} };
        if (this.multipleValue) {
            plugins.remove_button = { title: '' };
        } else if (this.allowClearValue) {
            plugins.clear_button = { title: '' };
        }

        const options = {
            plugins,
            valueField: 'id',
            labelField: 'label',
            searchField: [],
            // the server filters; every option it returns matches
            score: () => () => 1,
            highlight: !this.safeLabelValue,
            maxItems: this.multipleValue ? null : 1,
            maxOptions: null,
            placeholder: this.placeholderValue || undefined,
            hidePlaceholder: false,
            loadThrottle: this.delayValue,
            wrapperClass: `ts-wrapper ${this.containerClassValue}`.trim(),
            dropdownClass: `ts-dropdown ${this.dropdownClassValue}`.trim(),
            // outside a modal at the body, so a scrolling table or card does not cut it off
            dropdownParent: select.closest('.modal') ? null : 'body',
            shouldLoad(query) {
                if (!controller.cacheValue) {
                    delete this.loadedSearches[query];
                }

                return query.length >= controller.minimumInputLengthValue;
            },
            firstUrl: (query) => this.url(query, 1),
            load(query, callback) {
                const url = this.getUrl(query);
                fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then((response) => (response.ok ? response.json() : Promise.reject(response.status)))
                    .then((json) => {
                        if (json.more) {
                            const next = new URL(url, window.location.href);
                            next.searchParams.set(controller.pageParamValue, String(Number(next.searchParams.get(controller.pageParamValue)) + 1));
                            this.setNextUrl(query, next.toString());
                        }
                        callback((json.items ?? []).map((item) => ({ id: String(item.id), label: item.label })));
                    })
                    .catch(() => callback());
            },
            render: {
                option: (data, escape) => `<div class="${escape(this.itemClassValue)}">${label(data, escape)}</div>`,
                item: (data, escape) => `<div>${label(data, escape)}</div>`,
                no_results: (data, escape) => `<div class="no-results">${escape(this.noResultsValue)}</div>`,
                not_loading: (data, escape) => (data.input.length < this.minimumInputLengthValue
                    ? `<div class="no-results">${escape(this.tooShortValue)}</div>`
                    : ''),
                loading_more: (data, escape) => `<div class="loading-more-results">${escape(this.loadingMoreValue)}</div>`,
                no_more_results: () => '',
            },
            onChange: () => this.syncInputs(),
        };

        const params = { ...this.paramsValue };
        this.dispatch('options', { detail: { options, params } });
        this.params = params;

        this.tomSelect = new TomSelect(select, options);
        // dropdown_auto_width: at least as wide as the field, wider for long labels (Tom Select sets the width of a
        // dropdown at the body inline)
        this.tomSelect.on('dropdown_open', (dropdown) => {
            if (dropdown.classList.contains('adminlte4-dropdown-auto-width')) {
                dropdown.style.minWidth = `${this.tomSelect.control.offsetWidth}px`;
            }
        });
        if (this.disabledValue) {
            this.tomSelect.disable();
        }
    }

    disconnect() {
        this.tomSelect?.destroy();
        this.tomSelect = undefined;
    }

    url(query, page) {
        const url = new URL(this.urlValue, window.location.href);
        Object.entries(this.params).forEach(([key, value]) => url.searchParams.set(key, String(value)));
        url.searchParams.set(this.searchParamValue, query);
        url.searchParams.set(this.pageParamValue, String(page));

        return url.toString();
    }

    /* the hidden inputs carry the value, as in Sonata */
    syncInputs() {
        const values = this.tomSelect.items;
        const inputs = (this.multipleValue ? values : [values[0] ?? '']).map((value) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = this.nameValue;
            input.value = value;
            input.disabled = this.disabledValue;

            return input;
        });
        this.inputsTarget.replaceChildren(...inputs);
        this.inputsTarget.dispatchEvent(new Event('change', { bubbles: true }));
    }

    /* an object created in the modal of the "add" button */
    created({ detail: { id, label } }) {
        const value = String(id);
        this.tomSelect.addOption({ id: value, label });
        if (this.multipleValue) {
            this.tomSelect.addItem(value);
        } else {
            this.tomSelect.setValue(value);
        }
    }
}
