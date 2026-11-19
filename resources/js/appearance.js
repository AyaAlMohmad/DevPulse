import { ref } from 'vue';

export function resolveTheme(preference, prefersDark) {
    return ['light', 'dark'].includes(preference) ? preference : prefersDark ? 'dark' : 'light';
}

export const theme = ref(resolveTheme(globalThis.document?.documentElement.dataset.theme, true));

export function setTheme(value, environment = globalThis) {
    if (!['light', 'dark'].includes(value)) return;
    theme.value = value;
    const root = environment.document?.documentElement;
    if (root) {
        root.dataset.theme = value;
        root.style.colorScheme = value;
    }
    try {
        environment.localStorage?.setItem('devpulse-theme', value);
    } catch {}
}
