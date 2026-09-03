import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/generatorService', () => ({
    default: {
        list: vi.fn(),
    },
}));
vi.mock('@/services/paymentService', () => ({
    default: {
        list: vi.fn(),
    },
}));
vi.mock('@/services/roleDashboardService', () => ({
    default: {
        ownerStats: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_dashboard: {
                load_error: 'تعذر تحميل لوحة التحكم',
                load_extra_stats_error: 'تعذر تحميل الإحصائيات الإضافية',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useOwnerDashboard } = await import('./useOwnerDashboard');
const generatorService = (await import('@/services/generatorService')).default;
const paymentService = (await import('@/services/paymentService')).default;
const roleDashboardService = (await import('@/services/roleDashboardService')).default;

describe('useOwnerDashboard', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('has the expected default reactive state before load()', () => {
        const { generators, pendingPayments, isLoading, error, extraStats, isLoadingExtraStats, extraStatsError } =
            useOwnerDashboard();

        expect(generators.value).toEqual([]);
        expect(pendingPayments.value).toEqual([]);
        expect(isLoading.value).toBe(true);
        expect(error.value).toBeNull();
        expect(extraStats.value).toBeNull();
        expect(isLoadingExtraStats.value).toBe(true);
        expect(extraStatsError.value).toBeNull();
    });

    describe('load', () => {
        it('populates generators and pendingPayments from the nested data.data.data shape', async () => {
            generatorService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, name: 'Gen A' }] } } });
            paymentService.list.mockResolvedValue({ data: { data: { data: [{ id: 2, status: 'pending' }] } } });

            const { load, generators, pendingPayments, isLoading, error } = useOwnerDashboard();
            await load();

            expect(generatorService.list).toHaveBeenCalledWith({ per_page: 5 });
            expect(paymentService.list).toHaveBeenCalledWith({ status: 'pending', per_page: 5 });
            expect(generators.value).toEqual([{ id: 1, name: 'Gen A' }]);
            expect(pendingPayments.value).toEqual([{ id: 2, status: 'pending' }]);
            expect(isLoading.value).toBe(false);
            expect(error.value).toBeNull();
        });

        it('falls back to data.data directly when there is no nested .data (flat array shape)', async () => {
            generatorService.list.mockResolvedValue({ data: { data: [{ id: 5 }] } });
            paymentService.list.mockResolvedValue({ data: { data: [] } });

            const { load, generators, pendingPayments } = useOwnerDashboard();
            await load();

            expect(generators.value).toEqual([{ id: 5 }]);
            expect(pendingPayments.value).toEqual([]);
        });

        it('sets a translated error message and clears loading on a network error', async () => {
            generatorService.list.mockRejectedValue({ message: 'Network Error' });
            paymentService.list.mockResolvedValue({ data: { data: [] } });

            const { load, error, isLoading } = useOwnerDashboard();
            await load();

            expect(error.value).toBe('تعذر تحميل لوحة التحكم');
            expect(isLoading.value).toBe(false);
        });

        it('surfaces the backend message on a 422/500-style response error', async () => {
            generatorService.list.mockResolvedValue({ data: { data: [] } });
            paymentService.list.mockRejectedValue({ response: { status: 500, data: { message: 'خطأ في الخادم' } } });

            const { load, error } = useOwnerDashboard();
            await load();

            expect(error.value).toBe('خطأ في الخادم');
        });
    });

    describe('loadExtraStats', () => {
        it('populates extraStats on success', async () => {
            roleDashboardService.ownerStats.mockResolvedValue({ data: { data: { total_revenue: 1000 } } });

            const { loadExtraStats, extraStats, isLoadingExtraStats, extraStatsError } = useOwnerDashboard();
            await loadExtraStats();

            expect(extraStats.value).toEqual({ total_revenue: 1000 });
            expect(isLoadingExtraStats.value).toBe(false);
            expect(extraStatsError.value).toBeNull();
        });

        it('sets a translated error message on failure and leaves extraStats untouched', async () => {
            roleDashboardService.ownerStats.mockRejectedValue({ message: 'Network Error' });

            const { loadExtraStats, extraStats, extraStatsError, isLoadingExtraStats } = useOwnerDashboard();
            await loadExtraStats();

            expect(extraStatsError.value).toBe('تعذر تحميل الإحصائيات الإضافية');
            expect(extraStats.value).toBeNull();
            expect(isLoadingExtraStats.value).toBe(false);
        });
    });
});
