<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import LanguageTabs from '@/components/LanguageTabs.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/appearance';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Appearance settings',
        href: edit().url,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Appearance settings" />

        <h1 class="sr-only">Appearance Settings</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <HeadingSmall
                    title="Appearance settings"
                    description="Update your account's appearance settings"
                />
                
                <div class="space-y-4">
                    <div>
                        <h3 class="mb-2 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                            {{ $t('common.theme') }}
                        </h3>
                        <AppearanceTabs />
                    </div>
                    
                    <div>
                        <h3 class="mb-2 text-sm font-medium text-neutral-900 dark:text-neutral-100">
                            {{ $t('common.language') }}
                        </h3>
                        <LanguageTabs />
                    </div>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
