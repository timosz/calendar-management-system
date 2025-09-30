import { index as bookingsIndex } from '@/actions/App/Http/Controllers/Admin/BookingController';
import type { BookingFilters } from '@/types';
import { router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

export function useBookingFilters(initialFilters: BookingFilters) {
    const activeTab = ref(initialFilters.tab || 'upcoming');
    const statusFilter = ref(initialFilters.status || '');
    const fromDateFilter = ref(initialFilters.from_date || '');
    const toDateFilter = ref(initialFilters.to_date || '');

    const hasActiveFilters = computed(() => {
        return !!(statusFilter.value || fromDateFilter.value || toDateFilter.value);
    });

    const applyFilters = () => {
        router.get(
            bookingsIndex.url(),
            {
                tab: activeTab.value,
                status: statusFilter.value || undefined,
                from_date: fromDateFilter.value || undefined,
                to_date: toDateFilter.value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
            },
        );
    };

    const clearFilters = () => {
        statusFilter.value = '';
        fromDateFilter.value = '';
        toDateFilter.value = '';
        applyFilters();
    };

    const changeTab = (tab: string) => {
        activeTab.value = tab;
        // Clear date filters when changing tabs
        fromDateFilter.value = '';
        toDateFilter.value = '';
        applyFilters();
    };

    const navigateToPage = (page: number) => {
        router.get(
            bookingsIndex.url(),
            {
                page,
                tab: activeTab.value,
                status: statusFilter.value || undefined,
                from_date: fromDateFilter.value || undefined,
                to_date: toDateFilter.value || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    return {
        activeTab,
        statusFilter,
        fromDateFilter,
        toDateFilter,
        hasActiveFilters,
        applyFilters,
        clearFilters,
        changeTab,
        navigateToPage,
    };
}
