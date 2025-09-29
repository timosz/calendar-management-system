<script setup lang="ts">
    import { DatePicker } from 'v-calendar';
    import 'v-calendar/style.css';
    import { computed } from 'vue';

    interface Props {
        modelValue: string;
        minDate?: Date | string;
        maxDate?: Date | string;
        disabled?: boolean;
        hasError?: boolean;
        placeholder?: string;
    }

    const props = withDefaults(defineProps<Props>(), {
        placeholder: 'Select date',
    });

    const emit = defineEmits<{
        'update:modelValue': [value: string];
    }>();

    const handleDateChange = (date: Date) => {
        if (date) {
            // Format to YYYY-MM-DD
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            emit('update:modelValue', `${year}-${month}-${day}`);
        }
    };

    const dateValue = computed(() => {
        return props.modelValue ? new Date(props.modelValue) : null;
    });
</script>

<template>
    <DatePicker
        :model-value="dateValue"
        @update:model-value="handleDateChange"
        :min-date="minDate"
        :max-date="maxDate"
        :disabled="disabled"
        mode="date"
        :popover="{ visibility: 'click' }"
    >
        <template #default="{ togglePopover }">
            <button
                type="button"
                @click="togglePopover"
                :disabled="disabled"
                :class="[
                    'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background',
                    'file:border-0 file:bg-transparent file:text-sm file:font-medium',
                    'placeholder:text-muted-foreground',
                    'focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none',
                    'disabled:cursor-not-allowed disabled:opacity-50',
                    'text-left',
                    { 'border-destructive': hasError },
                ]"
            >
                <span v-if="modelValue" class="flex-1">
                    {{
                        new Date(modelValue).toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric',
                        })
                    }}
                </span>
                <span v-else class="flex-1 text-muted-foreground">
                    {{ placeholder }}
                </span>
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    class="ml-2 opacity-50"
                >
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                </svg>
            </button>
        </template>
    </DatePicker>
</template>
