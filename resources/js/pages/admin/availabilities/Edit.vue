<script setup lang="ts">
    import AvailabilityForm from '@/components/AvailabilityForm.vue';
    import AppLayout from '@/layouts/AppLayout.vue';
    import { index, update } from '@/routes/availabilities';
    import type { BreadcrumbItemType } from '@/types';
    import { Head, router } from '@inertiajs/vue3';

    interface Period {
        id: number;
        start_time: string;
        end_time: string;
    }

    interface Availability {
        id: number;
        name: string;
        description: string;
        start_date: string;
        end_date: string | null;
        periods: Period[];
        recurrence_pattern: any;
    }

    interface DayAvailability {
        enabled: boolean;
        periods: Array<{
            id: string;
            start_time: string;
            end_time: string;
        }>;
    }

    interface AvailabilityFormData {
        name: string;
        description: string;
        start_date: string;
        end_date: string;
        days: {
            monday: DayAvailability;
            tuesday: DayAvailability;
            wednesday: DayAvailability;
            thursday: DayAvailability;
            friday: DayAvailability;
            saturday: DayAvailability;
            sunday: DayAvailability;
        };
    }

    const props = defineProps<{
        availability: Availability;
    }>();

    const breadcrumbs: BreadcrumbItemType[] = [
        { title: 'Availabilities', href: index.url() },
        { title: 'Edit', href: '#' },
    ];

    // Helper function to create empty day availability
    function createEmptyDay(): DayAvailability {
        return {
            enabled: false,
            periods: [],
        };
    }

    // Helper function to generate unique IDs
    function generateId(): string {
        return Math.random().toString(36).substr(2, 9);
    }

    // Transform backend data to form format
    function transformAvailabilityToForm(availability: Availability): AvailabilityFormData {
        const days: Record<string, DayAvailability> = {
            monday: createEmptyDay(),
            tuesday: createEmptyDay(),
            wednesday: createEmptyDay(),
            thursday: createEmptyDay(),
            friday: createEmptyDay(),
            saturday: createEmptyDay(),
            sunday: createEmptyDay(),
        };

        // If we have recurrence pattern with days, distribute periods accordingly
        if (availability.recurrence_pattern?.days && Array.isArray(availability.recurrence_pattern.days)) {
            const recurrenceDays = availability.recurrence_pattern.days;

            recurrenceDays.forEach((dayName: string) => {
                if (days[dayName]) {
                    days[dayName].enabled = true;
                    days[dayName].periods = availability.periods.map((period) => ({
                        id: generateId(),
                        start_time: period.start_time,
                        end_time: period.end_time,
                    }));
                }
            });
        } else {
            // If no recurrence pattern, assume it's a single occurrence
            // For now, we'll put all periods in Monday as a fallback
            // In a real scenario, you might want to store day information with each period
            if (availability.periods.length > 0) {
                days.monday.enabled = true;
                days.monday.periods = availability.periods.map((period) => ({
                    id: generateId(),
                    start_time: period.start_time,
                    end_time: period.end_time,
                }));
            }
        }

        return {
            name: availability.name,
            description: availability.description || '',
            start_date: availability.start_date,
            end_date: availability.end_date || '',
            days: days as any,
        };
    }

    function handleSubmit(data: AvailabilityFormData) {
        // Transform the day-based structure to match backend expectations
        const periods: Array<{ start_time: string; end_time: string; day: string }> = [];
        const recurrence_days: string[] = [];

        Object.entries(data.days).forEach(([dayKey, dayData]) => {
            if (dayData.enabled && dayData.periods.length > 0) {
                recurrence_days.push(dayKey);
                dayData.periods.forEach((period) => {
                    periods.push({
                        start_time: period.start_time,
                        end_time: period.end_time,
                        day: dayKey,
                    });
                });
            }
        });

        const submitData = {
            name: data.name,
            description: data.description,
            start_date: data.start_date,
            end_date: data.end_date || null,
            periods: periods,
            recurrence_type: recurrence_days.length > 0 ? 'weekly' : null,
            recurrence_days: recurrence_days,
        };

        router.put(update.url(props.availability.id), submitData, {
            onSuccess: () => {
                // Success message will be handled by the backend redirect
            },
            onError: (errors) => {
                console.error('Validation errors:', errors);
            },
        });
    }

    function handleCancel() {
        router.visit(index.url());
    }
</script>

<template>
    <Head title="Edit Availability" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Edit Availability</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Update "{{ availability.name }}" availability settings</p>
            </div>

            <div class="max-w-4xl">
                <AvailabilityForm
                    :availability="transformAvailabilityToForm(availability)"
                    :is-editing="true"
                    @submit="handleSubmit"
                    @cancel="handleCancel"
                />
            </div>
        </div>
    </AppLayout>
</template>
