<script setup lang="ts">
import { inject, computed, type HTMLAttributes } from 'vue'
import { ChevronDown } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

interface SelectTriggerProps {
  class?: HTMLAttributes['class']
}

const props = defineProps<SelectTriggerProps>()

const selectContext = inject('selectContext') as any

const handleClick = (event: MouseEvent) => {
  event.stopPropagation() // Prevent event bubbling
  if (!selectContext.disabled.value) {
    selectContext.toggleOpen()
  }
}

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault()
    handleClick(event as any)
  }
  if (event.key === 'Escape') {
    selectContext.isOpen.value = false
  }
}
</script>

<template>
  <button
    type="button"
    role="combobox"
    :aria-expanded="selectContext.isOpen.value"
    :aria-disabled="selectContext.disabled.value"
    :disabled="selectContext.disabled.value"
    :class="cn(
      'flex h-10 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 [&>span]:line-clamp-1',
      props.class
    )"
    @click="handleClick"
    @keydown="handleKeydown"
  >
    <slot />
    <ChevronDown class="h-4 w-4 opacity-50" />
  </button>
</template>