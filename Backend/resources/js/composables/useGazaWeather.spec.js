import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { defineComponent } from 'vue';
import { mount } from '@vue/test-utils';

vi.mock('@/i18n', () => ({
    default: {
        global: {
            // Ignore interpolation params — tests assert on the key itself,
            // matching the useConfirm.spec.js convention in this repo.
            t: (key) => key,
        },
    },
}));

const { useGazaWeather } = await import('./useGazaWeather');

function mountWeather() {
    let api;
    const wrapper = mount(defineComponent({
        setup() {
            api = useGazaWeather();
            return () => null;
        },
    }));
    return { wrapper, api };
}

describe('useGazaWeather', () => {
    beforeEach(() => {
        vi.stubGlobal('fetch', vi.fn());
    });

    afterEach(() => {
        vi.unstubAllGlobals();
        vi.useRealTimers();
    });

    it('starts with loading defaults before the mounted fetch resolves', () => {
        // Never let the fetch settle during this test.
        fetch.mockReturnValue(new Promise(() => {}));
        const { api } = mountWeather();

        expect(api.isLoading.value).toBe(true);
        expect(api.temp.value).toBe(null);
        expect(api.conditionLabel.value).toBe('--');
        expect(api.conditionIcon.value).toBe('fa-cloud-sun');
        expect(api.humidity.value).toBe(null);
        expect(api.wind.value).toBe(null);
        expect(api.rain.value).toBe(null);
        expect(api.peakLabel.value).toBe('--');
        expect(api.isEstimate.value).toBe(false);
        expect(api.loadChipClass.value).toBe('chip-success');
        expect(api.loadPercent.value).toBe(0);
        expect(api.note.value).toBe('');
    });

    it('applies a successful API response: high heat branch (temp >= 32), rounds fields, and computes the peak-hour label from hourly data', async () => {
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({
                current: {
                    temperature_2m: 35.4,
                    relative_humidity_2m: 50.2,
                    wind_speed_10m: 12.6,
                    precipitation_probability: 20.4,
                    weather_code: 61,
                },
                hourly: {
                    time: ['2026-01-01T00:00', '2026-01-01T14:00', '2026-01-01T23:00'],
                    temperature_2m: [10, 40, 15],
                },
            }),
        });
        vi.setSystemTime(new Date('2026-01-01T10:00:00.000Z'));

        const { api } = mountWeather();
        await vi.waitFor(() => expect(api.isLoading.value).toBe(false));

        expect(api.temp.value).toBe(35);
        expect(api.humidity.value).toBe(50);
        expect(api.wind.value).toBe(13);
        expect(api.rain.value).toBe(20);
        expect(api.conditionIcon.value).toBe('fa-cloud-rain');
        expect(api.conditionLabel.value).toBe('gaza_weather.condition_light_rain');
        expect(api.isEstimate.value).toBe(false);
        expect(api.peakLabel.value).toBe('14:00–16:00');
        expect(api.loadPercent.value).toBe(75);
        expect(api.loadChipClass.value).toBe('chip-warning');
        expect(api.loadChipLabel.value).toBe('gaza_weather.load_high_heat');
        // isEstimate is false, so the "estimate" suffix must NOT be appended.
        expect(api.note.value).toBe('gaza_weather.note_high_heat');
    });

    it('applies a successful API response: high cold branch (temp <= 10)', async () => {
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({
                current: {
                    temperature_2m: 8,
                    relative_humidity_2m: 70,
                    wind_speed_10m: 5,
                    precipitation_probability: 0,
                    weather_code: 3,
                },
                hourly: { time: [], temperature_2m: [] },
            }),
        });

        const { api } = mountWeather();
        await vi.waitFor(() => expect(api.isLoading.value).toBe(false));

        expect(api.temp.value).toBe(8);
        expect(api.loadPercent.value).toBe(68);
        expect(api.loadChipClass.value).toBe('chip-warning');
        expect(api.loadChipLabel.value).toBe('gaza_weather.load_high_cold');
        expect(api.note.value).toBe('gaza_weather.note_high_cold');
        // No matching hourly rows for "today" -> maxIdx stays -1 -> peakLabel stays the default.
        expect(api.peakLabel.value).toBe('--');
    });

    it('applies a successful API response: normal-range temperature branch (else)', async () => {
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({
                current: {
                    temperature_2m: 20,
                    relative_humidity_2m: 55,
                    wind_speed_10m: 10,
                    precipitation_probability: 5,
                    weather_code: 0,
                },
                // Missing `hourly` entirely — the peak-hour try/catch must swallow this.
            }),
        });

        const { api } = mountWeather();
        await vi.waitFor(() => expect(api.isLoading.value).toBe(false));

        expect(api.temp.value).toBe(20);
        expect(api.loadPercent.value).toBe(38);
        expect(api.loadChipClass.value).toBe('chip-success');
        expect(api.loadChipLabel.value).toBe('gaza_weather.load_normal');
        expect(api.note.value).toBe('gaza_weather.note_normal');
        expect(api.peakLabel.value).toBe('--');
    });

    it('falls back to the local estimate when the HTTP response is not ok', async () => {
        fetch.mockResolvedValue({ ok: false, status: 500, json: async () => ({}) });
        vi.setSystemTime(new Date(2026, 0, 1, 15, 0, 0)); // Jan 1st, 15:00 local time

        const { api } = mountWeather();
        await vi.waitFor(() => expect(api.isLoading.value).toBe(false));

        expect(api.isEstimate.value).toBe(true);
        // Deterministic estimate math for month=0 ([10,18]) at hour 15 (peak of the cosine curve):
        expect(api.temp.value).toBe(18);
        expect(api.humidity.value).toBe(64);
        expect(api.wind.value).toBe(15);
        expect(api.rain.value).toBe(0);
        expect(api.conditionIcon.value).toBe('fa-sun');
        expect(api.conditionLabel.value).toBe('gaza_weather.condition_clear');
        expect(api.peakLabel.value).toBe('15:00–17:00');
        // Normal-range branch, with the estimate suffix appended.
        expect(api.note.value).toBe('gaza_weather.note_normalgaza_weather.note_estimate_suffix');
    });

    it('falls back to the local estimate when fetch rejects (network error / abort)', async () => {
        fetch.mockRejectedValue(new Error('Network Error'));
        vi.setSystemTime(new Date(2026, 0, 1, 15, 0, 0));

        const { api } = mountWeather();
        await vi.waitFor(() => expect(api.isLoading.value).toBe(false));

        expect(api.isEstimate.value).toBe(true);
        expect(api.temp.value).toBe(18);
        expect(api.note.value).toBe('gaza_weather.note_normalgaza_weather.note_estimate_suffix');
    });

    it('falls back to the local estimate when the response shape is unexpected (no `current` field)', async () => {
        fetch.mockResolvedValue({ ok: true, json: async () => ({ hourly: {} }) });

        const { api } = mountWeather();
        await vi.waitFor(() => expect(api.isLoading.value).toBe(false));

        expect(api.isEstimate.value).toBe(true);
    });

    it('refresh() calls fetch again and re-applies fresh data', async () => {
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({
                current: { temperature_2m: 20, relative_humidity_2m: 55, wind_speed_10m: 10, precipitation_probability: 5, weather_code: 0 },
            }),
        });

        const { api } = mountWeather();
        await vi.waitFor(() => expect(api.isLoading.value).toBe(false));
        expect(fetch).toHaveBeenCalledTimes(1);

        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({
                current: { temperature_2m: 33, relative_humidity_2m: 40, wind_speed_10m: 8, precipitation_probability: 0, weather_code: 1 },
            }),
        });
        await api.refresh();

        expect(fetch).toHaveBeenCalledTimes(2);
        expect(api.temp.value).toBe(33);
        expect(api.loadChipClass.value).toBe('chip-warning');
    });

    it('re-fetches automatically every 15 minutes while mounted', async () => {
        vi.useFakeTimers();
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({
                current: { temperature_2m: 20, relative_humidity_2m: 55, wind_speed_10m: 10, precipitation_probability: 5, weather_code: 0 },
            }),
        });

        mountWeather();
        await vi.advanceTimersByTimeAsync(0);
        expect(fetch).toHaveBeenCalledTimes(1);

        await vi.advanceTimersByTimeAsync(15 * 60 * 1000);
        expect(fetch).toHaveBeenCalledTimes(2);

        await vi.advanceTimersByTimeAsync(15 * 60 * 1000);
        expect(fetch).toHaveBeenCalledTimes(3);
    });

    it('stops refetching once unmounted (clearInterval on onUnmounted)', async () => {
        vi.useFakeTimers();
        fetch.mockResolvedValue({
            ok: true,
            json: async () => ({
                current: { temperature_2m: 20, relative_humidity_2m: 55, wind_speed_10m: 10, precipitation_probability: 5, weather_code: 0 },
            }),
        });

        const { wrapper } = mountWeather();
        await vi.advanceTimersByTimeAsync(0);
        expect(fetch).toHaveBeenCalledTimes(1);

        wrapper.unmount();
        await vi.advanceTimersByTimeAsync(30 * 60 * 1000);

        // No further calls after unmount, even though 30 minutes have elapsed.
        expect(fetch).toHaveBeenCalledTimes(1);
    });
});
