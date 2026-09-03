import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/rolePermissionService', () => ({
    default: {
        index: vi.fn(),
        sync: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            roles_permissions_page: {
                load_error: 'تعذر تحميل الأدوار والصلاحيات',
                save_error: 'تعذر حفظ الصلاحيات',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useRolePermissions } = await import('./useRolePermissions');
const rolePermissionService = (await import('@/services/rolePermissionService')).default;

describe('useRolePermissions', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts loading, with empty roles/permissions and no error', () => {
        const { roles, allPermissions, isLoading, error, isSaving } = useRolePermissions();

        expect(roles.value).toEqual([]);
        expect(allPermissions.value).toEqual([]);
        expect(isLoading.value).toBe(true);
        expect(error.value).toBeNull();
        expect(isSaving.value).toBe(false);
    });

    it('fetchData populates roles and allPermissions on success', async () => {
        rolePermissionService.index.mockResolvedValue({
            data: { data: { roles: [{ id: 1, name: 'admin', permissions: ['x'] }], all_permissions: ['x', 'y'] } },
        });

        const { fetchData, roles, allPermissions, isLoading, error } = useRolePermissions();
        await fetchData();

        expect(roles.value).toEqual([{ id: 1, name: 'admin', permissions: ['x'] }]);
        expect(allPermissions.value).toEqual(['x', 'y']);
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
    });

    it('fetchData sets the translated fallback message on a network error', async () => {
        rolePermissionService.index.mockRejectedValue({ message: 'Network Error' });

        const { fetchData, error, isLoading } = useRolePermissions();
        await fetchData();

        expect(error.value).toBe('تعذر تحميل الأدوار والصلاحيات');
        expect(isLoading.value).toBe(false);
    });

    it('fetchData surfaces the backend message on a non-network failure', async () => {
        rolePermissionService.index.mockRejectedValue({
            response: { status: 500, data: { message: 'خطأ في الخادم.' } },
        });

        const { fetchData, error } = useRolePermissions();
        await fetchData();

        expect(error.value).toBe('خطأ في الخادم.');
    });

    it('saveRole calls sync with the role id/permissions and returns true on success', async () => {
        rolePermissionService.sync.mockResolvedValue({});

        const { saveRole, isSaving, error } = useRolePermissions();
        const result = await saveRole({ id: 5, permissions: ['a', 'b'] });

        expect(result).toBe(true);
        expect(rolePermissionService.sync).toHaveBeenCalledWith(5, ['a', 'b']);
        expect(isSaving.value).toBe(false);
        expect(error.value).toBeNull();
    });

    it('saveRole returns false and sets the translated fallback message on failure', async () => {
        rolePermissionService.sync.mockRejectedValue({ message: 'Network Error' });

        const { saveRole, error, isSaving } = useRolePermissions();
        const result = await saveRole({ id: 5, permissions: [] });

        expect(result).toBe(false);
        expect(error.value).toBe('تعذر حفظ الصلاحيات');
        expect(isSaving.value).toBe(false);
    });
});
