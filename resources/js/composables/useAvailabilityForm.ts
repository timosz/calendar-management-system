import { useTimeUtils } from '@/composables/useTimeUtils';
import type { TimeSlot, WeeklyScheduleItem } from '@/types';
import { useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

export const useAvailabilityForm = (weeklySchedule: WeeklyScheduleItem[], timeSlots: TimeSlot[]) => {
    const { calculateHoursBetween } = useTimeUtils();

    const form = useForm({
        availabilities: weeklySchedule.map((day) => ({
            day_of_week: day.day_of_week,
            is_active: Boolean(day.is_active),
            start_time: day.start_time,
            end_time: day.end_time,
        })),
    });

    // Helper function to get errors for a specific day
    const getErrorsForDay = (dayIndex: number) => {
        const errors: { start_time?: string; end_time?: string } = {};

        // Check for start_time errors
        const startTimeError = form.errors[`availabilities.${dayIndex}.start_time`];
        if (startTimeError) {
            errors.start_time = startTimeError;
        }

        // Check for end_time errors
        const endTimeError = form.errors[`availabilities.${dayIndex}.end_time`];
        if (endTimeError) {
            errors.end_time = endTimeError;
        }

        return errors;
    };

    // Check if a specific day has any errors
    const dayHasErrors = (dayIndex: number) => {
        const errors = getErrorsForDay(dayIndex);
        return Object.keys(errors).length > 0;
    };

    // Computed properties
    const totalActiveDays = computed(() => {
        return form.availabilities.filter((day) => day.is_active).length;
    });

    const totalWorkingHours = computed(() => {
        return form.availabilities.reduce((total, day) => {
            if (!day.is_active || !day.start_time || !day.end_time) {
                return total;
            }
            return total + calculateHoursBetween(day.start_time, day.end_time);
        }, 0);
    });

    const averageDailyHours = computed(() => {
        return totalActiveDays.value > 0 ? totalWorkingHours.value / totalActiveDays.value : 0;
    });

    // Methods
    const toggleDay = (index: number, checked: boolean) => {
        form.availabilities[index].is_active = checked;

        if (!checked) {
            form.availabilities[index].start_time = null;
            form.availabilities[index].end_time = null;
        }
    };

    const updateStartTime = (index: number, value: string) => {
        form.availabilities[index].start_time = value;

        // Auto-adjust end time if it's before start time
        if (form.availabilities[index].end_time && value >= form.availabilities[index].end_time!) {
            const startIndex = timeSlots.findIndex((slot) => slot.value === value);
            if (startIndex < timeSlots.length - 1) {
                form.availabilities[index].end_time = timeSlots[startIndex + 1].value;
            }
        }
    };

    const updateEndTime = (index: number, value: string) => {
        form.availabilities[index].end_time = value;
    };

    // Watch for changes to clear times when days are deactivated
    watch(
        () => form.availabilities.map((day) => day.is_active),
        (newValues, oldValues) => {
            newValues.forEach((isActive, index) => {
                if (oldValues && oldValues[index] && !isActive) {
                    form.availabilities[index].start_time = null;
                    form.availabilities[index].end_time = null;
                }
            });
        },
        { deep: true },
    );

    return {
        form,
        totalActiveDays,
        totalWorkingHours,
        averageDailyHours,
        toggleDay,
        updateStartTime,
        updateEndTime,
        getErrorsForDay,
        dayHasErrors,
    };
};
