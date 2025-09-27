<script setup lang="ts">
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Card } from '@/components/ui/card';
    import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
    import { useToast } from '@/composables/useToast';
    import AppLayout from '@/layouts/AppLayout.vue';
    import { Head, Link, router } from '@inertiajs/vue3';
    import { Clock, Edit, MoreHorizontal, Plus, Power, Trash2 } from 'lucide-vue-next';
    import { ref } from 'vue';

    // Import Wayfinder routes
    import { create, destroy, edit, toggle } from '@/routes/admin/availability-periods';

    interface AvailabilityPeriod {
        id: number;
        day_of_week: number;
        start_time: string;
        end_time: string;
        is_active: boolean;
        user_id: number;
        created_at: string;
        updated_at: string;
    }

    interface Props {
        availabilityPeriods: Record<number, AvailabilityPeriod[]>;
        dayNames: Record<number, string>;
    }

    const props = defineProps<Props>();

    const isDeleting = ref<number | null>(null);
    const isToggling = ref<number | null>(null);

    const { success, error } = useToast();

    const formatTime = (time: string) => {
        return new Date(`2000-01-01T${time}`).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        });
    };

    const calculateDuration = (startTime: string, endTime: string) => {
        const start = new Date(`2000-01-01T${startTime}`);
        const end = new Date(`2000-01-01T${endTime}`);
        const durationHours = (end.getTime() - start.getTime()) / (1000 * 60 * 60);
        return Math.round(durationHours * 100) / 100;
    };

    const deleteAvailabilityPeriod = (id: number) => {
        if (!confirm('Are you sure you want to delete this availability period?')) {
            return;
        }

        isDeleting.value = id;
        router.delete(destroy(id), {
            onSuccess: () => {
                success('Availability period deleted successfully.');
            },
            onError: () => {
                error('Failed to delete availability period.');
            },
            onFinish: () => {
                isDeleting.value = null;
            },
        });
    };

    const toggleAvailabilityPeriod = (period: AvailabilityPeriod) => {
        isToggling.value = period.id;
        router.patch(
            toggle(period.id),
            {},
            {
                onSuccess: () => {
                    const status = !period.is_active ? 'activated' : 'deactivated';
                    success(`Availability period ${status} successfully.`);
                },
                onError: () => {
                    error('Failed to toggle availability period.');
                },
                onFinish: () => {
                    isToggling.value = null;
                },
            },
        );
    };

    const getDayPeriods = (dayOfWeek: number) => {
        return props.availabilityPeriods[dayOfWeek] || [];
    };

    const getAllDays = () => {
        return [1, 2, 3, 4, 5, 6, 0]; // Monday to Sunday
    };
</script>

<template>
    <Head title="Availability Periods" />

    <AppLayout>
        <template #header>
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Availability Periods</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Manage your working hours and availability schedule.</p>
            </div>
        </template>

        <!-- Header Actions -->
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-medium">Weekly Schedule</h2>
                <p class="text-sm text-muted-foreground">Set your available time slots for each day of the week.</p>
            </div>
            <Link :href="create()">
                <Button>
                    <Plus class="mr-2 h-4 w-4" />
                    Add Availability Period
                </Button>
            </Link>
        </div>

        <!-- Weekly Schedule -->
        <div class="space-y-4">
            <div v-for="dayOfWeek in getAllDays()" :key="dayOfWeek" class="space-y-2">
                <!-- Compact Day Header -->
                <div class="flex items-center justify-between border-b border-gray-200 pb-1 dark:border-gray-700">
                    <h3 class="text-lg font-medium">{{ dayNames[dayOfWeek] }}</h3>
                    <Badge variant="outline" class="text-xs">
                        {{ getDayPeriods(dayOfWeek).length }} period{{ getDayPeriods(dayOfWeek).length !== 1 ? 's' : '' }}
                    </Badge>
                </div>

                <!-- No Periods State -->
                <div v-if="getDayPeriods(dayOfWeek).length === 0" class="py-4 text-center text-gray-500">
                    <Clock class="mx-auto mb-1 h-8 w-8 text-gray-400" />
                    <p class="text-sm">No availability periods set</p>
                </div>

                <!-- Periods Grid -->
                <div v-else class="grid gap-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <Card v-for="period in getDayPeriods(dayOfWeek)" :key="period.id" :class="{ 'opacity-60': !period.is_active }" class="p-3">
                        <!-- Compact Card Content -->
                        <div class="space-y-2">
                            <!-- Time Range and Status -->
                            <div class="flex items-center justify-between">
                                <div class="text-sm font-medium">{{ formatTime(period.start_time) }} - {{ formatTime(period.end_time) }}</div>
                                <Badge :variant="period.is_active ? 'default' : 'secondary'" class="px-1.5 py-0.5 text-xs">
                                    {{ period.is_active ? 'Active' : 'Inactive' }}
                                </Badge>
                            </div>

                            <!-- Duration and Actions -->
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-muted-foreground"> {{ calculateDuration(period.start_time, period.end_time) }}h </span>
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="ghost" size="sm" class="h-6 w-6 p-0">
                                            <MoreHorizontal class="h-3 w-3" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem as-child>
                                            <Link :href="edit(period.id)">
                                                <Edit class="mr-2 h-4 w-4" />
                                                Edit
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem @click="toggleAvailabilityPeriod(period)" :disabled="isToggling === period.id">
                                            <Power class="mr-2 h-4 w-4" />
                                            {{ period.is_active ? 'Deactivate' : 'Activate' }}
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            @click="deleteAvailabilityPeriod(period.id)"
                                            :disabled="isDeleting === period.id"
                                            class="text-red-600"
                                        >
                                            <Trash2 class="mr-2 h-4 w-4" />
                                            Delete
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
