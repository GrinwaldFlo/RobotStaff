<script setup lang="ts">
import PublicLayout from '@/layouts/PublicLayout.vue';
import { useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import type { Staff } from '@/types/models';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Check } from 'lucide-vue-next';

interface Props {
    staff: Staff;
}

const props = defineProps<Props>();

// Track which fields have been saved
const savedFields = ref<Record<string, boolean>>({});

const form = useForm({
    first_name: props.staff.first_name || '',
    last_name: props.staff.last_name || '',
    phone_number: props.staff.phone_number || '',
    city: props.staff.city || '',
    languages: props.staff.languages || [],
    comment: props.staff.comment || '',
});

// Languages as comma-separated string for input
const languagesString = ref((props.staff.languages || []).join(', '));

// Debounce timer
let debounceTimer: ReturnType<typeof setTimeout>;

const saveField = (field: string) => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        // Parse languages from string
        if (field === 'languages') {
            form.languages = languagesString.value
                .split(',')
                .map(l => l.trim())
                .filter(l => l.length > 0);
        }

        form.patch('/staff', {
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

const uploadPhoto = (event: globalThis.Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('photo', file);

    router.post('/staff/photo', formData, {
        preserveScroll: true,
    });
};

const deletePhoto = () => {
    if (confirm('Are you sure you want to delete your photo?')) {
        router.delete('/staff/photo', {
            preserveScroll: true,
        });
    }
};

const deleteData = () => {
    if (confirm('Are you sure you want to delete all your data? This action cannot be undone.')) {
        router.delete('/staff/data');
    }
};

const isProfileComplete = computed(() => {
    return form.first_name && form.last_name && form.phone_number;
});
</script>

<template>
    <PublicLayout :title="$t('staff.profile_title')" :staff="staff">
        <div class="mx-auto max-w-2xl">
            <Card>
                <CardHeader>
                    <CardTitle>{{ $t('staff.profile_title') }}</CardTitle>
                    <CardDescription>{{ $t('staff.profile_description') }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <!-- Photo -->
                    <div>
                        <Label>{{ $t('staff.photo') }}</Label>
                        <div class="mt-2 flex items-center gap-4">
                            <div class="h-24 w-24 overflow-hidden rounded-full bg-gray-200">
                                <img
                                    v-if="staff.photo_path"
                                    :src="`/storage/${staff.photo_path}`"
                                    alt="Profile photo"
                                    class="h-full w-full object-cover"
                                />
                                <div v-else class="flex h-full w-full items-center justify-center text-2xl text-gray-400">
                                    {{ staff.first_name?.[0] || staff.username[0] }}
                                </div>
                            </div>
                            <div class="flex flex-col gap-2">
                                <label class="cursor-pointer">
                                    <span class="rounded-md bg-gray-100 px-3 py-1.5 text-sm hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600">
                                        {{ $t('staff.upload_photo') }}
                                    </span>
                                    <input type="file" accept="image/jpeg,image/png" class="hidden" @change="uploadPhoto" />
                                </label>
                                <Button v-if="staff.photo_path" variant="ghost" size="sm" @click="deletePhoto">
                                    {{ $t('staff.delete_photo') }}
                                </Button>
                            </div>
                        </div>
                    </div>

                    <!-- First Name -->
                    <div class="relative">
                        <Label for="first_name">{{ $t('staff.first_name') }} *</Label>
                        <div class="relative">
                            <Input
                                id="first_name"
                                v-model="form.first_name"
                                type="text"
                                @input="saveField('first_name')"
                            />
                            <Check
                                v-if="savedFields.first_name"
                                class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-green-500"
                            />
                        </div>
                    </div>

                    <!-- Last Name -->
                    <div class="relative">
                        <Label for="last_name">{{ $t('staff.last_name') }} *</Label>
                        <div class="relative">
                            <Input
                                id="last_name"
                                v-model="form.last_name"
                                type="text"
                                @input="saveField('last_name')"
                            />
                            <Check
                                v-if="savedFields.last_name"
                                class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-green-500"
                            />
                        </div>
                    </div>

                    <!-- Phone Number -->
                    <div class="relative">
                        <Label for="phone_number">{{ $t('staff.phone_number') }} *</Label>
                        <div class="relative">
                            <Input
                                id="phone_number"
                                v-model="form.phone_number"
                                type="tel"
                                @input="saveField('phone_number')"
                            />
                            <Check
                                v-if="savedFields.phone_number"
                                class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-green-500"
                            />
                        </div>
                    </div>

                    <!-- City -->
                    <div class="relative">
                        <Label for="city">{{ $t('staff.city') }}</Label>
                        <div class="relative">
                            <Input
                                id="city"
                                v-model="form.city"
                                type="text"
                                @input="saveField('city')"
                            />
                            <Check
                                v-if="savedFields.city"
                                class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-green-500"
                            />
                        </div>
                    </div>

                    <!-- Languages -->
                    <div class="relative">
                        <Label for="languages">{{ $t('staff.languages') }}</Label>
                        <div class="relative">
                            <Input
                                id="languages"
                                v-model="languagesString"
                                type="text"
                                :placeholder="$t('staff.languages_placeholder')"
                                @input="saveField('languages')"
                            />
                            <Check
                                v-if="savedFields.languages"
                                class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-green-500"
                            />
                        </div>
                    </div>

                    <!-- Comment -->
                    <div class="relative">
                        <Label for="comment">{{ $t('staff.comment') }}</Label>
                        <div class="relative">
                            <Textarea
                                id="comment"
                                v-model="form.comment"
                                rows="4"
                                @input="saveField('comment')"
                            />
                            <Check
                                v-if="savedFields.comment"
                                class="absolute right-3 top-3 h-4 w-4 text-green-500"
                            />
                        </div>
                    </div>

                    <!-- Profile Status -->
                    <div class="rounded-lg p-4" :class="isProfileComplete ? 'bg-green-50 dark:bg-green-900/20' : 'bg-yellow-50 dark:bg-yellow-900/20'">
                        <p class="text-sm" :class="isProfileComplete ? 'text-green-700 dark:text-green-300' : 'text-yellow-700 dark:text-yellow-300'">
                            {{ isProfileComplete ? $t('staff.profile_complete') : $t('staff.profile_incomplete') }}
                        </p>
                    </div>

                    <!-- Delete Data -->
                    <div class="border-t pt-6">
                        <h3 class="mb-2 text-lg font-medium text-red-600">{{ $t('staff.danger_zone') }}</h3>
                        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                            {{ $t('staff.delete_data_warning') }}
                        </p>
                        <Button variant="destructive" @click="deleteData">
                            {{ $t('staff.delete_all_data') }}
                        </Button>
                    </div>
                </CardContent>
            </Card>
        </div>
    </PublicLayout>
</template>
