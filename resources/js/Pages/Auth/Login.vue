<script setup lang="ts">
import { useDarkMode } from '@/composables/useDarkMode';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    BoltIcon,
    ChartBarSquareIcon,
    CheckCircleIcon,
    DevicePhoneMobileIcon,
    ExclamationCircleIcon,
    EyeIcon,
    EyeSlashIcon,
    LockClosedIcon,
    MoonIcon,
    PaperAirplaneIcon,
    ShieldCheckIcon,
    SunIcon,
} from '@heroicons/vue/24/outline';
import { onMounted, ref } from 'vue';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const { isDark, init, toggle } = useDarkMode();
onMounted(() => init());

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};

const features = [
    { icon: PaperAirplaneIcon, title: 'Envio em massa', text: 'SMS individuais ou para milhares de contactos.' },
    { icon: DevicePhoneMobileIcon, title: 'Multi-dispositivo', text: 'Vários telemóveis e números num só painel.' },
    { icon: ChartBarSquareIcon, title: 'Relatórios em tempo real', text: 'Entregas, falhas e consumo por empresa.' },
    { icon: ShieldCheckIcon, title: 'API segura', text: 'Tokens, webhooks e permissões granulares.' },
];
</script>

<template>
    <Head title="Entrar" />

    <div class="flex min-h-screen bg-white dark:bg-gray-950">
        <!-- Painel de marca -->
        <div class="relative hidden w-1/2 overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-indigo-900 lg:flex lg:flex-col lg:justify-between lg:p-12">
            <!-- formas decorativas -->
            <div class="pointer-events-none absolute inset-0 opacity-20">
                <div class="absolute -left-20 -top-20 h-80 w-80 rounded-full bg-white/30 blur-3xl" />
                <div class="absolute bottom-0 right-0 h-96 w-96 rounded-full bg-indigo-400/40 blur-3xl" />
            </div>

            <div class="relative z-10 flex items-center gap-3 text-white">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 backdrop-blur">
                    <PaperAirplaneIcon class="h-6 w-6 -rotate-45" />
                </div>
                <span class="text-xl font-bold">SMS Gateway Manager</span>
            </div>

            <div class="relative z-10 text-white">
                <h1 class="max-w-md text-4xl font-bold leading-tight">
                    A tua plataforma de SMS, ao nível dos melhores.
                </h1>
                <p class="mt-4 max-w-md text-brand-100">
                    Envia, agenda e acompanha mensagens com fiabilidade — com API REST,
                    multi-empresa e relatórios profissionais.
                </p>

                <div class="mt-10 grid grid-cols-2 gap-5">
                    <div v-for="f in features" :key="f.title" class="flex items-start gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white/15">
                            <component :is="f.icon" class="h-5 w-5 text-white" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-white">{{ f.title }}</p>
                            <p class="text-xs text-brand-100/80">{{ f.text }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="relative z-10 flex items-center gap-2 text-xs text-brand-100/70">
                <BoltIcon class="h-4 w-4" />
                Powered by httpSMS · {{ new Date().getFullYear() }}
            </div>
        </div>

        <!-- Painel do formulário -->
        <div class="flex w-full flex-col justify-center px-6 py-12 sm:px-12 lg:w-1/2 lg:px-20">
            <div class="absolute right-5 top-5">
                <button
                    @click="toggle"
                    class="rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 dark:hover:bg-gray-800"
                    :title="isDark ? 'Modo claro' : 'Modo escuro'"
                >
                    <SunIcon v-if="isDark" class="h-5 w-5" />
                    <MoonIcon v-else class="h-5 w-5" />
                </button>
            </div>

            <div class="mx-auto w-full max-w-md">
                <!-- logo mobile -->
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-brand-600 text-white">
                        <PaperAirplaneIcon class="h-5 w-5 -rotate-45" />
                    </div>
                    <span class="text-lg font-bold text-gray-900 dark:text-white">SMS Gateway</span>
                </div>

                <h2 class="text-3xl font-bold tracking-tight text-gray-900 dark:text-white">Bem-vindo de volta</h2>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    Entra na tua conta para gerir os teus SMS.
                </p>

                <div
                    v-if="status"
                    class="mt-6 flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300"
                >
                    <CheckCircleIcon class="h-5 w-5" />
                    {{ status }}
                </div>

                <form class="mt-8 space-y-5" @submit.prevent="submit">
                    <!-- Email -->
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="nome@empresa.com"
                            class="block w-full rounded-xl border-gray-300 bg-white px-4 py-3 text-sm shadow-sm transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/40 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                            :class="form.errors.email ? 'border-red-400 focus:border-red-500 focus:ring-red-500/30' : ''"
                        />
                        <p v-if="form.errors.email" class="mt-1.5 flex items-center gap-1 text-sm text-red-600">
                            <ExclamationCircleIcon class="h-4 w-4" /> {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Palavra-passe</label>
                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm font-medium text-brand-600 hover:text-brand-700 hover:underline"
                            >
                                Esqueceste-te?
                            </Link>
                        </div>
                        <div class="relative">
                            <LockClosedIcon class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                autocomplete="current-password"
                                placeholder="••••••••"
                                class="block w-full rounded-xl border-gray-300 bg-white px-4 py-3 pl-11 pr-11 text-sm shadow-sm transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/40 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
                                :class="form.errors.password ? 'border-red-400 focus:border-red-500 focus:ring-red-500/30' : ''"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 rounded-md p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                                tabindex="-1"
                            >
                                <EyeSlashIcon v-if="showPassword" class="h-5 w-5" />
                                <EyeIcon v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 flex items-center gap-1 text-sm text-red-600">
                            <ExclamationCircleIcon class="h-4 w-4" /> {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Remember -->
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                        <input
                            type="checkbox"
                            v-model="form.remember"
                            class="rounded border-gray-300 text-brand-600 focus:ring-brand-500 dark:border-gray-600 dark:bg-gray-900"
                        />
                        Manter sessão iniciada
                    </label>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-brand-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/50 disabled:opacity-60"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>
                        {{ form.processing ? 'A entrar…' : 'Entrar' }}
                    </button>
                </form>

                <p class="mt-8 text-center text-xs text-gray-400">
                    Ao entrar aceitas os termos de utilização da plataforma.
                </p>
            </div>
        </div>
    </div>
</template>
