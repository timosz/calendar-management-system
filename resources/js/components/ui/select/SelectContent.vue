<script setup lang="ts">
import { inject, onMounted, onUnmounted, ref, nextTick, type HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'

interface SelectContentProps {
  class?: HTMLAttributes['class']
}

const props = defineProps<SelectContentProps>()

const selectContext = inject('selectContext') as any
const contentRef = ref<HTMLElement>()

// Close dropdown when clicking outside
const handleClickOutside = (event: MouseEvent) => {
  if (contentRef.value && !contentRef.value.contains(event.target as Node)) {
    selectContext.isOpen.value = false
  }
}

onMounted(() => {
  // Add a small delay to prevent immediate closing
  nextTick(() => {
    document.addEventListener('click', handleClickOutside)
  })
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
})

// Handle escape key
const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    selectContext.isOpen.value = false
  }
}
</script>

<template>
  <div
    v-if="selectContext.isOpen.value"
    ref="contentRef"
    :class="cn(
      'absolute top-full left-0 z-50 mt-1 min-w-full overflow-hidden rounded-md border bg-popover p-1 text-popover-foreground shadow-md animate-in fade-in-0 zoom-in-95',
      props.class
    )"
    @keydown="handleKeydown"
  >
    <slot />
  </div>
</template>