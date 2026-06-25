<script setup lang="ts">
import Card from '@/Components/ui/Card.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Contact, ContactList, Device } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    devices: { data: Device[] };
    lists: { data: ContactList[] };
    contacts: Contact[];
}>();

type Mode = 'numbers' | 'contacts' | 'groups';
const mode = ref<Mode>('numbers');
const scheduleEnabled = ref(false);
const numbersRaw = ref('');

const form = useForm({
    content: '',
    device_id: props.devices.data[0]?.id ?? null,
    from: '',
    recipients: [] as string[],
    contact_ids: [] as number[],
    list_ids: [] as number[],
    scheduled_at: '',
});

const charCount = computed(() => form.content.length);
const segments = computed(() => Math.max(1, Math.ceil(charCount.value / 153)));

const recipientCount = computed(() => {
    if (mode.value === 'numbers') {
        return numbersRaw.value.split(/[\n,;]+/).map((s) => s.trim()).filter(Boolean).length;
    }
    if (mode.value === 'contacts') return form.contact_ids.length;
    return props.lists.data
        .filter((l) => form.list_ids.includes(l.id))
        .reduce((acc, l) => acc + (l.contacts_count ?? 0), 0);
});

function submit() {
    form.recipients = mode.value === 'numbers'
        ? numbersRaw.value.split(/[\n,;]+/).map((s) => s.trim()).filter(Boolean)
        : [];
    if (mode.value !== 'contacts') form.contact_ids = [];
    if (mode.value !== 'groups') form.list_ids = [];
    if (!scheduleEnabled.value) form.scheduled_at = '';

    form.post(route('messages.store'), {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Enviar SMS" />
    <AppLayout>
        <template #title>Enviar SMS</template>
        <template #subtitle>Compõe e envia mensagens individuais ou em massa</template>

        <form class="grid grid-cols-1 gap-6 lg:grid-cols-3" @submit.prevent="submit">
            <div class="space-y-6 lg:col-span-2">
                <Card title="Destinatários">
                    <div class="mb-4 flex gap-2">
                        <button
                            v-for="m in (['numbers', 'contacts', 'groups'] as Mode[])"
                            :key="m"
                            type="button"
                            class="rounded-lg px-3 py-1.5 text-sm font-medium transition"
                            :class="
                                mode === m
                                    ? 'bg-brand-600 text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300'
                            "
                            @click="mode = m"
                        >
                            {{ { numbers: 'Números', contacts: 'Contactos', groups: 'Grupos' }[m] }}
                        </button>
                    </div>

                    <div v-show="mode === 'numbers'">
                        <textarea
                            v-model="numbersRaw"
                            rows="4"
                            placeholder="+258840000001, +258840000002 (separa por vírgula ou nova linha)"
                            class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        />
                    </div>

                    <div v-show="mode === 'contacts'" class="max-h-60 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <label
                            v-for="c in contacts"
                            :key="c.id"
                            class="flex cursor-pointer items-center gap-3 border-b border-gray-100 px-3 py-2 text-sm last:border-0 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50"
                        >
                            <input type="checkbox" :value="c.id" v-model="form.contact_ids" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                            <span class="font-medium text-gray-800 dark:text-gray-100">{{ c.name }}</span>
                            <span class="text-gray-400">{{ c.phone_number }}</span>
                        </label>
                        <p v-if="!contacts.length" class="p-3 text-sm text-gray-400">Sem contactos.</p>
                    </div>

                    <div v-show="mode === 'groups'" class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <label
                            v-for="l in lists.data"
                            :key="l.id"
                            class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-3 py-2.5 text-sm hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-700/50"
                        >
                            <input type="checkbox" :value="l.id" v-model="form.list_ids" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                            <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: l.color || '#9ca3af' }" />
                            <span class="font-medium text-gray-800 dark:text-gray-100">{{ l.name }}</span>
                            <span class="ml-auto text-xs text-gray-400">{{ l.contacts_count }} contactos</span>
                        </label>
                        <p v-if="!lists.data.length" class="p-3 text-sm text-gray-400">Sem grupos.</p>
                    </div>
                    <p v-if="form.errors.recipients" class="mt-2 text-sm text-red-600">{{ form.errors.recipients }}</p>
                </Card>

                <Card title="Mensagem">
                    <textarea
                        v-model="form.content"
                        rows="6"
                        maxlength="1600"
                        placeholder="Escreve a tua mensagem..."
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                    />
                    <div class="mt-2 flex items-center justify-between text-xs text-gray-400">
                        <span>{{ charCount }} / 1600 caracteres</span>
                        <span>{{ segments }} segmento(s) SMS</span>
                    </div>
                    <p v-if="form.errors.content" class="mt-1 text-sm text-red-600">{{ form.errors.content }}</p>
                </Card>
            </div>

            <div class="space-y-6">
                <Card title="Opções de envio">
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">Dispositivo</label>
                    <select
                        v-model="form.device_id"
                        class="w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                    >
                        <option :value="null">— Usar número por omissão —</option>
                        <option v-for="d in devices.data" :key="d.id" :value="d.id">
                            {{ d.name }} ({{ d.phone_number }})
                        </option>
                    </select>

                    <div class="mt-4">
                        <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <input type="checkbox" v-model="scheduleEnabled" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500" />
                            Agendar envio
                        </label>
                        <input
                            v-if="scheduleEnabled"
                            v-model="form.scheduled_at"
                            type="datetime-local"
                            class="mt-2 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        />
                        <p v-if="form.errors.scheduled_at" class="mt-1 text-sm text-red-600">{{ form.errors.scheduled_at }}</p>
                    </div>
                </Card>

                <Card>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-500 dark:text-gray-400">Destinatários</span>
                        <span class="font-semibold text-gray-900 dark:text-white">{{ recipientCount }}</span>
                    </div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-60"
                    >
                        {{ scheduleEnabled ? 'Agendar SMS' : 'Enviar agora' }}
                    </button>
                </Card>
            </div>
        </form>
    </AppLayout>
</template>
