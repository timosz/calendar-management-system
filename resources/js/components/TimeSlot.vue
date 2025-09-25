<script setup lang="ts">
    import { Button } from '@/components/ui/button';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Trash2 } from 'lucide-vue-next';
    import { computed } from 'vue';

    interface Period {
        id: string;
        start_time: string;
        end_time: string;
    }

    interface Props {
        period: Period;
        index: number;
        dayKey: string;
        canRemove?: boolean;
        errors?: {
            start_time?: string;
            end_time?: string;
        };
    }

    const props = withDefaults(defineProps<Props>(), {
        canRemove: true,
    });

    const emit = defineEmits<{
        'update:period': [period: Period];
        remove: [];
    }>();

    // Computed properties for two-way binding
    const startTime = computed({
        get: () => props.period.start_time,
        set: (value: string) => {
            emit('update:period', {
                ...props.period,
                start_time: value,
            });
        },
    });

    const endTime = computed({
        get: () => props.period.end_time,
        set: (value: string) => {
            emit('update:period', {
                ...props.period,
                end_time: value,
            });
        },
    });
</script>

<template>
    <div
        class="group relative flex items-end gap-3 rounded-lg border border-border bg-card/50 p-3 transition-colors hover:bg-card/80 dark:bg-card/30 dark:hover:bg-card/50"
    >
        <!-- Start Time -->
        <div class="flex-1 space-y-1.5">
            <Label :for="`${dayKey}_start_${index}`" class="text-xs font-medium text-muted-foreground"> Start Time </Label>
            <Input
                :id="`${dayKey}_start_${index}`"
                v-model="startTime"
                type="time"
                :class="{
                    'border-destructive focus-visible:border-destructive': errors?.start_time,
                    'focus-visible:ring-destructive/20': errors?.start_time,
                }"
            />
            <p v-if="errors?.start_time" class="text-xs text-destructive">
                {{ errors.start_time }}
            </p>
        </div>

        <!-- End Time -->
        <div class="flex-1 space-y-1.5">
            <Label :for="`${dayKey}_end_${index}`" class="text-xs font-medium text-muted-foreground"> End Time </Label>
            <Input
                :id="`${dayKey}_end_${index}`"
                v-model="endTime"
                type="time"
                :class="{
                    'border-destructive focus-visible:border-destructive': errors?.end_time,
                    'focus-visible:ring-destructive/20': errors?.end_time,
                }"
            />
            <p v-if="errors?.end_time" class="text-xs text-destructive">
                {{ errors.end_time }}
            </p>
        </div>

        <!-- Remove Button -->
        <Button
            type="button"
            variant="outline"
            size="sm"
            @click="$emit('remove')"
            :disabled="!canRemove"
            class="shrink-0 border-destructive/20 text-destructive transition-opacity hover:bg-destructive hover:text-destructive-foreground disabled:cursor-not-allowed disabled:opacity-30"
        >
            <Trash2 class="h-3 w-3" />
            <span class="sr-only">Remove time slot</span>
        </Button>
    </div>
</template>
