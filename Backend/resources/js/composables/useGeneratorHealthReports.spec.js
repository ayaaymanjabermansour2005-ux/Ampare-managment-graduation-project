import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/generatorService', () => ({
    default: { healthReports: vi.fn() },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_generators: {
                health_reports_load_error: 'تعذّر تحميل تقارير الصحة.',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useGeneratorHealthReports } = await import('./useGeneratorHealthReports');
const generatorService = (await import('@/services/generatorService')).default;

describe('useGeneratorHealthReports', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with an empty list, not loading, and no error', () => {
        const { reports, isLoading, error } = useGeneratorHealthReports(7);

        expect(reports.value).toEqual([]);
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
    });

    it('fetchReports forwards the generatorId passed into the composable and stores data.data', async () => {
        generatorService.healthReports.mockResolvedValue({ data: { data: [{ id: 1, score: 90 }] } });

        const { reports, isLoading, fetchReports } = useGeneratorHealthReports(7);
        await fetchReports();

        expect(generatorService.healthReports).toHaveBeenCalledWith(7);
        expect(reports.value).toEqual([{ id: 1, score: 90 }]);
        expect(isLoading.value).toBe(false);
    });

    it('uses whatever generatorId the composable was created with, for a different instance', async () => {
        generatorService.healthReports.mockResolvedValue({ data: { data: [] } });

        const { fetchReports } = useGeneratorHealthReports(42);
        await fetchReports();

        expect(generatorService.healthReports).toHaveBeenCalledWith(42);
    });

    it('handles an empty result set without throwing (no reports is a valid state)', async () => {
        generatorService.healthReports.mockResolvedValue({ data: { data: [] } });

        const { reports, fetchReports } = useGeneratorHealthReports(7);
        await fetchReports();

        expect(reports.value).toEqual([]);
    });

    it('sets error to the server message on a 422', async () => {
        generatorService.healthReports.mockRejectedValue({
            response: { status: 422, data: { message: 'خطأ في الطلب.' } },
        });

        const { error, fetchReports } = useGeneratorHealthReports(7);
        await fetchReports();

        expect(error.value).toBe('خطأ في الطلب.');
    });

    it('falls back to the translated message on a network error', async () => {
        generatorService.healthReports.mockRejectedValue({ message: 'Network Error' });

        const { error, isLoading, fetchReports } = useGeneratorHealthReports(7);
        await fetchReports();

        expect(error.value).toBe('تعذّر تحميل تقارير الصحة.');
        expect(isLoading.value).toBe(false);
    });
});
