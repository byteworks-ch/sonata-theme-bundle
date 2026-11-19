/*
 * Loaded blocking in <head>: applies what has to be there before the first paint.
 * - The color mode, so a dark page does not flash white.
 * - The collapsed sidebar stays open after a click on one of its links while it was open on hover, instead of closing
 *   and opening again (lib/sidebar-hold.js).
 */
import { applyMode, getMode, onSystemChange } from './lib/color-mode';
import { HOLD_CLASS, takeHold } from './lib/sidebar-hold';

applyMode(getMode());
onSystemChange(() => {
    if ('auto' === getMode()) {
        applyMode('auto');
    }
});

if (takeHold()) {
    document.documentElement.classList.add(HOLD_CLASS);
}
