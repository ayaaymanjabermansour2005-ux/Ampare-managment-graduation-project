import { describe, it, expect, vi, beforeEach } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import AnalyticsSection from './AnalyticsSection.vue';

// vReveal + useScrollGlow both need IntersectionObserver; useScrollGlow also
// reads matchMedia synchronously on setup. Neither exists in jsdom.
vi.stubGlobal('matchMedia', vi.fn((query) => ({ media: query, matches: false })));
vi.stubGlobal('IntersectionObserver', class {
    observe() {}
    unobserve() {}
    disconnect() {}
});

vi.mock('@/services/publicAnalyticsService', () => ({
    default: { platformAnalytics: vi.fn() },
}));

import publicAnalyticsService from '@/services/publicAnalyticsService';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            landing: {
                analytics: {
                    eyebrow: 'التحليلات', title: 'أرقام تتحدث عن نفسها', subtitle: 'لقطة سريعة',
                    quick_stats: {
                        today_revenue: 'إيرادات اليوم', active_generators: 'مولدات نشطة',
                        maintenance_alerts: 'تنبيهات صيانة', monthly_readings: 'قراءات هالشهر',
                    },
                    groups: { revenue_growth: 'نمو الإيرادات', subscriber_growth: 'نمو المشتركين' },
                    labels: { jan: 'يناير', feb: 'فبراير', mar: 'مارس', cash: 'نقدًا', wallet: 'محفظة إلكترونية' },
                    payment_methods_title: 'طرق الدفع الأكثر استخدامًا',
                    no_data_yet: 'لا توجد بيانات كافية بعد',
                    top_generators_title: 'أعلى المولدات أداءً',
                },
            },
        },
    },
});

function mountComponent() {
    return mount(AnalyticsSection, { global: { plugins: [i18n] } });
}

const FULL_PAYLOAD = {
    quick_stats: { today_revenue_ils: 1500, active_generators_count: 8, open_faults_count: 2, monthly_readings_count: 120 },
    revenue_growth: { labels: ['jan', 'feb', 'mar'], values: [100, 200, 300] },
    subscriber_growth: { labels: ['jan', 'feb'], values: [10, 20] },
    payment_methods: [{ type: 'cash', percentage: 60 }, { type: 'wallet', percentage: 40 }],
    top_generators: [
        { name: 'مولد الأول', city: 'غزة', subscribers_count: 50 },
        { name: 'مولد الثاني', city: 'خان يونس', subscribers_count: 30 },
    ],
};

function ar(n) {
    return Number(n).toLocaleString('ar-EG');
}

describe('AnalyticsSection', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('shows 4 skeleton placeholders while the request is pending', () => {
        publicAnalyticsService.platformAnalytics.mockReturnValue(new Promise(() => {}));

        const wrapper = mountComponent();

        expect(wrapper.findAll('.animate-pulse')).toHaveLength(4);
        expect(wrapper.text()).not.toContain('طرق الدفع الأكثر استخدامًا');
    });

    it('renders the formatted quick stats once the request resolves', async () => {
        publicAnalyticsService.platformAnalytics.mockResolvedValue({ data: FULL_PAYLOAD });

        const wrapper = mountComponent();
        await flushPromises();

        expect(wrapper.text()).toContain(`₪${ar(1500)}`);
        expect(wrapper.text()).toContain(ar(8));
        expect(wrapper.text()).toContain(ar(2));
        expect(wrapper.text()).toContain(ar(120));
        expect(wrapper.findAll('.animate-pulse')).toHaveLength(0);
    });

    it('renders one bar row per revenue/subscriber growth label, translated', async () => {
        publicAnalyticsService.platformAnalytics.mockResolvedValue({ data: FULL_PAYLOAD });

        const wrapper = mountComponent();
        await flushPromises();

        expect(wrapper.text()).toContain('يناير');
        expect(wrapper.text()).toContain('فبراير');
        expect(wrapper.text()).toContain('مارس');
        expect(wrapper.text()).toContain(`₪${ar(100)}`);
    });

    it('renders payment method rows with their percentage', async () => {
        publicAnalyticsService.platformAnalytics.mockResolvedValue({ data: FULL_PAYLOAD });

        const wrapper = mountComponent();
        await flushPromises();

        expect(wrapper.text()).toContain('نقدًا');
        expect(wrapper.text()).toContain('60%');
        expect(wrapper.text()).toContain('محفظة إلكترونية');
        expect(wrapper.text()).toContain('40%');
    });

    it('shows the "no data yet" placeholder for payment methods and top generators when both are empty', async () => {
        publicAnalyticsService.platformAnalytics.mockResolvedValue({
            data: { ...FULL_PAYLOAD, payment_methods: [], top_generators: [] },
        });

        const wrapper = mountComponent();
        await flushPromises();

        expect(wrapper.text().match(/لا توجد بيانات كافية بعد/g)).toHaveLength(2);
        expect(wrapper.text()).not.toContain('نقدًا');
        expect(wrapper.text()).not.toContain('مولد الأول');
    });

    it('renders the top generators list ranked with their subscriber counts', async () => {
        publicAnalyticsService.platformAnalytics.mockResolvedValue({ data: FULL_PAYLOAD });

        const wrapper = mountComponent();
        await flushPromises();

        expect(wrapper.text()).toContain('مولد الأول');
        expect(wrapper.text()).toContain('غزة');
        expect(wrapper.text()).toContain('50');
        expect(wrapper.text()).toContain('مولد الثاني');
    });

    it('shows only the header (no stats, no skeleton) when the request fails', async () => {
        publicAnalyticsService.platformAnalytics.mockRejectedValue(new Error('network down'));

        const wrapper = mountComponent();
        await flushPromises();

        expect(wrapper.text()).toContain('أرقام تتحدث عن نفسها');
        expect(wrapper.text()).not.toContain('إيرادات اليوم');
        expect(wrapper.findAll('.animate-pulse')).toHaveLength(0);
    });
});
