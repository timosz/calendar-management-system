import {
    bulkAction as bookingsBulkAction,
    cancel as bookingsCancel,
    confirm as bookingsConfirm,
    destroy as bookingsDestroy,
    reject as bookingsReject,
    show as bookingsShow,
} from '@/actions/App/Http/Controllers/Admin/BookingController';
import { router } from '@inertiajs/vue3';

export function useBookingActions() {
    const confirmBooking = (bookingId: number) => {
        router.patch(
            bookingsConfirm.url(bookingId),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const rejectBooking = (bookingId: number) => {
        router.patch(
            bookingsReject.url(bookingId),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const cancelBooking = (bookingId: number) => {
        router.patch(
            bookingsCancel.url(bookingId),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    const deleteBooking = (bookingId: number) => {
        if (confirm('Are you sure you want to delete this booking?')) {
            router.delete(bookingsDestroy.url(bookingId), {
                preserveScroll: true,
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
                onSuccess,
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
