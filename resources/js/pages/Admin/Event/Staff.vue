<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import type { Event, StaffEventRegistration, EventRole, EventDay } from '@/types/models';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Check, X, Mail, Phone, MapPin, CheckCircle, XCircle } from 'lucide-vue-next';

interface Props {
    event: Event;
    registrations: StaffEventRegistration[];
}

const props = defineProps<Props>();

const currentTab = ref('list');

const validateRegistration = (registrationId: string) => {
    router.post(`/admin/event/${props.event.tagname}/staff/${registrationId}/validate`, {}, {
        preserveScroll: true,
    });
};

const assignRole = (registrationId: string, roleId: string) => {
    router.post(`/admin/event/${props.event.tagname}/staff/${registrationId}/role`, {
        role_id: parseInt(roleId),
    }, {
        preserveScroll: true,
    });
};

const sendReminders = () => {
    if (confirm('Send event reminder to all validated staff?')) {
        router.post(`/admin/event/${props.event.tagname}/reminder`, {}, {
            preserveScroll: true,
        });
    }
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString(undefined, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
};

const getPreferredRoles = (registration: StaffEventRegistration) => {
    return registration.role_preferences?.map(p => p.role?.designation).filter(Boolean) || [];
};

const getRoleOptions = (registration: StaffEventRegistration) => {
    const preferredIds = registration.role_preferences?.map(p => p.role_id) || [];
    const preferred = props.event.roles?.filter(r => preferredIds.includes(r.id)) || [];
    const others = props.event.roles?.filter(r => !preferredIds.includes(r.id)) || [];
    return { preferred, others };
};

const getAvailabilityForDay = (registration: StaffEventRegistration, dayId: number) => {
    const availability = registration.availability?.find(a => a.event_day_id === dayId);
    if (!availability) return { morning: false, afternoon: false };
    return {
        morning: availability.is_available_morning,
        afternoon: availability.is_available_afternoon,
    };
};

const sortedRegistrations = computed(() => {
    return [...props.registrations].sort((a, b) => {
        // Validated first
        if (a.is_validated !== b.is_validated) return a.is_validated ? -1 : 1;
        // Then by name
        const nameA = a.staff?.last_name || a.staff?.username || '';
        const nameB = b.staff?.last_name || b.staff?.username || '';
        return nameA.localeCompare(nameB);
    });
});

const breadcrumbs = [
    { title: 'Admin', href: '/admin' },
    { title: props.event.name, href: `/admin/event/${props.event.tagname}` },
    { title: 'Staff', href: `/admin/event/${props.event.tagname}/staff` },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">{{ event.name }} - {{ $t('admin.staff_management') }}</h1>
                    <p class="text-gray-500">{{ registrations.length }} {{ $t('admin.registrations') }}</p>
                </div>
                <Button @click="sendReminders">
                    <Mail class="mr-2 h-4 w-4" />
                    {{ $t('admin.send_reminders') }}
                </Button>
            </div>

            <!-- Tabs -->
            <Tabs v-model="currentTab">
                <TabsList>
                    <TabsTrigger value="list">{{ $t('admin.staff_list') }}</TabsTrigger>
                    <TabsTrigger value="availability">{{ $t('admin.availability_grid') }}</TabsTrigger>
                    <TabsTrigger value="contact">{{ $t('admin.contact_info') }}</TabsTrigger>
                </TabsList>

                <!-- Staff List -->
                <TabsContent value="list">
                    <div class="space-y-4">
                        <Card v-for="reg in sortedRegistrations" :key="reg.id">
                            <CardContent class="flex items-start gap-4 p-4">
                                <!-- Avatar -->
                                <div class="h-12 w-12 flex-shrink-0 overflow-hidden rounded-full bg-gray-200">
                                    <img
                                        v-if="reg.staff?.photo_path"
                                        :src="`/storage/${reg.staff.photo_path}`"
                                        :alt="reg.staff.first_name || reg.staff.username"
                                        class="h-full w-full object-cover"
                                    />
                                    <div v-else class="flex h-full w-full items-center justify-center text-lg text-gray-400">
                                        {{ reg.staff?.first_name?.[0] || reg.staff?.username[0] }}
                                    </div>
                                </div>

                                <!-- Info -->
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <h3 class="font-medium">
                                            {{ reg.staff?.first_name }} {{ reg.staff?.last_name }}
                                            <span v-if="!reg.staff?.first_name" class="text-gray-500">({{ reg.staff?.username }})</span>
                                        </h3>
                                        <Badge v-if="reg.is_validated" variant="default" class="bg-green-500">
                                            {{ $t('admin.validated') }}
                                        </Badge>
                                        <Badge v-if="reg.is_first_participation" variant="secondary">
                                            {{ $t('admin.first_time') }}
                                        </Badge>
                                    </div>

                                    <div class="mt-1 flex flex-wrap gap-2 text-sm text-gray-500">
                                        <span v-if="reg.staff?.email" class="flex items-center gap-1">
                                            <Mail class="h-3 w-3" /> {{ reg.staff.email }}
                                        </span>
                                        <span v-if="reg.staff?.phone_number" class="flex items-center gap-1">
                                            <Phone class="h-3 w-3" /> {{ reg.staff.phone_number }}
                                        </span>
                                        <span v-if="reg.staff?.city" class="flex items-center gap-1">
                                            <MapPin class="h-3 w-3" /> {{ reg.staff.city }}
                                        </span>
                                    </div>

                                    <div class="mt-2 flex flex-wrap gap-1">
                                        <Badge v-for="(role, idx) in getPreferredRoles(reg)" :key="idx" variant="outline">
                                            #{{ idx + 1 }} {{ role }}
                                        </Badge>
                                    </div>

                                    <p v-if="reg.comment" class="mt-2 text-sm italic text-gray-600">
                                        "{{ reg.comment }}"
                                    </p>

                                    <p v-if="reg.team_affiliation" class="mt-1 text-sm text-gray-500">
                                        {{ $t('admin.teams') }}: {{ reg.team_affiliation }}
                                    </p>
                                </div>

                                <!-- Actions -->
                                <div class="flex flex-col gap-2">
                                    <Button
                                        v-if="!reg.is_validated"
                                        size="sm"
                                        @click="validateRegistration(reg.id)"
                                    >
                                        <CheckCircle class="mr-1 h-4 w-4" />
                                        {{ $t('admin.validate') }}
                                    </Button>

                                    <Select
                                        :model-value="reg.assigned_role_id?.toString() || ''"
                                        @update:model-value="(val) => assignRole(reg.id, val)"
                                    >
                                        <SelectTrigger class="w-48">
                                            <SelectValue :placeholder="$t('admin.assign_role')">
                                                {{ reg.assigned_role?.designation || $t('admin.assign_role') }}
                                            </SelectValue>
                                        </SelectTrigger>
                                        <SelectContent>
                                            <template v-if="getRoleOptions(reg).preferred.length">
                                                <div class="px-2 py-1 text-xs text-gray-500">{{ $t('admin.preferred') }}</div>
                                                <SelectItem
                                                    v-for="role in getRoleOptions(reg).preferred"
                                                    :key="role.id"
                                                    :value="role.id.toString()"
                                                >
                                                    {{ role.designation }}
                                                </SelectItem>
                                            </template>
                                            <template v-if="getRoleOptions(reg).others.length">
                                                <div class="px-2 py-1 text-xs text-gray-500">{{ $t('admin.other_roles') }}</div>
                                                <SelectItem
                                                    v-for="role in getRoleOptions(reg).others"
                                                    :key="role.id"
                                                    :value="role.id.toString()"
                                                >
                                                    {{ role.designation }}
                                                </SelectItem>
                                            </template>
                                        </SelectContent>
                                    </Select>
                                </div>
                            </CardContent>
                        </Card>

                        <p v-if="registrations.length === 0" class="text-center text-gray-500">
                            {{ $t('admin.no_registrations') }}
                        </p>
                    </div>
                </TabsContent>

                <!-- Availability Grid -->
                <TabsContent value="availability">
                    <Card>
                        <CardContent class="overflow-x-auto p-4">
                            <table class="w-full min-w-max">
                                <thead>
                                    <tr>
                                        <th class="border-b p-2 text-left">{{ $t('admin.staff') }}</th>
                                        <template v-for="day in event.days" :key="day.id">
                                            <th class="border-b p-2 text-center" colspan="2">
                                                {{ formatDate(day.date) }}
                                            </th>
                                        </template>
                                    </tr>
                                    <tr>
                                        <th></th>
                                        <template v-for="day in event.days" :key="day.id">
                                            <th class="border-b p-1 text-center text-xs text-gray-500">AM</th>
                                            <th class="border-b p-1 text-center text-xs text-gray-500">PM</th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="reg in sortedRegistrations" :key="reg.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="border-b p-2">
                                            {{ reg.staff?.first_name || reg.staff?.username }} {{ reg.staff?.last_name || '' }}
                                        </td>
                                        <template v-for="day in event.days" :key="day.id">
                                            <td class="border-b p-2 text-center">
                                                <Check
                                                    v-if="getAvailabilityForDay(reg, day.id).morning"
                                                    class="mx-auto h-4 w-4 text-green-500"
                                                />
                                                <X v-else class="mx-auto h-4 w-4 text-red-500" />
                                            </td>
                                            <td class="border-b p-2 text-center">
                                                <Check
                                                    v-if="getAvailabilityForDay(reg, day.id).afternoon"
                                                    class="mx-auto h-4 w-4 text-green-500"
                                                />
                                                <X v-else class="mx-auto h-4 w-4 text-red-500" />
                                            </td>
                                        </template>
                                    </tr>
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                </TabsContent>

                <!-- Contact Info -->
                <TabsContent value="contact">
                    <Card>
                        <CardContent class="overflow-x-auto p-4">
                            <table class="w-full">
                                <thead>
                                    <tr>
                                        <th class="border-b p-2 text-left">{{ $t('admin.name') }}</th>
                                        <th class="border-b p-2 text-left">{{ $t('admin.email') }}</th>
                                        <th class="border-b p-2 text-left">{{ $t('admin.phone') }}</th>
                                        <th class="border-b p-2 text-left">{{ $t('admin.city') }}</th>
                                        <th class="border-b p-2 text-left">{{ $t('admin.role') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="reg in sortedRegistrations" :key="reg.id" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                        <td class="border-b p-2">
                                            {{ reg.staff?.first_name }} {{ reg.staff?.last_name }}
                                            <span v-if="!reg.staff?.first_name" class="text-gray-500">({{ reg.staff?.username }})</span>
                                        </td>
                                        <td class="border-b p-2">
                                            <a :href="`mailto:${reg.staff?.email}`" class="text-blue-500 hover:underline">
                                                {{ reg.staff?.email }}
                                            </a>
                                        </td>
                                        <td class="border-b p-2">
                                            <a :href="`tel:${reg.staff?.phone_number}`" class="text-blue-500 hover:underline">
                                                {{ reg.staff?.phone_number }}
                                            </a>
                                        </td>
                                        <td class="border-b p-2">{{ reg.staff?.city || '-' }}</td>
                                        <td class="border-b p-2">
                                            <Badge v-if="reg.assigned_role" variant="default">
                                                {{ reg.assigned_role.designation }}
                                            </Badge>
                                            <span v-else class="text-gray-400">-</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                </TabsContent>
            </Tabs>
        </div>
    </AppLayout>
</template>
