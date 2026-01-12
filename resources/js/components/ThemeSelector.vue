<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useAppearance } from '@/composables/useAppearance';
import { Monitor, Moon, Sun } from 'lucide-vue-next';

const { appearance, updateAppearance } = useAppearance();

const themes = [
    { value: 'light' as const, label: 'Light', Icon: Sun },
    { value: 'dark' as const, label: 'Dark', Icon: Moon },
    { value: 'system' as const, label: 'System', Icon: Monitor },
];

const currentTheme = () => {
    const theme = themes.find(t => t.value === appearance.value);
    return theme || themes[2]; // Default to system
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
                <component :is="currentTheme().Icon" class="h-5 w-5" />
                <span class="sr-only">{{ $t('common.theme') }}</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem
                v-for="theme in themes"
                :key="theme.value"
                @click="updateAppearance(theme.value)"
                :class="{ 'bg-accent': appearance === theme.value }"
            >
                <component :is="theme.Icon" class="mr-2 h-4 w-4" />
                {{ theme.label }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
