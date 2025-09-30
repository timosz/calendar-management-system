<script setup lang="ts">
    import VueDatePicker from '@vuepic/vue-datepicker';
    import '@vuepic/vue-datepicker/dist/main.css';
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

    const handleDateChange = (date: Date | null) => {
        if (date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            emit('update:modelValue', `${year}-${month}-${day}`);
        } else {
            emit('update:modelValue', '');
        }
    };

    const dateValue = computed(() => {
        return props.modelValue ? new Date(props.modelValue) : null;
    });
</script>

<template>
    <VueDatePicker
        :model-value="dateValue"
        @update:model-value="handleDateChange"
        :min-date="minDate"
        :max-date="maxDate"
        :disabled="disabled"
        :placeholder="placeholder"
        format="yyyy-MM-dd"
        :enable-time-picker="false"
        auto-apply
        :teleport="true"
    >
        <template #dp-input="{ value, onInput, onEnter, onTab, onClear, onBlur, onKeypress, onPaste, isMenuOpen }">
            <input
                type="text"
                :value="value"
                @input="onInput"
                @keydown.enter="onEnter"
                @keydown.tab="onTab"
                @blur="onBlur"
                @keypress="onKeypress"
                @paste="onPaste"
                :placeholder="placeholder"
                :disabled="disabled"
                :class="[
                    'flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm ring-offset-background',
                    'file:border-0 file:bg-transparent file:text-sm file:font-medium',
                    'placeholder:text-muted-foreground',
                    'focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none',
                    'disabled:cursor-not-allowed disabled:opacity-50',
                    hasError ? 'border-destructive' : 'border-input',
                ]"
            />
        </template>
    </VueDatePicker>
</template>

<style scoped>
    :deep(.dp__menu) {
        background-color: hsl(var(--popover));
        border: 1px solid hsl(var(--border));
        border-radius: var(--radius-lg);
        box-shadow:
            0 10px 15px -3px rgb(0 0 0 / 0.1),
            0 4px 6px -4px rgb(0 0 0 / 0.1);
        color: hsl(var(--popover-foreground));
    }

    :deep(.dp__calendar_header) {
        color: hsl(var(--foreground));
    }

    :deep(.dp__calendar_header_item) {
        color: hsl(var(--muted-foreground));
        font-weight: 500;
    }

    :deep(.dp__calendar_item) {
        border-radius: var(--radius-sm);
        color: hsl(var(--foreground));
    }

    :deep(.dp__cell_inner) {
        color: hsl(var(--foreground));
    }

    :deep(.dp__cell_inner:hover) {
        background-color: hsl(var(--accent));
        color: hsl(var(--accent-foreground));
    }

    :deep(.dp__today) {
        border: 1px solid hsl(var(--primary));
    }

    :deep(.dp__active_date) {
        background-color: hsl(var(--primary));
        color: hsl(var(--primary-foreground));
    }

    :deep(.dp__month_year_select) {
        color: hsl(var(--foreground));
    }

    :deep(.dp__month_year_select:hover) {
        background-color: hsl(var(--accent));
        color: hsl(var(--accent-foreground));
    }

    :deep(.dp__button) {
        border-radius: var(--radius-sm);
        color: hsl(var(--foreground));
    }

    :deep(.dp__button:hover) {
        background-color: hsl(var(--accent));
        color: hsl(var(--accent-foreground));
    }

    :deep(.dp__arrow_top),
    :deep(.dp__arrow_bottom) {
        background-color: hsl(var(--popover));
        border-color: hsl(var(--border));
    }

    :deep(.dp__overlay) {
        background-color: hsl(var(--popover));
        color: hsl(var(--popover-foreground));
    }

    :deep(.dp__overlay_cell) {
        color: hsl(var(--foreground));
    }

    :deep(.dp__overlay_cell:hover) {
        background-color: hsl(var(--accent));
        color: hsl(var(--accent-foreground));
    }

    :deep(.dp__overlay_cell_active) {
        background-color: hsl(var(--primary));
        color: hsl(var(--primary-foreground));
    }

    :deep(.dp__cell_disabled) {
        color: hsl(var(--muted-foreground));
        opacity: 0.5;
    }
</style>
