<script setup lang="ts">
    import { Button } from '@/components/ui/button';
    import { Ban, Check, Trash2, X } from 'lucide-vue-next';

    interface Props {
        selectedCount: number;
        showDelete?: boolean;
        showActions?: boolean;
    }

    interface Emits {
        (e: 'confirm'): void;
        (e: 'reject'): void;
        (e: 'cancel'): void;
        (e: 'delete'): void;
    }

    withDefaults(defineProps<Props>(), {
        showDelete: true,
        showActions: true,
    });

    const emit = defineEmits<Emits>();
</script>

<template>
    <div class="flex items-center gap-2 rounded-lg border bg-muted p-4">
        <span class="text-sm font-medium">{{ selectedCount }} selected</span>
        <div class="flex gap-2">
            <template v-if="showActions">
                <Button @click="emit('confirm')" size="sm" variant="outline">
                    <Check class="mr-2 h-4 w-4" />
                    Confirm
                </Button>
                <Button @click="emit('reject')" size="sm" variant="outline">
                    <X class="mr-2 h-4 w-4" />
                    Reject
                </Button>
                <Button @click="emit('cancel')" size="sm" variant="outline">
                    <Ban class="mr-2 h-4 w-4" />
                    Cancel
                </Button>
            </template>
            <Button v-if="showDelete" @click="emit('delete')" size="sm" variant="destructive">
                <Trash2 class="mr-2 h-4 w-4" />
                Delete
            </Button>
        </div>
    </div>
</template>
