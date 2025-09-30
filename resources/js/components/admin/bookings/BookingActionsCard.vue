<script setup lang="ts">
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
    import type { BookingDetailItem } from '@/types';
    import { ArrowLeft, Ban, Check, Trash2, X } from 'lucide-vue-next';

    interface Props {
        booking: BookingDetailItem;
    }

    interface Emits {
        (e: 'confirm'): void;
        (e: 'reject'): void;
        (e: 'cancel'): void;
        (e: 'delete'): void;
        (e: 'back'): void;
    }

    defineProps<Props>();
    const emit = defineEmits<Emits>();
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Actions</CardTitle>
            <CardDescription>Manage this booking</CardDescription>
        </CardHeader>
        <CardContent class="space-y-3">
            <div class="flex flex-wrap gap-2">
                <Button v-if="booking.can_be_confirmed" @click="emit('confirm')" variant="default" class="flex-1" :disabled="booking.has_conflicts">
                    <Check class="mr-2 h-4 w-4" />
                    Confirm Booking
                </Button>

                <Button v-if="booking.can_be_rejected" @click="emit('reject')" variant="outline" class="flex-1">
                    <X class="mr-2 h-4 w-4" />
                    Reject
                </Button>

                <Button v-if="booking.can_be_cancelled" @click="emit('cancel')" variant="outline" class="flex-1">
                    <Ban class="mr-2 h-4 w-4" />
                    Cancel
                </Button>
            </div>

            <div v-if="booking.has_conflicts" class="rounded-md bg-muted p-3 text-sm text-muted-foreground">
                <p class="font-medium text-destructive">Cannot confirm booking</p>
                <p class="mt-1 text-xs">This booking has conflicts that must be resolved first.</p>
            </div>

            <div class="space-y-2 border-t pt-3">
                <Button @click="emit('back')" variant="outline" class="w-full">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to Bookings
                </Button>

                <Button @click="emit('delete')" variant="destructive" class="w-full">
                    <Trash2 class="mr-2 h-4 w-4" />
                    Delete Booking
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
