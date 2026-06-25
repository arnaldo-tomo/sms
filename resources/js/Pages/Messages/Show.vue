<script setup lang="ts">
import Card from '@/Components/ui/Card.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Message } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ message: { data: Message } | Message }>();

const msg = computed<Message>(() => ('data' in props.message ? (props.message as any).data : props.message) as Message);

function formatDate(d?: string | null) {
    return d ? new Date(d).toLocaleString('pt-PT') : '—';
}
</script>

<template>
    <Head title="Detalhe da mensagem" />
    <AppLayout>
        <template #title>Detalhe da mensagem</template>

        <Link :href="route('messages.index')" class="mb-4 inline-block text-sm text-brand-600 hover:underline">
            ← Voltar ao histórico
        </Link>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <Card title="Conteúdo" class="lg:col-span-2">
                <p class="whitespace-pre-wrap text-gray-800 dark:text-gray-100">{{ msg.content }}</p>
                <div v-if="msg.error" class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-500/10 dark:text-red-300">
                    {{ msg.error }}
                </div>
            </Card>

            <Card title="Detalhes">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between"><dt class="text-gray-500">Estado</dt><dd><StatusBadge :status="msg.status" /></dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Para</dt><dd class="font-medium text-gray-800 dark:text-gray-100">{{ msg.to_number }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">De</dt><dd class="text-gray-800 dark:text-gray-100">{{ msg.from_number ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Dispositivo</dt><dd class="text-gray-800 dark:text-gray-100">{{ msg.device?.name ?? '—' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Segmentos</dt><dd class="text-gray-800 dark:text-gray-100">{{ msg.segments }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Agendado</dt><dd class="text-gray-800 dark:text-gray-100">{{ formatDate(msg.scheduled_at) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Enviado</dt><dd class="text-gray-800 dark:text-gray-100">{{ formatDate(msg.sent_at) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Entregue</dt><dd class="text-gray-800 dark:text-gray-100">{{ formatDate(msg.delivered_at) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-gray-500">Criado</dt><dd class="text-gray-800 dark:text-gray-100">{{ formatDate(msg.created_at) }}</dd></div>
                </dl>
            </Card>
        </div>
    </AppLayout>
</template>
