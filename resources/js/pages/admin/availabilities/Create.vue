<script setup lang="ts">
    import AvailabilityForm from '@/components/AvailabilityForm.vue';
    import AppLayout from '@/layouts/AppLayout.vue';
    import { index, store } from '@/routes/availabilities';
    import type { BreadcrumbItemType } from '@/types';
    import { Head, router } from '@inertiajs/vue3';

    interface AvailabilityForm {
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

    interface DayAvailability {
        enabled: boolean;
        periods: Array<{
            id: string;
            start_time: string;
            end_time: string;
        }>;
    }

    const breadcrumbs: BreadcrumbItemType[] = [
        { title: 'Availabilities', href: index.url() },
        { title: 'Create', href: '#' },
    ];

    function handleSubmit(data: AvailabilityForm) {
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

        router.post(store.url(), submitData, {
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
    <Head title="Create Availability" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Create New Availability</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Set up your working hours and availability schedule</p>
            </div>

            <div class="max-w-4xl">
                <AvailabilityForm @submit="handleSubmit" @cancel="handleCancel" />
            </div>
        </div>
    </AppLayout>
</template>
