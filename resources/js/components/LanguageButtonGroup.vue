<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const locale = computed(() => page.props.locale as string);
const availableLocales = computed(() => (page.props.availableLocales as string[]) || ['en', 'fr']);

const localeConfig = {
    en: { label: 'English' },
    fr: { label: 'Français' },
} as const;

const switchLanguage = (lang: string) => {
    if (lang === locale.value) return;
    
    // Use window.location for a full page reload to ensure translations are updated
    const url = new URL(window.location.href);
    url.searchParams.set('lang', lang);
    window.location.href = url.toString();
};
</script>

<template>
    <div class="flex gap-2">
        <button
            v-for="lang in availableLocales"
            :key="lang"
            @click="switchLanguage(lang)"
            :class=" [
                'flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                locale === lang
                    ? 'bg-blue-600 text-white shadow-sm hover:bg-blue-700'
                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600',
            ]"
        >
            <!-- UK Flag for English -->
            <svg v-if="lang === 'en'" class="h-4 w-6 rounded-sm" viewBox="0 0 60 30" xmlns="http://www.w3.org/2000/svg">
                <clipPath id="s"><path d="M0,0 v30 h60 v-30 z"/></clipPath>
                <clipPath id="t"><path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/></clipPath>
                <g clip-path="url(#s)">
                    <path d="M0,0 v30 h60 v-30 z" fill="#012169"/>
                    <path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/>
                    <path d="M0,0 L60,30 M60,0 L0,30" clip-path="url(#t)" stroke="#C8102E" stroke-width="4"/>
                    <path d="M30,0 v30 M0,15 h60" stroke="#fff" stroke-width="10"/>
                    <path d="M30,0 v30 M0,15 h60" stroke="#C8102E" stroke-width="6"/>
                </g>
            </svg>
            
            <!-- French Flag -->
            <svg v-if="lang === 'fr'" class="h-4 w-6 rounded-sm" viewBox="0 0 900 600" xmlns="http://www.w3.org/2000/svg">
                <rect width="900" height="600" fill="#ED2939"/>
                <rect width="600" height="600" fill="#fff"/>
                <rect width="300" height="600" fill="#002395"/>
            </svg>
            
            <span>{{ localeConfig[lang as keyof typeof localeConfig]?.label }}</span>
        </button>
    </div>
</template>
