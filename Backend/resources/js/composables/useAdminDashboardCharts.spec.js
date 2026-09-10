import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/adminDashboardService', () => ({
    default: {
        revenueChart: vi.fn(),
        subscriberGrowthChart: vi.fn(),
        fuelChart: vi.fn(),
        maintenanceChart: vi.fn(),
    },
}));

// نفس نمط useAdminDashboardFull.spec.js — useI18n() الحقيقي بيرمي خطأ
// خارج سياق setup()، فلازم mock بسيط يرجّع t() فعلية (لرسائل الخطأ الجديدة).
const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: { ar: { dashboard: { fuel_chart_load_error: 'تعذّر تحميل مخطط مشتريات الوقود.' } } },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminDashboardCharts } = await import('./useAdminDashboardCharts');
const adminDashboardService = (await import('@/services/adminDashboardService')).default;

describe('useAdminDashboardCharts', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts every chart in a loading state with null data', () => {
        const {
            revenue, isLoadingRevenue,
            subscriberGrowth, isLoadingSubscriberGrowth,
            fuel, isLoadingFuel,
            maintenance, isLoadingMaintenance,
        } = useAdminDashboardCharts();

        expect(revenue.value).toBeNull();
        expect(isLoadingRevenue.value).toBe(true);
        expect(subscriberGrowth.value).toBeNull();
        expect(isLoadingSubscriberGrowth.value).toBe(true);
        expect(fuel.value).toBeNull();
        expect(isLoadingFuel.value).toBe(true);
        expect(maintenance.value).toBeNull();
        expect(isLoadingMaintenance.value).toBe(true);
    });

    it('fetchRevenue forwards params to the service and stores data.data, clearing the loading flag', async () => {
        adminDashboardService.revenueChart.mockResolvedValue({ data: { data: { labels: ['Jan'], values: [100] } } });

        const { revenue, isLoadingRevenue, fetchRevenue } = useAdminDashboardCharts();
        await fetchRevenue({ period: 'year', year: 2026 });

        expect(adminDashboardService.revenueChart).toHaveBeenCalledWith({ period: 'year', year: 2026 });
        expect(revenue.value).toEqual({ labels: ['Jan'], values: [100] });
        expect(isLoadingRevenue.value).toBe(false);
    });

    // NOTE (suspected source bug): fetchSubscriberGrowth, fetchFuel and
    // fetchMaintenance are defined in useAdminDashboardCharts.js but are never
    // included in its returned object (only fetchRevenue and loadAllCharts
    // are) — see resources/js/composables/useAdminDashboardCharts.js lines
    // 69-80. They are therefore only reachable indirectly through
    // loadAllCharts(), which is what the tests below exercise instead of
    // calling them directly (they are not part of the public API to test in
    // isolation).

    it('loadAllCharts loads subscriberGrowth/fuel/maintenance data (via the internal fetch* functions) in parallel but NOT revenue (revenue is independent)', async () => {
        adminDashboardService.subscriberGrowthChart.mockResolvedValue({ data: { data: { a: 1 } } });
        adminDashboardService.fuelChart.mockResolvedValue({ data: { data: { b: 2 } } });
        adminDashboardService.maintenanceChart.mockResolvedValue({ data: { data: { c: 3 } } });

        const { revenue, subscriberGrowth, fuel, maintenance, loadAllCharts } = useAdminDashboardCharts();
        await loadAllCharts();

        expect(subscriberGrowth.value).toEqual({ a: 1 });
        expect(fuel.value).toEqual({ b: 2 });
        expect(maintenance.value).toEqual({ c: 3 });
        expect(revenue.value).toBeNull();
        expect(adminDashboardService.revenueChart).not.toHaveBeenCalled();
    });

    // FIX (تدقيق شامل — لوحة التحكم الرئيسية): كانت هالدالة بلا catch، فأي
    // خطأ بمخطط واحد كان يُسقط loadAllCharts() بالكامل (Promise.all يرفض)،
    // اللي بيمنع تحميل باقي أقسام onMounted بـ DashboardView.vue التالية له.
    // هلق كل خطأ محصور بمخططه فقط عبر fuelError/إلخ، وloadAllCharts() ما
    // بترفض إطلاقًا.
    it('catches a rejected chart fetch inside loadAllCharts, storing its error and leaving the other charts unaffected', async () => {
        adminDashboardService.subscriberGrowthChart.mockResolvedValue({ data: { data: { a: 1 } } });
        adminDashboardService.fuelChart.mockRejectedValue(new Error('Network Error'));
        adminDashboardService.maintenanceChart.mockResolvedValue({ data: { data: { c: 3 } } });

        const { subscriberGrowth, isLoadingFuel, fuelError, loadAllCharts } = useAdminDashboardCharts();
        await expect(loadAllCharts()).resolves.toBeUndefined();

        expect(isLoadingFuel.value).toBe(false);
        expect(fuelError.value).toBe('تعذّر تحميل مخطط مشتريات الوقود.');
        // sibling promises inside the Promise.all are independent async calls,
        // so a rejection in one does not stop the others from completing.
        expect(subscriberGrowth.value).toEqual({ a: 1 });
    });
});
