import { describe, it, expect, beforeEach, vi } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

vi.mock('@/config/menu.js', () => ({
    default: [
        { label: 'Dashboard', route: 'admin.dashboard', roles: ['admin'] },
        { label: 'Generators', route: 'admin.generators', roles: ['admin'], permission: 'generators.view' },
        { label: 'Owner Dashboard', route: 'owner.dashboard', roles: ['generator_owner'] },
        { label: 'Public Page', route: 'public.page' },
    ],
}));

const { useMenu } = await import('./useMenu');
const { useAuthStore } = await import('@/stores/auth');

describe('useMenu', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('includes an item with no roles/permission restriction regardless of the current user', () => {
        const { visibleMenu } = useMenu();

        expect(visibleMenu.value.map((i) => i.route)).toContain('public.page');
    });

    it('excludes items whose required role does not match the current user role', () => {
        useAuthStore().roles = ['generator_owner'];
        const { visibleMenu } = useMenu();

        const routes = visibleMenu.value.map((i) => i.route);
        expect(routes).not.toContain('admin.dashboard');
        expect(routes).toContain('owner.dashboard');
    });

    it('excludes a role-matched item when the required permission is missing', () => {
        useAuthStore().roles = ['admin'];
        const { visibleMenu } = useMenu();

        expect(visibleMenu.value.map((i) => i.route)).not.toContain('admin.generators');
    });

    it('includes a role-matched item once the required permission is present', () => {
        const authStore = useAuthStore();
        authStore.roles = ['admin'];
        authStore.permissions = ['generators.view'];
        const { visibleMenu } = useMenu();

        expect(visibleMenu.value.map((i) => i.route)).toContain('admin.generators');
    });

    it('is reactive to auth store changes (the computed re-evaluates after login)', () => {
        const authStore = useAuthStore();
        const { visibleMenu } = useMenu();

        expect(visibleMenu.value.map((i) => i.route)).not.toContain('admin.dashboard');

        authStore.roles = ['admin'];

        expect(visibleMenu.value.map((i) => i.route)).toContain('admin.dashboard');
    });

    it('shows only unrestricted items for a logged-out user', () => {
        const { visibleMenu } = useMenu();

        expect(visibleMenu.value.map((i) => i.route)).toEqual(['public.page']);
    });
});
