<script setup lang="ts">
import UsageChart from '@/Components/charts/UsageChart.vue';
import Card from '@/Components/ui/Card.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    BanknotesIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    PaperAirplaneIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps<{
    company: {
        id: number;
        name: string;
        currency: string;
        price_per_segment: number;
        messages_per_minute: number;
    };
    stats: {
        total: number;
        sent: number;
        delivered: number;
        failed: number;
        pending: number;
        billable_segments: number;
        total_cost: number;
        month_count: number;
        month_segments: number;
        month_cost: number;
    };
    daily: { date: string; sent: number; delivered: number; failed: number }[];
}>();

function money(v: number) {
    return `${v.toLocaleString('pt-PT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ${props.company.currency}`;
}
</script>

<template>
    <Head :title="`Consumo — ${company.name}`" />
    <AppLayout>
        <template #title>Consumo — {{ company.name }}</template>
        <template #subtitle>Estatísticas de envio e faturação</template>

        <Link :href="route('companies.index')" class="mb-4 inline-block text-sm text-brand-600 hover:underline">
            ← Voltar às empresas
        </Link>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Total de SMS" :value="stats.total" :icon="PaperAirplaneIcon" accent="brand" />
            <StatCard label="Entregues" :value="stats.delivered" :icon="CheckCircleIcon" accent="green" :hint="`${stats.sent} enviados`" />
            <StatCard label="Falhados" :value="stats.failed" :icon="ExclamationTriangleIcon" accent="red" />
            <StatCard label="Segmentos faturáveis" :value="stats.billable_segments" :icon="BanknotesIcon" accent="amber" />
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <Card title="Utilização diária" subtitle="Últimos 30 dias">
                    <UsageChart :data="daily" />
                </Card>
            </div>

            <Card title="Faturação">
                <dl class="space-y-4 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500">Preço por SMS</dt>
                        <dd class="font-medium text-gray-800 dark:text-gray-100">{{ money(company.price_per_segment) }}</dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700/40">
                        <p class="text-xs uppercase tracking-wide text-gray-400">Este mês</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ money(stats.month_cost) }}</p>
                        <p class="text-xs text-gray-400">{{ stats.month_count }} mensagens · {{ stats.month_segments }} segmentos</p>
                    </div>
                    <div class="rounded-lg bg-brand-50 p-4 dark:bg-brand-500/10">
                        <p class="text-xs uppercase tracking-wide text-brand-600/70 dark:text-brand-300/70">Total acumulado</p>
                        <p class="mt-1 text-2xl font-bold text-brand-700 dark:text-brand-300">{{ money(stats.total_cost) }}</p>
                        <p class="text-xs text-brand-600/70 dark:text-brand-300/70">{{ stats.billable_segments }} segmentos faturáveis</p>
                    </div>
                    <p class="text-xs text-gray-400">
                        Faturável = segmentos de mensagens efetivamente enviadas (enviado/entregue). Limite atual: {{ company.messages_per_minute }} SMS/min.
                    </p>
                </dl>
            </Card>
        </div>
    </AppLayout>
</template>
