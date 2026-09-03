import { describe, it, expect, vi, beforeEach } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import GeneratorsMapSection from './GeneratorsMapSection.vue';
import { useAuthStore } from '@/stores/auth';

// vReveal uses IntersectionObserver, unavailable in jsdom by default.
vi.stubGlobal('IntersectionObserver', class {
    observe() {}
    unobserve() {}
    disconnect() {}
});

// Fake Leaflet — the real library needs a real laid-out DOM, which jsdom
// doesn't provide (same fakes/approach as useLeafletMap.spec.js).
class FakeLayer {
    addTo(map) { this.addedTo = map; return this; }
}
class FakeMarker extends FakeLayer {
    constructor(latlng, options) {
        super();
        this.latlng = latlng;
        this.options = options;
        this.opened = false;
        this._events = {};
    }
    bindPopup(content) { this.popupContent = content; return this; }
    on(event, cb) { this._events[event] = cb; return this; }
    openPopup() { this.opened = true; return this; }
    remove() { this.removed = true; }
}
class FakeMap {
    constructor(container, options) {
        this.container = container;
        this.options = options;
        this.setViewCalls = [];
        this.fitBoundsCalls = [];
    }
    setView(center, zoom, opts) { this.setViewCalls.push({ center, zoom, opts }); return this; }
    fitBounds(bounds, options) { this.fitBoundsCalls.push({ bounds, options }); return this; }
    invalidateSize() {}
    remove() {}
}
const mapSpy = vi.fn((container, options) => new FakeMap(container, options));
const tileLayerSpy = vi.fn(() => new FakeLayer());
const markerSpy = vi.fn((latlng, options) => new FakeMarker(latlng, options));
const divIconSpy = vi.fn((opts) => ({ __divIcon: opts }));

vi.mock('leaflet', () => ({
    default: {
        map: (...args) => mapSpy(...args),
        tileLayer: (...args) => tileLayerSpy(...args),
        marker: (...args) => markerSpy(...args),
        divIcon: (...args) => divIconSpy(...args),
    },
}));

vi.mock('@/services/publicGeneratorsListService', () => ({
    default: { list: vi.fn() },
}));
vi.mock('@/services/liveScheduleService', () => ({
    default: { get: vi.fn() },
}));

const resolveSpy = vi.fn((route) => ({ href: `#${route.name}?${JSON.stringify(route.query ?? {})}` }));
vi.mock('vue-router', () => ({
    useRouter: () => ({ resolve: (route) => resolveSpy(route) }),
}));

import publicGeneratorsListService from '@/services/publicGeneratorsListService';
import liveScheduleService from '@/services/liveScheduleService';

const RouterLinkStub = {
    props: ['to'],
    template: '<a :data-to="JSON.stringify(to)"><slot /></a>',
};

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    fallbackLocale: 'ar',
    messages: {
        ar: {
            landing: {
                map: {
                    eyebrow: 'خريطة المولدات', title: 'لاقِ أقرب مولد لمنطقتك', subtitle: 'كل المولدات المتوفرة',
                    disclaimer: 'أمبير منصة تقنية مش شركة كهرباء',
                    subscribers_unit: 'مشترك', kwh_unit: 'ك.و',
                    status_active: 'نشط', status_inactive: 'متوقف',
                    subscribe_button: 'اشترك', search_placeholder: 'دوّري باسم المولد أو المدينة...',
                    no_results: 'لا توجد نتائج مطابقة', loading: 'جارِ تحميل بيانات التغطية...',
                    error: 'تعذّر تحميل خريطة التغطية.', empty: 'لا توجد بيانات تغطية كافية بعد.',
                    stat_generators: 'مولد نشط', stat_cities: 'مدينة مغطاة',
                    live_widget_title: 'شغّال الآن', live_widget_count: '{count} مولد شغّال هلق',
                    live_widget_empty: 'ما في مولد معلَن شغّال هلق', live_widget_link: 'عرض جدول التشغيل الكامل',
                },
            },
            live_schedule_page: { load_error: 'تعذر تحميل الجدول الآن' },
        },
        en: {
            landing: { map: { eyebrow: 'Generators map' } },
        },
    },
});

function mountComponent() {
    const pinia = createPinia();
    setActivePinia(pinia);
    const wrapper = mount(GeneratorsMapSection, {
        global: {
            plugins: [i18n, pinia],
            stubs: { RouterLink: RouterLinkStub },
        },
    });
    return { wrapper, pinia };
}

function gen(overrides = {}) {
    return {
        id: 1, name: 'مولد جباليا', name_en: 'Jabalia Generator', city: 'جباليا',
        latitude: 31.5, longitude: 34.48, subscribers_count: 10, price_per_kw: 2.5,
        ...overrides,
    };
}

describe('GeneratorsMapSection', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        i18n.global.locale.value = 'ar';
        liveScheduleService.get.mockResolvedValue({ data: { data: { active_now: [], upcoming: [] } } });
    });

    it('shows the loading indicator while the generators request is pending, without touching the map', async () => {
        publicGeneratorsListService.list.mockReturnValue(new Promise(() => {}));

        const { wrapper } = mountComponent();
        await flushPromises();

        expect(wrapper.text()).toContain('جارِ تحميل بيانات التغطية...');
        expect(mapSpy).not.toHaveBeenCalled();
    });

    it('shows the empty state and does not initialize the map when there are no generators', async () => {
        publicGeneratorsListService.list.mockResolvedValue({ data: { generators: [], total_cities: 0 } });

        const { wrapper } = mountComponent();
        await flushPromises();

        expect(wrapper.text()).toContain('لا توجد بيانات تغطية كافية بعد.');
        expect(mapSpy).not.toHaveBeenCalled();
    });

    it('shows the error state when the generators request fails', async () => {
        publicGeneratorsListService.list.mockRejectedValue(new Error('network down'));

        const { wrapper } = mountComponent();
        await flushPromises();

        expect(wrapper.text()).toContain('تعذّر تحميل خريطة التغطية.');
        expect(mapSpy).not.toHaveBeenCalled();
    });

    it('on success: renders the generator/city counters and places one marker per generator on the map', async () => {
        publicGeneratorsListService.list.mockResolvedValue({
            data: { generators: [gen({ id: 1 }), gen({ id: 2, name: 'مولد غزة' })], total_cities: 2 },
        });

        const { wrapper } = mountComponent();
        await flushPromises();

        expect(wrapper.text()).toContain(Number(2).toLocaleString('ar-EG')); // generators count
        expect(mapSpy).toHaveBeenCalledTimes(1);
        expect(markerSpy).toHaveBeenCalledTimes(2);
        expect(wrapper.text()).toContain('مولد جباليا');
        expect(wrapper.text()).toContain('مولد غزة');
    });

    it('filters the generator list by name/city as the user types, case-insensitively', async () => {
        publicGeneratorsListService.list.mockResolvedValue({
            data: {
                generators: [
                    gen({ id: 1, name: 'مولد جباليا', city: 'جباليا' }),
                    gen({ id: 2, name: 'مولد غزة', city: 'غزة' }),
                ],
                total_cities: 2,
            },
        });

        const { wrapper } = mountComponent();
        await flushPromises();

        await wrapper.find('input[type="text"]').setValue('غزة');

        expect(wrapper.text()).toContain('مولد غزة');
        expect(wrapper.text()).not.toContain('مولد جباليا');
    });

    it('shows "no results" when the search query matches nothing', async () => {
        publicGeneratorsListService.list.mockResolvedValue({ data: { generators: [gen()], total_cities: 1 } });

        const { wrapper } = mountComponent();
        await flushPromises();

        await wrapper.find('input[type="text"]').setValue('xyz-no-match');

        expect(wrapper.text()).toContain('لا توجد نتائج مطابقة');
    });

    it('sorts the generator list by subscriber count, descending', async () => {
        publicGeneratorsListService.list.mockResolvedValue({
            data: {
                generators: [
                    gen({ id: 1, name: 'الأقل', subscribers_count: 5 }),
                    gen({ id: 2, name: 'الأعلى', subscribers_count: 50 }),
                ],
                total_cities: 1,
            },
        });

        const { wrapper } = mountComponent();
        await flushPromises();

        const names = wrapper.findAll('h4').map((h) => h.text());
        expect(names.indexOf('الأعلى')).toBeLessThan(names.indexOf('الأقل'));
    });

    it('clicking a generator card highlights it and pans/opens its map marker', async () => {
        publicGeneratorsListService.list.mockResolvedValue({
            data: {
                generators: [gen({ id: 1, latitude: 31.1, longitude: 34.1 }), gen({ id: 2, latitude: 31.9, longitude: 34.9 })],
                total_cities: 1,
            },
        });

        const { wrapper } = mountComponent();
        await flushPromises();

        const cards = wrapper.findAll('.gen-card');
        await cards[1].trigger('click');

        expect(cards[1].classes()).toContain('!border-[#D4AF37]');
        expect(cards[0].classes()).not.toContain('!border-[#D4AF37]');

        const map = mapSpy.mock.results[0].value;
        expect(map.setViewCalls.at(-1)).toMatchObject({ center: [31.9, 34.9], zoom: 14 });

        const secondMarker = markerSpy.mock.results[1].value;
        expect(secondMarker.opened).toBe(true);
    });

    it('links a guest subscribe button to registration with the generator id', async () => {
        publicGeneratorsListService.list.mockResolvedValue({ data: { generators: [gen({ id: 7 })], total_cities: 1 } });

        const { wrapper } = mountComponent();
        await flushPromises();

        const subscribeLink = wrapper.find('.gen-card').findComponent(RouterLinkStub);
        expect(subscribeLink.attributes('data-to')).toBe(
            JSON.stringify({ name: 'register.subscriber', query: { generator_id: 7 } }),
        );
    });

    it('links an authenticated subscriber\'s subscribe button to their subscription center instead', async () => {
        publicGeneratorsListService.list.mockResolvedValue({ data: { generators: [gen({ id: 9 })], total_cities: 1 } });

        const { wrapper, pinia } = mountComponent();
        const authStore = useAuthStore(pinia);
        authStore.user = { id: 1 };
        authStore.roles = ['subscriber'];
        await flushPromises();

        const subscribeLink = wrapper.find('.gen-card').findComponent(RouterLinkStub);
        expect(subscribeLink.attributes('data-to')).toBe(
            JSON.stringify({ name: 'subscriber.subscription', query: { highlight: 9 } }),
        );
    });

    it('shows the live-schedule widget with the active count when it loads successfully', async () => {
        publicGeneratorsListService.list.mockResolvedValue({ data: { generators: [gen()], total_cities: 1 } });
        liveScheduleService.get.mockResolvedValue({ data: { data: { active_now: [{ id: 1 }, { id: 2 }], upcoming: [] } } });

        const { wrapper } = mountComponent();
        await flushPromises();

        expect(wrapper.text()).toContain('شغّال الآن');
        expect(wrapper.text()).toContain('2 مولد شغّال هلق');
    });

    it('hides the live-schedule widget entirely when it fails to load', async () => {
        publicGeneratorsListService.list.mockResolvedValue({ data: { generators: [gen()], total_cities: 1 } });
        liveScheduleService.get.mockRejectedValue(new Error('down'));

        const { wrapper } = mountComponent();
        await flushPromises();

        expect(wrapper.text()).not.toContain('شغّال الآن');
    });
});
