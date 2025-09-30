<script setup lang="ts">
import type { TabsTriggerProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { TabsTrigger, useForwardProps } from "reka-ui"
import { cn } from "@/lib/utils"

const props = defineProps<TabsTriggerProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <TabsTrigger
    data-slot="tabs-trigger"
    v-bind="forwardedProps"
    :class="cn(
      'inline-flex h-8 items-center justify-center whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium transition-all cursor-pointer',
      'text-muted-foreground hover:text-foreground',
      'data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow',
      'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
      'disabled:pointer-events-none disabled:opacity-50',
      props.class,
    )"
  >
    <slot />
  </TabsTrigger>
</template>