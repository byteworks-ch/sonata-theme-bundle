/*
 * Keeps the collapsed sidebar ("mini") open across a page load. When it is open because the pointer is over it and
 * one of its links is clicked, the next page would first render it collapsed and open it again a moment later, once
 * the browser notices the pointer over it. So the click is remembered in the session storage of the tab, the script
 * in <head> sets HOLD_CLASS on <html> before the first paint (the stylesheet treats it like hovering the sidebar),
 * and the first pointer movement hands over to :hover again.
 *
 * HOLD_CLASS is also the class the PostCSS plugin of the build (assets/postcss/sidebar-hold.mjs) generates the rules
 * for: both must stay the same.
 */
export const HOLD_CLASS = 'adminlte4-sidebar-hold';

const STORAGE_KEY = 'sonata-theme-sidebar-hold';

/** A navigation that does not happen within this time (cancelled, prevented) does not hold a later page. */
const MAX_AGE = 30000;

export function rememberHold() {
    try {
        window.sessionStorage.setItem(STORAGE_KEY, String(Date.now()));
    } catch {
        // storage unavailable (private mode): the next page opens the sidebar on hover as before
    }
}

/** Whether the page just loaded should hold the sidebar open; the remembered click counts for one page only. */
export function takeHold() {
    try {
        const clicked = window.sessionStorage.getItem(STORAGE_KEY);
        window.sessionStorage.removeItem(STORAGE_KEY);

        return null !== clicked && Date.now() - Number(clicked) < MAX_AGE;
    } catch {
        return false;
    }
}
