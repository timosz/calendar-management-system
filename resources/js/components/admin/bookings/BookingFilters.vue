<script setup lang="ts">
    import DatePicker from '@/components/DatePicker.vue';
    import { Button } from '@/components/ui/button';
    import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
    import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
    import { Filter } from 'lucide-vue-next';
    import { ref } from 'vue';

    interface Props {
        statuses: Record<string, string>;
        statusFilter: string;
        fromDateFilter: string;
        toDateFilter: string;
    }

    interface Emits {
        (e: 'update:statusFilter', value: string): void;
        (e: 'update:fromDateFilter', value: string): void;
        (e: 'update:toDateFilter', value: string): void;
        (e: 'apply'): void;
        (e: 'clear'): void;
    }

    defineProps<Props>();
    const emit = defineEmits<Emits>();

    const showFilters = ref(false);
</script>

<template>
    <Card>
        <CardHeader>
            <div class="flex items-center justify-between">
                <CardTitle>Filters</CardTitle>
                <Button @click="showFilters = !showFilters" variant="ghost" size="sm">
                    <Filter class="h-4 w-4" />
                </Button>
            </div>
        </CardHeader>
        <CardContent v-if="showFilters">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="space-y-2">
                    <label class="text-sm font-medium">Status</label>
                    <Select :model-value="statusFilter" @update:model-value="emit('update:statusFilter', $event)">
                        <SelectTrigger>
                            <SelectValue placeholder="All statuses" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All statuses</SelectItem>
                            <SelectItem v-for="(label, value) in statuses" :key="value" :value="value">
                                {{ label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium">From Date</label>
                    <DatePicker
                        :model-value="fromDateFilter"
                        @update:model-value="emit('update:fromDateFilter', $event)"
                        placeholder="Select start date"
                    />
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium">To Date</label>
                    <DatePicker
                        :model-value="toDateFilter"
                        @update:model-value="emit('update:toDateFilter', $event)"
                        placeholder="Select end date"
                        :min-date="fromDateFilter ? fromDateFilter : undefined"
                    />
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <Button @click="emit('apply')">Apply Filters</Button>
                <Button @click="emit('clear')" variant="outline">Clear</Button>
            </div>
        </CardContent>
    </Card>
</template>
