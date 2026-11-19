//

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { translate } from './translation.js';

const pages = import.meta.glob('./Pages/**/*.vue');

createInertiaApp({
    title: (title) => title ? `${title} | DevPulse` : 'DevPulse',
    resolve: (name) => {
        const page = pages[`./Pages/${name}.vue`];
        if (!page) throw new Error(`Unknown Inertia page: ${name}`);
        return page();
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) }).use(plugin);
        app.config.globalProperties.$t = function (key, parameters) {
            return translate(this.$page.props.translations, key, parameters);
        };
        app.mount(el);
    },
});
