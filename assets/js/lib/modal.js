import { Modal } from 'bootstrap';

/* Bootstrap's z-index of modals; stacked modals go up in steps, each with its own backdrop below it. */
const BASE_Z_INDEX = 1055;
const STEP = 20;

/*
 * A Bootstrap modal created on demand at the end of <body>: outside any form, so its content may contain a form.
 * Modals can be stacked (e.g. a list modal opened from a form in a modal); the element is removed once hidden.
 */
export function createModal({ title = '', closeLabel = 'Close' } = {}) {
    const level = document.querySelectorAll('.adminlte4-modal.show').length;
    const element = document.createElement('div');
    element.className = 'modal fade adminlte4-modal';
    element.tabIndex = -1;
    element.setAttribute('aria-hidden', 'true');
    element.style.zIndex = String(BASE_Z_INDEX + level * STEP);
    element.innerHTML = '<div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">'
        + '<div class="modal-header"><h5 class="modal-title"></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>'
        + '<div class="modal-body"></div></div></div>';
    element.querySelector('.modal-title').textContent = title;
    element.querySelector('.btn-close').setAttribute('aria-label', closeLabel);
    document.body.append(element);

    const modal = new Modal(element);
    element.addEventListener('shown.bs.modal', () => {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        if (backdrops.length > 0) {
            backdrops[backdrops.length - 1].style.zIndex = String(BASE_Z_INDEX - 5 + level * STEP);
        }
    });
    element.addEventListener('hidden.bs.modal', () => {
        modal.dispose();
        element.remove();
        // Bootstrap unlocks the page scroll when a modal closes, also if another one is still open
        if (document.querySelector('.modal.show')) {
            document.body.classList.add('modal-open');
        }
    });

    return {
        element,
        body: element.querySelector('.modal-body'),
        show: () => modal.show(),
        hide: () => modal.hide(),
        isOpen: () => element.isConnected,
    };
}

export function spinner(label) {
    const wrapper = document.createElement('div');
    wrapper.className = 'd-flex align-items-center gap-2 text-body-secondary';
    wrapper.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span></span>';
    wrapper.lastElementChild.textContent = label;

    return wrapper;
}
