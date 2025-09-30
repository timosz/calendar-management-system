<script setup lang="ts">
    import { Button } from '@/components/ui/button';
    import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
    import AppLayout from '@/layouts/AppLayout.vue';
    import { Download } from 'lucide-vue-next';
    import { computed, ref } from 'vue';

    import BookingFilters from '@/components/admin/bookings/BookingFilters.vue';
    import BookingPagination from '@/components/admin/bookings/BookingPagination.vue';
    import BookingStatsCards from '@/components/admin/bookings/BookingStatsCards.vue';
    import BookingTable from '@/components/admin/bookings/BookingTable.vue';
    import BulkActionBar from '@/components/admin/bookings/BulkActionBar.vue';
    import BulkActionDialog from '@/components/admin/bookings/BulkActionDialog.vue';
    import { useBookingActions } from '@/composables/useBookingActions';
    import { useBookingFilters } from '@/composables/useBookingFilters';

    import { exportMethod as bookingsExport } from '@/actions/App/Http/Controllers/Admin/BookingController';
    import type { BookingFilters as BookingFiltersType, BookingIndexItem, BookingStats, PaginatedData } from '@/types';

    interface Props {
        bookings: PaginatedData<BookingIndexItem>;
        stats: BookingStats;
        filters: BookingFiltersType;
        statuses: Record<string, string>;
    }

    const props = defineProps<Props>();

    // Composables
    const { activeTab, statusFilter, fromDateFilter, toDateFilter, applyFilters, clearFilters, changeTab, navigateToPage } = useBookingFilters(
        props.filters,
    );

    const { confirmBooking, rejectBooking, cancelBooking, deleteBooking, viewBooking, executeBulkAction } = useBookingActions();

    // Selection state
    const selectedBookings = ref<number[]>([]);

    // Bulk action state
    const bulkActionDialogOpen = ref(false);
    const bulkAction = ref<'confirm' | 'reject' | 'cancel' | 'delete' | null>(null);

    // Computed
    const hasSelectedBookings = computed(() => selectedBookings.value.length > 0);

    // Methods
    const toggleSelectAll = () => {
        if (selectedBookings.value.length === props.bookings.data.length) {
            selectedBookings.value = [];
        } else {
            selectedBookings.value = props.bookings.data.map((b) => b.id);
        }
    };

    const toggleSelectBooking = (bookingId: number) => {
        const index = selectedBookings.value.indexOf(bookingId);
        if (index > -1) {
            selectedBookings.value.splice(index, 1);
        } else {
            selectedBookings.value.push(bookingId);
        }
    };

    const openBulkActionDialog = (action: 'confirm' | 'reject' | 'cancel' | 'delete') => {
        if (selectedBookings.value.length === 0) {
            alert('Please select at least one booking');
            return;
        }
        bulkAction.value = action;
        bulkActionDialogOpen.value = true;
    };

    const handleBulkAction = () => {
        if (!bulkAction.value) return;

        executeBulkAction(bulkAction.value, selectedBookings.value, () => {
            selectedBookings.value = [];
            bulkActionDialogOpen.value = false;
            bulkAction.value = null;
        });
    };

    const exportBookings = () => {
        const params = new URLSearchParams();

        if (activeTab.value) {
            params.append('tab', activeTab.value);
        }
        if (statusFilter.value) {
            params.append('status', statusFilter.value);
        }
        if (fromDateFilter.value) {
            params.append('from_date', fromDateFilter.value);
        }
        if (toDateFilter.value) {
            params.append('to_date', toDateFilter.value);
        }

        const queryString = params.toString();
        window.location.href = queryString ? `${bookingsExport.url()}?${queryString}` : bookingsExport.url();
    };
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">Bookings</h1>
                    <p class="text-muted-foreground">Manage your client bookings and appointments</p>
                </div>
                <Button @click="exportBookings" variant="outline">
                    <Download class="mr-2 h-4 w-4" />
                    Export
                </Button>
            </div>

            <!-- Stats Cards -->
            <BookingStatsCards :stats="stats" />

            <!-- Filters -->
            <BookingFilters
                :statuses="statuses"
                v-model:status-filter="statusFilter"
                v-model:from-date-filter="fromDateFilter"
                v-model:to-date-filter="toDateFilter"
                @apply="applyFilters"
                @clear="clearFilters"
            />

            <!-- Tabs -->
            <Tabs :default-value="activeTab" @update:model-value="changeTab">
                <TabsList class="grid w-full grid-cols-2 bg-muted p-1">
                    <TabsTrigger value="upcoming" class="data-[state=active]:bg-background data-[state=active]:shadow-sm"> Upcoming </TabsTrigger>
                    <TabsTrigger value="past" class="data-[state=active]:bg-background data-[state=active]:shadow-sm"> Past </TabsTrigger>
                </TabsList>

                <!-- Upcoming Tab -->
                <TabsContent value="upcoming" class="space-y-4">
                    <!-- Bulk Actions -->
                    <BulkActionBar
                        v-if="hasSelectedBookings"
                        :selected-count="selectedBookings.length"
                        @confirm="openBulkActionDialog('confirm')"
                        @reject="openBulkActionDialog('reject')"
                        @cancel="openBulkActionDialog('cancel')"
                        @delete="openBulkActionDialog('delete')"
                    />

                    <!-- Bookings Table -->
                    <BookingTable
                        :bookings="bookings.data"
                        :selected-bookings="selectedBookings"
                        empty-message="No upcoming bookings found"
                        @toggle-select-all="toggleSelectAll"
                        @toggle-select="toggleSelectBooking"
                        @view="viewBooking"
                        @confirm="confirmBooking"
                        @reject="rejectBooking"
                        @cancel="cancelBooking"
                        @delete="deleteBooking"
                    />

                    <!-- Pagination -->
                    <BookingPagination
                        :current-page="bookings.current_page"
                        :last-page="bookings.last_page"
                        :per-page="bookings.per_page"
                        :total="bookings.total"
                        @navigate="navigateToPage"
                    />
                </TabsContent>

                <!-- Past Tab -->
                <TabsContent value="past" class="space-y-4">
                    <!-- Bulk Actions -->
                    <BulkActionBar
                        v-if="hasSelectedBookings"
                        :selected-count="selectedBookings.length"
                        :show-actions="false"
                        @delete="openBulkActionDialog('delete')"
                    />

                    <!-- Bookings Table -->
                    <BookingTable
                        :bookings="bookings.data"
                        :selected-bookings="selectedBookings"
                        :show-actions="false"
                        empty-message="No past bookings found"
                        @toggle-select-all="toggleSelectAll"
                        @toggle-select="toggleSelectBooking"
                        @view="viewBooking"
                        @delete="deleteBooking"
                    />

                    <!-- Pagination -->
                    <BookingPagination
                        :current-page="bookings.current_page"
                        :last-page="bookings.last_page"
                        :per-page="bookings.per_page"
                        :total="bookings.total"
                        @navigate="navigateToPage"
                    />
                </TabsContent>
            </Tabs>
        </div>

        <!-- Bulk Action Confirmation Dialog -->
        <BulkActionDialog
            v-model:open="bulkActionDialogOpen"
            :action="bulkAction"
            :selected-count="selectedBookings.length"
            @confirm="handleBulkAction"
        />
    </AppLayout>
</template>
