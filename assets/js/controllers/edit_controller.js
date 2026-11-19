/*!
 * Adapted from the edit controller of the Sonata Project package SonataAdminBundle.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { Controller } from '@hotwired/stimulus';
import { Tab } from 'bootstrap';

/*
 * Edit form (Sonata's "sonata-edit", on Bootstrap 5): shows the first tab with errors (after validation on the server
 * and when the browser finds an invalid field), keeps the current tab in the URL and in the hidden field "_tab",
 * and disables the submit buttons while the form is sent.
 */
export default class extends Controller {
    static targets = ['tab', 'tabStore'];

    connect() {
        if (this.tabSelected) {
            this.showFirstTabWithErrors('.sonata-ba-field-error');
        }
    }

    checkValidity() {
        if (this.tabSelected) {
            this.showFirstTabWithErrors(':invalid');
        }
    }

    prepareSubmit() {
        setTimeout(() => {
            this.submitters.forEach((submitter) => {
                submitter.disabled = true;
            });
        }, 1);

        if (this.tabSelected && this.hasTabStoreTarget) {
            this.tabStoreTarget.value = this.tabSelected.getAttribute('aria-controls');
        }
    }

    /*
     * Enables the submit buttons again, e.g. after a form in a modal was sent and came back with errors.
     */
    enableSubmit() {
        this.submitters.forEach((submitter) => {
            submitter.disabled = false;
        });
    }

    showFirstTabWithErrors(errorSelector) {
        let firstTabWithErrors = null;

        this.tabTargets.forEach((tab) => {
            const pane = this.element.querySelector(tab.getAttribute('href'));
            const icon = tab.querySelector('.has-errors');
            const hasErrors = null !== pane && pane.querySelectorAll(errorSelector).length > 0;

            if (hasErrors && !firstTabWithErrors) {
                Tab.getOrCreateInstance(tab).show();
                firstTabWithErrors = tab;
            }
            if (icon) {
                icon.hidden = !hasErrors;
            }
        });
    }

    changeTab(event) {
        // a form in a modal does not change the address of the page
        if (this.element.closest('.modal')) {
            return;
        }

        const { history, location } = window;
        const url = new URL(location.href);
        url.searchParams.set('_tab', event.currentTarget.getAttribute('aria-controls'));
        history.pushState({ path: url.toString() }, '', url.toString());
    }

    get tabSelected() {
        return this.tabTargets.find((tab) => tab.classList.contains('active'));
    }

    get submitters() {
        return this.element.querySelectorAll('button[type="submit"], button:not([type])');
    }
}
