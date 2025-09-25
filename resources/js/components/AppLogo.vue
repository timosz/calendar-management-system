<script setup lang="ts">
import { LayoutGrid } from 'lucide-vue-next';
import type { Component } from 'vue';

interface Props {
    title?: string;
    subtitle?: string;
    icon?: Component;
}

withDefaults(defineProps<Props>(), {
    title: 'Laravel App',
    subtitle: 'Vue Starter Kit',
    icon: LayoutGrid,
});
</script>

<template>
    <div class="flex items-center gap-2">
        <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-primary text-primary-foreground">
            <component :is="icon" class="size-4" />
        </div>
        <div class="flex flex-col gap-0.5 leading-none">
            <span class="font-semibold">{{ title }}</span>
            <span class="text-xs text-muted-foreground">{{ subtitle }}</span>
        </div>
    </div>
</template>
