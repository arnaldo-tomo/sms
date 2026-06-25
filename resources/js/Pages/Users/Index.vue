<script setup lang="ts">
import Modal from '@/Components/Modal.vue';
import Card from '@/Components/ui/Card.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import type { Paginated, User } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { PencilSquareIcon, PlusIcon, TrashIcon } from '@heroicons/vue/24/outline';
import { ref } from 'vue';

const props = defineProps<{
    users: Paginated<User>;
    roles: string[];
}>();

const showModal = ref(false);
const editingId = ref<number | null>(null);
const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    roles: [] as string[],
});

function openCreate() {
    editingId.value = null;
    form.reset();
    showModal.value = true;
}
function openEdit(u: User) {
    editingId.value = u.id;
    form.name = u.name;
    form.email = u.email;
    form.password = '';
    form.password_confirmation = '';
    form.roles = [...(u.roles ?? [])];
    showModal.value = true;
}
function save() {
    const opts = { onSuccess: () => (showModal.value = false) };
    if (editingId.value) form.put(route('users.update', editingId.value), opts);
    else form.post(route('users.store'), opts);
}
function destroyUser(u: User) {
    if (confirm(`Eliminar o utilizador "${u.name}"?`)) router.delete(route('users.destroy', u.id));
}
</script>

<template>
    <Head title="Utilizadores" />
    <AppLayout>
        <template #title>Utilizadores</template>
        <template #subtitle>Gestão de utilizadores, perfis e permissões</template>

        <div class="mb-4">
            <button @click="openCreate" class="flex items-center gap-1.5 rounded-lg bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-700">
                <PlusIcon class="h-4 w-4" /> Novo utilizador
            </button>
        </div>

        <Card>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            <th class="px-3 py-3">Nome</th>
                            <th class="px-3 py-3">Email</th>
                            <th class="px-3 py-3">Perfis</th>
                            <th class="px-3 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <tr v-for="u in users.data" :key="u.id" class="text-sm hover:bg-gray-50 dark:hover:bg-gray-700/40">
                            <td class="px-3 py-3 font-medium text-gray-800 dark:text-gray-100">{{ u.name }}</td>
                            <td class="px-3 py-3 text-gray-600 dark:text-gray-300">{{ u.email }}</td>
                            <td class="px-3 py-3">
                                <span v-for="r in u.roles" :key="r" class="mr-1 inline-block rounded-full bg-brand-50 px-2 py-0.5 text-xs font-medium text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">{{ r }}</span>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <button class="mr-2 text-gray-400 hover:text-brand-600" @click="openEdit(u)"><PencilSquareIcon class="h-4 w-4" /></button>
                                <button class="text-gray-400 hover:text-red-500" @click="destroyUser(u)"><TrashIcon class="h-4 w-4" /></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4"><Pagination :links="users.links" /></div>
        </Card>

        <Modal :show="showModal" @close="showModal = false" max-width="lg">
            <div class="p-6">
                <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-white">{{ editingId ? 'Editar utilizador' : 'Novo utilizador' }}</h3>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-300">Nome</label>
                        <input v-model="form.name" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                        <p v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-300">Email</label>
                        <input v-model="form.email" type="email" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                        <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Password {{ editingId ? '(opcional)' : '' }}</label>
                            <input v-model="form.password" type="password" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                            <p v-if="form.errors.password" class="text-sm text-red-600">{{ form.errors.password }}</p>
                        </div>
                        <div>
                            <label class="text-sm text-gray-600 dark:text-gray-300">Confirmar</label>
                            <input v-model="form.password_confirmation" type="password" class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" />
                        </div>
                    </div>
                    <div>
                        <label class="text-sm text-gray-600 dark:text-gray-300">Perfis</label>
                        <div class="mt-1 flex flex-wrap gap-2">
                            <label v-for="r in roles" :key="r" class="flex items-center gap-1.5 rounded-lg border border-gray-200 px-2 py-1 text-sm dark:border-gray-600">
                                <input type="checkbox" :value="r" v-model="form.roles" class="rounded text-brand-600" /> {{ r }}
                            </label>
                        </div>
                        <p v-if="form.errors.roles" class="text-sm text-red-600">{{ form.errors.roles }}</p>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button @click="showModal = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm dark:border-gray-600 dark:text-gray-200">Cancelar</button>
                    <button @click="save" :disabled="form.processing" class="rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">Guardar</button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
