<script setup lang="ts">
defineProps<{
    title: string
    description?: string
}>()
</script>

<template>
    <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 sm:mb-8 gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">{{ title }}</h1>
            <p v-if="description" class="text-slate-500 mt-1 sm:mt-2">{{ description }}</p>
        </div>
        <div class="flex gap-2 w-full sm:w-auto">
            <slot name="actions"></slot>
        </div>
    </header>
</template>
