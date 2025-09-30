<script setup lang="ts">
    import { Badge } from '@/components/ui/badge';
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import type { BookingDetailItem } from '@/types';
    import { AlertCircle, Calendar, Clock, FileText, Mail, Phone, User } from 'lucide-vue-next';

    interface Props {
        booking: BookingDetailItem;
    }

    defineProps<Props>();

    const getStatusColor = (status: string) => {
        const colors = {
            pending: 'bg-yellow-100 text-yellow-800 border-yellow-300',
            confirmed: 'bg-green-100 text-green-800 border-green-300',
            rejected: 'bg-red-100 text-red-800 border-red-300',
            cancelled: 'bg-gray-100 text-gray-800 border-gray-300',
        };
        return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800';
    };

    const formatDuration = (minutes: number) => {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        if (hours > 0) {
            return `${hours}h ${mins}m`;
        }
        return `${mins}m`;
    };
</script>

<template>
    <div class="grid gap-6 md:grid-cols-2">
        <!-- Booking Information -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center justify-between">
                    <span>Booking Information</span>
                    <Badge :class="getStatusColor(booking.status)" variant="outline">
                        {{ booking.status_label }}
                    </Badge>
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex items-start gap-3">
                    <Calendar class="mt-0.5 h-5 w-5 text-muted-foreground" />
                    <div class="flex-1">
                        <p class="text-sm font-medium">Date</p>
                        <p class="text-sm text-muted-foreground">{{ booking.booking_date_formatted }}</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <Clock class="mt-0.5 h-5 w-5 text-muted-foreground" />
                    <div class="flex-1">
                        <p class="text-sm font-medium">Time</p>
                        <p class="text-sm text-muted-foreground">
                            {{ booking.start_time }} - {{ booking.end_time }}
                            <span class="text-xs">({{ formatDuration(booking.duration_minutes) }})</span>
                        </p>
                    </div>
                </div>

                <div v-if="booking.notes" class="flex items-start gap-3">
                    <FileText class="mt-0.5 h-5 w-5 text-muted-foreground" />
                    <div class="flex-1">
                        <p class="text-sm font-medium">Notes</p>
                        <p class="text-sm text-muted-foreground">{{ booking.notes }}</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <FileText class="mt-0.5 h-5 w-5 text-muted-foreground" />
                    <div class="flex-1">
                        <p class="text-sm font-medium">Created</p>
                        <p class="text-sm text-muted-foreground">
                            {{ new Date(booking.created_at).toLocaleString() }}
                        </p>
                    </div>
                </div>

                <div v-if="booking.google_calendar_event_id" class="flex items-start gap-3">
                    <Calendar class="mt-0.5 h-5 w-5 text-muted-foreground" />
                    <div class="flex-1">
                        <p class="text-sm font-medium">Google Calendar</p>
                        <p class="font-mono text-sm text-xs text-muted-foreground">
                            {{ booking.google_calendar_event_id }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Client Information -->
        <Card>
            <CardHeader>
                <CardTitle>Client Information</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex items-start gap-3">
                    <User class="mt-0.5 h-5 w-5 text-muted-foreground" />
                    <div class="flex-1">
                        <p class="text-sm font-medium">Name</p>
                        <p class="text-sm text-muted-foreground">{{ booking.client_name }}</p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <Mail class="mt-0.5 h-5 w-5 text-muted-foreground" />
                    <div class="flex-1">
                        <p class="text-sm font-medium">Email</p>
                        <a :href="`mailto:${booking.client_email}`" class="text-sm text-primary hover:underline">
                            {{ booking.client_email }}
                        </a>
                    </div>
                </div>

                <div v-if="booking.client_phone" class="flex items-start gap-3">
                    <Phone class="mt-0.5 h-5 w-5 text-muted-foreground" />
                    <div class="flex-1">
                        <p class="text-sm font-medium">Phone</p>
                        <a :href="`tel:${booking.client_phone}`" class="text-sm text-primary hover:underline">
                            {{ booking.client_phone }}
                        </a>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Validation Errors -->
        <Card v-if="booking.has_conflicts" class="md:col-span-2">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-destructive">
                    <AlertCircle class="h-5 w-5" />
                    <span>Booking Conflicts</span>
                </CardTitle>
            </CardHeader>
            <CardContent>
                <ul class="space-y-2">
                    <li v-for="(error, index) in booking.validation_errors" :key="index" class="flex items-start gap-2 text-sm text-destructive">
                        <span class="mt-1">•</span>
                        <span>{{ error }}</span>
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>
</template>
