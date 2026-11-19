import { Controller } from '@hotwired/stimulus';
import { previousPage } from '../lib/back';

/*
 * "Back" button of the error pages: shown only when the previous page is one of the application, then it goes back
 * in the history; in a new tab (no history) it opens that page.
 */
export default class extends Controller {
    connect() {
        this.previous = previousPage(document.referrer, window.location.href);
        this.element.hidden = null === this.previous;
    }

    back(event) {
        event.preventDefault();
        if (window.history.length > 1) {
            window.history.back();
        } else if (this.previous) {
            window.location.assign(this.previous);
        }
    }
}
