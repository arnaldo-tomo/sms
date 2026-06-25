<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    label: string;
    value: number | string;
    icon?: Component;
    accent?: 'brand' | 'green' | 'amber' | 'red' | 'gray';
    hint?: string;
}>();

const accents: Record<string, string> = {
    brand: 'bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400',
    green: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400',
    amber: 'bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400',
    red: 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400',
    gray: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
};
</script>

<template>
    <div
        class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800"
    >
        <div class="flex items-center justify-between">
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ label }}</p>
            <span
                v-if="icon"
                class="flex h-9 w-9 items-center justify-center rounded-lg"
                :class="accents[accent ?? 'brand']"
            >
                <component :is="icon" class="h-5 w-5" />
            </span>
        </div>
        <p class="mt-3 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">
            {{ value }}
        </p>
        <p v-if="hint" class="mt-1 text-xs text-gray-400">{{ hint }}</p>
    </div>
</template>
