import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/neighborhoodDashboardService', () => ({
    default: { summary: vi.fn() },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            neighborhood_dashboard_page: {
                load_error: 'تعذّر تحميل ملخص الأحياء.',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useNeighborhoodDashboard } = await import('./useNeighborhoodDashboard');
const neighborhoodDashboardService = (await import('@/services/neighborhoodDashboardService')).default;

describe('useNeighborhoodDashboard', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with an empty list, not loading, and no error', () => {
        const { neighborhoods, isLoading, error } = useNeighborhoodDashboard();

        expect(neighborhoods.value).toEqual([]);
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
    });

    it('fetchSummary stores data.data on success', async () => {
        neighborhoodDashboardService.summary.mockResolvedValue({
            data: { data: [{ id: 1, name: 'حي الرمال', open_faults: 2 }] },
        });

        const { neighborhoods, isLoading, error, fetchSummary } = useNeighborhoodDashboard();
        await fetchSummary();

        expect(neighborhoods.value).toEqual([{ id: 1, name: 'حي الرمال', open_faults: 2 }]);
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
    });

    it('handles an empty result set without throwing (no neighborhoods is a valid state)', async () => {
        neighborhoodDashboardService.summary.mockResolvedValue({ data: { data: [] } });

        const { neighborhoods, fetchSummary } = useNeighborhoodDashboard();
        await fetchSummary();

        expect(neighborhoods.value).toEqual([]);
    });

    it('sets error to the server message on a 422', async () => {
        neighborhoodDashboardService.summary.mockRejectedValue({
            response: { status: 422, data: { message: 'خطأ في الطلب.' } },
        });

        const { error, fetchSummary } = useNeighborhoodDashboard();
        await fetchSummary();

        expect(error.value).toBe('خطأ في الطلب.');
    });

    it('falls back to the translated message on a network error, and clears isLoading', async () => {
        neighborhoodDashboardService.summary.mockRejectedValue({ message: 'Network Error' });

        const { error, isLoading, fetchSummary } = useNeighborhoodDashboard();
        await fetchSummary();

        expect(error.value).toBe('تعذّر تحميل ملخص الأحياء.');
        expect(isLoading.value).toBe(false);
    });
});
