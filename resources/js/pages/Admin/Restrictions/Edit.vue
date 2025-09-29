<script setup lang="ts">
    import DatePicker from '@/components/DatePicker.vue';
    import TimeSelect from '@/components/admin/TimeSelect.vue';
    import { Alert, AlertDescription } from '@/components/ui/alert';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Label } from '@/components/ui/label';
    import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
    import { Textarea } from '@/components/ui/textarea';
    import { useToast } from '@/composables/useToast';
    import AppLayout from '@/layouts/AppLayout.vue';
    import type { RestrictionType, TimeSlot } from '@/types';
    import { Head, useForm, usePage } from '@inertiajs/vue3';
    import { AlertCircle, ArrowLeft, Save } from 'lucide-vue-next';
    import { computed, ref } from 'vue';

    interface RestrictionData {
        id: number;
        start_date: string;
        end_date: string;
        start_time: string | null;
        end_time: string | null;
        reason: string | null;
        type: RestrictionType;
    }

    interface Props {
        restriction: RestrictionData;
        types: Record<RestrictionType, string>;
        timeSlots: TimeSlot[];
    }

    const props = defineProps<Props>();
    const { success, error } = useToast();
    const page = usePage();

    const form = useForm({
        start_date: props.restriction.start_date,
        end_date: props.restriction.end_date,
        start_time: props.restriction.start_time || '',
        end_time: props.restriction.end_time || '',
        reason: props.restriction.reason || '',
        type: props.restriction.type,
    });

    const isAllDay = ref(!props.restriction.start_time && !props.restriction.end_time);

    // Watch for all-day toggle
    const toggleAllDay = (checked: boolean) => {
        isAllDay.value = checked;
        if (checked) {
            form.start_time = '';
            form.end_time = '';
        }
    };

    // Auto-fill end_date when start_date changes if end_date is empty
    const handleStartDateChange = () => {
        if (!form.end_date) {
            form.end_date = form.start_date;
        }
    };

    const submit = () => {
        form.put(`/admin/restrictions/${props.restriction.id}`, {
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

    const hasErrors = computed(() => Object.keys(form.errors).length > 0);

    // Minimum date for end_date (start_date)
    const minEndDate = computed(() => {
        return form.start_date ? new Date(form.start_date) : new Date();
    });
</script>

<template>
    <Head title="Edit Restriction" />

    <AppLayout>
        <!-- Page Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Edit Restriction</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Update restriction details</p>
            </div>
            <Button variant="outline" asChild>
                <a href="/admin/restrictions">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to Restrictions
                </a>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Restriction Details</CardTitle>
                <CardDescription> Modify the unavailable period or time block </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Error Alert -->
                    <Alert v-if="hasErrors" variant="destructive">
                        <AlertCircle class="h-4 w-4" />
                        <AlertDescription> Please fix the errors below before submitting. </AlertDescription>
                    </Alert>

                    <!-- Type Selection -->
                    <div class="space-y-2">
                        <Label for="type"> Type <span class="text-destructive">*</span> </Label>
                        <Select v-model="form.type" required>
                            <SelectTrigger id="type" :class="{ 'border-destructive': form.errors.type }">
                                <SelectValue placeholder="Select restriction type" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="(label, value) in types" :key="value" :value="value">
                                    {{ label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.type" class="text-sm text-destructive">
                            {{ form.errors.type }}
                        </p>
                        <p class="text-sm text-muted-foreground">Select the category that best describes this restriction</p>
                    </div>

                    <!-- Date Range -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="start_date"> Start Date <span class="text-destructive">*</span> </Label>
                            <DatePicker
                                v-model="form.start_date"
                                :has-error="!!form.errors.start_date"
                                placeholder="Select start date"
                                @update:model-value="handleStartDateChange"
                            />
                            <p v-if="form.errors.start_date" class="text-sm text-destructive">
                                {{ form.errors.start_date }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="end_date"> End Date <span class="text-destructive">*</span> </Label>
                            <DatePicker
                                v-model="form.end_date"
                                :min-date="minEndDate"
                                :has-error="!!form.errors.end_date"
                                placeholder="Select end date"
                            />
                            <p v-if="form.errors.end_date" class="text-sm text-destructive">
                                {{ form.errors.end_date }}
                            </p>
                        </div>
                    </div>

                    <!-- All Day Toggle -->
                    <div class="flex items-center space-x-2">
                        <Checkbox id="all_day" :checked="isAllDay" @update:checked="toggleAllDay" />
                        <Label
                            for="all_day"
                            class="cursor-pointer text-sm leading-none font-medium peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                        >
                            All day restriction
                        </Label>
                    </div>

                    <!-- Time Range (only if not all day) -->
                    <div v-if="!isAllDay" class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="start_time"> Start Time <span v-if="!isAllDay" class="text-destructive">*</span> </Label>
                            <TimeSelect
                                id="start_time"
                                v-model="form.start_time"
                                :time-slots="timeSlots"
                                :disabled="isAllDay"
                                :has-error="!!form.errors.start_time"
                                placeholder="Select start time"
                            />
                            <p v-if="form.errors.start_time" class="text-sm text-destructive">
                                {{ form.errors.start_time }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="end_time"> End Time <span v-if="!isAllDay" class="text-destructive">*</span> </Label>
                            <TimeSelect
                                id="end_time"
                                v-model="form.end_time"
                                :time-slots="timeSlots"
                                :disabled="isAllDay"
                                :has-error="!!form.errors.end_time"
                                placeholder="Select end time"
                            />
                            <p v-if="form.errors.end_time" class="text-sm text-destructive">
                                {{ form.errors.end_time }}
                            </p>
                        </div>
                    </div>

                    <!-- Reason -->
                    <div class="space-y-2">
                        <Label for="reason"> Reason </Label>
                        <Textarea
                            id="reason"
                            v-model="form.reason"
                            placeholder="Optional description or reason for this restriction"
                            rows="3"
                            :class="{ 'border-destructive': form.errors.reason }"
                        />
                        <p v-if="form.errors.reason" class="text-sm text-destructive">
                            {{ form.errors.reason }}
                        </p>
                        <p class="text-sm text-muted-foreground">Add any additional context about this restriction</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center gap-4 pt-4">
                        <Button type="submit" :disabled="form.processing">
                            <Save class="mr-2 h-4 w-4" />
                            {{ form.processing ? 'Updating...' : 'Update Restriction' }}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <a href="/admin/restrictions">Cancel</a>
                        </Button>
                    </div>
                </form>
            </CardContent>
        </Card>

        <!-- Help Card -->
        <Card>
            <CardHeader>
                <CardTitle>About Restrictions</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-sm text-muted-foreground">
                <p><strong>Restrictions</strong> are used to block out time when you're unavailable or when a resource cannot be booked.</p>
                <ul class="list-disc space-y-1 pl-5">
                    <li><strong>All Day:</strong> Blocks the entire day(s) for the date range</li>
                    <li><strong>Specific Time:</strong> Blocks only certain hours during the day(s)</li>
                    <li><strong>Date Range:</strong> Can span multiple days (e.g., vacation, maintenance period)</li>
                    <li><strong>Booking Conflicts:</strong> The system will check for conflicts with existing confirmed bookings</li>
                </ul>
            </CardContent>
        </Card>
    </AppLayout>
</template>
