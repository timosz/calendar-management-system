<script setup lang="ts">
import { computed, ref, watch, type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

interface SwitchProps {
  defaultChecked?: boolean
  checked?: boolean
  disabled?: boolean
  required?: boolean
  name?: string
  value?: string
  id?: string
  class?: HTMLAttributes['class']
}

const props = withDefaults(defineProps<SwitchProps>(), {
  defaultChecked: false
})

const emits = defineEmits<{
  'update:checked': [checked: boolean]
}>()

const internalChecked = ref(props.defaultChecked)

// Watch for external changes to checked prop
watch(() => props.checked, (newValue) => {
  if (newValue !== undefined) {
    internalChecked.value = newValue
  }
}, { immediate: true })

const isChecked = computed({
  get: () => props.checked !== undefined ? props.checked : internalChecked.value,
  set: (value) => {
    if (props.disabled) return
    internalChecked.value = value
    emits('update:checked', value)
  }
})

const toggle = () => {
  if (props.disabled) return
  isChecked.value = !isChecked.value
}

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === ' ' || event.key === 'Enter') {
    event.preventDefault()
    toggle()
  }
}
</script>

<template>
  <button
    :id="id"
    :name="name"
    :value="value"
    :disabled="disabled"
    :required="required"
    :aria-checked="isChecked"
    :aria-disabled="disabled"
    role="switch"
    type="button"
    :class="cn(
      'peer inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50',
      isChecked 
        ? 'bg-primary' 
        : 'bg-input',
      props.class
    )"
    @click="toggle"
    @keydown="handleKeydown"
  >
    <span
      :class="cn(
        'pointer-events-none block h-5 w-5 rounded-full bg-background shadow-lg ring-0 transition-transform',
        isChecked 
          ? 'translate-x-5' 
          : 'translate-x-0'
      )"
    />
  </button>
</template>