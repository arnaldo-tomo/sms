<script setup lang="ts">
import Card from '@/Components/ui/Card.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Message, Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from '@/utils/debounce';
import { reactive, watch } from 'vue';

const props = defineProps<{
    messages: Paginated<Message>;
    filters: { search?: string; status?: string; direction?: string; from_date?: string; to_date?: string };
    statuses: string[];
}>();

const filters = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    direction: props.filters.direction ?? '',
    from_date: props.filters.from_date ?? '',
    to_date: props.filters.to_date ?? '',
});

const apply = debounce(() => {
    router.get(route('messages.index'), { ...filters }, { preserveState: true, replace: true });
}, 350);

watch(filters, apply);

function formatDate(d?: string | null) {
    return d ? new Date(d).toLocaleString('pt-PT') : '—';
}
</script>

<template>
    <Head title="Histórico" />
    <AppLayout>
        <template #title>Histórico de SMS</template>
        <template #subtitle>Todas as mensagens enviadas e recebidas</template>

        <Card>
            <div class="mb-4 grid grid-cols-1 gap-3 md:grid-cols-5">
                <input
                    v-model="filters.search"
                    type="text"
                    placeholder="Pesquisar número ou texto..."
                    class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100 md:col-span-2"
                />
                <select v-model="filters.status" class="rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">Todos os estados</option>
                    <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                </select>
                <input v-model="filters.from_date" type="date" class="rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                <input v-model="filters.to_date" type="date" class="rounded-lg border-gray-300 text-sm shadow-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-3">Destinatário</th>
                            <th class="px-3 py-3">Mensagem</th>
                            <th class="px-3 py-3">Estado</th>
                            <th class="px-3 py-3">Dispositivo</th>
                            <th class="px-3 py-3">Data</th>
                            <th class="px-3 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="m in messages.data" :key="m.id" class="text-sm hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-3 py-3">
                                <p class="font-medium text-gray-800 dark:text-gray-100">{{ m.to_number }}</p>
                                <p v-if="m.contact" class="text-xs text-gray-400">{{ m.contact.name }}</p>
                            </td>
                            <td class="max-w-xs px-3 py-3">
                                <p class="truncate text-gray-600 dark:text-gray-300">{{ m.content }}</p>
                            </td>
                            <td class="px-3 py-3"><StatusBadge :status="m.status" /></td>
                            <td class="px-3 py-3 text-gray-500 dark:text-gray-400">{{ m.device?.name ?? '—' }}</td>
                            <td class="px-3 py-3 text-gray-500 dark:text-gray-400">{{ formatDate(m.created_at) }}</td>
                            <td class="px-3 py-3 text-right">
                                <Link :href="route('messages.show', m.id)" class="text-sm font-medium text-brand-600 hover:underline">Ver</Link>
                            </td>
                        </tr>
                        <tr v-if="!messages.data.length">
                            <td colspan="6" class="px-3 py-10 text-center text-sm text-gray-400">Sem mensagens.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                <Pagination :links="messages.links" />
            </div>
        </Card>
    </AppLayout>
</template>
