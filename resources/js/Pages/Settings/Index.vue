<script setup lang="ts">
import Card from '@/Components/ui/Card.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ClipboardDocumentIcon } from '@heroicons/vue/24/outline';

const props = defineProps<{
    settings: {
        httpsms_base_url: string;
        httpsms_default_from: string | null;
        httpsms_api_key_set: boolean;
        httpsms_webhook_secret_set: boolean;
        queue_connection: string | null;
        queue_name: string | null;
        status_poll_enabled: boolean;
        webhook_url: string;
    };
    queueConnections: string[];
}>();

const form = useForm({
    httpsms_base_url: props.settings.httpsms_base_url,
    httpsms_api_key: '',
    httpsms_default_from: props.settings.httpsms_default_from ?? '',
    httpsms_webhook_secret: '',
    queue_connection: props.settings.queue_connection ?? 'database',
    queue_name: props.settings.queue_name ?? 'default',
    status_poll_enabled: props.settings.status_poll_enabled,
});

function save() {
    form.put(route('settings.update'), { preserveScroll: true });
}

function testConnection() {
    router.post(route('settings.test'), {}, { preserveScroll: true });
}

function copyWebhook() {
    navigator.clipboard.writeText(props.settings.webhook_url);
}
</script>

<template>
    <Head title="Configurações" />
    <AppLayout>
        <template #title>Configurações</template>
        <template #subtitle>Integração httpSMS, webhooks e filas</template>

        <form class="max-w-3xl space-y-6" @submit.prevent="save">
            <Card title="Integração httpSMS">
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">URL Base da API</label>
                        <input v-model="form.httpsms_base_url" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                        <p v-if="form.errors.httpsms_base_url" class="text-sm text-red-600">{{ form.errors.httpsms_base_url }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            API Key
                            <span v-if="settings.httpsms_api_key_set" class="ml-1 text-xs text-emerald-500">(definida)</span>
                        </label>
                        <input v-model="form.httpsms_api_key" type="password" :placeholder="settings.httpsms_api_key_set ? '•••••••• (deixa vazio para manter)' : 'Cola aqui a tua API Key'" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Número por omissão</label>
                        <input v-model="form.httpsms_default_from" placeholder="+258..." class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <button type="button" @click="testConnection" class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        Testar ligação
                    </button>
                </div>
            </Card>

            <Card title="Webhooks">
                <div class="space-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">URL do Webhook (regista no httpSMS)</label>
                        <div class="mt-1 flex gap-2">
                            <input :value="settings.webhook_url" readonly class="w-full rounded-lg border-gray-300 bg-gray-50 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                            <button type="button" @click="copyWebhook" class="rounded-lg border border-gray-300 px-3 dark:border-gray-600">
                                <ClipboardDocumentIcon class="h-5 w-5 text-gray-500" />
                            </button>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Segredo do Webhook
                            <span v-if="settings.httpsms_webhook_secret_set" class="ml-1 text-xs text-emerald-500">(definido)</span>
                        </label>
                        <input v-model="form.httpsms_webhook_secret" type="password" :placeholder="settings.httpsms_webhook_secret_set ? '•••••••• (deixa vazio para manter)' : 'Opcional — valida os webhooks recebidos'" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                </div>
            </Card>

            <Card title="Filas (Queues)">
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Conexão</label>
                            <select v-model="form.queue_connection" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                <option v-for="c in queueConnections" :key="c" :value="c">{{ c }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Nome da fila</label>
                            <input v-model="form.queue_name" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" v-model="form.status_poll_enabled" class="rounded text-brand-600" />
                        Ativar polling de estado de entrega (reserva, caso os webhooks falhem)
                    </label>
                </div>
            </Card>

            <div class="flex justify-end">
                <button type="submit" :disabled="form.processing" class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700 disabled:opacity-60">
                    Guardar configurações
                </button>
            </div>
        </form>
    </AppLayout>
</template>
