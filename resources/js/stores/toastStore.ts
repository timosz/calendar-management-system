import { defineStore } from 'pinia';

interface Toast {
    id: number;
    message: string;
    type: 'success' | 'error' | 'warning' | 'info';
    duration: number;
}

export const useToastStore = defineStore('toast', {
    state: () => ({
        toasts: [] as Toast[],
        lastToastId: 0,
        isListenerSetup: false,
    }),

    actions: {
        addToast(message: string, type: Toast['type'] = 'success', duration = 7000): number | void {
            // Don't add toasts with empty messages
            if (!message) return;

            // Don't add duplicate messages
            if (this.toasts.some((toast) => toast.message === message && toast.type === type)) {
                return;
            }

            const id = ++this.lastToastId;

            this.toasts.push({
                id,
                message,
                type,
                duration,
            });

            // Auto-remove toast after duration
            setTimeout(() => {
                this.removeToast(id);
            }, duration);

            return id;
        },

        removeToast(id: number) {
            this.toasts = this.toasts.filter((toast) => toast.id !== id);
        },

        clearToasts() {
            this.toasts = [];
        },

        // Process flash messages and validation errors from Inertia
        processPageData(pageProps: any) {
            if (!pageProps) return;

            const { flash, errors } = pageProps;

            // Handle flash messages
            if (flash?.success) {
                this.addToast(flash.success, 'success');
            }
            if (flash?.error) {
                this.addToast(flash.error, 'error');
            }
            if (flash?.warning) {
                this.addToast(flash.warning, 'warning');
            }
            if (flash?.info) {
                this.addToast(flash.info, 'info');
            }

            // Handle validation errors
            if (errors && Object.keys(errors).length > 0) {
                const errorMessages = Object.values(errors).flat() as string[];
                const totalErrors = errorMessages.length;

                if (totalErrors === 1) {
                    this.addToast(errorMessages[0], 'error');
                } else {
                    this.addToast(`${totalErrors} validation errors occurred. Please check the form.`, 'error');
                }
            }
        },

        // Set up Inertia event listeners (call once from ToastContainer)
        setupInertiaListener() {
            if (this.isListenerSetup || typeof window === 'undefined') return;

            // Process initial page data
            setTimeout(() => {
                const appElement = document.getElementById('app');
                if (appElement?.dataset.page) {
                    try {
                        const pageData = JSON.parse(appElement.dataset.page);
                        this.processPageData(pageData.props);
                    } catch (e) {
                        console.warn('Failed to parse initial page data for toasts');
                    }
                }
            }, 100);

            // Listen for Inertia navigation
            const handleInertiaFinish = (event: any) => {
                // Be defensive about the event structure
                const pageProps = event?.detail?.page?.props;
                if (pageProps) {
                    this.processPageData(pageProps);
                }
            };

            document.addEventListener('inertia:finish', handleInertiaFinish);
            this.isListenerSetup = true;
        },
    },
});
