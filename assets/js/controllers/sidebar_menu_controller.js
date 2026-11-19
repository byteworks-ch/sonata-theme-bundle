import { Controller } from '@hotwired/stimulus';
import { HOLD_CLASS, rememberHold } from '../lib/sidebar-hold';

/*
 * Remembers which groups of the sidebar menu are open (cookie "sonata_sidebar_open", the names of the groups,
 * read by the server, so the menu renders open without a jump), and which groups the user closed (cookie
 * "sonata_sidebar_closed"): the group of the current page opens by itself, unless the user closed it. AdminLTE's
 * treeview opens and closes them and sets the class right after these events, before its animation; the events have
 * dots in their names, which Stimulus actions cannot express.
 *
 * And keeps the collapsed sidebar open across a page load (lib/sidebar-hold.js): a mouse click on one of its links
 * while it is open on hover is remembered for the next page, and on a page that holds it open the first pointer
 * movement hands over to :hover (still over the sidebar: it stays open; elsewhere: it closes as when leaving it).
 */
const OPEN_COOKIE = 'sonata_sidebar_open';
const CLOSED_COOKIE = 'sonata_sidebar_closed';
const EVENTS = ['expand.lte.treeview', 'collapse.lte.treeview'];

function readGroups(cookie) {
    const value = document.cookie.split('; ').find((part) => part.startsWith(`${cookie}=`))?.slice(cookie.length + 1);

    return value ? decodeURIComponent(value).split(',').filter(Boolean) : [];
}

function writeGroups(cookie, groups) {
    document.cookie = `${cookie}=${encodeURIComponent(groups.join(','))}; path=/; max-age=31536000; SameSite=Lax`;
}

export default class extends Controller {
    connect() {
        this.schedule = (event) => setTimeout(() => this.store(event.target), 0);
        EVENTS.forEach((name) => this.element.addEventListener(name, this.schedule));

        this.sidebar = this.element.closest('.app-sidebar') ?? this.element;
        this.click = (event) => this.rememberClick(event);
        this.sidebar.addEventListener('click', this.click);
        this.release = () => document.documentElement.classList.remove(HOLD_CLASS);
        if (document.documentElement.classList.contains(HOLD_CLASS)) {
            window.addEventListener('pointermove', this.release, { once: true });
        }
    }

    disconnect() {
        EVENTS.forEach((name) => this.element.removeEventListener(name, this.schedule));
        this.sidebar.removeEventListener('click', this.click);
        window.removeEventListener('pointermove', this.release);
    }

    /** A click with the mouse on a link of the collapsed sidebar while it is open on hover, which loads a page here. */
    rememberClick(event) {
        const link = event.target.closest('a[href]');
        const body = document.body.classList;
        const mouse = 'mouse' === (event.pointerType ?? 'mouse') && event.detail > 0; // keyboard: detail 0
        if (!link || event.defaultPrevented || !mouse || 0 !== event.button
            || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey
            || link.getAttribute('href').startsWith('#') || link.hasAttribute('download')
            || (link.target && '_self' !== link.target)
            || !body.contains('sidebar-mini') || !body.contains('sidebar-collapse') || !this.sidebar.matches(':hover')) {
            return;
        }
        rememberHold();
    }

    /** @param {HTMLElement} item the menu item the user opened or closed */
    store(item) {
        const open = Array.from(this.element.querySelectorAll('.nav-item.menu-open[data-adminlte4-group]'))
            .map((group) => group.dataset.adminlte4Group);
        const closed = new Set(readGroups(CLOSED_COOKIE));
        const name = item?.dataset?.adminlte4Group;
        if (name && !item.classList.contains('menu-open')) {
            closed.add(name);
        }
        open.forEach((group) => closed.delete(group));
        writeGroups(OPEN_COOKIE, open);
        writeGroups(CLOSED_COOKIE, [...closed]);
    }
}
