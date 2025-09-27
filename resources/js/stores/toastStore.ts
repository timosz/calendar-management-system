import { defineStore } from 'pinia';

interface Toast {
    id: number;
    message: string;
    type: 'success' | 'error' | 'warning' | 'info';
    duration: number;
    timestamp: number;
    source: string;
}

export const useToastStore = defineStore('toast', {
    state: () => ({
        toasts: [] as Toast[],
        lastToastId: 0,
    }),

    actions: {
        addToast(message: string, type: Toast['type'] = 'success', duration = 7000, source = 'unknown'): number | void {
            // Don't add toasts with empty or undefined messages
            if (!message || message === type) {
                return;
            }

            // Don't add exactly duplicate messages
            if (this.toasts.some((toast) => toast.message === message && toast.type === type)) {
                return;
            }

            // Clear all existing toasts first
            this.clearToasts();

            // Create new toast
            const id = ++this.lastToastId;

            // Add toast to store
            this.toasts.push({
                id,
                message,
                type,
                duration,
                timestamp: Date.now(),
                source: source,
            });

            // Automatically remove the toast after duration
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

        processFlashMessages(flash: any) {
            if (!flash) return;

            if (flash.success) {
                this.addToast(flash.success, 'success', 7000, 'flash-success');
            }
            if (flash.error) {
                this.addToast(flash.error, 'error', 7000, 'flash-error');
            }
            if (flash.warning) {
                this.addToast(flash.warning, 'warning', 7000, 'flash-warning');
            }
            if (flash.info) {
                this.addToast(flash.info, 'info', 7000, 'flash-info');
            }
        },

        // Updated to handle both flash messages and validation errors
        setupFlashListener() {
            // Check for flash messages and validation errors on page load
            const checkInitialMessages = () => {
                // Try multiple ways to access Inertia page data
                let pageProps = null;

                // Method 1: Check window.Inertia (older versions)
                if ((window as any)?.Inertia?.page?.props) {
                    pageProps = (window as any).Inertia.page.props;
                }

                // Method 2: Check for newer Inertia structure
                if (!pageProps && (window as any)?.__inertia?.page?.props) {
                    pageProps = (window as any).__inertia.page.props;
                }

                // Method 3: Try to get from app element data attribute
                if (!pageProps) {
                    const appElement = document.getElementById('app');
                    if (appElement && appElement.hasAttribute('data-page')) {
                        try {
                            const pageData = JSON.parse(appElement.getAttribute('data-page') || '{}');
                            pageProps = pageData.props;
                        } catch (e) {
                            console.warn('Failed to parse page data from app element:', e);
                        }
                    }
                }

                if (!pageProps) {
                    console.warn('Toast Debug - No page props found');
                    return;
                }

                const flash = pageProps.flash;
                const errors = pageProps.errors;

                // Handle flash messages
                if (flash) {
                    if (flash.message) {
                        const type = flash.type || 'success';
                        this.addToast(flash.message, type, 7000, 'store-initial-message');
                    } else if (flash.success) {
                        this.addToast(flash.success, 'success', 7000, 'store-initial-success');
                    } else if (flash.error) {
                        this.addToast(flash.error, 'error');
                    }
                }

                // Handle validation errors
                if (errors && Object.keys(errors).length > 0) {
                    const errorMessages = Object.values(errors).flat();
                    const firstError = errorMessages[0] as string;
                    const totalErrors = errorMessages.length;

                    if (totalErrors === 1) {
                        this.addToast(firstError, 'error', 7000, 'validation-error');
                    } else {
                        this.addToast(`${totalErrors} validation errors occurred. Please check the form.`, 'error', 7000, 'validation-errors');
                    }
                }
            };

            // Handle messages during Inertia page visits
            const handleInertiaSuccess = (event: any) => {
                const pageProps = event.detail.page.props;
                const flash = pageProps.flash;
                const errors = pageProps.errors;

                // Handle flash messages
                if (flash) {
                    if (flash.message) {
                        const type = flash.type || 'success';
                        this.addToast(flash.message, type, 7000, 'store-inertia-message');
                    } else if (flash.success) {
                        this.addToast(flash.success, 'success', 7000, 'store-inertia-success');
                    } else if (flash.error) {
                        this.addToast(flash.error, 'error');
                    }
                }

                // Handle validation errors
                if (errors && Object.keys(errors).length > 0) {
                    const errorMessages = Object.values(errors).flat();
                    const firstError = errorMessages[0] as string;
                    const totalErrors = errorMessages.length;

                    if (totalErrors === 1) {
                        this.addToast(firstError, 'error', 7000, 'validation-error');
                    } else {
                        this.addToast(`${totalErrors} validation errors occurred. Please check the form.`, 'error', 7000, 'validation-errors');
                    }
                }
            };

            // Add listeners if we're in browser environment
            if (typeof window !== 'undefined') {
                // Use setTimeout to ensure DOM is ready
                setTimeout(() => {
                    checkInitialMessages();
                }, 100);

                // Listen for future page visits - try multiple event types
                window.addEventListener('inertia:success', handleInertiaSuccess);
                window.addEventListener('inertia:finish', handleInertiaSuccess);
            }
        },
    },
});
