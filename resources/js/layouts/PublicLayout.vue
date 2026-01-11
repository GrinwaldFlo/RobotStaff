<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    title?: string;
    staff?: Staff | null;
}

interface Staff {
    id: string;
    username: string;
    first_name: string | null;
    last_name: string | null;
    email: string;
    photo_path: string | null;
}

const props = withDefaults(defineProps<Props>(), {
    title: '',
    staff: null,
});

const displayName = computed(() => {
    if (props.staff?.first_name) {
        return props.staff.first_name;
    }
    return props.staff?.username || '';
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <Head :title="title" />
        
        <!-- Header -->
        <header class="bg-white shadow dark:bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <div class="flex items-center">
                        <Link href="/" class="text-xl font-bold text-gray-900 dark:text-white">
                            RobotStaff
                        </Link>
                    </div>
                    
                    <nav class="flex items-center space-x-4">
                        <template v-if="staff">
                            <Link
                                href="/staff"
                                class="text-sm text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white"
                            >
                                {{ displayName }}
                            </Link>
                            <Link
                                href="/staff/logout"
                                method="post"
                                as="button"
                                class="rounded-md bg-red-500 px-3 py-1.5 text-sm text-white hover:bg-red-600"
                            >
                                {{ $t('common.logout') }}
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="mt-auto border-t border-gray-200 bg-white py-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="mx-auto max-w-7xl px-4 text-center text-sm text-gray-500 dark:text-gray-400">
                &copy; {{ new Date().getFullYear() }} RobotStaff
            </div>
        </footer>
    </div>
</template>
