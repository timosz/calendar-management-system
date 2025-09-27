<script setup lang="ts">
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
    import { Switch } from '@/components/ui/switch';
    import AppLayout from '@/layouts/AppLayout.vue';
    import { Head, Link, useForm } from '@inertiajs/vue3';
    import { ArrowLeft } from 'lucide-vue-next';

    // Import Wayfinder routes
    import { index, update } from '@/routes/admin/availability-periods';

    interface DayOption {
        value: number;
        label: string;
    }

    interface AvailabilityPeriod {
        id: number;
        day_of_week: number;
        start_time: string;
        end_time: string;
        is_active: boolean;
        user_id: number;
        created_at: string;
        updated_at: string;
    }

    interface Props {
        availabilityPeriod: AvailabilityPeriod;
        dayOptions: DayOption[];
    }

    const props = defineProps<Props>();

    const form = useForm({
        day_of_week: props.availabilityPeriod.day_of_week,
        start_time: props.availabilityPeriod.start_time.substring(0, 5), // Remove seconds
        end_time: props.availabilityPeriod.end_time.substring(0, 5), // Remove seconds
        is_active: props.availabilityPeriod.is_active,
    });

    const submit = () => {
        form.patch(update(props.availabilityPeriod.id), {
            onSuccess: () => {
                // Form will redirect on success
            },
        });
    };
</script>

<template>
    <Head title="Edit Availability Period" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <Link :href="index()">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back to Availability Periods
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Edit Availability Period</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Update the details of this availability period.</p>
                </div>
            </div>
        </template>

        <!-- Form -->
        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Availability Period Details</CardTitle>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Day of Week -->
                    <div class="space-y-2">
                        <Label for="day_of_week">Day of Week</Label>
                        <Select v-model="form.day_of_week" required>
                            <SelectTrigger>
                                <SelectValue placeholder="Select a day" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="day in dayOptions" :key="day.value" :value="day.value">
                                    {{ day.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <div v-if="form.errors.day_of_week" class="text-sm text-red-600">
                            {{ form.errors.day_of_week }}
                        </div>
                    </div>

                    <!-- Time Range -->
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="start_time">Start Time</Label>
                            <Input id="start_time" v-model="form.start_time" type="time" required class="w-full" />
                            <div v-if="form.errors.start_time" class="text-sm text-red-600">
                                {{ form.errors.start_time }}
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label for="end_time">End Time</Label>
                            <Input id="end_time" v-model="form.end_time" type="time" required class="w-full" />
                            <div v-if="form.errors.end_time" class="text-sm text-red-600">
                                {{ form.errors.end_time }}
                            </div>
                        </div>
                    </div>

                    <!-- Active Status -->
                    <div class="flex items-center space-x-2">
                        <Switch id="is_active" v-model:checked="form.is_active" />
                        <Label for="is_active">Active</Label>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-4 pt-4">
                        <Link :href="index()">
                            <Button variant="outline" type="button">Cancel</Button>
                        </Link>
                        <Button type="submit" :disabled="form.processing">
                            {{ form.processing ? 'Updating...' : 'Update Availability Period' }}
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>
    </AppLayout>
</template>
