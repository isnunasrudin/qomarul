import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const translations = computed(() => page.props.lang ?? {});

export function t(key, params = {}) {
    let value = translations.value[key];

    if (value === undefined) {
        value = key;
    }

    for (const [name, replacement] of Object.entries(params)) {
        value = value.replaceAll(`:${name}`, String(replacement));
    }

    return value;
}

export function useTranslation() {
    return { t };
}
