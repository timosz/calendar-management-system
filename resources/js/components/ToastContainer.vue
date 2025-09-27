<script setup lang="ts">
    import { useToastStore } from '@/stores/toastStore';
    import { AlertCircle, CheckCircle, Info, X, XCircle } from 'lucide-vue-next';
    import { onMounted } from 'vue';

    const toastStore = useToastStore();

    // Set up Inertia listener when component mounts
    onMounted(() => {
        toastStore.setupInertiaListener();
    });

    const removeToast = (id: number) => {
        toastStore.removeToast(id);
    };

    const typeToIcon = {
        success: CheckCircle,
        error: XCircle,
        warning: AlertCircle,
        info: Info,
    };

    const typeToColors = {
        success: 'bg-green-50 text-green-800 dark:bg-green-900 dark:text-green-100 ring-1 ring-green-200 dark:ring-green-800',
        error: 'bg-red-50 text-red-800 dark:bg-red-900 dark:text-red-100 ring-1 ring-red-200 dark:ring-red-800',
        warning: 'bg-yellow-50 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100 ring-1 ring-yellow-200 dark:ring-yellow-800',
        info: 'bg-blue-50 text-blue-800 dark:bg-blue-900 dark:text-blue-100 ring-1 ring-blue-200 dark:ring-blue-800',
    };

    const iconColors = {
        success: 'text-green-500 dark:text-green-300',
        error: 'text-red-500 dark:text-red-300',
        warning: 'text-yellow-500 dark:text-yellow-300',
        info: 'text-blue-500 dark:text-blue-300',
    };
</script>

<template>
    <div
        aria-live="assertive"
        class="pointer-events-none fixed inset-x-0 top-0 z-[100] mt-12 flex flex-col items-center px-4 py-6 sm:items-end sm:p-6"
    >
        <TransitionGroup
            enter-active-class="transform ease-out duration-300 transition"
            enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
            enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-for="toast in toastStore.toasts"
                :key="toast.id"
                class="pointer-events-auto mb-4 w-[280px] rounded-lg shadow-lg ring-1 ring-black/5 sm:w-[350px] lg:w-[450px] dark:ring-white/10"
            >
                <div :class="[typeToColors[toast.type], 'overflow-hidden rounded-lg']">
                    <div class="flex items-start p-4">
                        <div class="flex-shrink-0">
                            <component :is="typeToIcon[toast.type]" :class="[iconColors[toast.type], 'h-5 w-5']" aria-hidden="true" />
                        </div>
                        <div class="ml-3 w-0 flex-1 pt-0.5">
                            <p class="text-sm font-medium">{{ toast.message }}</p>
                        </div>
                        <div class="ml-4 flex flex-shrink-0">
                            <button
                                type="button"
                                class="inline-flex rounded-md p-1 transition-colors hover:bg-black/5 dark:hover:bg-white/10"
                                @click="removeToast(toast.id)"
                            >
                                <span class="sr-only">Close</span>
                                <X :class="[iconColors[toast.type], 'h-4 w-4']" aria-hidden="true" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </TransitionGroup>
    </div>
</template>
