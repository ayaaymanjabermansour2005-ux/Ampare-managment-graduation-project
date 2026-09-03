import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/adminDashboardService', () => ({
    default: {
        stats: vi.fn(),
        invoiceStatusBreakdown: vi.fn(),
        alerts: vi.fn(),
    },
}));
// useGeneratorsTable (composed internally, not mocked) calls generatorService
// directly, so its dependency has to be mocked here too.
vi.mock('@/services/generatorService', () => ({
    default: { list: vi.fn(), cities: vi.fn() },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_generators: {
                load_error: 'تعذّر تحميل قائمة المولدات.',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminDashboardFull } = await import('./useAdminDashboardFull');
const adminDashboardService = (await import('@/services/adminDashboardService')).default;
const generatorService = (await import('@/services/generatorService')).default;

describe('useAdminDashboardFull', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts loading with null stats/breakdown, an empty alerts list, and 0% invoicePercentages', () => {
        const { stats, isLoadingStats, invoiceBreakdown, isLoadingBreakdown, invoicePercentages, alerts, isLoadingAlerts } = useAdminDashboardFull();

        expect(stats.value).toBeNull();
        expect(isLoadingStats.value).toBe(true);
        expect(invoiceBreakdown.value).toBeNull();
        expect(isLoadingBreakdown.value).toBe(true);
        expect(invoicePercentages.value).toEqual({ paid: 0, pending: 0, overdue: 0 });
        expect(alerts.value).toEqual([]);
        expect(isLoadingAlerts.value).toBe(true);
    });

    it('fetchStats stores data.data and clears isLoadingStats', async () => {
        adminDashboardService.stats.mockResolvedValue({ data: { data: { generators: 10, subscribers: 200 } } });

        const { stats, isLoadingStats, fetchStats } = useAdminDashboardFull();
        await fetchStats();

        expect(stats.value).toEqual({ generators: 10, subscribers: 200 });
        expect(isLoadingStats.value).toBe(false);
    });

    it('fetchInvoiceBreakdown stores data.data and invoicePercentages computes rounded percentages from it', async () => {
        adminDashboardService.invoiceStatusBreakdown.mockResolvedValue({
            data: { data: { paid: 30, pending: 10, overdue: 10, total: 50 } },
        });

        const { invoiceBreakdown, isLoadingBreakdown, invoicePercentages, fetchInvoiceBreakdown } = useAdminDashboardFull();
        await fetchInvoiceBreakdown();

        expect(invoiceBreakdown.value).toEqual({ paid: 30, pending: 10, overdue: 10, total: 50 });
        expect(isLoadingBreakdown.value).toBe(false);
        expect(invoicePercentages.value).toEqual({ paid: 60, pending: 20, overdue: 20 });
    });

    it('invoicePercentages stays at 0/0/0 when total is 0 (guards a division by zero)', async () => {
        adminDashboardService.invoiceStatusBreakdown.mockResolvedValue({
            data: { data: { paid: 0, pending: 0, overdue: 0, total: 0 } },
        });

        const { invoicePercentages, fetchInvoiceBreakdown } = useAdminDashboardFull();
        await fetchInvoiceBreakdown();

        expect(invoicePercentages.value).toEqual({ paid: 0, pending: 0, overdue: 0 });
    });

    it('fetchAlerts stores data.data and clears isLoadingAlerts', async () => {
        adminDashboardService.alerts.mockResolvedValue({ data: { data: [{ id: 1, level: 'critical' }] } });

        const { alerts, isLoadingAlerts, fetchAlerts } = useAdminDashboardFull();
        await fetchAlerts();

        expect(alerts.value).toEqual([{ id: 1, level: 'critical' }]);
        expect(isLoadingAlerts.value).toBe(false);
    });

    it('loadAll loads stats, invoice breakdown, alerts, and (via the spread useGeneratorsTable) generators + cities in parallel', async () => {
        adminDashboardService.stats.mockResolvedValue({ data: { data: { generators: 5 } } });
        adminDashboardService.invoiceStatusBreakdown.mockResolvedValue({ data: { data: { paid: 1, pending: 1, overdue: 0, total: 2 } } });
        adminDashboardService.alerts.mockResolvedValue({ data: { data: [] } });
        generatorService.list.mockResolvedValue({
            data: { data: { data: [{ id: 1, name: 'G1', fuel_percentage: 50 }], meta: { current_page: 1, last_page: 1, total: 1, per_page: 8 } } },
        });
        generatorService.cities.mockResolvedValue({ data: { data: ['Gaza'] } });

        const { stats, invoiceBreakdown, alerts, generators, cities, loadAll } = useAdminDashboardFull();
        await loadAll();

        expect(stats.value).toEqual({ generators: 5 });
        expect(invoiceBreakdown.value).toEqual({ paid: 1, pending: 1, overdue: 0, total: 2 });
        expect(alerts.value).toEqual([]);
        expect(generators.value).toEqual([{ id: 1, name: 'G1', fuel_percentage: 50 }]);
        expect(cities.value).toEqual(['Gaza']);
        expect(generatorService.list).toHaveBeenCalledWith(expect.objectContaining({ page: 1 }));
    });

    it('propagates a rejected fetchStats (no catch in the composable) while still clearing isLoadingStats', async () => {
        adminDashboardService.stats.mockRejectedValue(new Error('Network Error'));

        const { isLoadingStats, fetchStats } = useAdminDashboardFull();
        await expect(fetchStats()).rejects.toThrow('Network Error');

        expect(isLoadingStats.value).toBe(false);
    });
});
