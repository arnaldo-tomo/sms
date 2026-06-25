<script setup lang="ts">
import Modal from '@/Components/Modal.vue';
import Card from '@/Components/ui/Card.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { usePermissions } from '@/composables/usePermissions';
import { debounce } from '@/utils/debounce';
import type { Contact, ContactList, Paginated } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ArrowUpTrayIcon,
    PencilSquareIcon,
    PlusIcon,
    TrashIcon,
} from '@heroicons/vue/24/outline';
import { reactive, ref, watch } from 'vue';

const props = defineProps<{
    contacts: Paginated<Contact>;
    lists: { data: ContactList[] };
    filters: { search?: string; list_id?: number };
}>();

const { can } = usePermissions();

const filters = reactive({
    search: props.filters.search ?? '',
    list_id: props.filters.list_id ?? '',
});

const apply = debounce(() => {
    router.get(route('contacts.index'), { ...filters }, { preserveState: true, replace: true });
}, 350);
watch(filters, apply);

// --- Contact modal ---
const showContactModal = ref(false);
const editingId = ref<number | null>(null);
const contactForm = useForm({
    name: '',
    phone_number: '',
    email: '',
    notes: '',
    list_ids: [] as number[],
});

function openCreate() {
    editingId.value = null;
    contactForm.reset();
    showContactModal.value = true;
}
function openEdit(c: Contact) {
    editingId.value = c.id;
    contactForm.name = c.name;
    contactForm.phone_number = c.phone_number;
    contactForm.email = c.email ?? '';
    contactForm.notes = c.notes ?? '';
    contactForm.list_ids = (c.lists ?? []).map((l) => l.id);
    showContactModal.value = true;
}
function saveContact() {
    const opts = { onSuccess: () => (showContactModal.value = false) };
    if (editingId.value) {
        contactForm.put(route('contacts.update', editingId.value), opts);
    } else {
        contactForm.post(route('contacts.store'), opts);
    }
}
function destroyContact(c: Contact) {
    if (confirm(`Eliminar o contacto "${c.name}"?`)) {
        router.delete(route('contacts.destroy', c.id));
    }
}

// --- Group modal ---
const showGroupModal = ref(false);
const groupForm = useForm({ name: '', description: '', color: '#6366f1' });
function saveGroup() {
    groupForm.post(route('lists.store'), { onSuccess: () => { showGroupModal.value = false; groupForm.reset(); } });
}
function destroyGroup(l: ContactList) {
    if (confirm(`Eliminar o grupo "${l.name}"?`)) router.delete(route('lists.destroy', l.id));
}

// --- Import modal ---
const showImportModal = ref(false);
const importForm = useForm<{ file: File | null; list_id: string }>({ file: null, list_id: '' });
function submitImport() {
    importForm.post(route('contacts.import'), { onSuccess: () => { showImportModal.value = false; importForm.reset(); } });
}
</script>

<template>
    <Head title="Contactos" />
    <AppLayout>
        <template #title>Contactos</template>
        <template #subtitle>Gere os teus contactos e grupos de envio</template>

        <div class="mb-4 flex flex-wrap gap-2" v-if="can('contacts.manage')">
            <button @click="openCreate" class="flex items-center gap-1.5 rounded-lg bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-700">
                <PlusIcon class="h-4 w-4" /> Novo contacto
            </button>
            <button @click="showGroupModal = true" class="flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                <PlusIcon class="h-4 w-4" /> Novo grupo
            </button>
            <button @click="showImportModal = true" class="flex items-center gap-1.5 rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                <ArrowUpTrayIcon class="h-4 w-4" /> Importar Excel
            </button>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
            <!-- Groups -->
            <Card title="Grupos" class="lg:col-span-1">
                <ul class="space-y-1 text-sm">
                    <li>
                        <button
                            class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left"
                            :class="!filters.list_id ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/10' : 'hover:bg-gray-50 dark:hover:bg-gray-700'"
                            @click="filters.list_id = ''"
                        >
                            Todos os contactos
                        </button>
                    </li>
                    <li v-for="l in lists.data" :key="l.id">
                        <div
                            class="flex items-center justify-between rounded-lg px-3 py-2"
                            :class="filters.list_id === l.id ? 'bg-brand-50 dark:bg-brand-500/10' : 'hover:bg-gray-50 dark:hover:bg-gray-700'"
                        >
                            <button class="flex flex-1 items-center gap-2 text-left" @click="filters.list_id = l.id">
                                <span class="h-3 w-3 rounded-full" :style="{ backgroundColor: l.color || '#9ca3af' }" />
                                <span class="text-gray-700 dark:text-gray-200">{{ l.name }}</span>
                                <span class="ml-auto text-xs text-gray-400">{{ l.contacts_count }}</span>
                            </button>
                            <button v-if="can('contacts.manage')" class="ml-2 text-gray-400 hover:text-red-500" @click="destroyGroup(l)">
                                <TrashIcon class="h-4 w-4" />
                            </button>
                        </div>
                    </li>
                </ul>
            </Card>

            <!-- Contacts table -->
            <Card class="lg:col-span-3">
                <input
                    v-model="filters.search"
                    type="text"
                    placeholder="Pesquisar contacto..."
                    class="mb-4 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                />
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead>
                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                <th class="px-3 py-3">Nome</th>
                                <th class="px-3 py-3">Telefone</th>
                                <th class="px-3 py-3">Grupos</th>
                                <th class="px-3 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            <tr v-for="c in contacts.data" :key="c.id" class="text-sm hover:bg-gray-50 dark:hover:bg-gray-700/40">
                                <td class="px-3 py-3">
                                    <p class="font-medium text-gray-800 dark:text-gray-100">{{ c.name }}</p>
                                    <p v-if="c.email" class="text-xs text-gray-400">{{ c.email }}</p>
                                </td>
                                <td class="px-3 py-3 text-gray-600 dark:text-gray-300">{{ c.phone_number }}</td>
                                <td class="px-3 py-3">
                                    <span v-for="l in c.lists" :key="l.id" class="mr-1 inline-block rounded-full px-2 py-0.5 text-xs" :style="{ backgroundColor: (l.color || '#9ca3af') + '22', color: l.color || '#6b7280' }">
                                        {{ l.name }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-right" v-if="can('contacts.manage')">
                                    <button class="mr-2 text-gray-400 hover:text-brand-600" @click="openEdit(c)"><PencilSquareIcon class="h-4 w-4" /></button>
                                    <button class="text-gray-400 hover:text-red-500" @click="destroyContact(c)"><TrashIcon class="h-4 w-4" /></button>
                                </td>
                            </tr>
                            <tr v-if="!contacts.data.length"><td colspan="4" class="px-3 py-10 text-center text-sm text-gray-400">Sem contactos.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4"><Pagination :links="contacts.links" /></div>
            </Card>
        </div>

        <!-- Contact Modal -->
        <Modal :show="showContactModal" @close="showContactModal = false" max-width="lg">
            <div class="p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ editingId ? 'Editar contacto' : 'Novo contacto' }}</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-300">Nome</label>
                        <input v-model="contactForm.name" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                        <p v-if="contactForm.errors.name" class="text-sm text-red-600">{{ contactForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-300">Telefone</label>
                        <input v-model="contactForm.phone_number" placeholder="+258..." class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                        <p v-if="contactForm.errors.phone_number" class="text-sm text-red-600">{{ contactForm.errors.phone_number }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-300">Email (opcional)</label>
                        <input v-model="contactForm.email" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-300">Grupos</label>
                        <div class="mt-1 flex flex-wrap gap-2">
                            <label v-for="l in lists.data" :key="l.id" class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-2 py-1 text-sm dark:border-gray-600">
                                <input type="checkbox" :value="l.id" v-model="contactForm.list_ids" class="rounded text-brand-600" />
                                {{ l.name }}
                            </label>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button @click="showContactModal = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:text-gray-200">Cancelar</button>
                    <button @click="saveContact" :disabled="contactForm.processing" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Guardar</button>
                </div>
            </div>
        </Modal>

        <!-- Group Modal -->
        <Modal :show="showGroupModal" @close="showGroupModal = false" max-width="md">
            <div class="p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">Novo grupo</h3>
                <div class="space-y-3">
                    <input v-model="groupForm.name" placeholder="Nome do grupo" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                    <p v-if="groupForm.errors.name" class="text-sm text-red-600">{{ groupForm.errors.name }}</p>
                    <input v-model="groupForm.description" placeholder="Descrição (opcional)" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                    <div class="flex items-center gap-2">
                        <label class="text-sm text-gray-600 dark:text-gray-300">Cor</label>
                        <input v-model="groupForm.color" type="color" class="h-8 w-12 rounded border-gray-300" />
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button @click="showGroupModal = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:text-gray-200">Cancelar</button>
                    <button @click="saveGroup" :disabled="groupForm.processing" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Criar</button>
                </div>
            </div>
        </Modal>

        <!-- Import Modal -->
        <Modal :show="showImportModal" @close="showImportModal = false" max-width="md">
            <div class="p-6">
                <h3 class="mb-1 text-lg font-semibold text-gray-900 dark:text-white">Importar contactos</h3>
                <p class="mb-4 text-sm text-gray-500">Ficheiro .xlsx/.csv com colunas: <code>name</code>, <code>phone_number</code>, <code>email</code>.</p>
                <input type="file" accept=".xlsx,.xls,.csv" @input="importForm.file = ($event.target as HTMLInputElement).files?.[0] ?? null" class="w-full text-sm text-gray-600 dark:text-gray-300" />
                <p v-if="importForm.errors.file" class="text-sm text-red-600">{{ importForm.errors.file }}</p>
                <select v-model="importForm.list_id" class="mt-3 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    <option value="">Sem grupo</option>
                    <option v-for="l in lists.data" :key="l.id" :value="l.id">{{ l.name }}</option>
                </select>
                <div class="mt-6 flex justify-end gap-2">
                    <button @click="showImportModal = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:text-gray-200">Cancelar</button>
                    <button @click="submitImport" :disabled="importForm.processing || !importForm.file" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-60">Importar</button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
