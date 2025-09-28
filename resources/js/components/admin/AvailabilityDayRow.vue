<script setup lang="ts">
    import TimeSelect from '@/components/admin/TimeSelect.vue';
    import { Badge } from '@/components/ui/badge';
    import { Checkbox } from '@/components/ui/checkbox';
    import { useTimeUtils } from '@/composables/useTimeUtils';
    import type { TimeSlot } from '@/types';
    import { computed } from 'vue';

    interface Props {
        availability: {
            day_of_week: number;
            is_active: boolean;
            start_time: string | null;
            end_time: string | null;
        };
        dayName: string;
        timeSlots: TimeSlot[];
        errors?: {
            start_time?: string;
            end_time?: string;
        };
    }

    const props = defineProps<Props>();

    const emits = defineEmits<{
        toggle: [checked: boolean];
        'update-start-time': [value: string];
        'update-end-time': [value: string];
    }>();

    const { formatDuration } = useTimeUtils();

    const filteredEndTimeSlots = computed(() => {
        if (!props.availability.start_time) return props.timeSlots;

        return props.timeSlots.filter((slot) => slot.value > props.availability.start_time!);
    });

    const hasErrors = computed(() => {
        return props.errors && Object.keys(props.errors).length > 0;
    });
</script>

<template>
    <div
        class="grid grid-cols-1 items-center gap-4 rounded-lg border p-4 md:grid-cols-6"
        :class="{
            'bg-muted/50': !availability.is_active,
            'border-destructive bg-destructive/5': hasErrors,
        }"
    >
        <!-- Day Name and Toggle -->
        <div class="flex items-center space-x-3 md:col-span-2">
            <Checkbox :id="`day-${availability.day_of_week}`" v-model="availability.is_active" />
            <label
                :for="`day-${availability.day_of_week}`"
                class="cursor-pointer text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
            >
                {{ dayName }}
            </label>
            <Badge :variant="availability.is_active ? 'default' : 'secondary'">
                {{ availability.is_active ? 'Active' : 'Inactive' }}
            </Badge>
        </div>

        <!-- Start Time -->
        <div>
            <label class="mb-1 block text-xs text-muted-foreground">Start</label>
            <TimeSelect
                :disabled="!availability.is_active"
                :model-value="availability.start_time"
                :time-slots="timeSlots"
                :has-error="!!errors?.start_time"
                placeholder="Start time"
                @update:model-value="(value) => emits('update-start-time', value)"
            />
            <div v-if="errors?.start_time" class="mt-1 text-xs text-destructive">
                {{ errors.start_time }}
            </div>
        </div>

        <!-- End Time -->
        <div>
            <label class="mb-1 block text-xs text-muted-foreground">End</label>
            <TimeSelect
                :disabled="!availability.is_active"
                :model-value="availability.end_time"
                :time-slots="filteredEndTimeSlots"
                :has-error="!!errors?.end_time"
                placeholder="End time"
                @update:model-value="(value) => emits('update-end-time', value)"
            />
            <div v-if="errors?.end_time" class="mt-1 text-xs text-destructive">
                {{ errors.end_time }}
            </div>
        </div>

        <!-- Duration -->
        <div class="text-center">
            <label class="mb-1 block text-xs text-muted-foreground">Duration</label>
            <div class="text-sm font-medium">
                {{ formatDuration(availability.start_time, availability.end_time) }}
            </div>
        </div>
    </div>
</template>
