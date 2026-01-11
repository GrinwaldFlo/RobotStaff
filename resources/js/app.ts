import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { initializeTheme } from './composables/useAppearance';
import { i18nPlugin } from './plugins/i18n';

const appName = import.meta.env.VITE_APP_NAME || 'RobotStaff';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        
        // Get translations from page props if available
        const pageProps = props.initialPage.props as Record<string, unknown>;
        const locale = (pageProps.locale as string) || 'en';
        const translations = (pageProps.translations as Record<string, Record<string, string>>) || {};
        
        app.use(plugin)
            .use(i18nPlugin, { locale, translations })
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
