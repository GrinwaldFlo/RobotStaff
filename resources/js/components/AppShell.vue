<script setup lang="ts">
import { SidebarProvider } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/vue3';
import { Toaster } from 'vue-sonner';
import { watch } from 'vue';
import { useToast } from '@/composables/useToast';

interface Props {
    variant?: 'header' | 'sidebar';
}

defineProps<Props>();

const page = usePage();
const isOpen = page.props.sidebarOpen;
const { success, error } = useToast();

// Watch for flash messages and display toasts
watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) {
            success(flash.success as string);
        }
        if (flash?.error) {
            error(flash.error as string);
        }
    },
    { deep: true, immediate: true }
);
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
        <Toaster position="top-right" richColors />
    </div>
    <SidebarProvider v-else :default-open="isOpen">
        <slot />
        <Toaster position="top-right" richColors />
    </SidebarProvider>
</template>
