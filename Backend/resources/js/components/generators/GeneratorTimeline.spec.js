import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import GeneratorTimeline from './GeneratorTimeline.vue';

vi.mock('@/services/generatorService', () => ({
    default: { timeline: vi.fn() },
}));

import generatorService from '@/services/generatorService';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            generator_timeline: {
                title: 'السجل الزمني (آخر ٣٠ يوم)',
                empty: 'لا يوجد أحداث مسجَّلة.',
            },
        },
    },
});

function mountTimeline(generatorId = 5) {
    return mount(GeneratorTimeline, {
        props: { generatorId },
        global: { plugins: [i18n], stubs: { AppIcon: true } },
    });
}

describe('GeneratorTimeline', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('fetches the timeline for the given generatorId on mount and shows a loading skeleton first', () => {
        generatorService.timeline.mockReturnValue(new Promise(() => {}));
        const wrapper = mountTimeline(5);

        expect(generatorService.timeline).toHaveBeenCalledWith(5);
        expect(wrapper.find('.animate-pulse').exists()).toBe(true);
    });

    it('shows the empty state once loaded with no events', async () => {
        generatorService.timeline.mockResolvedValue({ data: { data: [] } });
        const wrapper = mountTimeline(5);
        await flushPromises();

        expect(wrapper.find('.animate-pulse').exists()).toBe(false);
        expect(wrapper.text()).toContain('لا يوجد أحداث مسجَّلة.');
    });

    it('renders each event with its title, description and date once loaded', async () => {
        generatorService.timeline.mockResolvedValue({
            data: {
                data: [
                    { type: 'fault', title: 'عطل في المحرك', description: 'توقف مفاجئ', date: '2026-01-01' },
                    { type: 'health_report', title: 'تقرير صحة', description: 'كل شيء طبيعي', date: '2026-01-02' },
                ],
            },
        });
        const wrapper = mountTimeline(7);
        await flushPromises();

        expect(generatorService.timeline).toHaveBeenCalledWith(7);
        expect(wrapper.text()).toContain('عطل في المحرك');
        expect(wrapper.text()).toContain('توقف مفاجئ');
        expect(wrapper.text()).toContain('2026-01-01');
        expect(wrapper.text()).toContain('تقرير صحة');
        expect(wrapper.text()).toContain('كل شيء طبيعي');
    });
});
