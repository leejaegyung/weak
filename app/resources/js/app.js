import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { tooltip } from './utils/tooltip';
import { initTheme } from './utils/theme';

initTheme();

createInertiaApp({
    title: (title) => `${title} - 주간업무보고`,
    resolve: (name) => resolvePageComponent(
        `./Pages/${name}.vue`,
        import.meta.glob('./Pages/**/*.vue')
    ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .directive('tooltip', tooltip)
            .mount(el);
    },
    progress: {
        color: '#FD4401',
    },
});
