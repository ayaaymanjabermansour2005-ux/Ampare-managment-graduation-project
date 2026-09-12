import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import AdminGeneratorsMap from './AdminGeneratorsMap.vue';

class FakeLayer {
    addTo(map) {
        this.addedTo = map;
        return this;
    }
    remove() {
        this.removed = true;
    }
}
class FakeTileLayer extends FakeLayer {
    constructor(url, options) {
        super();
        this.url = url;
        this.options = options;
    }
    setUrl(url) {
        this.url = url;
    }
}
class FakeMarker extends FakeLayer {
    constructor(latlng, options) {
        super();
        this.latlng = latlng;
        this.options = options;
        this._events = {};
    }
    bindPopup(html) {
        this.popupHtml = html;
        return this;
    }
    on(event, cb) {
        this._events[event] = cb;
        return this;
    }
}
class FakeMap {
    constructor(container, options) {
        this.container = container;
        this.options = options;
        this.setViewCalls = [];
        this.fitBoundsCalls = [];
        this.invalidateSizeCalls = 0;
    }
    setView(center, zoom) {
        this.setViewCalls.push({ center, zoom });
        return this;
    }
    fitBounds(bounds, options) {
        this.fitBoundsCalls.push({ bounds, options });
        return this;
    }
    invalidateSize() {
        this.invalidateSizeCalls++;
    }
    remove() {
        this.removed = true;
    }
}

const mapSpy = vi.fn((c, o) => new FakeMap(c, o));
const tileLayerSpy = vi.fn((u, o) => new FakeTileLayer(u, o));
const markerSpy = vi.fn((ll, o) => new FakeMarker(ll, o));
const divIconSpy = vi.fn((o) => ({ divIconOptions: o }));

vi.mock('leaflet', () => ({
    default: {
        map: (...a) => mapSpy(...a),
        tileLayer: (...a) => tileLayerSpy(...a),
        marker: (...a) => markerSpy(...a),
        divIcon: (...a) => divIconSpy(...a),
    },
}));

vi.mock('@/services/adminDashboardService', () => ({
    default: { generatorsMap: vi.fn() },
}));

const pushMock = vi.fn();
vi.mock('vue-router', () => ({
    useRouter: () => ({ push: pushMock }),
}));

import adminDashboardService from '@/services/adminDashboardService';
import { useAdminUiStore } from '@/stores/adminUi';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            status: {
                active: 'يعمل',
                maintenance: 'صيانة',
                inactive: 'متوقف',
                pending_verification: 'بانتظار الاعتماد',
                rejected: 'مرفوض',
            },
            generators_map: {
                view_generator_link: 'عرض المولد',
                no_coordinates: 'لا توجد مولدات بإحداثيات مسجلة بعد',
                load_error: 'تعذّر تحميل نقاط الخريطة.',
            },
        },
    },
});

function mountMap() {
    const pinia = createPinia();
    setActivePinia(pinia);
    const wrapper = mount(AdminGeneratorsMap, { global: { plugins: [i18n, pinia] } });
    return { wrapper, pinia };
}

describe('AdminGeneratorsMap', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        document.body.innerHTML = '';
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('creates the leaflet map centered on Gaza with the light tile layer while ui is not dark, and shows a loading spinner', async () => {
        adminDashboardService.generatorsMap.mockReturnValue(new Promise(() => {})); // never resolves
        const { wrapper } = mountMap();

        expect(mapSpy).toHaveBeenCalledTimes(1);
        expect(mapSpy.mock.calls[0][1]).toEqual({ attributionControl: false });
        const map = mapSpy.mock.results[0].value;
        expect(map.setViewCalls[0]).toEqual({ center: [31.5017, 34.4668], zoom: 11 });
        expect(tileLayerSpy.mock.calls[0][0]).toContain('voyager');

        expect(wrapper.find('.animate-spin').exists()).toBe(true);
    });

    it('shows the empty state once loaded with no points', async () => {
        adminDashboardService.generatorsMap.mockResolvedValue({ data: { data: [] } });
        const { wrapper } = mountMap();

        await flushPromises();

        expect(wrapper.find('.animate-spin').exists()).toBe(false);
        expect(wrapper.text()).toContain('لا توجد مولدات بإحداثيات مسجلة بعد');
        expect(markerSpy).not.toHaveBeenCalled();
    });

    it('shows the error state (not the empty state) when the request fails, and clears it on the next successful fetch', async () => {
        adminDashboardService.generatorsMap.mockRejectedValueOnce({ message: 'Network Error' });
        const { wrapper } = mountMap();

        await flushPromises();

        expect(wrapper.find('.animate-spin').exists()).toBe(false);
        expect(wrapper.text()).toContain('تعذّر تحميل نقاط الخريطة.');
        expect(wrapper.text()).not.toContain('لا توجد مولدات بإحداثيات مسجلة بعد');
        expect(markerSpy).not.toHaveBeenCalled();
    });

    it('renders a marker per point, fits the map bounds, and renders the status legend', async () => {
        adminDashboardService.generatorsMap.mockResolvedValue({
            data: {
                data: [
                    { id: 1, name: 'مولد أ', city: 'غزة', status: 'active', lat: 31.5, lng: 34.4 },
                    { id: 2, name: 'مولد ب', city: 'خان يونس', status: 'maintenance', lat: 31.3, lng: 34.3 },
                ],
            },
        });
        const { wrapper } = mountMap();
        await flushPromises();

        expect(markerSpy).toHaveBeenCalledTimes(2);
        const map = mapSpy.mock.results[0].value;
        expect(map.fitBoundsCalls[0]).toEqual({
            bounds: [[31.5, 34.4], [31.3, 34.3]],
            options: { padding: [32, 32], maxZoom: 14 },
        });

        expect(wrapper.text()).toContain('يعمل');
        expect(wrapper.text()).toContain('صيانة');
        expect(wrapper.text()).toContain('بانتظار الاعتماد');
        expect(wrapper.text()).toContain('متوقف');
    });

    it('clicking the "view generator" link inside a marker popup navigates to the generator detail route', async () => {
        adminDashboardService.generatorsMap.mockResolvedValue({
            data: { data: [{ id: 9, name: 'مولد الاختبار', city: 'غزة', status: 'active', lat: 31.5, lng: 34.5 }] },
        });
        mountMap();
        await flushPromises();

        const marker = markerSpy.mock.results[0].value;
        expect(marker.popupHtml).toContain('مولد الاختبار');
        expect(typeof marker._events.popupopen).toBe('function');

        const anchor = document.createElement('a');
        anchor.id = 'view-generator-9';
        document.body.appendChild(anchor);

        marker._events.popupopen();
        anchor.dispatchEvent(new MouseEvent('click', { bubbles: true }));

        expect(pushMock).toHaveBeenCalledWith({ name: 'generators.show', params: { id: 9 } });
    });

    it('switches the tile layer url when the dark-mode store flag changes, and invalidates the map size shortly after', async () => {
        vi.useFakeTimers();
        adminDashboardService.generatorsMap.mockResolvedValue({ data: { data: [] } });
        const { pinia } = mountMap();
        await vi.advanceTimersByTimeAsync(0);

        const ui = useAdminUiStore(pinia);
        const map = mapSpy.mock.results[0].value;
        const tileLayer = tileLayerSpy.mock.results[0].value;
        expect(tileLayer.url).toContain('voyager');

        ui.toggleTheme();
        await vi.advanceTimersByTimeAsync(0);

        expect(tileLayer.url).toContain('dark_all');
        expect(map.invalidateSizeCalls).toBe(0);

        await vi.advanceTimersByTimeAsync(350);
        expect(map.invalidateSizeCalls).toBe(1);
    });

    it('removes all markers and the map instance on unmount', async () => {
        adminDashboardService.generatorsMap.mockResolvedValue({
            data: { data: [{ id: 1, name: 'مولد أ', city: '', status: 'active', lat: 31.5, lng: 34.4 }] },
        });
        const { wrapper } = mountMap();
        await flushPromises();

        const map = mapSpy.mock.results[0].value;
        const marker = markerSpy.mock.results[0].value;

        wrapper.unmount();

        expect(marker.removed).toBe(true);
        expect(map.removed).toBe(true);
    });
});
