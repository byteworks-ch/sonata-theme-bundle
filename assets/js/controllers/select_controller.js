import { Controller } from '@hotwired/stimulus';
import TomSelect from 'tom-select';

/*
 * Select enhancement (Tom Select; Sonata used Select2). Reads Sonata's attributes: data-placeholder,
 * data-sonata-select2-allow-clear, -allow-tags, -maximum-selection-length and -minimum-results-for-search (no
 * search box below this number of options, default 10). The search box sits in the dropdown, as with Select2: a
 * select of fixed values is no text field (only one that allows new values, "allow tags", takes typing in the
 * field). Outside a modal the dropdown hangs at the body, so a scrolling table or card does not cut it off; in a
 * modal it stays with the field (Bootstrap keeps the focus inside the modal). Changes reach the original select as
 * native events. With
 * data-sonata-sortable="true" (sortable multiple choice), the selected items are ordered by drag and drop, and the
 * options of the select follow that order (the browser sends the selected options in the order of the document).
 */
export default class extends Controller {
    connect() {
        const select = this.element;
        if (select.tomselect) {
            return;
        }

        const { dataset } = select;
        const emptyOption = select.querySelector('option[value=""]');
        const placeholder = dataset.placeholder ?? emptyOption?.textContent.trim() ?? undefined;
        const allowClear = undefined !== dataset.sonataSelect2AllowClear
            ? 'false' !== dataset.sonataSelect2AllowClear
            : !select.required && null !== emptyOption;
        const minimumForSearch = Number(dataset.sonataSelect2MinimumResultsForSearch ?? 10);

        const plugins = {};
        if (select.multiple) {
            plugins.remove_button = { title: '' };
            if ('true' === dataset.sonataSortable) {
                plugins.drag_drop = {};
            }
        } else if (allowClear) {
            plugins.clear_button = { title: '' };
        }

        const create = 'true' === dataset.sonataSelect2AllowTags;
        const options = {
            plugins,
            placeholder,
            maxOptions: null,
            create,
            hidePlaceholder: false,
        };
        if (select.multiple) {
            options.maxItems = Number(dataset.sonataSelect2MaximumSelectionLength) || null;
        }
        if (!create) {
            if (select.options.length >= minimumForSearch) {
                plugins.dropdown_input = {};
            } else {
                options.controlInput = null;
            }
        }
        if (!select.closest('.modal')) {
            options.dropdownParent = 'body';
        }

        this.tomSelect = new TomSelect(select, options);
        if ('true' === dataset.sonataSortable) {
            this.tomSelect.on('change', () => this.followOrder());
        }
    }

    /* the selected options last, in the order of the items */
    followOrder() {
        const select = this.element;
        this.tomSelect.items.forEach((value) => {
            const option = Array.from(select.options).find((candidate) => candidate.value === value);
            if (option) {
                select.append(option);
            }
        });
    }

    disconnect() {
        this.tomSelect?.destroy();
        this.tomSelect = undefined;
    }
}
