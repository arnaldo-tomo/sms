<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    links: { url: string | null; label: string; active: boolean }[];
}>();
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap items-center gap-1">
        <template v-for="(link, i) in links" :key="i">
            <span
                v-if="!link.url"
                class="cursor-not-allowed rounded-md px-3 py-1.5 text-sm text-gray-400 dark:text-gray-600"
                v-html="link.label"
            />
            <Link
                v-else
                :href="link.url"
                class="rounded-md px-3 py-1.5 text-sm transition"
                :class="
                    link.active
                        ? 'bg-brand-600 text-white'
                        : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700'
                "
                v-html="link.label"
            />
        </template>
    </nav>
</template>
