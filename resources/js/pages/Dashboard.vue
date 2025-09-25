<script setup lang="ts">
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
    import AppLayout from '@/layouts/AppLayout.vue';
    import { Head } from '@inertiajs/vue3';
    import { Calendar, CalendarCheck, CalendarX, Clock } from 'lucide-vue-next';

    interface DashboardStats {
        totalAvailabilities: number;
        totalUnavailabilities: number;
        totalBookings: number;
        pendingBookings: number;
    }

    defineProps<{
        stats: DashboardStats;
    }>();
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
                    <CardTitle class="text-sm font-medium">Total Availabilities</CardTitle>
                    <Calendar class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.totalAvailabilities }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Unavailabilities</CardTitle>
                    <CalendarX class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.totalUnavailabilities }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Bookings</CardTitle>
                    <CalendarCheck class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stats.totalBookings }}</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Pending Bookings</CardTitle>
                    <Clock class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold text-orange-600">{{ stats.pendingBookings }}</div>
                </CardContent>
            </Card>
        </div>

        <!-- Recent Activity Section -->
        <div class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Recent Bookings</CardTitle>
                    <CardDescription>Latest booking requests</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="text-sm text-muted-foreground">No recent bookings to display.</div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Upcoming Availabilities</CardTitle>
                    <CardDescription>Your next available slots</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="text-sm text-muted-foreground">No upcoming availabilities to display.</div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
