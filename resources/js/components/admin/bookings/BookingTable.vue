<script setup lang="ts">
    import { Card, CardContent } from '@/components/ui/card';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
    import type { BookingIndexItem } from '@/types';
    import { computed } from 'vue';
    import BookingTableRow from './BookingTableRow.vue';

    interface Props {
        bookings: BookingIndexItem[];
        selectedBookings: number[];
        emptyMessage?: string;
        showActions?: boolean;
    }

    interface Emits {
        (e: 'toggle-select-all'): void;
        (e: 'toggle-select', bookingId: number): void;
        (e: 'view', bookingId: number): void;
        (e: 'confirm', bookingId: number): void;
        (e: 'reject', bookingId: number): void;
        (e: 'cancel', bookingId: number): void;
        (e: 'delete', bookingId: number): void;
    }

    const props = withDefaults(defineProps<Props>(), {
        emptyMessage: 'No bookings found',
        showActions: true,
    });

    const emit = defineEmits<Emits>();

    const allSelected = computed(() => {
        return props.bookings.length > 0 && props.selectedBookings.length === props.bookings.length;
    });

    const someSelected = computed(() => {
        return props.selectedBookings.length > 0 && props.selectedBookings.length < props.bookings.length;
    });
</script>

<template>
    <Card>
        <CardContent class="p-0">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-12">
                            <Checkbox :checked="allSelected" :indeterminate="someSelected" @update:checked="emit('toggle-select-all')" />
                        </TableHead>
                        <TableHead>Client</TableHead>
                        <TableHead>Date & Time</TableHead>
                        <TableHead>Duration</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead>Contact</TableHead>
                        <TableHead class="w-12"></TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="bookings.length === 0">
                        <TableCell colspan="7" class="py-8 text-center text-muted-foreground">
                            {{ emptyMessage }}
                        </TableCell>
                    </TableRow>
                    <BookingTableRow
                        v-for="booking in bookings"
                        :key="booking.id"
                        :booking="booking"
                        :selected="selectedBookings.includes(booking.id)"
                        :show-actions="showActions"
                        @toggle-select="emit('toggle-select', booking.id)"
                        @view="emit('view', booking.id)"
                        @confirm="emit('confirm', booking.id)"
                        @reject="emit('reject', booking.id)"
                        @cancel="emit('cancel', booking.id)"
                        @delete="emit('delete', booking.id)"
                    />
                </TableBody>
            </Table>
        </CardContent>
    </Card>
</template>
