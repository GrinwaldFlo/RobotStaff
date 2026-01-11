<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm, router, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Event, EventRole, EventDay, DocumentLink } from '@/types/models';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Check, Plus, Trash2, Copy, Users, ExternalLink } from 'lucide-vue-next';

interface Props {
    event: Event;
}

const props = defineProps<Props>();

const savedFields = ref<Record<string, boolean>>({});
let debounceTimer: ReturnType<typeof setTimeout>;

const showCopyDialog = ref(false);
const showAddRoleDialog = ref(false);
const editingRoleId = ref<number | null>(null);

const form = useForm({
    name: props.event.name,
    short_description: props.event.short_description || '',
    long_description: props.event.long_description || '',
    start_date: props.event.start_date,
    end_date: props.event.end_date,
    location: props.event.location || '',
    contact_email: props.event.contact_email || '',
    whatsapp_link: props.event.whatsapp_link || '',
    general_documents_links: props.event.general_documents_links || [],
});

const copyForm = useForm({
    new_tagname: '',
});

const roleForm = useForm({
    designation: '',
    number_required: 0,
    document_links: [] as DocumentLink[],
});

const saveField = (field: string) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.patch(`/admin/event/${props.event.tagname}`, {
            [field]: form[field as keyof typeof form],
        }, {
            preserveScroll: true,
            onSuccess: () => {
                savedFields.value[field] = true;
                setTimeout(() => {
                    savedFields.value[field] = false;
                }, 2000);
            },
        });
    }, 500);
};

const uploadLogo = (event: globalThis.Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('logo', file);

    router.post(`/admin/event/${props.event.tagname}/logo`, formData, {
        preserveScroll: true,
    });
};

const deleteEvent = () => {
    if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
        router.delete(`/admin/event/${props.event.tagname}`);
    }
};

const copyEvent = () => {
    copyForm.post(`/admin/event/${props.event.tagname}/copy`, {
        preserveScroll: true,
        onSuccess: () => {
            showCopyDialog.value = false;
            copyForm.reset();
        },
    });
};

const addRole = () => {
    roleForm.post(`/admin/event/${props.event.tagname}/role`, {
        preserveScroll: true,
        onSuccess: () => {
            showAddRoleDialog.value = false;
            roleForm.reset();
        },
    });
};

const startEditRole = (role: EventRole) => {
    editingRoleId.value = role.id;
    roleForm.designation = role.designation;
    roleForm.number_required = role.number_required;
    roleForm.document_links = role.document_links || [];
};

const saveRole = (roleId: number) => {
    router.patch(`/admin/event/${props.event.tagname}/role/${roleId}`, {
        designation: roleForm.designation,
        number_required: roleForm.number_required,
        document_links: roleForm.document_links,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            editingRoleId.value = null;
            roleForm.reset();
        },
    });
};

const deleteRole = (roleId: number) => {
    if (confirm('Are you sure you want to delete this role?')) {
        router.delete(`/admin/event/${props.event.tagname}/role/${roleId}`, {
            preserveScroll: true,
        });
    }
};

const addDocumentLink = () => {
    form.general_documents_links.push({ title: '', url: '' });
};

const removeDocumentLink = (index: number) => {
    form.general_documents_links.splice(index, 1);
    saveField('general_documents_links');
};

const updateDaySchedule = (day: EventDay, schedule: string) => {
    router.patch(`/admin/event/${props.event.tagname}/day/${day.id}`, {
        schedule,
    }, {
        preserveScroll: true,
    });
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString(undefined, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
};

const breadcrumbs = [
    { title: 'Admin', href: '/admin' },
    { title: props.event.name, href: `/admin/event/${props.event.tagname}` },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold">{{ event.name }}</h1>
                <div class="flex gap-2">
                    <Link :href="`/admin/event/${event.tagname}/staff`">
                        <Button variant="outline">
                            <Users class="mr-2 h-4 w-4" />
                            {{ $t('admin.manage_staff') }}
                        </Button>
                    </Link>
                    <Dialog v-model:open="showCopyDialog">
                        <DialogTrigger as-child>
                            <Button variant="outline">
                                <Copy class="mr-2 h-4 w-4" />
                                {{ $t('admin.copy_event') }}
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>{{ $t('admin.copy_event') }}</DialogTitle>
                                <DialogDescription>{{ $t('admin.copy_event_description') }}</DialogDescription>
                            </DialogHeader>
                            <form @submit.prevent="copyEvent" class="space-y-4">
                                <div>
                                    <Label for="new_tagname">{{ $t('admin.new_tagname') }}</Label>
                                    <Input
                                        id="new_tagname"
                                        v-model="copyForm.new_tagname"
                                        required
                                        pattern="[a-z0-9-]+"
                                    />
                                    <p v-if="copyForm.errors.new_tagname" class="mt-1 text-sm text-red-600">
                                        {{ copyForm.errors.new_tagname }}
                                    </p>
                                </div>
                                <Button type="submit" class="w-full" :disabled="copyForm.processing">
                                    {{ $t('admin.copy') }}
                                </Button>
                            </form>
                        </DialogContent>
                    </Dialog>
                    <Button variant="destructive" @click="deleteEvent">
                        <Trash2 class="mr-2 h-4 w-4" />
                        {{ $t('admin.delete') }}
                    </Button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Basic Info -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ $t('admin.basic_info') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div>
                            <Label>{{ $t('admin.logo') }}</Label>
                            <div class="mt-2 flex items-center gap-4">
                                <img
                                    v-if="event.logo_path"
                                    :src="`/storage/${event.logo_path}`"
                                    :alt="event.name"
                                    class="h-20 w-20 rounded-lg object-cover"
                                />
                                <label class="cursor-pointer">
                                    <span class="rounded-md bg-gray-100 px-3 py-1.5 text-sm hover:bg-gray-200 dark:bg-gray-700">
                                        {{ $t('admin.upload_logo') }}
                                    </span>
                                    <input type="file" accept="image/jpeg,image/png" class="hidden" @change="uploadLogo" />
                                </label>
                            </div>
                        </div>

                        <div class="relative">
                            <Label for="name">{{ $t('admin.event_name') }}</Label>
                            <Input id="name" v-model="form.name" @input="saveField('name')" />
                            <Check v-if="savedFields.name" class="absolute right-3 top-8 h-4 w-4 text-green-500" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="relative">
                                <Label for="start_date">{{ $t('admin.start_date') }}</Label>
                                <Input id="start_date" v-model="form.start_date" type="date" @change="saveField('start_date')" />
                            </div>
                            <div class="relative">
                                <Label for="end_date">{{ $t('admin.end_date') }}</Label>
                                <Input id="end_date" v-model="form.end_date" type="date" @change="saveField('end_date')" />
                            </div>
                        </div>

                        <div class="relative">
                            <Label for="location">{{ $t('admin.location') }}</Label>
                            <Input id="location" v-model="form.location" @input="saveField('location')" />
                            <Check v-if="savedFields.location" class="absolute right-3 top-8 h-4 w-4 text-green-500" />
                        </div>

                        <div class="relative">
                            <Label for="contact_email">{{ $t('admin.contact_email') }}</Label>
                            <Input id="contact_email" v-model="form.contact_email" type="email" @input="saveField('contact_email')" />
                            <Check v-if="savedFields.contact_email" class="absolute right-3 top-8 h-4 w-4 text-green-500" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Descriptions -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ $t('admin.descriptions') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="relative">
                            <Label for="short_description">{{ $t('admin.short_description') }}</Label>
                            <Textarea id="short_description" v-model="form.short_description" rows="2" @input="saveField('short_description')" />
                            <Check v-if="savedFields.short_description" class="absolute right-3 top-8 h-4 w-4 text-green-500" />
                        </div>

                        <div class="relative">
                            <Label for="long_description">{{ $t('admin.long_description') }}</Label>
                            <Textarea id="long_description" v-model="form.long_description" rows="6" @input="saveField('long_description')" />
                            <Check v-if="savedFields.long_description" class="absolute right-3 top-8 h-4 w-4 text-green-500" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Roles -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle>{{ $t('admin.roles') }}</CardTitle>
                        <Dialog v-model:open="showAddRoleDialog">
                            <DialogTrigger as-child>
                                <Button size="sm">
                                    <Plus class="mr-1 h-4 w-4" />
                                    {{ $t('admin.add_role') }}
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>{{ $t('admin.add_role') }}</DialogTitle>
                                </DialogHeader>
                                <form @submit.prevent="addRole" class="space-y-4">
                                    <div>
                                        <Label for="role_designation">{{ $t('admin.role_name') }}</Label>
                                        <Input id="role_designation" v-model="roleForm.designation" required />
                                    </div>
                                    <div>
                                        <Label for="role_number">{{ $t('admin.number_required') }}</Label>
                                        <Input id="role_number" v-model.number="roleForm.number_required" type="number" min="0" />
                                    </div>
                                    <Button type="submit" class="w-full" :disabled="roleForm.processing">
                                        {{ $t('admin.add') }}
                                    </Button>
                                </form>
                            </DialogContent>
                        </Dialog>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div
                                v-for="role in event.roles"
                                :key="role.id"
                                class="flex items-center justify-between rounded-lg border p-3"
                            >
                                <template v-if="editingRoleId === role.id">
                                    <div class="flex flex-1 gap-2">
                                        <Input v-model="roleForm.designation" class="flex-1" />
                                        <Input v-model.number="roleForm.number_required" type="number" min="0" class="w-20" />
                                        <Button size="sm" @click="saveRole(role.id)">{{ $t('common.save') }}</Button>
                                        <Button size="sm" variant="ghost" @click="editingRoleId = null">{{ $t('common.cancel') }}</Button>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">{{ role.designation }}</span>
                                        <span class="text-sm text-gray-500">({{ role.number_required }} {{ $t('admin.needed') }})</span>
                                    </div>
                                    <div class="flex gap-1">
                                        <Button size="sm" variant="ghost" @click="startEditRole(role)">{{ $t('common.edit') }}</Button>
                                        <Button size="sm" variant="ghost" class="text-red-600" @click="deleteRole(role.id)">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </template>
                            </div>
                            <p v-if="!event.roles?.length" class="text-center text-sm text-gray-500">
                                {{ $t('admin.no_roles') }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Links -->
                <Card>
                    <CardHeader>
                        <CardTitle>{{ $t('admin.links') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="relative">
                            <Label for="whatsapp_link">{{ $t('admin.whatsapp_link') }}</Label>
                            <Input id="whatsapp_link" v-model="form.whatsapp_link" type="url" @input="saveField('whatsapp_link')" />
                            <Check v-if="savedFields.whatsapp_link" class="absolute right-3 top-8 h-4 w-4 text-green-500" />
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between">
                                <Label>{{ $t('admin.documents') }}</Label>
                                <Button size="sm" variant="ghost" @click="addDocumentLink">
                                    <Plus class="mr-1 h-4 w-4" />
                                    {{ $t('admin.add') }}
                                </Button>
                            </div>
                            <div class="space-y-2">
                                <div
                                    v-for="(doc, index) in form.general_documents_links"
                                    :key="index"
                                    class="flex gap-2"
                                >
                                    <Input
                                        v-model="doc.title"
                                        :placeholder="$t('admin.document_title')"
                                        class="flex-1"
                                        @input="saveField('general_documents_links')"
                                    />
                                    <Input
                                        v-model="doc.url"
                                        :placeholder="$t('admin.document_url')"
                                        class="flex-1"
                                        type="url"
                                        @input="saveField('general_documents_links')"
                                    />
                                    <Button size="icon" variant="ghost" class="text-red-600" @click="removeDocumentLink(index)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Event Days Schedule -->
            <Card v-if="event.days?.length">
                <CardHeader>
                    <CardTitle>{{ $t('admin.daily_schedule') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="space-y-4">
                        <div v-for="day in event.days" :key="day.id" class="rounded-lg border p-4">
                            <Label class="mb-2 block font-medium">{{ formatDate(day.date) }}</Label>
                            <Textarea
                                :model-value="day.schedule || ''"
                                rows="2"
                                :placeholder="$t('admin.schedule_placeholder')"
                                @change="(e: Event) => updateDaySchedule(day, (e.target as HTMLTextAreaElement).value)"
                            />
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
