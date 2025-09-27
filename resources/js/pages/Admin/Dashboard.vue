<script setup lang="ts">
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
    import AppLayout from '@/layouts/AppLayout.vue';
    import { Head, Link } from '@inertiajs/vue3';
    import { AlertCircle, Calendar, CalendarCheck, CalendarX, CheckCircle, Clock, XCircle } from 'lucide-vue-next';

    interface Stats {
        total_bookings: number;
        pending_bookings: number;
        confirmed_bookings: number;
        this_week_bookings: number;
        this_month_bookings: number;
        active_availability_periods: number;
        current_unavailable_periods: number;
    }

    interface Booking {
        id: number;
        booking_date: string;
        start_time: string;
        end_time: string;
        status: 'pending' | 'confirmed' | 'rejected' | 'cancelled';
        user?: {
            name: string;
            email: string;
        };
        created_at: string;
    }

    interface UnavailablePeriod {
        id: number;
        title: string;
        start_date: string;
        end_date: string;
        description?: string;
    }

    interface StatusDistribution {
        [key: string]: number;
    }

    interface TrendData {
        week: string;
        bookings: number;
    }

    interface MonthlyData {
        month: string;
        bookings: number;
    }

    interface AvailabilitySummary {
        [key: string]: {
            day: string;
            periods: Array<{
                time_range: string;
                is_active: boolean;
            }>;
        };
    }

    defineProps<{
        stats: Stats;
        recentBookings: Booking[];
        upcomingBookings: Booking[];
        todaysBookings: Booking[];
        currentUnavailablePeriods: UnavailablePeriod[];
        statusDistribution: StatusDistribution;
        weeklyTrend: TrendData[];
        monthlyStats: MonthlyData[];
        availabilitySummary: AvailabilitySummary;
        currentDate: string;
        currentTime: string;
    }>();

    const getStatusBadgeVariant = (status: string) => {
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

    const getStatusIcon = (status: string) => {
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
                    <div class="text-2xl font-bold text-green-600">{{ stats.active_availability_periods }}</div>
                    <p class="text-xs text-muted-foreground">Current time slots</p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Unavailable Periods</CardTitle>
                    <CalendarX class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-red-600">{{ stats.current_unavailable_periods }}</div>
                    <p class="text-xs text-muted-foreground">Currently blocked</p>
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
                                    <p class="text-xs text-muted-foreground" v-if="booking.user">
                                        {{ booking.user.name }}
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
                    <CardTitle>Current Unavailable Periods</CardTitle>
                    <CardDescription>Active blocks on your calendar</CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div v-if="currentUnavailablePeriods.length === 0" class="text-sm text-muted-foreground">
                        No unavailable periods active today.
                    </div>
                    <div v-else class="space-y-2">
                        <div v-for="period in currentUnavailablePeriods" :key="period.id" class="rounded-lg border p-2">
                            <p class="text-sm font-medium">{{ period.title }}</p>
                            <p class="text-xs text-muted-foreground">{{ formatDate(period.start_date) }} - {{ formatDate(period.end_date) }}</p>
                            <p v-if="period.description" class="mt-1 text-xs text-muted-foreground">
                                {{ period.description }}
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
                                    <p class="text-sm font-medium" v-if="booking.user">
                                        {{ booking.user.name }}
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
                                    <p class="text-sm font-medium" v-if="booking.user">
                                        {{ booking.user.name }}
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
                        <div v-if="summary.periods.length === 0" class="text-xs text-muted-foreground">No availability</div>
                        <div v-else class="space-y-1">
                            <div
                                v-for="(period, index) in summary.periods"
                                :key="index"
                                class="rounded p-1 text-xs"
                                :class="
                                    period.is_active
                                        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                                "
                            >
                                {{ period.time_range }}
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </AppLayout>
</template>
