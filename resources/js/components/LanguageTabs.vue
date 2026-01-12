<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const currentLocale = computed(() => page.props.locale as string);
const availableLocales = computed(() => (page.props.availableLocales as string[]) || ['en', 'fr']);

const localeConfig = {
    en: { label: 'English', flag: '🇬🇧' },
    fr: { label: 'Français', flag: '🇫🇷' },
} as const;

const switchLanguage = (lang: string) => {
    if (lang === currentLocale.value) return;
    
    // Use window.location for a full page reload to ensure translations are updated
    const url = new URL(window.location.href);
    url.searchParams.set('lang', lang);
    window.location.href = url.toString();
};
</script>

<template>
    <div
        class="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800"
    >
        <button
            v-for="locale in availableLocales"
            :key="locale"
            @click="switchLanguage(locale)"
            :class="[
                'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                currentLocale === locale
                    ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
            ]"
        >
            <span class="text-lg leading-none">{{ localeConfig[locale as keyof typeof localeConfig]?.flag }}</span>
            <span class="ml-1.5 text-sm">{{ localeConfig[locale as keyof typeof localeConfig]?.label || locale }}</span>
        </button>
    </div>
</template>
