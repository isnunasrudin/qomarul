import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from 'ziggy-js';
import '../css/app.css';

createInertiaApp({
    title: (title) => (title ? `${title} — SIMQOH` : 'SIMQOH'),
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, props.initialPage.props.ziggy)
            .mount(el);
    },
    progress: {
        color: '#2563eb',
    },
});
