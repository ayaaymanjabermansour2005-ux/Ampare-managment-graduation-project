import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { defineComponent } from 'vue';
import { mount } from '@vue/test-utils';

// Fake Leaflet — the real library needs a real laid-out DOM/canvas, which
// jsdom doesn't provide. These fakes track exactly what the composable calls.
class FakeLayer {
    addTo(map) {
        this.addedTo = map;
        return this;
    }
}
class FakeTileLayer extends FakeLayer {
    constructor(url, options) {
        super();
        this.url = url;
        this.options = options;
    }
}
class FakeMarker extends FakeLayer {
    constructor(latlng, options) {
        super();
        this.latlng = latlng;
        this.options = options;
        this.removed = false;
        this._events = {};
    }
    bindPopup(content) {
        this.popupContent = content;
        return this;
    }
    on(event, cb) {
        this._events[event] = cb;
        return this;
    }
    openPopup() {
        this.opened = true;
        return this;
    }
    remove() {
        this.removed = true;
    }
}
class FakeMap {
    constructor(container, options) {
        this.container = container;
        this.options = options;
        this.setViewCalls = [];
        this.fitBoundsCalls = [];
        this.removed = false;
        this.invalidateSizeCalls = 0;
    }
    setView(center, zoom, opts) {
        this.setViewCalls.push({ center, zoom, opts });
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

const mapSpy = vi.fn((container, options) => new FakeMap(container, options));
const tileLayerSpy = vi.fn((url, options) => new FakeTileLayer(url, options));
const markerSpy = vi.fn((latlng, options) => new FakeMarker(latlng, options));

vi.mock('leaflet', () => ({
    default: {
        map: (...args) => mapSpy(...args),
        tileLayer: (...args) => tileLayerSpy(...args),
        marker: (...args) => markerSpy(...args),
    },
}));

const { useLeafletMap } = await import('./useLeafletMap');

function mountLeaflet(options) {
    let api;
    const wrapper = mount(defineComponent({
        setup() {
            api = useLeafletMap(options);
            return () => null;
        },
    }));
    return { wrapper, api };
}

describe('useLeafletMap', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('init() is a no-op returning null when containerRef has no element yet', () => {
        const { api } = mountLeaflet();

        const result = api.init();

        expect(result).toBe(null);
        expect(mapSpy).not.toHaveBeenCalled();
        expect(api.getMap()).toBe(null);
    });

    it('init() creates the map with defaults (attributionControl:false + center/zoom) and adds a tile layer', () => {
        const { api } = mountLeaflet();
        api.containerRef.value = document.createElement('div');

        const map = api.init();

        expect(mapSpy).toHaveBeenCalledTimes(1);
        expect(mapSpy).toHaveBeenCalledWith(api.containerRef.value, { attributionControl: false });
        expect(map.setViewCalls[0]).toEqual({ center: [31.5017, 34.4668], zoom: 11, opts: undefined });
        expect(tileLayerSpy).toHaveBeenCalledWith('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 });
        expect(api.getMap()).toBe(map);
    });

    it('init() forwards custom center/zoom/tileUrl/tileOptions/mapOptions', () => {
        const { api } = mountLeaflet({
            center: [1, 2],
            zoom: 5,
            tileUrl: 'https://tiles.example/{z}/{x}/{y}.png',
            tileOptions: { minZoom: 3 },
            mapOptions: { scrollWheelZoom: false },
        });
        api.containerRef.value = document.createElement('div');

        const map = api.init();

        expect(mapSpy).toHaveBeenCalledWith(api.containerRef.value, { attributionControl: false, scrollWheelZoom: false });
        expect(map.setViewCalls[0]).toEqual({ center: [1, 2], zoom: 5, opts: undefined });
        expect(tileLayerSpy).toHaveBeenCalledWith('https://tiles.example/{z}/{x}/{y}.png', { maxZoom: 18, minZoom: 3 });
    });

    it('init() called a second time returns the existing map instead of creating a new one', () => {
        const { api } = mountLeaflet();
        api.containerRef.value = document.createElement('div');

        const first = api.init();
        const second = api.init();

        expect(first).toBe(second);
        expect(mapSpy).toHaveBeenCalledTimes(1);
    });

    it('setMarkers() returns [] and does nothing when the map has not been initialized', () => {
        const { api } = mountLeaflet();

        const bounds = api.setMarkers([{ id: 1, lat: 1, lng: 2 }], { getLatLng: (i) => [i.lat, i.lng] });

        expect(bounds).toEqual([]);
        expect(markerSpy).not.toHaveBeenCalled();
    });

    it('setMarkers() creates a marker per item, skips items with no lat/lng, and returns the bounds', () => {
        const { api } = mountLeaflet();
        api.containerRef.value = document.createElement('div');
        api.init();

        const onClick = vi.fn();
        const items = [
            { id: 1, lat: 31.5, lng: 34.4 },
            { id: 2, lat: null, lng: null }, // filtered out
            { id: 3, lat: 31.6, lng: 34.5 },
        ];
        const bounds = api.setMarkers(items, {
            getLatLng: (i) => [i.lat, i.lng],
            popup: (i) => `popup-${i.id}`,
            onClick,
        });

        expect(markerSpy).toHaveBeenCalledTimes(2);
        expect(bounds).toEqual([[31.5, 34.4], [31.6, 34.5]]);
        expect(api.getMarker(1)).not.toBe(null);
        expect(api.getMarker(1).popupContent).toBe('popup-1');
        expect(api.getMarker(2)).toBe(null); // filtered item never got a marker

        // The onClick handler is wired through marker.on('click', ...).
        api.getMarker(3)._events.click();
        expect(onClick).toHaveBeenCalledWith(items[2], api.getMarker(3));
    });

    it('setMarkers() called again removes the previous markers first (clearMarkers)', () => {
        const { api } = mountLeaflet();
        api.containerRef.value = document.createElement('div');
        api.init();

        api.setMarkers([{ id: 1, lat: 1, lng: 1 }], { getLatLng: (i) => [i.lat, i.lng] });
        const firstMarker = api.getMarker(1);

        api.setMarkers([{ id: 2, lat: 2, lng: 2 }], { getLatLng: (i) => [i.lat, i.lng] });

        expect(firstMarker.removed).toBe(true);
        expect(api.getMarker(1)).toBe(null);
        expect(api.getMarker(2)).not.toBe(null);
    });

    it('fitToBounds() with a single point uses setView at max(zoom, 14); with multiple points calls fitBounds', () => {
        const { api } = mountLeaflet({ zoom: 11 });
        api.containerRef.value = document.createElement('div');
        const map = api.init();

        api.fitToBounds([[1, 2]]);
        expect(map.setViewCalls.at(-1)).toEqual({ center: [1, 2], zoom: 14, opts: undefined });

        api.fitToBounds([[1, 2], [3, 4]], { padding: [10, 10] });
        expect(map.fitBoundsCalls).toEqual([{ bounds: [[1, 2], [3, 4]], options: { padding: [10, 10] } }]);
    });

    it('fitToBounds() is a no-op with an empty/undefined bounds array or no map', () => {
        const { api } = mountLeaflet();

        expect(() => api.fitToBounds([])).not.toThrow();
        expect(() => api.fitToBounds(undefined)).not.toThrow();

        api.containerRef.value = document.createElement('div');
        const map = api.init();
        api.fitToBounds([]);
        expect(map.setViewCalls).toEqual([map.setViewCalls[0]]); // only the initial setView() from init()
    });

    it('focus() calls map.setView with the given coordinates, zoom, and animate:true', () => {
        const { api } = mountLeaflet();
        api.containerRef.value = document.createElement('div');
        const map = api.init();

        api.focus(31.9, 34.8, 16);

        expect(map.setViewCalls.at(-1)).toEqual({ center: [31.9, 34.8], zoom: 16, opts: { animate: true } });
    });

    it('focus() does not throw when the map has not been initialized', () => {
        const { api } = mountLeaflet();
        expect(() => api.focus(1, 2)).not.toThrow();
    });

    it('invalidateSize() calls map.invalidateSize immediately with no delay, and after a delay when given one', () => {
        vi.useFakeTimers();
        const { api } = mountLeaflet();
        api.containerRef.value = document.createElement('div');
        const map = api.init();

        api.invalidateSize();
        expect(map.invalidateSizeCalls).toBe(1);

        api.invalidateSize(200);
        expect(map.invalidateSizeCalls).toBe(1); // not yet — scheduled via setTimeout
        vi.advanceTimersByTime(200);
        expect(map.invalidateSizeCalls).toBe(2);
    });

    it('destroy() removes all markers, removes the map, and resets getMap() to null', () => {
        const { api } = mountLeaflet();
        api.containerRef.value = document.createElement('div');
        const map = api.init();
        api.setMarkers([{ id: 1, lat: 1, lng: 1 }], { getLatLng: (i) => [i.lat, i.lng] });
        const marker = api.getMarker(1);

        api.destroy();

        expect(marker.removed).toBe(true);
        expect(map.removed).toBe(true);
        expect(api.getMap()).toBe(null);
    });

    it('unmounting the host component triggers destroy() via onBeforeUnmount', () => {
        const { wrapper, api } = mountLeaflet();
        api.containerRef.value = document.createElement('div');
        const map = api.init();

        wrapper.unmount();

        expect(map.removed).toBe(true);
        expect(api.getMap()).toBe(null);
    });
});
