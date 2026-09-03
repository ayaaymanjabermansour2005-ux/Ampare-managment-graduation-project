import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/liveScheduleService', () => ({
    default: {
        get: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            live_schedule_page: {
                load_error: 'تعذر تحميل الجدول الآن',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useLiveSchedule } = await import('./useLiveSchedule');
const liveScheduleService = (await import('@/services/liveScheduleService')).default;

describe('useLiveSchedule', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts loading with empty activeNow/upcoming and no error', () => {
        const { activeNow, upcoming, isLoading, error } = useLiveSchedule();

        expect(activeNow.value).toEqual([]);
        expect(upcoming.value).toEqual([]);
        expect(isLoading.value).toBe(true);
        expect(error.value).toBeNull();
    });

    it('populates activeNow/upcoming on success and calls the service with the given neighborhoodId', async () => {
        liveScheduleService.get.mockResolvedValue({
            data: { data: { active_now: [{ id: 1 }], upcoming: [{ id: 2 }] } },
        });

        const { fetchSchedule, activeNow, upcoming, isLoading } = useLiveSchedule();
        await fetchSchedule(7);

        expect(liveScheduleService.get).toHaveBeenCalledWith(7);
        expect(activeNow.value).toEqual([{ id: 1 }]);
        expect(upcoming.value).toEqual([{ id: 2 }]);
        expect(isLoading.value).toBe(false);
    });

    it('defaults neighborhoodId to null when called with no argument', async () => {
        liveScheduleService.get.mockResolvedValue({ data: { data: { active_now: [], upcoming: [] } } });

        const { fetchSchedule } = useLiveSchedule();
        await fetchSchedule();

        expect(liveScheduleService.get).toHaveBeenCalledWith(null);
    });

    it('always sets the generic translated error message on failure, ignoring any backend message (no normalizeApiError here)', async () => {
        liveScheduleService.get.mockRejectedValue({
            response: { status: 422, data: { message: 'رسالة خاصة من الخادم', errors: {} } },
        });

        const { fetchSchedule, error, isLoading } = useLiveSchedule();
        await fetchSchedule();

        expect(error.value).toBe('تعذر تحميل الجدول الآن');
        expect(isLoading.value).toBe(false);
    });

    it('sets the same generic error message for a plain network error too', async () => {
        liveScheduleService.get.mockRejectedValue({ message: 'Network Error' });

        const { fetchSchedule, error } = useLiveSchedule();
        await fetchSchedule();

        expect(error.value).toBe('تعذر تحميل الجدول الآن');
    });

    it('clears a previous error and reloads data on a subsequent successful call', async () => {
        liveScheduleService.get.mockRejectedValueOnce({ message: 'Network Error' });
        const { fetchSchedule, error, activeNow } = useLiveSchedule();
        await fetchSchedule();
        expect(error.value).toBe('تعذر تحميل الجدول الآن');

        liveScheduleService.get.mockResolvedValueOnce({ data: { data: { active_now: [{ id: 3 }], upcoming: [] } } });
        await fetchSchedule();

        expect(error.value).toBeNull();
        expect(activeNow.value).toEqual([{ id: 3 }]);
    });
});
