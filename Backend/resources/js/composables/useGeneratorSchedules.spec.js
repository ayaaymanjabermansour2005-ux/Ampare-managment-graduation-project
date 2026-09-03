import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/generatorScheduleService', () => ({
    default: {
        list: vi.fn(),
        create: vi.fn(),
        destroy: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_generators: {
                schedule_load_error: 'تعذر تحميل الجدول',
                schedule_create_error: 'تعذر إنشاء الموعد',
                schedule_delete_error: 'تعذر حذف الموعد',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useGeneratorSchedules } = await import('./useGeneratorSchedules');
const generatorScheduleService = (await import('@/services/generatorScheduleService')).default;

describe('useGeneratorSchedules', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with an empty list, idle flags, no error, and null/empty derived computeds', () => {
        const { schedules, isLoading, isSaving, error, activeNow, upcoming } = useGeneratorSchedules(1);

        expect(schedules.value).toEqual([]);
        expect(isLoading.value).toBe(false);
        expect(isSaving.value).toBe(false);
        expect(error.value).toBeNull();
        expect(activeNow.value).toBeNull();
        expect(upcoming.value).toEqual([]);
    });

    it('fetchSchedules calls the service with the bound generatorId and stores the raw response data', async () => {
        generatorScheduleService.list.mockResolvedValue({ data: { data: [{ id: 1, is_active_now: false }] } });

        const { fetchSchedules, schedules, isLoading } = useGeneratorSchedules(7);
        await fetchSchedules();

        expect(generatorScheduleService.list).toHaveBeenCalledWith(7);
        expect(schedules.value).toEqual([{ id: 1, is_active_now: false }]);
        expect(isLoading.value).toBe(false);
    });

    it('reshapes a fetchSchedules failure into the translated fallback message', async () => {
        generatorScheduleService.list.mockRejectedValue({ message: 'Network Error' });

        const { fetchSchedules, error, isLoading } = useGeneratorSchedules(7);
        await fetchSchedules();

        expect(error.value).toBe('تعذر تحميل الجدول');
        expect(isLoading.value).toBe(false);
    });

    it('activeNow returns the currently-active schedule, or null when none is active', async () => {
        generatorScheduleService.list.mockResolvedValue({
            data: {
                data: [
                    { id: 1, is_active_now: false },
                    { id: 2, is_active_now: true },
                ],
            },
        });

        const { fetchSchedules, activeNow } = useGeneratorSchedules(7);
        await fetchSchedules();

        expect(activeNow.value).toEqual({ id: 2, is_active_now: true });
    });

    it('upcoming excludes the active schedule and past schedules, and sorts the rest ascending by start time', async () => {
        generatorScheduleService.list.mockResolvedValue({
            data: {
                data: [
                    { id: 1, is_active_now: true, starts_at: '2000-01-01T00:00:00Z' },
                    { id: 2, is_active_now: false, starts_at: '2000-01-01T00:00:00Z' }, // past -> excluded
                    { id: 3, is_active_now: false, starts_at: '2999-06-01T00:00:00Z' },
                    { id: 4, is_active_now: false, starts_at: '2999-01-01T00:00:00Z' },
                ],
            },
        });

        const { fetchSchedules, upcoming } = useGeneratorSchedules(7);
        await fetchSchedules();

        expect(upcoming.value.map((s) => s.id)).toEqual([4, 3]);
    });

    it('createSchedule creates then refetches the schedule list, clearing isSaving/error on success', async () => {
        generatorScheduleService.create.mockResolvedValue({ data: {} });
        generatorScheduleService.list.mockResolvedValue({ data: { data: [{ id: 9 }] } });

        const { createSchedule, schedules, isSaving, error } = useGeneratorSchedules(7);
        const payload = { starts_at: '2999-01-01T00:00:00Z', ends_at: '2999-01-02T00:00:00Z' };
        await createSchedule(payload);

        expect(generatorScheduleService.create).toHaveBeenCalledWith(7, payload);
        expect(generatorScheduleService.list).toHaveBeenCalledWith(7);
        expect(schedules.value).toEqual([{ id: 9 }]);
        expect(isSaving.value).toBe(false);
        expect(error.value).toBeNull();
    });

    it('createSchedule sets error AND rethrows on failure (caller is expected to catch)', async () => {
        generatorScheduleService.create.mockRejectedValue({ message: 'Network Error' });

        const { createSchedule, error, isSaving } = useGeneratorSchedules(7);

        await expect(createSchedule({})).rejects.toBeTruthy();
        expect(error.value).toBe('تعذر إنشاء الموعد');
        expect(isSaving.value).toBe(false);
    });

    it('deleteSchedule removes the schedule locally without a refetch, on success', async () => {
        generatorScheduleService.list.mockResolvedValue({
            data: { data: [{ id: 1 }, { id: 2 }] },
        });
        generatorScheduleService.destroy.mockResolvedValue({ data: {} });

        const { fetchSchedules, deleteSchedule, schedules } = useGeneratorSchedules(7);
        await fetchSchedules();
        await deleteSchedule(1);

        expect(generatorScheduleService.destroy).toHaveBeenCalledWith(1);
        expect(generatorScheduleService.list).toHaveBeenCalledTimes(1); // no refetch triggered
        expect(schedules.value).toEqual([{ id: 2 }]);
    });

    it('deleteSchedule sets error AND rethrows on failure, leaving the list untouched', async () => {
        generatorScheduleService.list.mockResolvedValue({ data: { data: [{ id: 1 }] } });
        generatorScheduleService.destroy.mockRejectedValue({ message: 'Network Error' });

        const { fetchSchedules, deleteSchedule, schedules, error } = useGeneratorSchedules(7);
        await fetchSchedules();

        await expect(deleteSchedule(1)).rejects.toBeTruthy();
        expect(schedules.value).toEqual([{ id: 1 }]);
        expect(error.value).toBe('تعذر حذف الموعد');
    });
});
