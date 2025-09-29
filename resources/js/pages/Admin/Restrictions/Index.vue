<script setup lang="ts">
    import {
        AlertDialog,
        AlertDialogAction,
        AlertDialogCancel,
        AlertDialogContent,
        AlertDialogDescription,
        AlertDialogFooter,
        AlertDialogHeader,
        AlertDialogTitle,
    } from '@/components/ui/alert-dialog';
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
    import { Input } from '@/components/ui/input';
    import { Label } from '@/components/ui/label';
    import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
    import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
    import { useTimeUtils } from '@/composables/useTimeUtils';
    import { useToast } from '@/composables/useToast';
    import AppLayout from '@/layouts/AppLayout.vue';
    import type { PaginatedData, Restriction, RestrictionType } from '@/types';
    import { Head, Link, router, usePage } from '@inertiajs/vue3';
    import { Calendar, Edit, Eye, Filter, Plus, Trash2 } from 'lucide-vue-next';
    import { computed, ref } from 'vue';

    interface Props {
        restrictions: PaginatedData<Restriction>;
        types: Record<RestrictionType, string>;
        filters: {
            type?: string;
            from_date?: string;
            to_date?: string;
        };
    }

    const props = defineProps<Props>();

    const { formatDateRange, formatTimeRange } = useTimeUtils();
    const { success, error } = useToast();
    const page = usePage();

    const showFilters = ref(false);
    const deleteConfirmOpen = ref(false);
    const restrictionToDelete = ref<number | null>(null);

    const localFilters = ref({
        type: props.filters.type || '',
        from_date: props.filters.from_date || '',
        to_date: props.filters.to_date || '',
    });

    const applyFilters = () => {
        router.get('/admin/restrictions', localFilters.value, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const clearFilters = () => {
        localFilters.value = {
            type: '',
            from_date: '',
            to_date: '',
        };
        applyFilters();
    };

    const hasActiveFilters = computed(() => {
        return !!(localFilters.value.type || localFilters.value.from_date || localFilters.value.to_date);
    });

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

    const confirmDelete = (id: number) => {
        restrictionToDelete.value = id;
        deleteConfirmOpen.value = true;
    };

    const deleteRestriction = () => {
        if (restrictionToDelete.value) {
            router.delete(`/admin/restrictions/${restrictionToDelete.value}`, {
                preserveScroll: true,
                onSuccess: () => {
                    const flashMessage = page.props.flash?.success;
                    if (flashMessage) {
                        success(flashMessage);
                    }
                    deleteConfirmOpen.value = false;
                    restrictionToDelete.value = null;
                },
                onError: (errors) => {
                    // Show a general error message for field-specific errors
                    const hasFieldErrors = Object.keys(errors).some((key) => key.startsWith('availabilities.'));

                    if (hasFieldErrors) {
                        error('Please check the highlighted fields and correct any errors.');
                    } else if (Object.keys(errors).length > 0) {
                        // Handle other types of errors
                        const firstErrorKey = Object.keys(errors)[0];
                        const firstErrorMessage = errors[firstErrorKey];
                        error(Array.isArray(firstErrorMessage) ? firstErrorMessage[0] : firstErrorMessage);
                    }

                    const flashError = page.props.flash?.error;
                    if (flashError) {
                        error(flashError);
                    }
                },
            });
        }
    };
</script>

<template>
    <Head title="Restrictions" />

    <AppLayout>
        <!-- Page Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100">Restrictions</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Manage unavailable periods and time blocks</p>
            </div>
            <Button asChild>
                <Link href="/admin/restrictions/create">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Restriction
                </Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle>All Restrictions</CardTitle>
                        <CardDescription> {{ restrictions.total }} total restriction{{ restrictions.total !== 1 ? 's' : '' }} </CardDescription>
                    </div>
                    <Button variant="outline" size="sm" @click="showFilters = !showFilters">
                        <Filter class="mr-2 h-4 w-4" />
                        Filters
                        <Badge v-if="hasActiveFilters" variant="secondary" class="ml-2"> Active </Badge>
                    </Button>
                </div>

                <!-- Filters Section -->
                <div v-if="showFilters" class="mt-4 space-y-4 border-t pt-4">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="space-y-2">
                            <Label for="filter-type">Type</Label>
                            <Select v-model="localFilters.type">
                                <SelectTrigger id="filter-type">
                                    <SelectValue placeholder="All types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All types</SelectItem>
                                    <SelectItem v-for="(label, value) in types" :key="value" :value="value">
                                        {{ label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="space-y-2">
                            <Label for="filter-from">From Date</Label>
                            <Input id="filter-from" v-model="localFilters.from_date" type="date" />
                        </div>

                        <div class="space-y-2">
                            <Label for="filter-to">To Date</Label>
                            <Input id="filter-to" v-model="localFilters.to_date" type="date" />
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <Button size="sm" @click="applyFilters"> Apply Filters </Button>
                        <Button size="sm" variant="outline" @click="clearFilters" :disabled="!hasActiveFilters"> Clear Filters </Button>
                    </div>
                </div>
            </CardHeader>

            <CardContent>
                <div v-if="restrictions.data.length === 0" class="py-8 text-center">
                    <Calendar class="mx-auto h-12 w-12 text-muted-foreground" />
                    <h3 class="mt-4 text-lg font-medium">No restrictions found</h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ hasActiveFilters ? 'Try adjusting your filters' : 'Get started by creating a new restriction' }}
                    </p>
                    <Button v-if="!hasActiveFilters" class="mt-4" asChild>
                        <Link href="/admin/restrictions/create">
                            <Plus class="mr-2 h-4 w-4" />
                            Add Restriction
                        </Link>
                    </Button>
                </div>

                <div v-else>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Date Range</TableHead>
                                <TableHead>Time</TableHead>
                                <TableHead>Type</TableHead>
                                <TableHead>Reason</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="restriction in restrictions.data" :key="restriction.id">
                                <TableCell class="font-medium">
                                    {{ formatDateRange(restriction.start_date, restriction.end_date) }}
                                </TableCell>
                                <TableCell>
                                    {{ formatTimeRange(restriction.start_time, restriction.end_time, restriction.is_all_day) }}
                                </TableCell>
                                <TableCell>
                                    <span
                                        class="inline-flex rounded px-2 py-1 text-xs font-medium"
                                        :class="getRestrictionTypeColor(restriction.type)"
                                    >
                                        {{ restriction.type_label }}
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <span class="text-sm text-muted-foreground">
                                        {{ restriction.reason || '—' }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button variant="ghost" size="sm" asChild>
                                            <Link :href="`/admin/restrictions/${restriction.id}`">
                                                <Eye class="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button variant="ghost" size="sm" asChild>
                                            <Link :href="`/admin/restrictions/${restriction.id}/edit`">
                                                <Edit class="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button variant="ghost" size="sm" @click="confirmDelete(restriction.id)">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>

                    <!-- Pagination -->
                    <div v-if="restrictions.last_page > 1" class="mt-4 flex items-center justify-between">
                        <div class="text-sm text-muted-foreground">
                            Showing {{ restrictions.from }} to {{ restrictions.to }} of {{ restrictions.total }} results
                        </div>
                        <div class="flex gap-2">
                            <Button
                                v-for="link in restrictions.links"
                                :key="link.label"
                                variant="outline"
                                size="sm"
                                :disabled="!link.url || link.active"
                                @click="link.url && router.get(link.url)"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Delete Confirmation Dialog -->
        <AlertDialog v-model:open="deleteConfirmOpen">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                    <AlertDialogDescription> This action cannot be undone. This will permanently delete the restriction. </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction @click="deleteRestriction"> Delete </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
