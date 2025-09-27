import { useToastStore } from '@/stores/toastStore';
import { usePage } from '@inertiajs/vue3';
import { onMounted } from 'vue';

export function useToast() {
    const toastStore = useToastStore();
    const page = usePage();

    // Show toast from flash messages and validation errors
    const showFlashToasts = () => {
        const flash = page.props.flash as any;
        const errors = page.props.errors as any;

        // Handle flash messages
        if (flash?.message) {
            toastStore.addToast(flash.message, flash.type || 'success', 7000, 'useToast-message');
        } else if (flash?.success) {
            toastStore.addToast(flash.success, 'success', 7000, 'useToast-success');
        } else if (flash?.error) {
            toastStore.addToast(flash.error, 'error');
        } else if (flash?.warning) {
            toastStore.addToast(flash.warning, 'warning');
        } else if (flash?.info) {
            toastStore.addToast(flash.info, 'info');
        }

        // Handle validation errors
        if (errors && Object.keys(errors).length > 0) {
            const errorMessages = Object.values(errors).flat();
            const firstError = errorMessages[0] as string;
            const totalErrors = errorMessages.length;

            if (totalErrors === 1) {
                toastStore.addToast(firstError, 'error', 7000, 'useToast-validation-error');
            } else {
                toastStore.addToast(`${totalErrors} validation errors occurred. Please check the form.`, 'error', 7000, 'useToast-validation-errors');
            }
        }
    };

    const success = (message: string, duration = 7000) => {
        toastStore.addToast(message, 'success', duration, 'useToast-success');
    };

    const error = (message: string, duration = 7000) => {
        toastStore.addToast(message, 'error', duration, 'useToast-error');
    };

    const warning = (message: string, duration = 7000) => {
        toastStore.addToast(message, 'warning', duration, 'useToast-warning');
    };

    const info = (message: string, duration = 7000) => {
        toastStore.addToast(message, 'info', duration, 'useToast-info');
    };

    // Set up flash toasts on component mount
    const setupFlashToasts = () => {
        onMounted(() => {
            showFlashToasts();

            // Set up event listener for navigation events
            const handleInertiaSuccess = () => {
                showFlashToasts();
            };

            // Remove any existing listeners first to prevent duplicates
            window.removeEventListener('inertia:success', handleInertiaSuccess);

            // Add the listener
            window.addEventListener('inertia:success', handleInertiaSuccess);
        });
    };

    return {
        showFlashToasts,
        setupFlashToasts,
        success,
        error,
        warning,
        info,
    };
}