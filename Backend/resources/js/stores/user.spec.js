import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

vi.mock('@/services/userService', () => ({
    default: {
        list: vi.fn(),
        update: vi.fn(),
        show: vi.fn(),
        destroy: vi.fn(),
        unlock: vi.fn(),
    },
}));

const { useUserStore } = await import('./user');
const userService = (await import('@/services/userService')).default;

describe('useUserStore.fetchUsers — errors ref stays null when empty, not the always-truthy {} default', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('sets errors to the raw fieldErrors object on a 422 response', async () => {
        userService.list.mockRejectedValue({
            response: { status: 422, data: { message: 'Invalid.', errors: { role: ['دور غير صالح.'] } } },
        });

        const store = useUserStore();
        await expect(store.fetchUsers()).rejects.toBeTruthy();

        expect(store.errors).toEqual({ role: ['دور غير صالح.'] });
        expect(store.error).toBe('Invalid.');
    });

    it('sets errors to null (not {}) when the failure has no field-level errors', async () => {
        userService.list.mockRejectedValue({
            response: { status: 500, data: { message: 'خطأ في الخادم.' } },
        });

        const store = useUserStore();
        await expect(store.fetchUsers()).rejects.toBeTruthy();

        expect(store.errors).toBeNull();
        expect(store.error).toBe('خطأ في الخادم.');
    });

    it('sets errors to null and error to a null-fallback message on a network error', async () => {
        userService.list.mockRejectedValue({ message: 'Network Error' });

        const store = useUserStore();
        await expect(store.fetchUsers()).rejects.toBeTruthy();

        expect(store.errors).toBeNull();
        expect(store.error).toBeNull();
    });

    it('re-throws the original error after setting errors/error (callers rely on this)', async () => {
        const originalError = { response: { status: 500, data: { message: 'خطأ في الخادم.' } } };
        userService.list.mockRejectedValue(originalError);

        const store = useUserStore();
        await expect(store.fetchUsers()).rejects.toBe(originalError);
    });

    it('clears both errors and error at the start of a new fetch, and resolves normally on success', async () => {
        userService.list.mockRejectedValueOnce({
            response: { status: 422, data: { message: 'Invalid.', errors: { role: ['x'] } } },
        });
        const store = useUserStore();
        await expect(store.fetchUsers()).rejects.toBeTruthy();
        expect(store.errors).toEqual({ role: ['x'] });

        userService.list.mockResolvedValueOnce({
            data: { data: { data: [{ id: 1 }], meta: { current_page: 1, last_page: 1, total: 1, per_page: 15 } } },
        });
        await store.fetchUsers();

        expect(store.errors).toBeNull();
        expect(store.error).toBeNull();
        expect(store.users).toEqual([{ id: 1 }]);
    });
});
