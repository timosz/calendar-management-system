<script setup lang="ts">
    import { Button } from '@/components/ui/button';
    import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

    interface Props {
        open: boolean;
        action: 'confirm' | 'reject' | 'cancel' | 'delete' | null;
        selectedCount: number;
    }

    interface Emits {
        (e: 'update:open', value: boolean): void;
        (e: 'confirm'): void;
    }

    defineProps<Props>();
    const emit = defineEmits<Emits>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Confirm Bulk Action</DialogTitle>
                <DialogDescription>
                    Are you sure you want to {{ action }} {{ selectedCount }} booking(s)? This action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button @click="emit('update:open', false)" variant="outline">Cancel</Button>
                <Button @click="emit('confirm')" :variant="action === 'delete' ? 'destructive' : 'default'"> Confirm </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
