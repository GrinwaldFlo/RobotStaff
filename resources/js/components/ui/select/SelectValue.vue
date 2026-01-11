<script setup lang="ts">
import { SelectValue as RekaSelectValue, type SelectValueProps } from 'reka-ui';

defineProps<SelectValueProps>();
</script>

<template>
    <RekaSelectValue v-bind="$props">
        <slot />
    </RekaSelectValue>
</template>
