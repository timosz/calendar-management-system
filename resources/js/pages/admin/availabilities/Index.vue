<script setup lang="ts">
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
    import AppLayout from '@/layouts/AppLayout.vue';
    import { create, destroy, edit } from '@/routes/availabilities';
    import type { BreadcrumbItemType } from '@/types';
    import { Head, Link, router } from '@inertiajs/vue3';
    import { Calendar, CalendarDays, Clock, Edit, Plus, Repeat, Trash2 } from 'lucide-vue-next';
    import { computed, ref } from 'vue';

    interface Period {
        id: number;
        start_time: string;
        end_time: string;
    }

    interface Availability {
        id: number;
        name: string;
        description: string;
        start_date: string;
        end_date: string | null;
        periods: Period[];
        recurrence_pattern: any;
        created_at: string;
    }

    interface PaginatedAvailabilities {
        data: Availability[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    }

    const props = defineProps<{
        availabilities: PaginatedAvailabilities;
    }>();

    const breadcrumbs: BreadcrumbItemType[] = [{ title: 'Availabilities', href: '#' }];

    const viewMode = ref<'list' | 'calendar'>('list');
    const showDeleteModal = ref(false);
    const availabilityToDelete = ref<Availability | null>(null);

    // Computed properties
    const hasAvailabilities = computed(() => props.availabilities.data.length > 0);
    const showPagination = computed(() => props.availabilities.last_page > 1);

    // Methods
    function formatTimeRange(periods: Period[]): string {
        return periods.map((period) => `${period.start_time} - ${period.end_time}`).join(', ');
    }

    function formatDateRange(startDate: string, endDate: string | null): string {
        const start = new Date(startDate).toLocaleDateString();
        if (!endDate) {
            return `From ${start}`;
        }
        const end = new Date(endDate).toLocaleDateString();
        return `${start} - ${end}`;
    }

    function getRecurrenceText(recurrencePattern: any): string {
        if (!recurrencePattern || !recurrencePattern.type) {
            return 'One-time';
        }

        if (recurrencePattern.type === 'weekly' && recurrencePattern.days) {
            const days = recurrencePattern.days.map((day: string) => day.charAt(0).toUpperCase() + day.slice(1, 3)).join(', ');
            return `Weekly on ${days}`;
        }

        return 'Recurring';
    }

    function editAvailability(id: number) {
        router.visit(edit.url(id));
    }

    function confirmDelete(availability: Availability) {
        availabilityToDelete.value = availability;
        showDeleteModal.value = true;
    }

    function deleteAvailability() {
        if (availabilityToDelete.value) {
            router.delete(destroy.url(availabilityToDelete.value.id), {
                onSuccess: () => {
                    showDeleteModal.value = false;
                    availabilityToDelete.value = null;
                },
            });
        }
    }

    function cancelDelete() {
        showDeleteModal.value = false;
        availabilityToDelete.value = null;
    }

    function goToPage(url: string) {
        router.visit(url);
    }
</script>

<template>
    <Head title="Availabilities" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Availabilities</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage your working hours and availability schedule</p>
                </div>
                <div class="flex items-center space-x-3">
                    <!-- View Toggle (Calendar will be added later) -->
                    <div class="flex items-center rounded-lg bg-gray-100 p-1 dark:bg-gray-800">
                        <Button
                            variant="ghost"
                            size="sm"
                            :class="{ 'bg-white shadow-sm dark:bg-gray-700': viewMode === 'list' }"
                            @click="viewMode = 'list'"
                        >
                            <CalendarDays class="mr-2 h-4 w-4" />
                            List
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            :class="{ 'bg-white shadow-sm dark:bg-gray-700': viewMode === 'calendar' }"
                            @click="viewMode = 'calendar'"
                            disabled
                        >
                            <Calendar class="mr-2 h-4 w-4" />
                            Calendar
                        </Button>
                    </div>

                    <Link :href="create.url()">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Add Availability
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- List View -->
            <div v-if="viewMode === 'list'">
                <!-- Empty State -->
                <div v-if="!hasAvailabilities" class="py-12 text-center">
                    <Calendar class="mx-auto mb-4 h-16 w-16 text-gray-400" />
                    <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-gray-100">No availabilities yet</h3>
                    <p class="mb-6 text-gray-600 dark:text-gray-400">Create your first availability to start managing your schedule.</p>
                    <Link :href="create.url()">
                        <Button>
                            <Plus class="mr-2 h-4 w-4" />
                            Create Availability
                        </Button>
                    </Link>
                </div>

                <!-- Availabilities Grid -->
                <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <Card v-for="availability in availabilities.data" :key="availability.id" class="transition-shadow duration-200 hover:shadow-lg">
                        <CardHeader class="pb-3">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <CardTitle class="text-lg">{{ availability.name }}</CardTitle>
                                    <CardDescription v-if="availability.description" class="mt-1">
                                        {{ availability.description }}
                                    </CardDescription>
                                </div>
                                <div class="ml-2 flex items-center space-x-1">
                                    <Button variant="ghost" size="sm" @click="editAvailability(availability.id)" class="h-8 w-8 p-0">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="confirmDelete(availability)"
                                        class="h-8 w-8 p-0 text-red-600 hover:text-red-700"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <!-- Date Range -->
                            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                <Calendar class="mr-2 h-4 w-4" />
                                {{ formatDateRange(availability.start_date, availability.end_date) }}
                            </div>

                            <!-- Time Periods -->
                            <div class="flex items-start text-sm text-gray-600 dark:text-gray-400">
                                <Clock class="mt-0.5 mr-2 h-4 w-4 flex-shrink-0" />
                                <div>{{ formatTimeRange(availability.periods) }}</div>
                            </div>

                            <!-- Recurrence -->
                            <div class="flex items-center justify-between">
                                <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                                    <Repeat class="mr-2 h-4 w-4" />
                                    {{ getRecurrenceText(availability.recurrence_pattern) }}
                                </div>
                                <Badge variant="secondary" class="text-xs">
                                    {{ availability.periods.length }} period{{ availability.periods.length > 1 ? 's' : '' }}
                                </Badge>
                            </div>

                            <div class="border-t pt-2 text-xs text-gray-500">Created {{ availability.created_at }}</div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Pagination -->
                <div v-if="showPagination" class="mt-8 flex items-center justify-between">
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        Showing {{ (availabilities.current_page - 1) * availabilities.per_page + 1 }} to
                        {{ Math.min(availabilities.current_page * availabilities.per_page, availabilities.total) }} of
                        {{ availabilities.total }} results
                    </div>
                    <div class="flex items-center space-x-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="!availabilities.prev_page_url"
                            @click="availabilities.prev_page_url && goToPage(availabilities.prev_page_url)"
                        >
                            Previous
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="!availabilities.next_page_url"
                            @click="availabilities.next_page_url && goToPage(availabilities.next_page_url)"
                        >
                            Next
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Calendar View Placeholder -->
            <div v-else class="py-12 text-center">
                <Calendar class="mx-auto mb-4 h-16 w-16 text-gray-400" />
                <h3 class="mb-2 text-lg font-medium text-gray-900 dark:text-gray-100">Calendar View Coming Soon</h3>
                <p class="text-gray-600 dark:text-gray-400">The calendar view will be implemented with FullCalendar integration.</p>
            </div>
        </div>
    </AppLayout>

    <!-- Delete Confirmation Modal -->
    <div v-if="showDeleteModal" class="bg-opacity-50 fixed inset-0 z-50 flex items-center justify-center bg-gray-600">
        <div class="mx-4 w-full max-w-md rounded-lg bg-white p-6 dark:bg-gray-800">
            <h3 class="mb-4 text-lg font-medium text-gray-900 dark:text-gray-100">Delete Availability</h3>
            <p class="mb-6 text-gray-600 dark:text-gray-400">
                Are you sure you want to delete "{{ availabilityToDelete?.name }}"? This action cannot be undone.
            </p>
            <div class="flex justify-end space-x-3">
                <Button variant="outline" @click="cancelDelete"> Cancel </Button>
                <Button variant="destructive" @click="deleteAvailability"> Delete </Button>
            </div>
        </div>
    </div>
</template>
