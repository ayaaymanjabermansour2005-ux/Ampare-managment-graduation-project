import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import GeneratorHealthReports from './GeneratorHealthReports.vue';

vi.mock('@/services/generatorService', () => ({
    default: { healthReports: vi.fn() },
}));

import generatorService from '@/services/generatorService';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            generator_health_reports: {
                title: 'تقارير الصحة الدورية (بالذكاء الاصطناعي)',
                empty: 'لا يوجد تقرير صحة بعد — يُولَّد تلقائيًا كل شهر.',
            },
            owner_generators: {
                health_reports_load_error: 'تعذّر تحميل تقارير الصحة.',
            },
        },
    },
});

function mountReports(generatorId = 3) {
    return mount(GeneratorHealthReports, {
        props: { generatorId },
        global: { plugins: [i18n] },
    });
}

describe('GeneratorHealthReports', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('fetches reports for the given generatorId and shows a loading skeleton first', async () => {
        generatorService.healthReports.mockReturnValue(new Promise(() => {}));
        const wrapper = mountReports(3);
        // isLoading only flips to true inside fetchReports(), called from onMounted — the DOM
        // reflects it after the next reactivity flush, not synchronously on mount.
        await flushPromises();

        expect(generatorService.healthReports).toHaveBeenCalledWith(3);
        expect(wrapper.findAll('.animate-pulse')).toHaveLength(2);
    });

    it('shows the empty state once loaded with no reports', async () => {
        generatorService.healthReports.mockResolvedValue({ data: { data: [] } });
        const wrapper = mountReports();
        await flushPromises();

        expect(wrapper.findAll('.animate-pulse')).toHaveLength(0);
        expect(wrapper.text()).toContain('لا يوجد تقرير صحة بعد — يُولَّد تلقائيًا كل شهر.');
    });

    it('renders each report with its period, risk label, summary, and optional recommendation', async () => {
        generatorService.healthReports.mockResolvedValue({
            data: {
                data: [
                    {
                        id: 1, period_start: '2026-01-01', period_end: '2026-01-31',
                        risk_level: 'high', risk_level_label: 'مخاطرة عالية',
                        summary: 'استهلاك وقود غير طبيعي هذا الشهر.',
                        recommendation: 'افحصي فلتر الوقود.',
                    },
                    {
                        id: 2, period_start: '2026-02-01', period_end: '2026-02-28',
                        risk_level: 'low', risk_level_label: 'مخاطرة منخفضة',
                        summary: 'كل المؤشرات طبيعية.',
                        recommendation: null,
                    },
                ],
            },
        });
        const wrapper = mountReports();
        await flushPromises();

        expect(wrapper.text()).toContain('استهلاك وقود غير طبيعي هذا الشهر.');
        expect(wrapper.text()).toContain('مخاطرة عالية');
        expect(wrapper.text()).toContain('افحصي فلتر الوقود.');
        expect(wrapper.text()).toContain('كل المؤشرات طبيعية.');

        const badges = wrapper.findAll('.rounded-full.px-2.py-0\\.5');
        expect(badges[0].classes()).toContain('bg-danger-bg');
        expect(badges[1].classes()).toContain('bg-success-bg');
    });

    it('shows the normalized error message when the request fails', async () => {
        generatorService.healthReports.mockRejectedValue({
            response: { status: 500, data: { message: 'خطأ بالخادم.' } },
        });
        const wrapper = mountReports();
        await flushPromises();

        expect(wrapper.text()).toContain('خطأ بالخادم.');
        expect(wrapper.findAll('.animate-pulse')).toHaveLength(0);
    });
});
