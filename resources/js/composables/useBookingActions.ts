import {
    bulkAction as bookingsBulkAction,
    cancel as bookingsCancel,
    confirm as bookingsConfirm,
    destroy as bookingsDestroy,
    reject as bookingsReject,
    show as bookingsShow,
} from '@/actions/App/Http/Controllers/Admin/BookingController';
import { useToast } from '@/composables/useToast';
import { router, usePage } from '@inertiajs/vue3';

export function useBookingActions() {
    const { success, error } = useToast();
    const page = usePage();

    const confirmBooking = (bookingId: number) => {
        router.patch(
            bookingsConfirm.url(bookingId),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    const flashMessage = page.props.flash?.success;
                    if (flashMessage) {
                        success(flashMessage);
                    }
                },
                onError: (errors) => {
                    const flashError = page.props.flash?.error;
                    if (flashError) {
                        error(flashError);
                    } else if (Object.keys(errors).length > 0) {
                        const firstErrorKey = Object.keys(errors)[0];
                        const firstErrorMessage = errors[firstErrorKey];
                        error(Array.isArray(firstErrorMessage) ? firstErrorMessage[0] : firstErrorMessage);
                    }
                },
            },
        );
    };

    const rejectBooking = (bookingId: number) => {
        router.patch(
            bookingsReject.url(bookingId),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    const flashMessage = page.props.flash?.success;
                    if (flashMessage) {
                        success(flashMessage);
                    }
                },
                onError: (errors) => {
                    const flashError = page.props.flash?.error;
                    if (flashError) {
                        error(flashError);
                    } else if (Object.keys(errors).length > 0) {
                        const firstErrorKey = Object.keys(errors)[0];
                        const firstErrorMessage = errors[firstErrorKey];
                        error(Array.isArray(firstErrorMessage) ? firstErrorMessage[0] : firstErrorMessage);
                    }
                },
            },
        );
    };

    const cancelBooking = (bookingId: number) => {
        router.patch(
            bookingsCancel.url(bookingId),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    const flashMessage = page.props.flash?.success;
                    if (flashMessage) {
                        success(flashMessage);
                    }
                },
                onError: (errors) => {
                    const flashError = page.props.flash?.error;
                    if (flashError) {
                        error(flashError);
                    } else if (Object.keys(errors).length > 0) {
                        const firstErrorKey = Object.keys(errors)[0];
                        const firstErrorMessage = errors[firstErrorKey];
                        error(Array.isArray(firstErrorMessage) ? firstErrorMessage[0] : firstErrorMessage);
                    }
                },
            },
        );
    };

    const deleteBooking = (bookingId: number) => {
        if (confirm('Are you sure you want to delete this booking?')) {
            router.delete(bookingsDestroy.url(bookingId), {
                preserveScroll: true,
                onSuccess: () => {
                    const flashMessage = page.props.flash?.success;
                    if (flashMessage) {
                        success(flashMessage);
                    }
                },
                onError: (errors) => {
                    const flashError = page.props.flash?.error;
                    if (flashError) {
                        error(flashError);
                    } else if (Object.keys(errors).length > 0) {
                        const firstErrorKey = Object.keys(errors)[0];
                        const firstErrorMessage = errors[firstErrorKey];
                        error(Array.isArray(firstErrorMessage) ? firstErrorMessage[0] : firstErrorMessage);
                    }
                },
            });
        }
    };

    const viewBooking = (bookingId: number) => {
        router.visit(bookingsShow.url(bookingId));
    };

    const executeBulkAction = (action: 'confirm' | 'reject' | 'cancel' | 'delete', bookingIds: number[], onSuccess?: () => void) => {
        router.patch(
            bookingsBulkAction.url(),
            {
                action,
                booking_ids: bookingIds,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    const flashMessage = page.props.flash?.success;
                    if (flashMessage) {
                        success(flashMessage);
                    }
                    onSuccess?.();
                },
                onError: (errors) => {
                    const flashError = page.props.flash?.error;
                    if (flashError) {
                        error(flashError);
                    } else if (Object.keys(errors).length > 0) {
                        const firstErrorKey = Object.keys(errors)[0];
                        const firstErrorMessage = errors[firstErrorKey];
                        error(Array.isArray(firstErrorMessage) ? firstErrorMessage[0] : firstErrorMessage);
                    }
                },
            },
        );
    };

    return {
        confirmBooking,
        rejectBooking,
        cancelBooking,
        deleteBooking,
        viewBooking,
        executeBulkAction,
    };
}
