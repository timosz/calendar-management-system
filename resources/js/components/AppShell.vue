<script setup lang="ts">
import { SidebarProvider } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/vue3';
import ToastContainer from '@/components/ToastContainer.vue';
import ToastInitializer from '@/components/ToastInitializer.vue';

interface Props {
    variant?: 'header' | 'sidebar';
}

defineProps<Props>();

const isOpen = usePage().props.sidebarOpen;
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
        <ToastContainer />
        <ToastInitializer />
    </div>
    <SidebarProvider v-else :default-open="isOpen">
        <slot />
        <ToastContainer />
        <ToastInitializer />
    </SidebarProvider>
</template>
