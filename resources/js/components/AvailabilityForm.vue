<script setup lang="ts">
    import TimeSlot from '@/components/TimeSlot.vue';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Textarea } from '@/components/ui/textarea';
    import { Plus } from 'lucide-vue-next';
    import { computed, reactive, ref } from 'vue';

    interface Period {
        id: string;
        start_time: string;
        end_time: string;
    }

    interface DayAvailability {
        enabled: boolean;
        periods: Period[];
    }

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

    interface Props {
        availability?: AvailabilityForm;
        isEditing?: boolean;
    }

    const props = withDefaults(defineProps<Props>(), {
        isEditing: false,
    });

    const emit = defineEmits<{
        submit: [data: AvailabilityForm];
        cancel: [];
    }>();

    // Helper function to generate unique IDs
    function generateId(): string {
        return Math.random().toString(36).substr(2, 9);
    }

    // Helper function to create empty day availability
    function createEmptyDay(): DayAvailability {
        return {
            enabled: false,
            periods: [],
        };
    }

    // Form data
    const form = reactive<AvailabilityForm>({
        name: props.availability?.name || '',
        description: props.availability?.description || '',
        start_date: props.availability?.start_date || '',
        end_date: props.availability?.end_date || '',
        days: props.availability?.days || {
            monday: createEmptyDay(),
            tuesday: createEmptyDay(),
            wednesday: createEmptyDay(),
            thursday: createEmptyDay(),
            friday: createEmptyDay(),
            saturday: createEmptyDay(),
            sunday: createEmptyDay(),
        },
    });

    const errors = ref<Record<string, string[]>>({});
    const isSubmitting = ref(false);

    // Days of the week configuration
    const daysOfWeek = [
        { key: 'monday', label: 'Monday' },
        { key: 'tuesday', label: 'Tuesday' },
        { key: 'wednesday', label: 'Wednesday' },
        { key: 'thursday', label: 'Thursday' },
        { key: 'friday', label: 'Friday' },
        { key: 'saturday', label: 'Saturday' },
        { key: 'sunday', label: 'Sunday' },
    ] as const;

    // Computed properties
    const hasAnyEnabledDays = computed(() => {
        return Object.values(form.days).some((day) => day.enabled);
    });

    const canAddPeriod = (dayKey: keyof typeof form.days) => {
        return form.days[dayKey].periods.length < 5; // Reasonable limit
    };

    // Methods
    function toggleDay(dayKey: keyof typeof form.days, enabled: boolean) {
        // Force reactivity by updating the whole object
        form.days[dayKey] = {
            enabled: enabled,
            periods:
                enabled && form.days[dayKey].periods.length === 0
                    ? [
                          {
                              id: generateId(),
                              start_time: '',
                              end_time: '',
                          },
                      ]
                    : enabled
                      ? form.days[dayKey].periods
                      : [],
        };
    }

    function updatePeriod(dayKey: keyof typeof form.days, index: number, updatedPeriod: Period) {
        form.days[dayKey].periods[index] = updatedPeriod;
    }

    function addPeriod(dayKey: keyof typeof form.days) {
        if (canAddPeriod(dayKey)) {
            form.days[dayKey].periods.push({
                id: generateId(),
                start_time: '',
                end_time: '',
            });
        }
    }

    function removePeriod(dayKey: keyof typeof form.days, index: number) {
        const day = form.days[dayKey];
        if (day.periods.length > 1) {
            day.periods.splice(index, 1);
        } else if (day.periods.length === 1) {
            // If removing the last period, disable the day
            day.periods = [];
            day.enabled = false;
        }
    }

    function validateForm(): boolean {
        errors.value = {};
        let isValid = true;

        // Name validation
        if (!form.name.trim()) {
            errors.value.name = ['Name is required'];
            isValid = false;
        }

        // Date validation
        if (!form.start_date) {
            errors.value.start_date = ['Start date is required'];
            isValid = false;
        }

        if (form.end_date && form.start_date && new Date(form.end_date) < new Date(form.start_date)) {
            errors.value.end_date = ['End date must be after start date'];
            isValid = false;
        }

        // At least one day must be enabled
        if (!hasAnyEnabledDays.value) {
            errors.value.days = ['At least one day must be enabled with time periods'];
            isValid = false;
        }

        // Period validation for each enabled day
        Object.entries(form.days).forEach(([dayKey, dayData]) => {
            if (dayData.enabled) {
                if (dayData.periods.length === 0) {
                    errors.value[`days.${dayKey}`] = ['At least one time period is required for enabled days'];
                    isValid = false;
                }

                dayData.periods.forEach((period, index) => {
                    const periodKey = `days.${dayKey}.periods.${index}`;

                    if (!period.start_time) {
                        errors.value[`${periodKey}.start_time`] = ['Start time is required'];
                        isValid = false;
                    }
                    if (!period.end_time) {
                        errors.value[`${periodKey}.end_time`] = ['End time is required'];
                        isValid = false;
                    }
                    if (period.start_time && period.end_time && period.start_time >= period.end_time) {
                        errors.value[`${periodKey}.end_time`] = ['End time must be after start time'];
                        isValid = false;
                    }
                });
            }
        });

        return isValid;
    }

    async function handleSubmit() {
        if (!validateForm()) {
            return;
        }

        isSubmitting.value = true;

        try {
            emit('submit', { ...form });
        } catch (error) {
            console.error('Form submission error:', error);
        } finally {
            isSubmitting.value = false;
        }
    }

    function getErrorMessage(field: string): string {
        return errors.value[field]?.[0] || '';
    }
</script>

<template>
    <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Basic Information -->
        <Card>
            <CardHeader>
                <CardTitle>Basic Information</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="name">Name *</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        placeholder="e.g., Office Hours"
                        :class="{ 'border-red-500': getErrorMessage('name') }"
                    />
                    <p v-if="getErrorMessage('name')" class="text-sm text-red-600">
                        {{ getErrorMessage('name') }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="description">Description</Label>
                    <Textarea id="description" v-model="form.description" placeholder="Optional description for this availability" rows="3" />
                </div>
            </CardContent>
        </Card>

        <!-- Date Range -->
        <Card>
            <CardHeader>
                <CardTitle>Date Range</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="start_date">Start Date *</Label>
                        <Input id="start_date" v-model="form.start_date" type="date" :class="{ 'border-red-500': getErrorMessage('start_date') }" />
                        <p v-if="getErrorMessage('start_date')" class="text-sm text-red-600">
                            {{ getErrorMessage('start_date') }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="end_date">End Date</Label>
                        <Input id="end_date" v-model="form.end_date" type="date" :class="{ 'border-red-500': getErrorMessage('end_date') }" />
                        <p v-if="getErrorMessage('end_date')" class="text-sm text-red-600">
                            {{ getErrorMessage('end_date') }}
                        </p>
                        <p class="text-sm text-gray-500">Leave empty for indefinite availability</p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Weekly Schedule -->
        <Card>
            <CardHeader>
                <CardTitle>Weekly Schedule</CardTitle>
                <p class="text-sm text-gray-600">Select the days you're available and set your working hours for each day.</p>
            </CardHeader>
            <CardContent class="space-y-6">
                <!-- General error for days -->
                <div v-if="getErrorMessage('days')" class="rounded-md bg-red-50 p-4">
                    <p class="text-sm text-red-600">{{ getErrorMessage('days') }}</p>
                </div>

                <!-- Days of the week -->
                <div v-for="day in daysOfWeek" :key="day.key" class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <Checkbox
                                :id="`day_${day.key}`"
                                v-model="form.days[day.key].enabled"
                                @update:modelValue="(checked) => toggleDay(day.key, !!checked)"
                            />
                            <Label :for="`day_${day.key}`" class="text-base font-medium">
                                {{ day.label }} ({{ form.days[day.key].enabled ? 'Enabled' : 'Disabled' }})
                            </Label>
                        </div>

                        <Button
                            v-if="form.days[day.key].enabled"
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="addPeriod(day.key)"
                            :disabled="!canAddPeriod(day.key)"
                        >
                            <Plus class="mr-1 h-3 w-3" />
                            Add Time
                        </Button>
                    </div>

                    <!-- Time periods for this day -->
                    <div v-if="form.days[day.key].enabled" class="ml-6 space-y-3 border-l-2 border-border/50 pl-4 dark:border-border/30">
                        <div v-if="getErrorMessage(`days.${day.key}`)" class="rounded-md bg-destructive/10 p-2">
                            <p class="text-xs text-destructive">
                                {{ getErrorMessage(`days.${day.key}`) }}
                            </p>
                        </div>

                        <TimeSlot
                            v-for="(period, index) in form.days[day.key].periods"
                            :key="period.id"
                            :period="period"
                            :index="index"
                            :day-key="day.key"
                            :can-remove="form.days[day.key].periods.length > 1"
                            :errors="{
                                start_time: getErrorMessage(`days.${day.key}.periods.${index}.start_time`),
                                end_time: getErrorMessage(`days.${day.key}.periods.${index}.end_time`),
                            }"
                            @update:period="(updatedPeriod) => updatePeriod(day.key, index, updatedPeriod)"
                            @remove="removePeriod(day.key, index)"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Submit Button -->
        <div class="flex justify-end space-x-4">
            <Button type="button" variant="outline" @click="$emit('cancel')" :disabled="isSubmitting"> Cancel </Button>
            <Button type="submit" :disabled="isSubmitting">
                <span v-if="isSubmitting">
                    {{ isEditing ? 'Updating...' : 'Creating...' }}
                </span>
                <span v-else>
                    {{ isEditing ? 'Update Availability' : 'Create Availability' }}
                </span>
            </Button>
        </div>
    </form>
</template>
