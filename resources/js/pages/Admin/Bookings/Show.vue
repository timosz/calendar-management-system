<script setup lang="ts">
    import { index as bookingsIndex } from '@/actions/App/Http/Controllers/Admin/BookingController';
    import BookingActionsCard from '@/components/admin/bookings/BookingActionsCard.vue';
    import BookingDetailCard from '@/components/admin/bookings/BookingDetailCard.vue';
    import { useBookingActions } from '@/composables/useBookingActions';
    import AppLayout from '@/layouts/AppLayout.vue';
    import type { BookingDetailItem } from '@/types';
    import { router } from '@inertiajs/vue3';

    interface Props {
        booking: BookingDetailItem;
    }

    const props = defineProps<Props>();

    const { confirmBooking, rejectBooking, cancelBooking, deleteBooking } = useBookingActions();

    const handleConfirm = () => {
        if (props.booking.has_conflicts) {
            alert('Cannot confirm booking with conflicts');
            return;
        }
        confirmBooking(props.booking.id);
    };

    const handleReject = () => {
        rejectBooking(props.booking.id);
    };

    const handleCancel = () => {
        cancelBooking(props.booking.id);
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
            deleteBooking(props.booking.id);
        }
    };

    const goBack = () => {
        router.visit(bookingsIndex.url());
    };
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div>
                <h1 class="text-3xl font-bold tracking-tight">Booking Details</h1>
                <p class="text-muted-foreground">View and manage booking information</p>
            </div>

            <!-- Content -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Main Content (2 columns) -->
                <div class="lg:col-span-2">
                    <BookingDetailCard :booking="booking" />
                </div>

                <!-- Sidebar (1 column) -->
                <div>
                    <BookingActionsCard
                        :booking="booking"
                        @confirm="handleConfirm"
                        @reject="handleReject"
                        @cancel="handleCancel"
                        @delete="handleDelete"
                        @back="goBack"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
