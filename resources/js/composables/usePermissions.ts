import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();

    const user = computed(() => page.props.auth?.user ?? null);

    function can(permission: string): boolean {
        if (!user.value) return false;
        if (user.value.roles?.includes('admin')) return true;
        return user.value.permissions?.includes(permission) ?? false;
    }

    function hasRole(role: string): boolean {
        return user.value?.roles?.includes(role) ?? false;
    }

    return { user, can, hasRole };
}
