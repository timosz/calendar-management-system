<script setup lang="ts">
import { inject, computed, type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

interface SelectValueProps {
  placeholder?: string
  class?: HTMLAttributes['class']
  options?: Array<{ value: any; label: string }> // Add options prop to find labels
}

const props = defineProps<SelectValueProps>()

const selectContext = inject('selectContext') as any

const displayValue = computed(() => {
  const currentValue = selectContext.selectedValue.value
  
  if (currentValue === undefined || currentValue === null) {
    return props.placeholder || 'Select an option'
  }
  
  // If options are provided, find the label for the current value
  if (props.options && Array.isArray(props.options)) {
    const selectedOption = props.options.find(option => option.value === currentValue)
    return selectedOption ? selectedOption.label : currentValue
  }
  
  // Fallback to raw value if no options provided
  return currentValue
})

const hasValue = computed(() => {
  return selectContext.selectedValue.value !== undefined && selectContext.selectedValue.value !== null
})
</script>

<template>
  <span 
    :class="cn(
      'text-sm',
      !hasValue && 'text-muted-foreground',
      props.class
    )"
  >
    {{ displayValue }}
  </span>
</template>