<script setup lang="ts">
import Card from '@/Components/ui/Card.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { debounce } from '@/utils/debounce';
import { Head, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';

interface Log {
    id: number;
    log_name: string;
    description: string;
    event: string | null;
    subject_type: string;
    subject_id: number | null;
    causer: string | null;
    created_at: string;
}

const props = defineProps<{
    logs: { data: Log[]; links: { url: string | null; label: string; active: boolean }[] };
    filters: { search?: string };
}>();

const filters = reactive({ search: props.filters.search ?? '' });
const apply = debounce(() => {
    router.get(route('audit-logs.index'), { ...filters }, { preserveState: true, replace: true });
}, 350);
watch(filters, apply);

function formatDate(d: string) {
    return new Date(d).toLocaleString('pt-PT');
}
</script>

<template>
    <Head title="Auditoria" />
    <AppLayout>
        <template #title>Logs de Auditoria</template>
        <template #subtitle>Registo de atividades do sistema</template>

        <Card>
            <input
                v-model="filters.search"
                type="text"
                placeholder="Pesquisar..."
                class="mb-4 w-full max-w-sm rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
            />
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-3">Descrição</th>
                            <th class="px-3 py-3">Evento</th>
                            <th class="px-3 py-3">Entidade</th>
                            <th class="px-3 py-3">Utilizador</th>
                            <th class="px-3 py-3">Data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="l in logs.data" :key="l.id" class="text-sm">
                            <td class="px-3 py-3 text-gray-800 dark:text-gray-100">{{ l.description }}</td>
                            <td class="px-3 py-3"><span class="rounded bg-gray-100 px-2 py-0.5 text-xs dark:bg-gray-700 dark:text-gray-300">{{ l.event ?? l.log_name }}</span></td>
                            <td class="px-3 py-3 text-gray-500 dark:text-gray-400">{{ l.subject_type }} {{ l.subject_id ? '#' + l.subject_id : '' }}</td>
                            <td class="px-3 py-3 text-gray-500 dark:text-gray-400">{{ l.causer ?? 'Sistema' }}</td>
                            <td class="px-3 py-3 text-gray-500 dark:text-gray-400">{{ formatDate(l.created_at) }}</td>
                        </tr>
                        <tr v-if="!logs.data.length"><td colspan="5" class="px-3 py-10 text-center text-sm text-gray-400">Sem registos.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4"><Pagination :links="logs.links" /></div>
        </Card>
    </AppLayout>
</template>
