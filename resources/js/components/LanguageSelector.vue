<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { usePage } from '@inertiajs/vue3';
import { Languages } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage();
const locale = computed(() => page.props.locale as string);
const availableLocales = computed(() => (page.props.availableLocales as string[]) || ['en', 'fr']);

const localeNames: Record<string, string> = {
    en: 'English',
    fr: 'Français',
};

const switchLanguage = (lang: string) => {
    if (lang === locale.value) return;
    
    // Use window.location for a full page reload to ensure translations are updated
    const url = new URL(window.location.href);
    url.searchParams.set('lang', lang);
    window.location.href = url.toString();
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger :as-child="true">
            <Button
                variant="ghost"
                size="icon"
                class="h-9 w-9"
            >
                <Languages class="h-5 w-5" />
                <span class="sr-only">Select Language</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem
                v-for="lang in availableLocales"
                :key="lang"
                @click="switchLanguage(lang)"
                :class="{ 'bg-accent': locale === lang }"
            >
                {{ localeNames[lang] || lang }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
