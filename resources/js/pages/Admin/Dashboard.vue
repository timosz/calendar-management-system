<script setup lang="ts">
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
    import AppLayout from '@/layouts/AppLayout.vue';
    import type { BookingStatus, DashboardPageProps, Restriction, RestrictionType } from '@/types';
    import { Head, Link } from '@inertiajs/vue3';
    import { AlertCircle, Calendar, CalendarCheck, CalendarX, CheckCircle, Clock, XCircle } from 'lucide-vue-next';

    defineProps<DashboardPageProps>();

    const getStatusBadgeVariant = (status: BookingStatus) => {
        switch (status) {
            case 'confirmed':
                return 'default';
            case 'pending':
                return 'secondary';
            case 'rejected':
                return 'destructive';
            case 'cancelled':
                return 'outline';
            default:
                return 'secondary';
        }
    };

    const getStatusIcon = (status: BookingStatus) => {
        switch (status) {
            case 'confirmed':
                return CheckCircle;
            case 'pending':
                return Clock;
            case 'rejected':
                return XCircle;
            case 'cancelled':
                return AlertCircle;
            default:
                return Clock;
        }
    };

    const getRestrictionTypeColor = (type: RestrictionType) => {
        switch (type) {
            case 'holiday':
                return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200';
            case 'break':
                return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
            case 'meeting':
                return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200';
            case 'personal':
                return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
            case 'maintenance':
                return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
            default:
                return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200';
        }
    };

    const formatTime = (time: string) => {
        return new Date(`2000-01-01T${time}`).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        });
    };

    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
        });
    };

    const formatRestrictionDisplay = (restriction: Restriction) => {
        let display = restriction.reason || `${restriction.type.charAt(0).toUpperCase() + restriction.type.slice(1)} Period`;

        if (restriction.start_time && restriction.end_time) {
            display += ` (${formatTime(restriction.start_time)} - ${formatTime(restriction.end_time)})`;
        } else {
            display += ' (All Day)';
        }

        return display;
    };
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Dashboard</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Overview of your calendar management system</p>
            </div>
        </template>

        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Bookings</CardTitle>
                    <CalendarCheck class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.total_bookings }}</div>
                    <p class="text-xs text-muted-foreground">{{ stats.this_month_bookings }} this month</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Pending Bookings</CardTitle>
                    <Clock class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-orange-600">{{ stats.pending_bookings }}</div>
                    <p class="text-xs text-muted-foreground">Awaiting your response</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Active Availabilities</CardTitle>
                    <Calendar class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-green-600">{{ stats.active_availabilities }}</div>
                    <p class="text-xs text-muted-foreground">Current time slots</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Current Restrictions</CardTitle>
                    <CalendarX class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-red-600">{{ stats.current_restrictions }}</div>
                    <p class="text-xs text-muted-foreground">Active today</p>
                </CardContent>
            </Card>
        </div>

        <!-- Today's Overview -->
        <div class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Today's Bookings</CardTitle>
                    <CardDescription>{{ currentDate }}</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div v-if="todaysBookings.length === 0" class="text-sm text-muted-foreground">No bookings scheduled for today.</div>
                    <div v-else class="space-y-2">
                        <div v-for="booking in todaysBookings" :key="booking.id" class="flex items-center justify-between rounded-lg border p-2">
                            <div class="flex items-center space-x-2">
                                <component :is="getStatusIcon(booking.status)" class="h-4 w-4" />
                                <div>
                                    <p class="text-sm font-medium">{{ formatTime(booking.start_time) }} - {{ formatTime(booking.end_time) }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ booking.client_name }}
                                    </p>
                                </div>
                            </div>
                            <Badge :variant="getStatusBadgeVariant(booking.status)">
                                {{ booking.status }}
                            </Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Current Restrictions</CardTitle>
                    <CardDescription>Active blocks on your calendar</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div v-if="currentRestrictions.length === 0" class="text-sm text-muted-foreground">No restrictions active today.</div>
                    <div v-else class="space-y-2">
                        <div v-for="restriction in currentRestrictions" :key="restriction.id" class="rounded-lg border p-2">
                            <div class="mb-1 flex items-start justify-between">
                                <p class="text-sm font-medium">{{ formatRestrictionDisplay(restriction) }}</p>
                                <span class="rounded px-1.5 py-0.5 text-xs font-medium" :class="getRestrictionTypeColor(restriction.type)">
                                    {{ restriction.type }}
                                </span>
                            </div>
                            <p class="text-xs text-muted-foreground">
                                {{ formatDate(restriction.start_date) }}
                                <span v-if="restriction.start_date !== restriction.end_date"> - {{ formatDate(restriction.end_date) }} </span>
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Recent Activity Section -->
        <div class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>Recent Bookings</CardTitle>
                        <CardDescription>Latest booking requests</CardDescription>
                    </div>
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/admin/bookings">View All</Link>
                    </Button>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div v-if="recentBookings.length === 0" class="text-sm text-muted-foreground">No recent bookings to display.</div>
                    <div v-else class="space-y-3">
                        <div v-for="booking in recentBookings" :key="booking.id" class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <component :is="getStatusIcon(booking.status)" class="h-4 w-4" />
                                <div>
                                    <p class="text-sm font-medium">
                                        {{ booking.client_name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatDate(booking.booking_date) }} at {{ formatTime(booking.start_time) }}
                                    </p>
                                </div>
                            </div>
                            <Badge :variant="getStatusBadgeVariant(booking.status)">
                                {{ booking.status }}
                            </Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <div>
                        <CardTitle>Upcoming Bookings</CardTitle>
                        <CardDescription>Your next scheduled appointments</CardDescription>
                    </div>
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/admin/bookings">Manage</Link>
                    </Button>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div v-if="upcomingBookings.length === 0" class="text-sm text-muted-foreground">No upcoming bookings to display.</div>
                    <div v-else class="space-y-3">
                        <div v-for="booking in upcomingBookings.slice(0, 5)" :key="booking.id" class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <component :is="getStatusIcon(booking.status)" class="h-4 w-4" />
                                <div>
                                    <p class="text-sm font-medium">
                                        {{ booking.client_name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ formatDate(booking.booking_date) }} at {{ formatTime(booking.start_time) }}
                                    </p>
                                </div>
                            </div>
                            <Badge :variant="getStatusBadgeVariant(booking.status)">
                                {{ booking.status }}
                            </Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Availability Overview -->
        <Card v-if="Object.keys(availabilitySummary).length > 0">
            <CardHeader>
                <CardTitle>Weekly Availability Overview</CardTitle>
                <CardDescription>Your current availability schedule</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                    <div v-for="(summary, dayKey) in availabilitySummary" :key="dayKey" class="rounded-lg border p-3">
                        <h4 class="mb-2 text-sm font-medium">{{ summary.day }}</h4>
                        <div v-if="summary.availabilities.length === 0" class="text-xs text-muted-foreground">No availability</div>
                        <div v-else class="space-y-1">
                            <div
                                v-for="(availability, index) in summary.availabilities"
                                :key="index"
                                class="rounded p-1 text-xs"
                                :class="
                                    availability.is_active
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                                "
                            >
                                {{ availability.time_range }}
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
