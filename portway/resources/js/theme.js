/**
 * Light/dark theme toggle. Stored per-browser in localStorage under
 * `portway-theme` with values "light" | "dark" | "system". This runs
 * as plain JS (not Alpine) so the correct class is applied before first
 * paint — see the inline snippet in resources/views/layouts/app.blade.php.
 */
const STORAGE_KEY = 'portway-theme';

function applyTheme(preference) {
    const isDark = preference === 'dark' ||
        (preference === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

    document.documentElement.classList.toggle('dark', isDark);
}

export function getThemePreference() {
    try {
        return localStorage.getItem(STORAGE_KEY) || 'system';
    } catch {
        return 'system';
    }
}

export function setThemePreference(preference) {
    try {
        localStorage.setItem(STORAGE_KEY, preference);
    } catch {
        // Private browsing / storage disabled — theme just won't persist.
    }
    applyTheme(preference);
    window.dispatchEvent(new CustomEvent('portway:theme-changed', { detail: { preference } }));
}

applyTheme(getThemePreference());

window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (getThemePreference() === 'system') applyTheme('system');
});

window.Portway = window.Portway || {};
window.Portway.theme = { get: getThemePreference, set: setThemePreference };
