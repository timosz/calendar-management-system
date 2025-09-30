<script setup lang="ts">
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import type { BookingStats } from '@/types';

    interface Props {
        stats: BookingStats;
    }

    defineProps<Props>();

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
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Bookings</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.total }}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Pending</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Confirmed</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-green-600">{{ stats.confirmed }}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Rejected</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-red-600">{{ stats.rejected }}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Total Duration</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ formatDuration(stats.total_duration_minutes) }}</div>
            </CardContent>
        </Card>
    </div>
</template>
