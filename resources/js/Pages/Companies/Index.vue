<script setup lang="ts">
import Modal from '@/Components/Modal.vue';
import Card from '@/Components/ui/Card.vue';
import StatusBadge from '@/Components/ui/StatusBadge.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ChartBarIcon,
    ClipboardDocumentIcon,
    KeyIcon,
    PencilSquareIcon,
    PlusIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';

interface Token {
    id: number;
    name: string;
    prefix: string;
    last_used_at: string | null;
}
interface Number {
    id: number;
    phone_number: string;
    status: string;
}
interface Company {
    id: number;
    name: string;
    slug: string;
    contact_email: string | null;
    status_callback_url: string | null;
    messages_per_minute: number;
    price_per_segment: number;
    currency: string;
    is_active: boolean;
    messages_count: number;
    numbers: Number[];
    tokens: Token[];
}

defineProps<{ companies: Company[]; availableNumbers: Number[] }>();

const selectedNumber = ref<Record<number, number | ''>>({});

function assignNumber(c: Company) {
    const deviceId = selectedNumber.value[c.id];
    if (!deviceId) return;
    router.post(route('companies.numbers.assign', c.id), { device_id: deviceId }, {
        preserveScroll: true,
        onSuccess: () => (selectedNumber.value[c.id] = ''),
    });
}
function unassignNumber(c: Company, n: Number) {
    router.delete(route('companies.numbers.unassign', [c.id, n.id]), { preserveScroll: true });
}

const page = usePage();
const newToken = computed(() => page.props.flash?.new_token as string | null);
const baseUrl = typeof window !== 'undefined' ? window.location.origin : '';

const showModal = ref(false);
const editingId = ref<number | null>(null);

const form = useForm({
    name: '',
    contact_email: '',
    status_callback_url: '',
    callback_secret: '',
    messages_per_minute: 60,
    price_per_segment: 0,
    currency: 'MZN',
    is_active: true,
});

function openCreate() {
    editingId.value = null;
    form.reset();
    showModal.value = true;
}
function openEdit(c: Company) {
    editingId.value = c.id;
    form.name = c.name;
    form.contact_email = c.contact_email ?? '';
    form.status_callback_url = c.status_callback_url ?? '';
    form.callback_secret = '';
    form.messages_per_minute = c.messages_per_minute;
    form.price_per_segment = c.price_per_segment;
    form.currency = c.currency;
    form.is_active = c.is_active;
    showModal.value = true;
}
function save() {
    const opts = { onSuccess: () => (showModal.value = false), preserveScroll: true };
    if (editingId.value) form.put(route('companies.update', editingId.value), opts);
    else form.post(route('companies.store'), opts);
}
function destroy(c: Company) {
    if (confirm(`Eliminar a empresa "${c.name}"? Os tokens deixam de funcionar.`))
        router.delete(route('companies.destroy', c.id), { preserveScroll: true });
}
function genToken(c: Company) {
    router.post(route('companies.tokens.store', c.id), {}, { preserveScroll: true });
}
function revokeToken(c: Company, t: Token) {
    if (confirm('Revogar este token? As apps que o usam deixam de funcionar.'))
        router.delete(route('companies.tokens.revoke', [c.id, t.id]), { preserveScroll: true });
}
function copy(text: string) {
    navigator.clipboard.writeText(text);
}
</script>

<template>
    <Head title="Empresas / API" />
    <AppLayout>
        <template #title>Empresas / API</template>
        <template #subtitle>Gere empresas, números e tokens da API REST (estilo Twilio)</template>

        <!-- Token gerado (mostrado uma vez) -->
        <div
            v-if="newToken"
            class="mb-6 rounded-xl border border-emerald-300 bg-emerald-50 p-5 dark:border-emerald-500/40 dark:bg-emerald-500/10"
        >
            <p class="font-semibold text-emerald-800 dark:text-emerald-300">🔑 Novo token gerado — guarda-o agora (não volta a ser mostrado):</p>
            <div class="mt-2 flex items-center gap-2">
                <code class="flex-1 overflow-x-auto rounded-lg bg-white px-3 py-2 font-mono text-sm dark:bg-gray-900">{{ newToken }}</code>
                <button @click="copy(newToken!)" class="rounded-lg border border-gray-300 p-2 dark:border-gray-600"><ClipboardDocumentIcon class="h-5 w-5 text-gray-500" /></button>
            </div>
        </div>

        <div class="mb-4">
            <button @click="openCreate" class="flex items-center gap-1.5 rounded-lg bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-700">
                <PlusIcon class="h-4 w-4" /> Nova empresa
            </button>
        </div>

        <div class="space-y-4">
            <Card v-for="c in companies" :key="c.id">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ c.name }}</h3>
                            <StatusBadge :status="c.is_active ? 'online' : 'offline'" />
                        </div>
                        <p class="text-sm text-gray-400">slug: <code>{{ c.slug }}</code> · {{ c.contact_email || 'sem email' }}</p>
                        <p class="mt-1 text-xs text-gray-400">
                            {{ c.messages_count }} mensagem(ns) · limite {{ c.messages_per_minute }}/min ·
                            usa o pool de números da plataforma
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <Link :href="route('companies.usage', c.id)" title="Ver consumo" class="rounded-lg border border-gray-300 p-2 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"><ChartBarIcon class="h-4 w-4 text-gray-500" /></Link>
                        <button @click="genToken(c)" title="Gerar token" class="rounded-lg border border-gray-300 p-2 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"><KeyIcon class="h-4 w-4 text-gray-500" /></button>
                        <button @click="openEdit(c)" class="rounded-lg border border-gray-300 p-2 hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"><PencilSquareIcon class="h-4 w-4 text-gray-500" /></button>
                        <button @click="destroy(c)" class="rounded-lg border border-gray-300 p-2 hover:bg-red-50 dark:border-gray-600"><TrashIcon class="h-4 w-4 text-red-500" /></button>
                    </div>
                </div>

                <!-- Números atribuídos -->
                <div class="mt-4 border-t border-gray-100 pt-3 dark:border-gray-700">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Números dedicados</p>
                    <div v-if="c.numbers.length" class="mb-2 flex flex-wrap gap-2">
                        <span v-for="n in c.numbers" :key="n.id" class="inline-flex items-center gap-1.5 rounded-full bg-brand-50 px-2.5 py-1 text-sm text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">
                            {{ n.phone_number }}
                            <span class="h-1.5 w-1.5 rounded-full" :class="n.status === 'online' ? 'bg-emerald-500' : 'bg-gray-400'" />
                            <button @click="unassignNumber(c, n)" class="ml-1 text-red-500 hover:text-red-600">&times;</button>
                        </span>
                    </div>
                    <p v-else class="mb-2 text-sm text-gray-400">Sem números dedicados — usa o <strong>pool partilhado</strong> da plataforma.</p>
                    <div v-if="availableNumbers.length" class="flex items-center gap-2">
                        <select v-model="selectedNumber[c.id]" class="rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                            <option value="">Atribuir número do pool…</option>
                            <option v-for="n in availableNumbers" :key="n.id" :value="n.id">{{ n.phone_number }} ({{ n.status }})</option>
                        </select>
                        <button @click="assignNumber(c)" :disabled="!selectedNumber[c.id]" class="rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50">Atribuir</button>
                    </div>
                </div>

                <!-- Tokens -->
                <div class="mt-4 border-t border-gray-100 pt-3 dark:border-gray-700">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-400">Tokens API ativos</p>
                    <div v-if="c.tokens.length" class="space-y-1">
                        <div v-for="t in c.tokens" :key="t.id" class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-1.5 text-sm dark:bg-gray-700/40">
                            <span><code>{{ t.prefix }}</code> <span class="text-gray-400">· {{ t.name }} · {{ t.last_used_at ? 'usado ' + t.last_used_at : 'nunca usado' }}</span></span>
                            <button @click="revokeToken(c, t)" class="text-xs font-medium text-red-500 hover:underline">Revogar</button>
                        </div>
                    </div>
                    <p v-else class="text-sm text-gray-400">Sem tokens. Clica na chave 🔑 para gerar.</p>
                </div>
            </Card>

            <Card v-if="!companies.length">
                <div class="py-10 text-center text-sm text-gray-400">Sem empresas. Cria a primeira para emitir tokens da API.</div>
            </Card>
        </div>

        <!-- Exemplo de uso da API -->
        <Card title="Como a empresa usa a API" class="mt-6">
            <pre class="overflow-x-auto rounded-lg bg-gray-900 p-4 text-xs text-gray-100"><code>curl -X POST {{ baseUrl }}/api/v1/sms \
  -H "Authorization: Bearer sk_live_..." \
  -H "Content-Type: application/json" \
  -d '{"to": "+258840000000", "content": "Olá do SMS Gateway"}'</code></pre>
            <p class="mt-2 text-sm text-gray-500">Endpoints: <code>POST /api/v1/sms</code> · <code>GET /api/v1/sms/&#123;id&#125;</code> · <code>GET /api/v1/sms</code> · <code>GET /api/v1/numbers</code> · <code>GET /api/v1/me</code></p>
        </Card>

        <!-- Modal criar/editar -->
        <Modal :show="showModal" @close="showModal = false" max-width="lg">
            <div class="p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ editingId ? 'Editar empresa' : 'Nova empresa' }}</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-300">Nome</label>
                        <input v-model="form.name" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                        <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-300">Email de contacto</label>
                        <input v-model="form.contact_email" type="email" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-300">Status Callback URL (webhook da empresa)</label>
                        <input v-model="form.status_callback_url" placeholder="https://app-da-empresa.com/sms/callback" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Segredo do callback (HMAC)</label>
                            <input v-model="form.callback_secret" type="password" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Limite (SMS/min)</label>
                            <input v-model.number="form.messages_per_minute" type="number" min="1" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Preço por SMS (segmento)</label>
                            <input v-model.number="form.price_per_segment" type="number" step="0.0001" min="0" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Moeda</label>
                            <input v-model="form.currency" maxlength="8" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                        <input type="checkbox" v-model="form.is_active" class="rounded text-brand-600" /> Empresa ativa
                    </label>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button @click="showModal = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:text-gray-200">Cancelar</button>
                    <button @click="save" :disabled="form.processing" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Guardar</button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
