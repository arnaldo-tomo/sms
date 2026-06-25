import { ref } from 'vue';

const isDark = ref<boolean>(false);

function apply(value: boolean) {
    isDark.value = value;
    if (typeof document !== 'undefined') {
        document.documentElement.classList.toggle('dark', value);
        localStorage.setItem('theme', value ? 'dark' : 'light');
    }
}

export function useDarkMode() {
    function init() {
        const stored = localStorage.getItem('theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        apply(stored ? stored === 'dark' : prefersDark);
    }

    function toggle() {
        apply(!isDark.value);
    }

    return { isDark, init, toggle };
}
