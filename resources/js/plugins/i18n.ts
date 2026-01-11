import type { App } from 'vue';

// Translation data loaded from Laravel
const translations: Record<string, Record<string, string>> = {};

// Current locale
let currentLocale = 'en';

/**
 * Load translations from Laravel
 */
export function loadTranslations(locale: string, data: Record<string, Record<string, string>>) {
    translations[locale] = {};
    
    // Flatten nested translations
    for (const [file, trans] of Object.entries(data)) {
        for (const [key, value] of Object.entries(trans)) {
            translations[locale][`${file}.${key}`] = value as string;
        }
    }
}

/**
 * Set the current locale
 */
export function setLocale(locale: string) {
    currentLocale = locale;
}

/**
 * Translate a key with optional replacements
 */
export function t(key: string, replacements: Record<string, string | number> = {}): string {
    let translation = translations[currentLocale]?.[key] || key;
    
    // Apply replacements
    for (const [placeholder, value] of Object.entries(replacements)) {
        translation = translation.replace(`:${placeholder}`, String(value));
    }
    
    return translation;
}

/**
 * Vue plugin
 */
export const i18nPlugin = {
    install(app: App, options: { locale: string; translations: Record<string, Record<string, string>> }) {
        setLocale(options.locale);
        loadTranslations(options.locale, options.translations);
        
        // Add global $t function
        app.config.globalProperties.$t = t;
    },
};

// Augment Vue types
declare module '@vue/runtime-core' {
    interface ComponentCustomProperties {
        $t: typeof t;
    }
}
