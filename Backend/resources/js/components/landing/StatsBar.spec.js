import { describe, it, expect, vi, beforeEach } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import StatsBar from './StatsBar.vue';

// v-reveal and v-count-up both create a real IntersectionObserver on mount — unavailable in
// jsdom by default. Its observe() is a no-op here, so the count-up animation never actually
// starts (the "in view" callback is never fired) — that's fine, we only assert the initial
// "0" text v-count-up writes on mount, not the animated end value.
vi.stubGlobal('IntersectionObserver', class {
    observe() {}
    unobserve() {}
    disconnect() {}
});

vi.mock('@/services/publicStatsService', () => ({
    default: { platformStats: vi.fn() },
}));

import publicStatsService from '@/services/publicStatsService';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            landing: {
                stats: {
                    subscribers: 'مشترك مُدار+',
                    generators: 'مولد كهرباء+',
                    owners: 'مالك مولد+',
                    readings: 'ألف قراءة شهريًا+',
                    uptime: 'نسبة تشغيل المولدات (30 يوم)',
                    note: 'الأرقام تُحدَّث تلقائيًا من بيانات المنصة الحية',
                },
            },
        },
    },
});

function mountComponent() {
    return mount(StatsBar, { global: { plugins: [i18n] } });
}

describe('StatsBar', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('shows "—" placeholders for every stat before platformStats() resolves', () => {
        publicStatsService.platformStats.mockReturnValue(new Promise(() => {})); // never resolves
        const wrapper = mountComponent();

        const dashes = wrapper.findAll('p.text-3xl').map((p) => p.text());
        expect(dashes).toEqual(['—', '—', '—%', '—']);
    });

    it('replaces the placeholders with the loaded stats once platformStats() resolves', async () => {
        publicStatsService.platformStats.mockResolvedValue({
            data: {
                subscribers_count: 240,
                active_generators_count: 18,
                owners_count: 12,
                meter_readings_count: 5400,
                uptime_percentage: 99.4,
            },
        });
        const wrapper = mountComponent();
        await flushPromises();

        // v-count-up writes "0" on mount and only animates once its own IntersectionObserver
        // fires — our stub never fires it — so once loaded, each stat renders "0" (no dash).
        const values = wrapper.findAll('p.text-3xl').map((p) => p.text());
        expect(values).toEqual(['0', '0', '0%', '0']);
        expect(wrapper.find('p.text-3xl').text()).not.toBe('—');
    });

    it('still leaves the loading state (isLoaded=true, no dashes) when platformStats() rejects', async () => {
        publicStatsService.platformStats.mockRejectedValue(new Error('network down'));
        const wrapper = mountComponent();
        await flushPromises();

        const values = wrapper.findAll('p.text-3xl').map((p) => p.text());
        expect(values).toEqual(['0', '0', '0%', '0']);
    });

    it('renders the translated labels and the note', () => {
        publicStatsService.platformStats.mockReturnValue(new Promise(() => {}));
        const wrapper = mountComponent();

        expect(wrapper.text()).toContain('مشترك مُدار+');
        expect(wrapper.text()).toContain('مولد كهرباء+');
        expect(wrapper.text()).toContain('نسبة تشغيل المولدات (30 يوم)');
        expect(wrapper.text()).toContain('ألف قراءة شهريًا+');
        expect(wrapper.text()).toContain('الأرقام تُحدَّث تلقائيًا من بيانات المنصة الحية');
    });
});
