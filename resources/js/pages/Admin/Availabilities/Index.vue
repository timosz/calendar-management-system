<script setup lang="ts">
    import { update } from '@/actions/App/Http/Controllers/Admin/AvailabilityController';
    import AvailabilityDayRow from '@/components/admin/AvailabilityDayRow.vue';
    import AvailabilityStats from '@/components/admin/AvailabilityStats.vue';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
    import { useAvailabilityForm } from '@/composables/useAvailabilityForm';
    import { useToast } from '@/composables/useToast';
    import AppLayout from '@/layouts/AppLayout.vue';
    import type { TimeSlot, WeeklyScheduleItem } from '@/types';
    import { usePage } from '@inertiajs/vue3';
    import { Save } from 'lucide-vue-next';

    interface Props {
        weeklySchedule: WeeklyScheduleItem[];
        timeSlots: TimeSlot[];
    }

    const props = defineProps<Props>();
    const { success, error } = useToast();
    const page = usePage();

    const { form, totalActiveDays, totalWorkingHours, averageDailyHours, toggleDay, updateStartTime, updateEndTime, getErrorsForDay, dayHasErrors } =
        useAvailabilityForm(props.weeklySchedule, props.timeSlots);

    const handleSubmit = () => {
        form.put(update().url, {
            preserveScroll: true,
            onSuccess: () => {
                const flashMessage = page.props.flash?.success;
                if (flashMessage) {
                    success(flashMessage);
                }
            },
            onError: (errors) => {
                // Show a general error message for field-specific errors
                const hasFieldErrors = Object.keys(errors).some((key) => key.startsWith('availabilities.'));

                if (hasFieldErrors) {
                    error('Please check the highlighted fields and correct any errors.');
                } else if (Object.keys(errors).length > 0) {
                    // Handle other types of errors
                    const firstErrorKey = Object.keys(errors)[0];
                    const firstErrorMessage = errors[firstErrorKey];
                    error(Array.isArray(firstErrorMessage) ? firstErrorMessage[0] : firstErrorMessage);
                }

                const flashError = page.props.flash?.error;
                if (flashError) {
                    error(flashError);
                }
            },
        });
    };
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Availability Management</h1>
                <p class="text-muted-foreground">Configure your weekly working hours and availability schedule.</p>
            </div>

            <!-- Stats -->
            <AvailabilityStats
                :total-active-days="totalActiveDays"
                :total-working-hours="totalWorkingHours"
                :average-daily-hours="averageDailyHours"
            />

            <!-- Form -->
            <Card>
                <CardHeader>
                    <CardTitle>Weekly Schedule</CardTitle>
                    <CardDescription>
                        Set your availability for each day of the week. Toggle days on/off and configure start and end times.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="handleSubmit" class="space-y-4">
                        <div class="space-y-4">
                            <AvailabilityDayRow
                                v-for="(day, index) in form.availabilities"
                                :key="day.day_of_week"
                                :availability="day"
                                :day-name="weeklySchedule[index].day_name"
                                :time-slots="timeSlots"
                                :errors="getErrorsForDay(index)"
                                @toggle="(checked) => toggleDay(index, checked)"
                                @update-start-time="(value) => updateStartTime(index, value)"
                                @update-end-time="(value) => updateEndTime(index, value)"
                            />
                        </div>

                        <div class="flex justify-end">
                            <Button type="submit" :disabled="form.processing" class="min-w-[120px]">
                                <Save class="mr-2 h-4 w-4" />
                                {{ form.processing ? 'Saving...' : 'Save Changes' }}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
