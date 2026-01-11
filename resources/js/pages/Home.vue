<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Event, SitePreference, Staff } from '@/types/models';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';

interface Props {
    sitePreferences: SitePreference;
    staff: Staff | null;
    registeredEvents: Event[];
    availableEvents: Event[];
    isProfileComplete?: boolean;
}

const props = defineProps<Props>();

const isNewStaff = ref(true);
const showLoginForm = ref(false);

const registerForm = useForm({
    username: '',
    email: '',
});

const loginForm = useForm({
    identifier: '',
});

const submitRegister = () => {
    registerForm.post('/staff/register', {
        preserveScroll: true,
        onSuccess: () => {
            registerForm.reset();
            showLoginForm.value = false;
        },
    });
};

const submitLogin = () => {
    loginForm.post('/staff/login', {
        preserveScroll: true,
        onSuccess: () => {
            loginForm.reset();
            showLoginForm.value = false;
        },
    });
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString();
};
</script>

<template>
    <PublicLayout title="Home" :staff="staff">
        <!-- Site Description -->
        <div class="mb-8 text-center" v-if="sitePreferences.association_description">
            <div v-if="sitePreferences.logo_path" class="mb-4">
                <img
                    :src="`/storage/${sitePreferences.logo_path}`"
                    alt="Association Logo"
                    class="mx-auto h-24 w-auto"
                />
            </div>
            <p class="text-gray-600 dark:text-gray-300">
                {{ sitePreferences.association_description }}
            </p>
        </div>

        <!-- Profile Incomplete Warning -->
        <Alert v-if="staff && !isProfileComplete" class="mb-6 border-yellow-500 bg-yellow-50">
            <AlertDescription>
                {{ $t('staff.profile_incomplete_warning') }}
                <Link href="/staff" class="font-medium underline">
                    {{ $t('staff.complete_profile') }}
                </Link>
            </AlertDescription>
        </Alert>

        <!-- Authenticated Staff View -->
        <template v-if="staff">
            <!-- Registered Events -->
            <section v-if="registeredEvents.length > 0" class="mb-8">
                <h2 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">
                    {{ $t('home.my_events') }}
                </h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="event in registeredEvents"
                        :key="event.id"
                        :href="`/event/${event.tagname}`"
                        class="block"
                    >
                        <Card class="transition-shadow hover:shadow-lg">
                            <CardHeader>
                                <CardTitle>{{ event.name }}</CardTitle>
                                <CardDescription>
                                    {{ formatDate(event.start_date) }}
                                    <span v-if="event.start_date !== event.end_date">
                                        - {{ formatDate(event.end_date) }}
                                    </span>
                                </CardDescription>
                            </CardHeader>
                            <CardContent v-if="event.short_description">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ event.short_description }}
                                </p>
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            </section>

            <!-- Available Events -->
            <section v-if="availableEvents.length > 0">
                <h2 class="mb-4 text-xl font-semibold text-gray-900 dark:text-white">
                    {{ $t('home.available_events') }}
                </h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Link
                        v-for="event in availableEvents"
                        :key="event.id"
                        :href="`/event/${event.tagname}`"
                        class="block"
                    >
                        <Card class="transition-shadow hover:shadow-lg">
                            <CardHeader>
                                <CardTitle>{{ event.name }}</CardTitle>
                                <CardDescription>
                                    {{ formatDate(event.start_date) }}
                                    <span v-if="event.start_date !== event.end_date">
                                        - {{ formatDate(event.end_date) }}
                                    </span>
                                </CardDescription>
                            </CardHeader>
                            <CardContent v-if="event.short_description">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ event.short_description }}
                                </p>
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            </section>

            <p v-if="registeredEvents.length === 0 && availableEvents.length === 0" class="text-center text-gray-500">
                {{ $t('home.no_events') }}
            </p>
        </template>

        <!-- Non-Authenticated View -->
        <template v-else>
            <div class="mx-auto max-w-md">
                <!-- Login/Register Toggle -->
                <div class="mb-6 flex rounded-lg bg-gray-100 p-1 dark:bg-gray-800">
                    <button
                        @click="isNewStaff = true"
                        :class="[
                            'flex-1 rounded-md px-4 py-2 text-sm font-medium transition-colors',
                            isNewStaff
                                ? 'bg-white text-gray-900 shadow dark:bg-gray-700 dark:text-white'
                                : 'text-gray-600 dark:text-gray-400',
                        ]"
                    >
                        {{ $t('auth.new_staff') }}
                    </button>
                    <button
                        @click="isNewStaff = false"
                        :class="[
                            'flex-1 rounded-md px-4 py-2 text-sm font-medium transition-colors',
                            !isNewStaff
                                ? 'bg-white text-gray-900 shadow dark:bg-gray-700 dark:text-white'
                                : 'text-gray-600 dark:text-gray-400',
                        ]"
                    >
                        {{ $t('auth.returning_staff') }}
                    </button>
                </div>

                <!-- New Staff Registration -->
                <Card v-if="isNewStaff">
                    <CardHeader>
                        <CardTitle>{{ $t('auth.register_title') }}</CardTitle>
                        <CardDescription>{{ $t('auth.register_description') }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submitRegister" class="space-y-4">
                            <div>
                                <Label for="username">{{ $t('auth.username') }}</Label>
                                <Input
                                    id="username"
                                    v-model="registerForm.username"
                                    type="text"
                                    required
                                    :disabled="registerForm.processing"
                                />
                                <p v-if="registerForm.errors.username" class="mt-1 text-sm text-red-600">
                                    {{ registerForm.errors.username }}
                                </p>
                            </div>
                            <div>
                                <Label for="email">{{ $t('auth.email') }}</Label>
                                <Input
                                    id="email"
                                    v-model="registerForm.email"
                                    type="email"
                                    required
                                    :disabled="registerForm.processing"
                                />
                                <p v-if="registerForm.errors.email" class="mt-1 text-sm text-red-600">
                                    {{ registerForm.errors.email }}
                                </p>
                            </div>
                            <Button type="submit" class="w-full" :disabled="registerForm.processing">
                                {{ $t('auth.send_link') }}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <!-- Returning Staff Login -->
                <Card v-else>
                    <CardHeader>
                        <CardTitle>{{ $t('auth.login_title') }}</CardTitle>
                        <CardDescription>{{ $t('auth.login_description') }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submitLogin" class="space-y-4">
                            <div>
                                <Label for="identifier">{{ $t('auth.username_or_email') }}</Label>
                                <Input
                                    id="identifier"
                                    v-model="loginForm.identifier"
                                    type="text"
                                    required
                                    :disabled="loginForm.processing"
                                />
                                <p v-if="loginForm.errors.identifier" class="mt-1 text-sm text-red-600">
                                    {{ loginForm.errors.identifier }}
                                </p>
                            </div>
                            <Button type="submit" class="w-full" :disabled="loginForm.processing">
                                {{ $t('auth.send_link') }}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <!-- Available Events for non-authenticated users -->
            <section v-if="availableEvents.length > 0" class="mt-12">
                <h2 class="mb-4 text-center text-xl font-semibold text-gray-900 dark:text-white">
                    {{ $t('home.upcoming_events') }}
                </h2>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="event in availableEvents" :key="event.id">
                        <CardHeader>
                            <CardTitle>{{ event.name }}</CardTitle>
                            <CardDescription>
                                {{ formatDate(event.start_date) }}
                                <span v-if="event.start_date !== event.end_date">
                                    - {{ formatDate(event.end_date) }}
                                </span>
                            </CardDescription>
                        </CardHeader>
                        <CardContent v-if="event.short_description">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ event.short_description }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </section>
        </template>
    </PublicLayout>
</template>
