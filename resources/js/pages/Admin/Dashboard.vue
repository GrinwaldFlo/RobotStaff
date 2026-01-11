<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Event, SitePreference } from '@/types/models';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Plus, Calendar, Users, Copy, Settings } from 'lucide-vue-next';

interface Props {
    events: (Event & { registrations_count: number })[];
    sitePreferences: SitePreference;
}

const props = defineProps<Props>();

const showNewEventDialog = ref(false);
const showPreferencesDialog = ref(false);

const newEventForm = useForm({
    name: '',
    tagname: '',
    start_date: '',
    end_date: '',
});

const preferencesForm = useForm({
    association_description: props.sitePreferences.association_description || '',
    website_url: props.sitePreferences.website_url || '',
    general_whatsapp_link: props.sitePreferences.general_whatsapp_link || '',
});

const createEvent = () => {
    newEventForm.post('/admin/event', {
        preserveScroll: true,
        onSuccess: () => {
            showNewEventDialog.value = false;
            newEventForm.reset();
        },
    });
};

const updatePreferences = () => {
    preferencesForm.patch('/admin/preferences', {
        preserveScroll: true,
        onSuccess: () => {
            showPreferencesDialog.value = false;
        },
    });
};

const uploadLogo = (event: globalThis.Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('logo', file);

    router.post('/admin/preferences/logo', formData, {
        preserveScroll: true,
    });
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString();
};

const generateTagname = () => {
    newEventForm.tagname = newEventForm.name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
};

const breadcrumbs = [
    { title: 'Admin', href: '/admin' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">{{ $t('admin.dashboard') }}</h1>
                <div class="flex gap-2">
                    <Dialog v-model:open="showPreferencesDialog">
                        <DialogTrigger as-child>
                            <Button variant="outline">
                                <Settings class="mr-2 h-4 w-4" />
                                {{ $t('admin.settings') }}
                            </Button>
                        </DialogTrigger>
                        <DialogContent class="max-w-lg">
                            <DialogHeader>
                                <DialogTitle>{{ $t('admin.site_preferences') }}</DialogTitle>
                                <DialogDescription>{{ $t('admin.site_preferences_description') }}</DialogDescription>
                            </DialogHeader>
                            <form @submit.prevent="updatePreferences" class="space-y-4">
                                <div>
                                    <Label>{{ $t('admin.logo') }}</Label>
                                    <div class="mt-2 flex items-center gap-4">
                                        <img
                                            v-if="sitePreferences.logo_path"
                                            :src="`/storage/${sitePreferences.logo_path}`"
                                            alt="Logo"
                                            class="h-16 w-16 rounded object-cover"
                                        />
                                        <label class="cursor-pointer">
                                            <span class="rounded-md bg-gray-100 px-3 py-1.5 text-sm hover:bg-gray-200">
                                                {{ $t('admin.upload_logo') }}
                                            </span>
                                            <input type="file" accept="image/jpeg,image/png" class="hidden" @change="uploadLogo" />
                                        </label>
                                    </div>
                                </div>
                                <div>
                                    <Label for="association_description">{{ $t('admin.association_description') }}</Label>
                                    <Textarea
                                        id="association_description"
                                        v-model="preferencesForm.association_description"
                                        rows="3"
                                    />
                                </div>
                                <div>
                                    <Label for="website_url">{{ $t('admin.website_url') }}</Label>
                                    <Input
                                        id="website_url"
                                        v-model="preferencesForm.website_url"
                                        type="url"
                                    />
                                </div>
                                <div>
                                    <Label for="general_whatsapp_link">{{ $t('admin.whatsapp_link') }}</Label>
                                    <Input
                                        id="general_whatsapp_link"
                                        v-model="preferencesForm.general_whatsapp_link"
                                        type="url"
                                    />
                                </div>
                                <Button type="submit" class="w-full" :disabled="preferencesForm.processing">
                                    {{ $t('common.save') }}
                                </Button>
                            </form>
                        </DialogContent>
                    </Dialog>

                    <Dialog v-model:open="showNewEventDialog">
                        <DialogTrigger as-child>
                            <Button>
                                <Plus class="mr-2 h-4 w-4" />
                                {{ $t('admin.new_event') }}
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{{ $t('admin.create_event') }}</DialogTitle>
                                <DialogDescription>{{ $t('admin.create_event_description') }}</DialogDescription>
                            </DialogHeader>
                            <form @submit.prevent="createEvent" class="space-y-4">
                                <div>
                                    <Label for="name">{{ $t('admin.event_name') }}</Label>
                                    <Input
                                        id="name"
                                        v-model="newEventForm.name"
                                        required
                                        @blur="generateTagname"
                                    />
                                    <p v-if="newEventForm.errors.name" class="mt-1 text-sm text-red-600">
                                        {{ newEventForm.errors.name }}
                                    </p>
                                </div>
                                <div>
                                    <Label for="tagname">{{ $t('admin.tagname') }}</Label>
                                    <Input
                                        id="tagname"
                                        v-model="newEventForm.tagname"
                                        required
                                        pattern="[a-z0-9-]+"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">{{ $t('admin.tagname_hint') }}</p>
                                    <p v-if="newEventForm.errors.tagname" class="mt-1 text-sm text-red-600">
                                        {{ newEventForm.errors.tagname }}
                                    </p>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label for="start_date">{{ $t('admin.start_date') }}</Label>
                                        <Input
                                            id="start_date"
                                            v-model="newEventForm.start_date"
                                            type="date"
                                            required
                                        />
                                    </div>
                                    <div>
                                        <Label for="end_date">{{ $t('admin.end_date') }}</Label>
                                        <Input
                                            id="end_date"
                                            v-model="newEventForm.end_date"
                                            type="date"
                                            required
                                        />
                                    </div>
                                </div>
                                <Button type="submit" class="w-full" :disabled="newEventForm.processing">
                                    {{ $t('admin.create') }}
                                </Button>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <!-- Events List -->
            <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="event in events"
                    :key="event.id"
                    :href="`/admin/event/${event.tagname}`"
                    class="block"
                >
                    <Card class="h-full transition-shadow hover:shadow-lg">
                        <CardHeader>
                            <CardTitle>{{ event.name }}</CardTitle>
                            <CardDescription class="flex items-center gap-2">
                                <Calendar class="h-4 w-4" />
                                {{ formatDate(event.start_date) }}
                                <span v-if="event.start_date !== event.end_date">
                                    - {{ formatDate(event.end_date) }}
                                </span>
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <Users class="h-4 w-4" />
                                {{ event.registrations_count }} {{ $t('admin.registrations') }}
                            </div>
                        </CardContent>
                    </Card>
                </Link>
            </div>

            <p v-if="events.length === 0" class="text-center text-gray-500">
                {{ $t('admin.no_events') }}
            </p>
        </div>
    </AppLayout>
</template>
