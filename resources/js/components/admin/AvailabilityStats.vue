<script setup lang="ts">
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import { Calendar, Clock } from 'lucide-vue-next';

    interface Props {
        totalActiveDays: number;
        totalWorkingHours: number;
        averageDailyHours: number;
    }

    defineProps<Props>();
</script>

<template>
    <div class="grid gap-4 md:grid-cols-3">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Active Days</CardTitle>
                <Calendar class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ totalActiveDays }}</div>
                <p class="text-xs text-muted-foreground">out of 7 days</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Weekly Hours</CardTitle>
                <Clock class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ totalWorkingHours.toFixed(1) }}</div>
                <p class="text-xs text-muted-foreground">hours per week</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">Average Daily</CardTitle>
                <Clock class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ averageDailyHours.toFixed(1) }}</div>
                <p class="text-xs text-muted-foreground">hours per active day</p>
            </CardContent>
        </Card>
    </div>
</template>
