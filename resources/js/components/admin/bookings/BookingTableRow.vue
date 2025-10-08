<script setup lang="ts">
    import { Badge } from '@/components/ui/badge';
    import { Button } from '@/components/ui/button';
    import { Checkbox } from '@/components/ui/checkbox';
    import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
    import { TableCell, TableRow } from '@/components/ui/table';
    import type { BookingIndexItem } from '@/types';
    import { Ban, Calendar, Check, Clock, Eye, MoreVertical, Trash2, X } from 'lucide-vue-next';
    import { computed } from 'vue';

    interface Props {
        booking: BookingIndexItem;
        selected: boolean;
        showActions?: boolean;
    }

    interface Emits {
        (e: 'update:selected', value: boolean): void;
        (e: 'view'): void;
        (e: 'confirm'): void;
        (e: 'reject'): void;
        (e: 'cancel'): void;
        (e: 'delete'): void;
    }

    const props = withDefaults(defineProps<Props>(), {
        showActions: true,
    });

    const emit = defineEmits<Emits>();

    const isSelected = computed({
        get: () => props.selected,
        set: (value) => emit('update:selected', value),
    });

    const getStatusColor = (status: string) => {
        const colors = {
            pending: 'bg-yellow-100 text-yellow-800 border-yellow-300',
            confirmed: 'bg-green-100 text-green-800 border-green-300',
            rejected: 'bg-red-100 text-red-800 border-red-300',
            cancelled: 'bg-gray-100 text-gray-800 border-gray-300',
        };
        return colors[status as keyof typeof colors] || 'bg-gray-100 text-gray-800';
    };

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
    <TableRow>
        <TableCell>
            <Checkbox v-model="isSelected" />
        </TableCell>
        <TableCell>
            <div class="font-medium">{{ booking.client_name }}</div>
            <div v-if="booking.notes" class="line-clamp-1 text-sm text-muted-foreground">
                {{ booking.notes }}
            </div>
        </TableCell>
        <TableCell>
            <div class="flex items-center gap-2">
                <Calendar class="h-4 w-4 text-muted-foreground" />
                <span>{{ booking.booking_date_formatted }}</span>
            </div>
            <div class="mt-1 flex items-center gap-2">
                <Clock class="h-4 w-4 text-muted-foreground" />
                <span class="text-sm">{{ booking.start_time }} - {{ booking.end_time }}</span>
            </div>
        </TableCell>
        <TableCell>
            {{ formatDuration(booking.duration_minutes) }}
        </TableCell>
        <TableCell>
            <Badge :class="getStatusColor(booking.status)" variant="outline">
                {{ booking.status_label }}
            </Badge>
        </TableCell>
        <TableCell>
            <div class="text-sm">{{ booking.client_email }}</div>
            <div v-if="booking.client_phone" class="text-sm text-muted-foreground">
                {{ booking.client_phone }}
            </div>
        </TableCell>
        <TableCell>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon">
                        <MoreVertical class="h-4 w-4" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem @click="emit('view')">
                        <Eye class="mr-2 h-4 w-4" />
                        View Details
                    </DropdownMenuItem>
                    <template v-if="showActions">
                        <DropdownMenuSeparator />
                        <DropdownMenuItem v-if="booking.can_be_confirmed" @click="emit('confirm')">
                            <Check class="mr-2 h-4 w-4" />
                            Confirm
                        </DropdownMenuItem>
                        <DropdownMenuItem v-if="booking.can_be_rejected" @click="emit('reject')">
                            <X class="mr-2 h-4 w-4" />
                            Reject
                        </DropdownMenuItem>
                        <DropdownMenuItem v-if="booking.can_be_cancelled" @click="emit('cancel')">
                            <Ban class="mr-2 h-4 w-4" />
                            Cancel
                        </DropdownMenuItem>
                    </template>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem @click="emit('delete')" class="text-destructive">
                        <Trash2 class="mr-2 h-4 w-4" />
                        Delete
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </TableCell>
    </TableRow>
</template>
