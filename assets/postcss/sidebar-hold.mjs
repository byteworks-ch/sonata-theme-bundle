/*
 * PostCSS plugin of the build: the class adminlte4-sidebar-hold on <html> makes the collapsed sidebar ("mini") look
 * as if the pointer were over it, so it can stay open across a page load (assets/js/lib/sidebar-hold.js, which sets
 * the class). Every rule for ".app-sidebar:hover" gets the same selector with the class instead of :hover, every rule
 * for ".app-sidebar:not(:hover)" applies only without the class. The specificity stays the same (a class for :hover,
 * :where() around the condition), so the cascade does not change. As the rules are generated from AdminLTE's and the
 * theme's own, they follow both when they change.
 */
export const HOLD_CLASS = 'adminlte4-sidebar-hold';

const HOVER = '.app-sidebar:hover';
const NOT_HOVER = '.app-sidebar:not(:hover)';

const sidebarHold = () => ({
    postcssPlugin: 'adminlte4-sidebar-hold',
    Rule(rule) {
        if (rule.selector.includes(HOLD_CLASS) || (!rule.selector.includes(HOVER) && !rule.selector.includes(NOT_HOVER))) {
            return;
        }
        const selectors = rule.selectors;
        rule.selectors = [
            ...selectors.map((selector) => (selector.includes(NOT_HOVER) ? `:where(html:not(.${HOLD_CLASS})) ${selector}` : selector)),
            ...selectors.filter((selector) => selector.includes(HOVER)).map((selector) => `.${HOLD_CLASS} ${selector.replaceAll(HOVER, '.app-sidebar')}`),
        ];
    },
});
sidebarHold.postcss = true;

export default sidebarHold;
