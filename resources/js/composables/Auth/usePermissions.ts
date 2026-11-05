import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

type AuthUser = {
    permissions?: string[];
    roles?: string[];
};

export function usePermissions() {
    const page = usePage();

    const permissions = computed<string[]>(() => {
        const auth = page.props.auth as { user?: AuthUser | null } | undefined;

        return auth?.user?.permissions ?? [];
    });

    const roles = computed<string[]>(() => {
        const auth = page.props.auth as { user?: AuthUser | null } | undefined;

        return auth?.user?.roles ?? [];
    });

    function can(permission: string): boolean {
        return permissions.value.includes(permission);
    }

    return { permissions, roles, can };
}
