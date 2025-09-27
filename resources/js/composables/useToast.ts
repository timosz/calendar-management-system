import { useToastStore } from '@/stores/toastStore';

export function useToast() {
    const toastStore = useToastStore();

    const success = (message: string, duration = 7000) => {
        toastStore.addToast(message, 'success', duration);
    };

    const error = (message: string, duration = 7000) => {
        toastStore.addToast(message, 'error', duration);
    };

    const warning = (message: string, duration = 7000) => {
        toastStore.addToast(message, 'warning', duration);
    };

    const info = (message: string, duration = 7000) => {
        toastStore.addToast(message, 'info', duration);
    };

    return {
        success,
        error,
        warning,
        info,
    };
}
