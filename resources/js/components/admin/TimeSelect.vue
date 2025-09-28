<script setup lang="ts">
    import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
    import type { TimeSlot } from '@/types';

    interface Props {
        disabled?: boolean;
        modelValue: string | null;
        timeSlots: TimeSlot[];
        placeholder?: string;
        hasError?: boolean;
        dataTest?: string;
    }

    defineProps<Props>();

    defineEmits<{
        'update:modelValue': [value: string];
    }>();
</script>

<template>
    <Select 
        :disabled="disabled" 
        :model-value="modelValue" 
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <SelectTrigger :data-test="dataTest" :class="{ 'border-destructive': hasError }">
            <SelectValue :placeholder="placeholder" />
        </SelectTrigger>
        <SelectContent class="max-h-60 overflow-y-auto">
            <SelectItem v-for="slot in timeSlots" :key="slot.value" :value="slot.value" :disabled="slot.disabled">
                {{ slot.label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
