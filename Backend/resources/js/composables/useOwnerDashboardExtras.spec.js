import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/generatorService', () => ({
    default: {
        list: vi.fn(),
        mapPoints: vi.fn(),
        exportUrl: vi.fn(),
    },
}));
vi.mock('@/services/invoiceService', () => ({
    default: {
        list: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            status: {
                active: 'نشط',
                maintenance: 'صيانة',
                pending_verification: 'قيد التحقق',
            },
            generators_management_page: {
                inactive_rejected: 'غير نشط/مرفوض',
            },
            generators_map: {
                load_error: 'تعذر تحميل الخريطة',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useOwnerDashboardExtras } = await import('./useOwnerDashboardExtras');
const generatorService = (await import('@/services/generatorService')).default;
const invoiceService = (await import('@/services/invoiceService')).default;

describe('useOwnerDashboardExtras', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('has expected default reactive state', () => {
        const {
            allGenerators, isLoadingAllGenerators, mapPoints, isLoadingMapPoints, mapPointsError,
            monthlyPerformance, isLoadingMonthlyPerformance,
        } = useOwnerDashboardExtras();

        expect(allGenerators.value).toEqual([]);
        expect(isLoadingAllGenerators.value).toBe(true);
        expect(mapPoints.value).toEqual([]);
        expect(isLoadingMapPoints.value).toBe(true);
        expect(mapPointsError.value).toBeNull();
        expect(monthlyPerformance.value).toEqual({ labels: [], paidAmounts: [], isEstimate: true });
        expect(isLoadingMonthlyPerformance.value).toBe(true);
    });

    describe('fetchAllGenerators', () => {
        it('populates allGenerators from payload.data on success', async () => {
            generatorService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, status: 'active' }] } } });

            const { fetchAllGenerators, allGenerators, isLoadingAllGenerators } = useOwnerDashboardExtras();
            await fetchAllGenerators();

            expect(generatorService.list).toHaveBeenCalledWith({ per_page: 100 });
            expect(allGenerators.value).toEqual([{ id: 1, status: 'active' }]);
            expect(isLoadingAllGenerators.value).toBe(false);
        });

        it('silently resets to an empty array on failure (no error ref exposed)', async () => {
            generatorService.list.mockRejectedValue({ message: 'Network Error' });

            const { fetchAllGenerators, allGenerators, isLoadingAllGenerators } = useOwnerDashboardExtras();
            await fetchAllGenerators();

            expect(allGenerators.value).toEqual([]);
            expect(isLoadingAllGenerators.value).toBe(false);
        });
    });

    describe('status computeds', () => {
        it('statusBreakdown, statusChartData and statusLegendItems derive from allGenerators', async () => {
            generatorService.list.mockResolvedValue({
                data: {
                    data: {
                        data: [
                            { id: 1, status: 'active' },
                            { id: 2, status: 'active' },
                            { id: 3, status: 'maintenance' },
                            { id: 4, status: 'inactive' },
                            { id: 5, status: 'rejected' },
                            { id: 6, status: 'pending_verification' },
                        ],
                    },
                },
            });

            const { fetchAllGenerators, statusBreakdown, statusChartData, statusLegendItems } = useOwnerDashboardExtras();
            await fetchAllGenerators();

            expect(statusBreakdown.value).toEqual({
                active: 2,
                maintenance: 1,
                inactive: 1,
                pending_verification: 1,
                rejected: 1,
            });

            expect(statusChartData.value.labels).toEqual(['نشط', 'صيانة', 'غير نشط/مرفوض', 'قيد التحقق']);
            expect(statusChartData.value.datasets[0].data).toEqual([2, 1, 2, 1]);

            expect(statusLegendItems.value).toEqual([
                { label: 'نشط', color: '#28A745', value: 2, pct: 33 },
                { label: 'صيانة', color: '#FFC107', value: 1, pct: 17 },
                { label: 'غير نشط/مرفوض', color: '#D9534F', value: 2, pct: 33 },
                { label: 'قيد التحقق', color: '#17A2B8', value: 1, pct: 17 },
            ]);
        });

        it('statusChartData reports all zeros with no generators loaded', () => {
            const { statusChartData } = useOwnerDashboardExtras();

            expect(statusChartData.value.datasets[0].data).toEqual([0, 0, 0, 0]);
        });
    });

    describe('lowestFuelGenerators', () => {
        it('filters out null/undefined fuel_percentage, sorts ascending, and caps at 5', async () => {
            generatorService.list.mockResolvedValue({
                data: {
                    data: {
                        data: [
                            { id: 1, fuel_percentage: 80 },
                            { id: 2, fuel_percentage: null },
                            { id: 3, fuel_percentage: 10 },
                            { id: 4, fuel_percentage: undefined },
                            { id: 5, fuel_percentage: 40 },
                            { id: 6, fuel_percentage: 5 },
                            { id: 7, fuel_percentage: 60 },
                            { id: 8, fuel_percentage: 20 },
                        ],
                    },
                },
            });

            const { fetchAllGenerators, lowestFuelGenerators } = useOwnerDashboardExtras();
            await fetchAllGenerators();

            expect(lowestFuelGenerators.value.map((g) => g.id)).toEqual([6, 3, 8, 5, 7]);
            expect(lowestFuelGenerators.value).toHaveLength(5);
        });
    });

    describe('fetchMapPoints', () => {
        it('populates mapPoints on success', async () => {
            generatorService.mapPoints.mockResolvedValue({ data: { data: [{ id: 1, lat: 1, lng: 2 }] } });

            const { fetchMapPoints, mapPoints, isLoadingMapPoints, mapPointsError } = useOwnerDashboardExtras();
            await fetchMapPoints();

            expect(mapPoints.value).toEqual([{ id: 1, lat: 1, lng: 2 }]);
            expect(isLoadingMapPoints.value).toBe(false);
            expect(mapPointsError.value).toBeNull();
        });

        it('resets mapPoints to [] and sets a translated error on failure', async () => {
            generatorService.mapPoints.mockRejectedValue({ message: 'Network Error' });

            const { fetchMapPoints, mapPoints, mapPointsError } = useOwnerDashboardExtras();
            await fetchMapPoints();

            expect(mapPoints.value).toEqual([]);
            expect(mapPointsError.value).toBe('تعذر تحميل الخريطة');
        });
    });

    describe('fetchMonthlyPerformance', () => {
        it('buckets paid/partially_paid invoices into the trailing 6 months and rounds amounts', async () => {
            vi.useFakeTimers();
            vi.setSystemTime(new Date(2026, 5, 15)); // June 2026

            invoiceService.list.mockResolvedValue({
                data: {
                    data: {
                        data: [
                            { id: 1, status: 'paid', final_amount: '500.4', paid_at: '2026-06-01T12:00:00' },
                            { id: 2, status: 'partially_paid', final_amount: 1000, remaining_balance_ils: 300, paid_at: '2026-05-10T12:00:00' },
                            { id: 3, status: 'pending', final_amount: 999, paid_at: '2026-06-05T12:00:00' },
                            { id: 4, status: 'paid', final_amount: 200 }, // no date at all -> skipped
                        ],
                    },
                },
            });

            const { fetchMonthlyPerformance, monthlyPerformance } = useOwnerDashboardExtras();
            await fetchMonthlyPerformance();

            expect(invoiceService.list).toHaveBeenCalledWith({ per_page: 100 });
            expect(monthlyPerformance.value.labels).toEqual(['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو']);
            expect(monthlyPerformance.value.paidAmounts).toEqual([0, 0, 0, 0, 700, 500]);
            expect(monthlyPerformance.value.isEstimate).toBe(true);
        });

        it('resets to an empty estimate shape on failure', async () => {
            invoiceService.list.mockRejectedValue({ message: 'Network Error' });

            const { fetchMonthlyPerformance, monthlyPerformance } = useOwnerDashboardExtras();
            await fetchMonthlyPerformance();

            expect(monthlyPerformance.value).toEqual({ labels: [], paidAmounts: [], isEstimate: true });
        });
    });

    it('generatorsExportUrl delegates to generatorService.exportUrl with the given params', () => {
        generatorService.exportUrl.mockReturnValue('/export?format=csv');

        const { generatorsExportUrl } = useOwnerDashboardExtras();
        const url = generatorsExportUrl({ format: 'csv' });

        expect(generatorService.exportUrl).toHaveBeenCalledWith({ format: 'csv' });
        expect(url).toBe('/export?format=csv');
    });

    it('printDashboard calls window.print()', () => {
        const printSpy = vi.spyOn(window, 'print').mockImplementation(() => {});

        const { printDashboard } = useOwnerDashboardExtras();
        printDashboard();

        expect(printSpy).toHaveBeenCalledTimes(1);
        printSpy.mockRestore();
    });

    describe('loadAll', () => {
        it('fetches generators, monthly performance and map points together', async () => {
            generatorService.list.mockResolvedValue({ data: { data: [] } });
            invoiceService.list.mockResolvedValue({ data: { data: [] } });
            generatorService.mapPoints.mockResolvedValue({ data: { data: [] } });

            const { loadAll, isLoadingAllGenerators, isLoadingMonthlyPerformance, isLoadingMapPoints } =
                useOwnerDashboardExtras();
            await loadAll();

            expect(generatorService.list).toHaveBeenCalledTimes(1);
            expect(invoiceService.list).toHaveBeenCalledTimes(1);
            expect(generatorService.mapPoints).toHaveBeenCalledTimes(1);
            expect(isLoadingAllGenerators.value).toBe(false);
            expect(isLoadingMonthlyPerformance.value).toBe(false);
            expect(isLoadingMapPoints.value).toBe(false);
        });
    });
});
