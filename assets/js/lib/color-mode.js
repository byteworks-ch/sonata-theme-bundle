/*
 * Color mode of the admin: "light", "dark" or "auto" (follows the operating system). The user's choice is kept in
 * the browser; without one, the default the server renders on <html data-adminlte4-color-mode> applies. The
 * resolved mode goes to <html data-bs-theme>, which Bootstrap and AdminLTE style.
 */
export const STORAGE_KEY = 'sonata-theme-color-mode';
export const MODES = ['light', 'dark', 'auto'];

const darkQuery = () => window.matchMedia('(prefers-color-scheme: dark)');

export function getStoredMode() {
    try {
        const mode = window.localStorage.getItem(STORAGE_KEY);

        return MODES.includes(mode) ? mode : null;
    } catch {
        return null;
    }
}

export function getMode() {
    const fallback = document.documentElement.dataset.adminlte4ColorMode;

    return getStoredMode() ?? (MODES.includes(fallback) ? fallback : 'auto');
}

export function resolveMode(mode) {
    if ('auto' === mode) {
        return darkQuery().matches ? 'dark' : 'light';
    }

    return mode;
}

export function applyMode(mode) {
    const resolved = resolveMode(mode);
    document.documentElement.setAttribute('data-bs-theme', resolved);
    document.documentElement.style.colorScheme = resolved;
}

export function storeMode(mode) {
    try {
        window.localStorage.setItem(STORAGE_KEY, mode);
    } catch {
        // storage unavailable (private mode): the choice lasts for this page only
    }
}

export function onSystemChange(callback) {
    darkQuery().addEventListener('change', callback);

    return () => darkQuery().removeEventListener('change', callback);
}
