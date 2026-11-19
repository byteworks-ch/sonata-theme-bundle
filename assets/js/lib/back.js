/*
 * The page a "back" button leads to: the previous page when it is one of the application (a referrer of the same
 * origin, not the page itself), otherwise none. A page opened from a bookmark, a mail or another site has none.
 */
export function previousPage(referrer, current) {
    if (!referrer) {
        return null;
    }
    try {
        const from = new URL(referrer);
        const here = new URL(current);

        return from.origin === here.origin && from.href !== here.href ? from.href : null;
    } catch {
        return null;
    }
}
