<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ status: string }>();

const map: Record<string, { label: string; classes: string }> = {
    pending: { label: 'Pendente', classes: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200' },
    scheduled: { label: 'Agendado', classes: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300' },
    queued: { label: 'Na fila', classes: 'bg-blue-100 text-blue-700 dark:bg-blue-500/15 dark:text-blue-300' },
    sending: { label: 'A enviar', classes: 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300' },
    sent: { label: 'Enviado', classes: 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300' },
    delivered: { label: 'Entregue', classes: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' },
    failed: { label: 'Falhou', classes: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300' },
    expired: { label: 'Expirado', classes: 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300' },
    received: { label: 'Recebido', classes: 'bg-purple-100 text-purple-700 dark:bg-purple-500/15 dark:text-purple-300' },
    online: { label: 'Online', classes: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300' },
    offline: { label: 'Offline', classes: 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' },
};

const item = computed(() => map[props.status] ?? { label: props.status, classes: 'bg-gray-100 text-gray-700' });
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="item.classes"
    >
        <span class="h-1.5 w-1.5 rounded-full bg-current opacity-70" />
        {{ item.label }}
    </span>
</template>
