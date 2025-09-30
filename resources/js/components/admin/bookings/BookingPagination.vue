<script setup lang="ts">
    import { Button } from '@/components/ui/button';

    interface Props {
        currentPage: number;
        lastPage: number;
        perPage: number;
        total: number;
    }

    interface Emits {
        (e: 'navigate', page: number): void;
    }

    defineProps<Props>();
    const emit = defineEmits<Emits>();
</script>

<template>
    <div v-if="lastPage > 1" class="flex items-center justify-between">
        <div class="text-sm text-muted-foreground">
            Showing {{ (currentPage - 1) * perPage + 1 }} to {{ Math.min(currentPage * perPage, total) }} of {{ total }} results
        </div>
        <div class="flex gap-2">
            <Button
                v-for="page in lastPage"
                :key="page"
                :variant="page === currentPage ? 'default' : 'outline'"
                size="sm"
                @click="emit('navigate', page)"
            >
                {{ page }}
            </Button>
        </div>
    </div>
</template>
