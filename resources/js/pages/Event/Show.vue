<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import type { Event, Staff, StaffEventRegistration, EventRole, EventDay } from '@/types/models';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Check, X, ExternalLink } from 'lucide-vue-next';

interface Props {
    event: Event;
    registration: StaffEventRegistration | null;
    staff: Staff;
}

const props = defineProps<Props>();

const savedFields = ref<Record<string, boolean>>({});
let debounceTimer: ReturnType<typeof setTimeout>;

// Registration form
const form = useForm({
    comment: props.registration?.comment || '',
    help_before_event: props.registration?.help_before_event || false,
    team_affiliation: props.registration?.team_affiliation || '',
    is_first_participation: props.registration?.is_first_participation || false,
});

// Role preferences (ordered array of role IDs)
const selectedRoles = ref<number[]>(
    props.registration?.role_preferences?.map(p => p.role_id) || []
);

// Availability
const availability = ref<Record<number, { morning: boolean; afternoon: boolean }>>(
    props.registration?.availability?.reduce((acc, a) => {
        acc[a.event_day_id] = {
            morning: a.is_available_morning,
            afternoon: a.is_available_afternoon,
        };
        return acc;
    }, {} as Record<number, { morning: boolean; afternoon: boolean }>) ||
    props.event.days?.reduce((acc, day) => {
        acc[day.id] = { morning: true, afternoon: true };
        return acc;
    }, {} as Record<number, { morning: boolean; afternoon: boolean }>) || {}
);

const register = () => {
    router.post(`/event/${props.event.tagname}/register`, {}, {
        preserveScroll: true,
    });
};

const cancelRegistration = () => {
    if (confirm('Are you sure you want to cancel your registration?')) {
        router.delete(`/event/${props.event.tagname}/register`, {
            preserveScroll: true,
        });
    }
};

const saveField = (field: string) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        router.patch(`/event/${props.event.tagname}`, {
            comment: form.comment,
            help_before_event: form.help_before_event,
            team_affiliation: form.team_affiliation,
            is_first_participation: form.is_first_participation,
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

const toggleRole = (roleId: number) => {
    const index = selectedRoles.value.indexOf(roleId);
    if (index > -1) {
        selectedRoles.value.splice(index, 1);
    } else if (selectedRoles.value.length < 3) {
        selectedRoles.value.push(roleId);
    }
    saveRolePreferences();
};

const saveRolePreferences = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        if (selectedRoles.value.length === 0) return;
        
        router.patch(`/event/${props.event.tagname}/roles`, {
            role_ids: selectedRoles.value,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                savedFields.value['roles'] = true;
                setTimeout(() => {
                    savedFields.value['roles'] = false;
                }, 2000);
            },
        });
    }, 500);
};

const toggleAvailability = (dayId: number, period: 'morning' | 'afternoon') => {
    if (!availability.value[dayId]) {
        availability.value[dayId] = { morning: true, afternoon: true };
    }
    availability.value[dayId][period] = !availability.value[dayId][period];
    saveAvailability();
};

const saveAvailability = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        const availabilityData = Object.entries(availability.value).map(([dayId, periods]) => ({
            event_day_id: parseInt(dayId),
            is_available_morning: periods.morning,
            is_available_afternoon: periods.afternoon,
        }));

        router.patch(`/event/${props.event.tagname}/availability`, {
            availability: availabilityData,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                savedFields.value['availability'] = true;
                setTimeout(() => {
                    savedFields.value['availability'] = false;
                }, 2000);
            },
        });
    }, 500);
};

const formatDate = (dateStr: string) => {
    return new Date(dateStr).toLocaleDateString(undefined, {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
};

const isRegistrationComplete = computed(() => {
    if (!props.registration) return false;
    return selectedRoles.value.length > 0 && props.staff.first_name && props.staff.last_name && props.staff.phone_number;
});

const getRolePreferenceOrder = (roleId: number) => {
    const index = selectedRoles.value.indexOf(roleId);
    return index > -1 ? index + 1 : null;
};
</script>

<template>
    <PublicLayout :title="event.name" :staff="staff">
        <div class="mx-auto max-w-4xl">
            <!-- Event Header -->
            <Card class="mb-6">
                <CardHeader>
                    <div class="flex items-start gap-4">
                        <img
                            v-if="event.logo_path"
                            :src="`/storage/${event.logo_path}`"
                            :alt="event.name"
                            class="h-20 w-20 rounded-lg object-cover"
                        />
                        <div class="flex-1">
                            <CardTitle class="text-2xl">{{ event.name }}</CardTitle>
                            <CardDescription>
                                {{ formatDate(event.start_date) }}
                                <span v-if="event.start_date !== event.end_date">
                                    - {{ formatDate(event.end_date) }}
                                </span>
                                <span v-if="event.location" class="ml-2">• {{ event.location }}</span>
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>
                <CardContent v-if="event.long_description">
                    <p class="whitespace-pre-wrap text-gray-600 dark:text-gray-300">
                        {{ event.long_description }}
                    </p>
                </CardContent>
            </Card>

            <!-- Not Registered -->
            <template v-if="!registration">
                <Card>
                    <CardContent class="py-8 text-center">
                        <h3 class="mb-4 text-lg font-medium">{{ $t('event.join_event') }}</h3>
                        <Button @click="register" size="lg">
                            {{ $t('event.register') }}
                        </Button>
                    </CardContent>
                </Card>
            </template>

            <!-- Registered -->
            <template v-else>
                <!-- Status Banner -->
                <div class="mb-6 flex flex-wrap gap-2">
                    <Badge :variant="isRegistrationComplete ? 'default' : 'secondary'">
                        {{ isRegistrationComplete ? $t('event.registration_complete') : $t('event.registration_incomplete') }}
                    </Badge>
                    <Badge v-if="registration.is_validated" variant="default" class="bg-green-500">
                        {{ $t('event.validated') }}
                    </Badge>
                    <Badge v-if="registration.assigned_role" variant="default" class="bg-blue-500">
                        {{ $t('event.assigned_role') }}: {{ registration.assigned_role.designation }}
                    </Badge>
                </div>

                <!-- Links Section -->
                <Card v-if="event.whatsapp_link || event.general_documents_links?.length" class="mb-6">
                    <CardHeader>
                        <CardTitle>{{ $t('event.resources') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-wrap gap-3">
                        <a
                            v-if="event.whatsapp_link"
                            :href="event.whatsapp_link"
                            target="_blank"
                            class="inline-flex items-center gap-1 rounded-md bg-green-500 px-3 py-1.5 text-sm text-white hover:bg-green-600"
                        >
                            WhatsApp <ExternalLink class="h-3 w-3" />
                        </a>
                        <a
                            v-for="doc in event.general_documents_links"
                            :key="doc.url"
                            :href="doc.url"
                            target="_blank"
                            class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-3 py-1.5 text-sm hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600"
                        >
                            {{ doc.title }} <ExternalLink class="h-3 w-3" />
                        </a>
                    </CardContent>
                </Card>

                <!-- Role Preferences -->
                <Card class="mb-6">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            {{ $t('event.role_preferences') }}
                            <Check v-if="savedFields.roles" class="h-4 w-4 text-green-500" />
                        </CardTitle>
                        <CardDescription>{{ $t('event.role_preferences_description') }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <button
                                v-for="role in event.roles"
                                :key="role.id"
                                @click="toggleRole(role.id)"
                                class="flex items-center justify-between rounded-lg border p-3 text-left transition-colors"
                                :class="[
                                    selectedRoles.includes(role.id)
                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                        : 'hover:bg-gray-50 dark:hover:bg-gray-800',
                                    selectedRoles.length >= 3 && !selectedRoles.includes(role.id)
                                        ? 'opacity-50 cursor-not-allowed'
                                        : ''
                                ]"
                            >
                                <span>{{ role.designation }}</span>
                                <Badge v-if="getRolePreferenceOrder(role.id)" variant="default">
                                    #{{ getRolePreferenceOrder(role.id) }}
                                </Badge>
                            </button>
                        </div>
                    </CardContent>
                </Card>

                <!-- Availability -->
                <Card class="mb-6">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            {{ $t('event.availability') }}
                            <Check v-if="savedFields.availability" class="h-4 w-4 text-green-500" />
                        </CardTitle>
                        <CardDescription>{{ $t('event.availability_description') }}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-3">
                            <div
                                v-for="day in event.days"
                                :key="day.id"
                                class="flex items-center gap-4 rounded-lg border p-3"
                            >
                                <span class="w-24 font-medium">{{ formatDate(day.date) }}</span>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2">
                                        <Checkbox
                                            :checked="availability[day.id]?.morning ?? true"
                                            @update:checked="toggleAvailability(day.id, 'morning')"
                                        />
                                        <span class="text-sm">{{ $t('event.morning') }}</span>
                                    </label>
                                    <label class="flex items-center gap-2">
                                        <Checkbox
                                            :checked="availability[day.id]?.afternoon ?? true"
                                            @update:checked="toggleAvailability(day.id, 'afternoon')"
                                        />
                                        <span class="text-sm">{{ $t('event.afternoon') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Additional Info -->
                <Card class="mb-6">
                    <CardHeader>
                        <CardTitle>{{ $t('event.additional_info') }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="is_first_participation"
                                :checked="form.is_first_participation"
                                @update:checked="(val: boolean) => { form.is_first_participation = val; saveField('is_first_participation'); }"
                            />
                            <Label for="is_first_participation">{{ $t('event.first_participation') }}</Label>
                        </div>

                        <div class="flex items-center gap-2">
                            <Checkbox
                                id="help_before_event"
                                :checked="form.help_before_event"
                                @update:checked="(val: boolean) => { form.help_before_event = val; saveField('help_before_event'); }"
                            />
                            <Label for="help_before_event">{{ $t('event.help_before') }}</Label>
                        </div>

                        <div class="relative">
                            <Label for="team_affiliation">{{ $t('event.team_affiliation') }}</Label>
                            <Input
                                id="team_affiliation"
                                v-model="form.team_affiliation"
                                @input="saveField('team_affiliation')"
                                :placeholder="$t('event.team_affiliation_placeholder')"
                            />
                            <Check v-if="savedFields.team_affiliation" class="absolute right-3 top-8 h-4 w-4 text-green-500" />
                        </div>

                        <div class="relative">
                            <Label for="comment">{{ $t('event.comment') }}</Label>
                            <Textarea
                                id="comment"
                                v-model="form.comment"
                                @input="saveField('comment')"
                                rows="3"
                            />
                            <Check v-if="savedFields.comment" class="absolute right-3 top-8 h-4 w-4 text-green-500" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Cancel Registration -->
                <div class="text-center">
                    <Button variant="outline" class="text-red-600" @click="cancelRegistration">
                        {{ $t('event.cancel_registration') }}
                    </Button>
                </div>
            </template>
        </div>
    </PublicLayout>
</template>
