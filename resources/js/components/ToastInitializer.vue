<script setup lang="ts">
    import { useToastStore } from '@/stores/toastStore';
    import { router, usePage } from '@inertiajs/vue3';
    import { onMounted, watch } from 'vue';

    const toastStore = useToastStore();
    const page = usePage();

    // Function to handle flash messages and validation errors
    const processPageMessages = () => {
        const flash = page.props.flash as any;
        const errors = page.props.errors as any;

        // Handle flash messages
        if (flash) {
            if (flash.message) {
                const type = flash.type || 'success';
                toastStore.addToast(flash.message, type, 7000, 'initializer-message');
            } else if (flash.success) {
                toastStore.addToast(flash.success, 'success', 7000, 'initializer-success');
            } else if (flash.error) {
                toastStore.addToast(flash.error, 'error', 7000, 'initializer-error');
            } else if (flash.warning) {
                toastStore.addToast(flash.warning, 'warning', 7000, 'initializer-warning');
            } else if (flash.info) {
                toastStore.addToast(flash.info, 'info', 7000, 'initializer-info');
            }
        }

        // Handle validation errors
        if (errors && Object.keys(errors).length > 0) {
            const errorMessages = Object.values(errors).flat();
            const firstError = errorMessages[0] as string;
            const totalErrors = errorMessages.length;

            if (totalErrors === 1) {
                toastStore.addToast(firstError, 'error', 7000, 'initializer-validation-error');
            } else {
                toastStore.addToast(
                    `${totalErrors} validation errors occurred. Please check the form.`,
                    'error',
                    7000,
                    'initializer-validation-errors',
                );
            }
        }
    };

    onMounted(() => {
        // Process initial page messages
        processPageMessages();

        // Listen for Inertia navigation events
        const handleNavigationFinish = (event: any) => {
            // Small delay to ensure page props are updated
            setTimeout(processPageMessages, 50);
        };

        // Use Inertia's router event listeners
        router.on('finish', handleNavigationFinish);

        // Cleanup on unmount
        return () => {
            router.off('finish', handleNavigationFinish);
        };
    });

    // Watch for changes in page props
    watch(
        () => [page.props.flash, page.props.errors],
        () => {
            processPageMessages();
        },
        { deep: true },
    );
</script>

<template>
    <!-- This component doesn't render anything -->
</template>
