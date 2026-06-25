<script setup lang="ts">
import { useDarkMode } from '@/composables/useDarkMode';
import { usePermissions } from '@/composables/usePermissions';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    TransitionRoot,
} from '@headlessui/vue';
import {
    ArrowRightOnRectangleIcon,
    Bars3Icon,
    BuildingOffice2Icon,
    ChartBarSquareIcon,
    ClipboardDocumentListIcon,
    Cog6ToothIcon,
    DevicePhoneMobileIcon,
    MoonIcon,
    PaperAirplaneIcon,
    SunIcon,
    UserCircleIcon,
    UserGroupIcon,
    UsersIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { computed, onMounted, ref } from 'vue';

const { isDark, init, toggle } = useDarkMode();
const { can, user } = usePermissions();
const page = usePage();

const sidebarOpen = ref(false);

onMounted(() => init());

const navigation = computed(() =>
    [
        { name: 'Dashboard', route: 'dashboard', icon: ChartBarSquareIcon, show: true },
        { name: 'Enviar SMS', route: 'messages.create', icon: PaperAirplaneIcon, show: can('sms.send') },
        { name: 'Histórico', route: 'messages.index', icon: ClipboardDocumentListIcon, show: can('messages.view') },
        { name: 'Contactos', route: 'contacts.index', icon: UserGroupIcon, show: can('contacts.view') },
        { name: 'Dispositivos', route: 'devices.index', icon: DevicePhoneMobileIcon, show: can('devices.view') },
        { name: 'Empresas / API', route: 'companies.index', icon: BuildingOffice2Icon, show: can('companies.manage') },
        { name: 'Utilizadores', route: 'users.index', icon: UsersIcon, show: can('users.manage') },
        { name: 'Auditoria', route: 'audit-logs.index', icon: ClipboardDocumentListIcon, show: can('audit.view') },
        { name: 'Configurações', route: 'settings.edit', icon: Cog6ToothIcon, show: can('settings.manage') },
    ].filter((i) => i.show),
);

function isActive(name: string): boolean {
    try {
        return route().current(name) || route().current(name.split('.')[0] + '.*');
    } catch {
        return false;
    }
}

const flashSuccess = computed(() => page.props.flash?.success as string | null);
const flashError = computed(() => page.props.flash?.error as string | null);

function logout() {
    router.post(route('logout'));
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900">
        <!-- Sidebar mobile backdrop -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-50 w-64 transform border-r border-gray-200 bg-white transition-transform dark:border-gray-700 dark:bg-gray-800 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-16 items-center gap-2 px-6">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-600 text-white">
                    <PaperAirplaneIcon class="h-5 w-5 -rotate-45" />
                </div>
                <span class="text-lg font-bold text-gray-900 dark:text-white">SMS Gateway</span>
                <button class="ml-auto lg:hidden" @click="sidebarOpen = false">
                    <XMarkIcon class="h-5 w-5 text-gray-500" />
                </button>
            </div>

            <nav class="space-y-1 px-3 py-4">
                <Link
                    v-for="item in navigation"
                    :key="item.route"
                    :href="route(item.route)"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition"
                    :class="
                        isActive(item.route)
                            ? 'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-300'
                            : 'text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700/60'
                    "
                >
                    <component :is="item.icon" class="h-5 w-5 shrink-0" />
                    {{ item.name }}
                </Link>
            </nav>
        </aside>

        <!-- Main -->
        <div class="lg:pl-64">
            <header
                class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-gray-200 bg-white/80 px-4 backdrop-blur dark:border-gray-700 dark:bg-gray-800/80 sm:px-6"
            >
                <button class="lg:hidden" @click="sidebarOpen = true">
                    <Bars3Icon class="h-6 w-6 text-gray-600 dark:text-gray-300" />
                </button>

                <div class="ml-auto flex items-center gap-3">
                    <button
                        class="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"
                        :title="isDark ? 'Modo claro' : 'Modo escuro'"
                        @click="toggle"
                    >
                        <SunIcon v-if="isDark" class="h-5 w-5" />
                        <MoonIcon v-else class="h-5 w-5" />
                    </button>

                    <Menu as="div" class="relative">
                        <MenuButton
                            class="flex items-center gap-2 rounded-lg p-1.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                            <UserCircleIcon class="h-7 w-7 text-gray-400" />
                            <span class="hidden text-left sm:block">
                                <span class="block font-medium text-gray-800 dark:text-gray-100">{{ user?.name }}</span>
                                <span class="block text-xs text-gray-400">{{ user?.roles?.[0] }}</span>
                            </span>
                        </MenuButton>
                        <TransitionRoot
                            enter="transition ease-out duration-100"
                            enter-from="opacity-0 scale-95"
                            enter-to="opacity-100 scale-100"
                            leave="transition ease-in duration-75"
                            leave-from="opacity-100 scale-100"
                            leave-to="opacity-0 scale-95"
                        >
                            <MenuItems
                                class="absolute right-0 mt-2 w-48 origin-top-right rounded-lg border border-gray-200 bg-white py-1 shadow-lg focus:outline-none dark:border-gray-700 dark:bg-gray-800"
                            >
                                <MenuItem v-slot="{ active }">
                                    <Link
                                        :href="route('profile.edit')"
                                        class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-200"
                                        :class="active ? 'bg-gray-100 dark:bg-gray-700' : ''"
                                    >
                                        <UserCircleIcon class="h-4 w-4" /> Perfil
                                    </Link>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <button
                                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-600 dark:text-red-400"
                                        :class="active ? 'bg-gray-100 dark:bg-gray-700' : ''"
                                        @click="logout"
                                    >
                                        <ArrowRightOnRectangleIcon class="h-4 w-4" /> Terminar sessão
                                    </button>
                                </MenuItem>
                            </MenuItems>
                        </TransitionRoot>
                    </Menu>
                </div>
            </header>

            <!-- Flash -->
            <div v-if="flashSuccess || flashError" class="px-4 pt-4 sm:px-6">
                <div
                    v-if="flashSuccess"
                    class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
                >
                    {{ flashSuccess }}
                </div>
                <div
                    v-if="flashError"
                    class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"
                >
                    {{ flashError }}
                </div>
            </div>

            <main class="p-4 sm:p-6">
                <div class="mb-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        <slot name="title" />
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        <slot name="subtitle" />
                    </p>
                </div>
                <slot />
            </main>
        </div>
    </div>
</template>
