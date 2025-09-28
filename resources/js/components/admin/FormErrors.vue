<script setup lang="ts">
    interface Props {
        errors: Record<string, string> | null;
    }

    defineProps<Props>();
</script>

<template>
    <div v-if="errors && Object.keys(errors).length > 0" class="space-y-2">
        <div v-for="(error, key) in errors" :key="key" class="text-sm text-destructive">
            {{ error }}
        </div>
    </div>
</template>
