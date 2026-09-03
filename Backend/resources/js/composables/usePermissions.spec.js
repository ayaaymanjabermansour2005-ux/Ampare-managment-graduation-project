import { describe, it, expect, beforeEach } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { useAuthStore } from '@/stores/auth';
import { usePermissions } from './usePermissions';

describe('usePermissions', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    it('can() reflects whether the permission is present in the auth store', () => {
        const authStore = useAuthStore();
        authStore.permissions = ['subscriptions.view'];
        authStore.roles = ['admin'];

        const { can } = usePermissions();

        expect(can('subscriptions.view')).toBe(true);
        expect(can('invoices.view')).toBe(false);
    });

    it('canAny() returns true when at least one of the listed permissions is present', () => {
        useAuthStore().permissions = ['subscriptions.view'];

        const { canAny } = usePermissions();

        expect(canAny(['invoices.view', 'subscriptions.view'])).toBe(true);
        expect(canAny(['invoices.view', 'payments.view'])).toBe(false);
    });

    it('canAny() defaults to an empty list and returns false when called with no argument', () => {
        useAuthStore().permissions = ['subscriptions.view'];
        const { canAny } = usePermissions();

        expect(canAny()).toBe(false);
    });

    it('canAll() returns true only when every listed permission is present', () => {
        useAuthStore().permissions = ['subscriptions.view', 'invoices.view'];

        const { canAll } = usePermissions();

        expect(canAll(['subscriptions.view', 'invoices.view'])).toBe(true);
        expect(canAll(['subscriptions.view', 'payments.view'])).toBe(false);
    });

    it('canAll() vacuously returns true for an empty permissions list (Array.every on [])', () => {
        const { canAll } = usePermissions();

        expect(canAll([])).toBe(true);
        expect(canAll()).toBe(true);
    });

    it('hasRole() reflects the roles array in the auth store', () => {
        useAuthStore().roles = ['admin', 'owner'];

        const { hasRole } = usePermissions();

        expect(hasRole('admin')).toBe(true);
        expect(hasRole('technician')).toBe(false);
    });

    it('treats a logged-out (never seeded) auth store as having no permissions/roles', () => {
        const { can, canAny, canAll, hasRole } = usePermissions();

        expect(can('anything')).toBe(false);
        expect(canAny(['anything'])).toBe(false);
        expect(canAll(['anything'])).toBe(false);
        expect(hasRole('admin')).toBe(false);
    });
});
