import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/platformCommissionService', () => ({
    default: { list: vi.fn(), updateStatus: vi.fn() },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            admin_payments_page: {
                owner_payments_load_error: 'تعذّر تحميل دفعات الملّاك.',
                owner_payments_update_error: 'تعذّر تحديث حالة العمولة.',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminOwnerCommissions } = await import('./useAdminOwnerCommissions');
const platformCommissionService = (await import('@/services/platformCommissionService')).default;

describe('useAdminOwnerCommissions', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with an empty list, not loading, and no error', () => {
        const { commissions, isLoading, error, updatingId, updateError } = useAdminOwnerCommissions();

        expect(commissions.value).toEqual([]);
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(updatingId.value).toBeNull();
        expect(updateError.value).toBeNull();
    });

    describe('fetchCommissions', () => {
        it('requests the given page with per_page 100 and stores the unwrapped list', async () => {
            platformCommissionService.list.mockResolvedValue({
                data: { data: { data: [{ id: 1, status: 'pending' }] } },
            });

            const { commissions, isLoading, fetchCommissions } = useAdminOwnerCommissions();
            await fetchCommissions(2);

            expect(platformCommissionService.list).toHaveBeenCalledWith({ page: 2, per_page: 100 });
            expect(commissions.value).toEqual([{ id: 1, status: 'pending' }]);
            expect(isLoading.value).toBe(false);
        });

        it('defaults to page 1 when called with no argument', async () => {
            platformCommissionService.list.mockResolvedValue({ data: { data: [] } });

            const { fetchCommissions } = useAdminOwnerCommissions();
            await fetchCommissions();

            expect(platformCommissionService.list).toHaveBeenCalledWith({ page: 1, per_page: 100 });
        });

        it('falls back to the payload itself when it has no nested .data (flat array edge case)', async () => {
            platformCommissionService.list.mockResolvedValue({ data: { data: [{ id: 3 }, { id: 4 }] } });

            const { commissions, fetchCommissions } = useAdminOwnerCommissions();
            await fetchCommissions();

            expect(commissions.value).toEqual([{ id: 3 }, { id: 4 }]);
        });

        it('sets error to the server message on a 422', async () => {
            platformCommissionService.list.mockRejectedValue({
                response: { status: 422, data: { message: 'بيانات غير صالحة.' } },
            });

            const { error, fetchCommissions } = useAdminOwnerCommissions();
            await fetchCommissions();

            expect(error.value).toBe('بيانات غير صالحة.');
        });

        it('falls back to the translated message on a network error', async () => {
            platformCommissionService.list.mockRejectedValue({ message: 'Network Error' });

            const { error, fetchCommissions } = useAdminOwnerCommissions();
            await fetchCommissions();

            expect(error.value).toBe('تعذّر تحميل دفعات الملّاك.');
        });
    });

    describe('markPaid', () => {
        it('replaces the matching commission in-place with the server response on success', async () => {
            platformCommissionService.list.mockResolvedValue({
                data: { data: [{ id: 1, status: 'pending' }, { id: 2, status: 'pending' }] },
            });
            platformCommissionService.updateStatus.mockResolvedValue({ data: { data: { id: 1, status: 'paid' } } });

            const { commissions, fetchCommissions, markPaid, updatingId, updateError } = useAdminOwnerCommissions();
            await fetchCommissions();
            const result = await markPaid(1);

            expect(platformCommissionService.updateStatus).toHaveBeenCalledWith(1, 'paid');
            expect(result).toBe(true);
            expect(commissions.value[0]).toEqual({ id: 1, status: 'paid' });
            expect(commissions.value[1]).toEqual({ id: 2, status: 'pending' });
            expect(updatingId.value).toBeNull();
            expect(updateError.value).toBeNull();
        });

        it('is a silent no-op on the list when the id is not found locally, but still resolves true', async () => {
            platformCommissionService.updateStatus.mockResolvedValue({ data: { data: { id: 999, status: 'paid' } } });

            const { commissions, markPaid } = useAdminOwnerCommissions();
            const result = await markPaid(999);

            expect(result).toBe(true);
            expect(commissions.value).toEqual([]);
        });

        it('sets updateError to the server message on a 422 and returns false', async () => {
            platformCommissionService.updateStatus.mockRejectedValue({
                response: { status: 422, data: { message: 'لا يمكن تحديث الحالة.' } },
            });

            const { markPaid, updateError } = useAdminOwnerCommissions();
            const result = await markPaid(1);

            expect(result).toBe(false);
            expect(updateError.value).toBe('لا يمكن تحديث الحالة.');
        });

        it('falls back to the translated message on a network error', async () => {
            platformCommissionService.updateStatus.mockRejectedValue({ message: 'Network Error' });

            const { markPaid, updateError } = useAdminOwnerCommissions();
            await markPaid(1);

            expect(updateError.value).toBe('تعذّر تحديث حالة العمولة.');
        });
    });
});
