<script setup lang="ts">
import Card from '@/Components/ui/Card.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';
import type { Device } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ArrowPathIcon, Battery50Icon, DevicePhoneMobileIcon, SignalIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

defineProps<{ devices: { data: Device[] } }>();
const { can } = usePermissions();
const syncing = ref(false);

function sync() {
    syncing.value = true;
    router.post(route('devices.sync'), {}, { onFinish: () => (syncing.value = false) });
}
</script>

<template>
    <Head title="Dispositivos" />
    <AppLayout>
        <template #title>Dispositivos</template>
        <template #subtitle>Telemóveis Android ligados via httpSMS</template>

        <div class="mb-4" v-if="can('devices.manage')">
            <button @click="sync" :disabled="syncing" class="flex items-center gap-1.5 rounded-lg bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60">
                <ArrowPathIcon class="h-4 w-4" :class="syncing ? 'animate-spin' : ''" /> Sincronizar
            </button>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Card v-for="d in devices.data" :key="d.id">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-700">
                            <DevicePhoneMobileIcon class="h-6 w-6 text-gray-500" />
                        </span>
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ d.name }}</p>
                            <p class="text-sm text-gray-400">{{ d.phone_number }}</p>
                        </div>
                    </div>
                    <StatusBadge :status="d.is_online ? 'online' : 'offline'" />
                </div>

                <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-700/40">
                        <dt class="flex items-center gap-1 text-xs text-gray-400"><Battery50Icon class="h-4 w-4" /> Bateria</dt>
                        <dd class="mt-1 font-semibold text-gray-800 dark:text-gray-100">{{ d.battery_level ?? '—' }}{{ d.battery_level !== null ? '%' : '' }} <span v-if="d.charging" class="text-xs text-emerald-500">⚡</span></dd>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-700/40">
                        <dt class="flex items-center gap-1 text-xs text-gray-400"><SignalIcon class="h-4 w-4" /> Sinal</dt>
                        <dd class="mt-1 font-semibold text-gray-800 dark:text-gray-100">{{ d.signal_strength ?? '—' }}{{ d.signal_strength !== null ? '%' : '' }}</dd>
                    </div>
                </dl>

                <p class="mt-3 text-xs text-gray-400">
                    Última sincronização: {{ d.last_heartbeat_human ?? 'nunca' }}
                </p>
                <p v-if="d.model" class="text-xs text-gray-400">Modelo: {{ d.model }}</p>
            </Card>

            <Card v-if="!devices.data.length" class="md:col-span-2 xl:col-span-3">
                <div class="py-10 text-center text-sm text-gray-400">
                    <DevicePhoneMobileIcon class="mx-auto mb-2 h-10 w-10 opacity-30" />
                    Nenhum dispositivo. Liga o teu telemóvel no httpSMS e clica em "Sincronizar".
                </div>
            </Card>
        </div>
    </AppLayout>
</template>
