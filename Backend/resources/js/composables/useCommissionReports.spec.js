import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/platformCommissionService', () => ({
    default: { list: vi.fn(), summary: vi.fn(), updateStatus: vi.fn() },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_reports: {
                load_error: 'تعذّر تحميل تقرير العمولات.',
                mark_paid_error: 'تعذّر تحديث حالة العمولة.',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useCommissionReports } = await import('./useCommissionReports');
const platformCommissionService = (await import('@/services/platformCommissionService')).default;

describe('useCommissionReports', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with default pagination, an empty list, and a zeroed summary', () => {
        const { commissions, pagination, isLoading, error, summary, totalEarned } = useCommissionReports();

        expect(commissions.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(summary.value).toEqual({ earned: 0, paid: 0, pending: 0 });
        expect(totalEarned.value).toBe(0);
    });

    describe('fetchSummary', () => {
        it('stores earned/paid/pending from the response and updates the totalEarned computed', async () => {
            platformCommissionService.summary.mockResolvedValue({ data: { data: { earned: 500, paid: 300, pending: 200 } } });

            const { summary, isLoadingSummary, totalEarned, fetchSummary } = useCommissionReports();
            await fetchSummary();

            expect(summary.value).toEqual({ earned: 500, paid: 300, pending: 200 });
            expect(totalEarned.value).toBe(500);
            expect(isLoadingSummary.value).toBe(false);
        });

        it('defaults missing fields to 0', async () => {
            platformCommissionService.summary.mockResolvedValue({ data: { data: {} } });

            const { summary, fetchSummary } = useCommissionReports();
            await fetchSummary();

            expect(summary.value).toEqual({ earned: 0, paid: 0, pending: 0 });
        });

        it('silently swallows a rejection, leaving the summary at its previous (default) value', async () => {
            platformCommissionService.summary.mockRejectedValue(new Error('Network Error'));

            const { summary, isLoadingSummary, fetchSummary } = useCommissionReports();
            await expect(fetchSummary()).resolves.toBeUndefined();

            expect(summary.value).toEqual({ earned: 0, paid: 0, pending: 0 });
            expect(isLoadingSummary.value).toBe(false);
        });
    });

    describe('fetchCommissions', () => {
        it('stores the list and full pagination meta on success', async () => {
            platformCommissionService.list.mockResolvedValue({
                data: { data: { data: [{ id: 1 }], meta: { current_page: 2, last_page: 5, total: 50, per_page: 15 } } },
            });

            const { commissions, pagination, isLoading, fetchCommissions } = useCommissionReports();
            await fetchCommissions(2);

            expect(platformCommissionService.list).toHaveBeenCalledWith({ page: 2 });
            expect(commissions.value).toEqual([{ id: 1 }]);
            expect(pagination.value).toEqual({ current_page: 2, last_page: 5, total: 50, per_page: 15 });
            expect(isLoading.value).toBe(false);
        });

        it('falls back total to commissions.length when meta has no total (edge case)', async () => {
            platformCommissionService.list.mockResolvedValue({ data: { data: [{ id: 1 }, { id: 2 }] } });

            const { commissions, pagination, fetchCommissions } = useCommissionReports();
            await fetchCommissions();

            expect(commissions.value).toHaveLength(2);
            expect(pagination.value.total).toBe(2);
            expect(pagination.value.current_page).toBe(1);
            expect(pagination.value.last_page).toBe(1);
            expect(pagination.value.per_page).toBe(15);
        });

        it('sets error to the server message on a 422', async () => {
            platformCommissionService.list.mockRejectedValue({
                response: { status: 422, data: { message: 'بيانات غير صالحة.' } },
            });

            const { error, fetchCommissions } = useCommissionReports();
            await fetchCommissions();

            expect(error.value).toBe('بيانات غير صالحة.');
        });

        it('falls back to the translated message on a network error', async () => {
            platformCommissionService.list.mockRejectedValue({ message: 'Network Error' });

            const { error, fetchCommissions } = useCommissionReports();
            await fetchCommissions();

            expect(error.value).toBe('تعذّر تحميل تقرير العمولات.');
        });
    });

    describe('markAsPaid', () => {
        it('replaces the matching commission and re-triggers fetchSummary on success', async () => {
            platformCommissionService.list.mockResolvedValue({
                data: { data: [{ id: 1, status: 'pending' }], meta: { current_page: 1, last_page: 1, total: 1, per_page: 15 } },
            });
            platformCommissionService.updateStatus.mockResolvedValue({ data: { data: { id: 1, status: 'paid' } } });
            platformCommissionService.summary.mockResolvedValue({ data: { data: { earned: 10, paid: 10, pending: 0 } } });

            const { commissions, fetchCommissions, markAsPaid, markingId, markError } = useCommissionReports();
            await fetchCommissions();
            const result = await markAsPaid(1);

            expect(platformCommissionService.updateStatus).toHaveBeenCalledWith(1, 'paid');
            expect(result).toBe(true);
            expect(commissions.value[0]).toEqual({ id: 1, status: 'paid' });
            expect(platformCommissionService.summary).toHaveBeenCalledTimes(1);
            expect(markingId.value).toBeNull();
            expect(markError.value).toBeNull();
        });

        it('sets markError to the server message on a 422 and returns false', async () => {
            platformCommissionService.updateStatus.mockRejectedValue({
                response: { status: 422, data: { message: 'غير مسموح.' } },
            });

            const { markAsPaid, markError } = useCommissionReports();
            const result = await markAsPaid(1);

            expect(result).toBe(false);
            expect(markError.value).toBe('غير مسموح.');
        });

        it('falls back to the translated message on a network error', async () => {
            platformCommissionService.updateStatus.mockRejectedValue({ message: 'Network Error' });

            const { markAsPaid, markError } = useCommissionReports();
            await markAsPaid(1);

            expect(markError.value).toBe('تعذّر تحديث حالة العمولة.');
        });
    });
});
