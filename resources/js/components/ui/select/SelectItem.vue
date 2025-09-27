<script setup lang="ts">
import { inject, computed, type HTMLAttributes } from 'vue'
import { Check } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

interface SelectItemProps {
  value: string | number
  disabled?: boolean
  class?: HTMLAttributes['class']
}

const props = defineProps<SelectItemProps>()

const selectContext = inject('selectContext') as any

const isSelected = computed(() => {
  return selectContext.selectedValue.value === props.value
})

const handleClick = () => {
  if (!props.disabled) {
    selectContext.selectValue(props.value)
  }
}

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault()
    handleClick()
  }
}
</script>

<template>
  <div
    role="option"
    :aria-selected="isSelected"
    :aria-disabled="disabled"
    :class="cn(
      'relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-8 pr-2 text-sm outline-none hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
      disabled && 'pointer-events-none opacity-50',
      props.class
    )"
    @click="handleClick"
    @keydown="handleKeydown"
    tabindex="0"
  >
    <span class="absolute left-2 flex h-3.5 w-3.5 items-center justify-center">
      <Check v-if="isSelected" class="h-4 w-4" />
    </span>
    <slot />
  </div>
</template>