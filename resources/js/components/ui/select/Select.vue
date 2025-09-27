<script setup lang="ts">
import { provide, ref, computed, watch } from 'vue'

interface SelectProps {
  modelValue?: string | number
  placeholder?: string
  disabled?: boolean
}

const props = defineProps<SelectProps>()

const emits = defineEmits<{
  'update:modelValue': [value: string | number]
}>()

const isOpen = ref(false)
const selectedValue = ref(props.modelValue)

watch(() => props.modelValue, (newValue) => {
  selectedValue.value = newValue
})

const selectValue = (value: string | number) => {
  selectedValue.value = value
  emits('update:modelValue', value)
  isOpen.value = false
}

const toggleOpen = () => {
  if (!props.disabled) {
    isOpen.value = !isOpen.value
  }
}

// Provide context to child components
provide('selectContext', {
  isOpen,
  selectedValue,
  selectValue,
  toggleOpen,
  disabled: computed(() => props.disabled)
})
</script>

<template>
  <div class="relative">
    <slot />
  </div>
</template>