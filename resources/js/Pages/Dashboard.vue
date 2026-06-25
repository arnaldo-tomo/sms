<script setup lang="ts">
import UsageChart from '@/Components/charts/UsageChart.vue';
import Card from '@/Components/ui/Card.vue';
import StatCard from '@/Components/ui/StatCard.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    CheckCircleIcon,
    ClockIcon,
    DevicePhoneMobileIcon,
    ExclamationTriangleIcon,
    PaperAirplaneIcon,
    UserGroupIcon,
} from '@heroicons/vue/24/outline';

defineProps<{
    stats: { sent: number; delivered: number; pending: number; failed: number; total: number };
    contactsCount: number;
    devices: {
        total: number;
        online: number;
        offline: number;
        list: {
            id: number;
            name: string;
            phone_number: string;
            is_online: boolean;
            battery_level: number | null;
            signal_strength: number | null;
            last_heartbeat_human: string | null;
        }[];
    };
    dailyUsage: { date: string; sent: number; delivered: number; failed: number }[];
    httpsmsConfigured: boolean;
}>();
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout>
        <template #title>Dashboard</template>
        <template #subtitle>Visão geral da tua gateway de SMS</template>

        <div
            v-if="!httpsmsConfigured"
            class="mb-6 flex items-center justify-between rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 dark:border-amber-500/30 dark:bg-amber-500/10"
        >
            <p class="text-sm text-amber-800 dark:text-amber-300">
                ⚠️ A integração com o httpSMS ainda não está configurada.
            </p>
            <Link
                :href="route('settings.edit')"
                class="rounded-lg bg-amber-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-amber-700"
            >
                Configurar agora
            </Link>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="SMS Enviados" :value="stats.sent" :icon="PaperAirplaneIcon" accent="brand" :hint="`${stats.delivered} entregues`" />
            <StatCard label="Pendentes" :value="stats.pending" :icon="ClockIcon" accent="amber" />
            <StatCard label="Falhados" :value="stats.failed" :icon="ExclamationTriangleIcon" accent="red" />
            <StatCard label="Contactos" :value="contactsCount" :icon="UserGroupIcon" accent="green" />
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <Card title="Utilização diária" subtitle="Últimos 14 dias">
                    <UsageChart :data="dailyUsage" />
                </Card>
            </div>

            <Card title="Dispositivos" :subtitle="`${devices.online} online / ${devices.total} total`">
                <template #actions>
                    <Link :href="route('devices.index')" class="text-sm font-medium text-brand-600 hover:underline">
                        Ver todos
                    </Link>
                </template>

                <div v-if="devices.list.length" class="space-y-3">
                    <div
                        v-for="d in devices.list"
                        :key="d.id"
                        class="flex items-center justify-between rounded-lg border border-gray-100 p-3 dark:border-gray-700"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700"
                            >
                                <DevicePhoneMobileIcon class="h-5 w-5 text-gray-500" />
                            </span>
                            <div>
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ d.name }}</p>
                                <p class="text-xs text-gray-400">{{ d.phone_number }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <StatusBadge :status="d.is_online ? 'online' : 'offline'" />
                            <p v-if="d.battery_level !== null" class="mt-1 text-xs text-gray-400">
                                🔋 {{ d.battery_level }}%
                            </p>
                        </div>
                    </div>
                </div>
                <div v-else class="py-8 text-center text-sm text-gray-400">
                    <CheckCircleIcon class="mx-auto mb-2 h-8 w-8 opacity-40" />
                    Nenhum dispositivo sincronizado.
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
