import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/activityLogService', () => ({
    default: { index: vi.fn() },
}));
vi.mock('@/services/generatorService', () => ({
    default: { timeline: vi.fn(), list: vi.fn(), exportUrl: vi.fn() },
}));
vi.mock('@/services/generatorScheduleService', () => ({
    default: { create: vi.fn() },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            dashboard: {
                maintenance_schedule_error: 'تعذرت جدولة الصيانة.',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminDashboardExtras } = await import('./useAdminDashboardExtras');
const activityLogService = (await import('@/services/activityLogService')).default;
const generatorService = (await import('@/services/generatorService')).default;
const generatorScheduleService = (await import('@/services/generatorScheduleService')).default;

describe('useAdminDashboardExtras', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with empty timeline/generatorTimeline/allGeneratorsForSelect and the documented default loading flags', () => {
        const {
            timeline, isLoadingTimeline,
            generatorTimeline, isLoadingGeneratorTimeline,
            allGeneratorsForSelect,
            isSavingMaintenance, maintenanceError,
        } = useAdminDashboardExtras();

        expect(timeline.value).toEqual([]);
        expect(isLoadingTimeline.value).toBe(true);
        expect(generatorTimeline.value).toEqual([]);
        expect(isLoadingGeneratorTimeline.value).toBe(false);
        expect(allGeneratorsForSelect.value).toEqual([]);
        expect(isSavingMaintenance.value).toBe(false);
        expect(maintenanceError.value).toBeNull();
    });

    describe('fetchTimeline', () => {
        it('unwraps a doubly-nested resource collection (data.data.data)', async () => {
            activityLogService.index.mockResolvedValue({ data: { data: { data: [{ id: 1 }, { id: 2 }] } } });

            const { timeline, isLoadingTimeline, fetchTimeline } = useAdminDashboardExtras();
            await fetchTimeline();

            expect(activityLogService.index).toHaveBeenCalledWith({ per_page: 6 });
            expect(timeline.value).toEqual([{ id: 1 }, { id: 2 }]);
            expect(isLoadingTimeline.value).toBe(false);
        });

        it('falls back to data.data when there is no inner .data (flat array shape)', async () => {
            activityLogService.index.mockResolvedValue({ data: { data: [{ id: 9 }] } });

            const { timeline, fetchTimeline } = useAdminDashboardExtras();
            await fetchTimeline();

            expect(timeline.value).toEqual([{ id: 9 }]);
        });
    });

    describe('fetchGeneratorTimeline', () => {
        it('loads and stores a single generator timeline, unwrapping data.data', async () => {
            generatorService.timeline.mockResolvedValue({ data: { data: [{ id: 1, type: 'maintenance' }] } });

            const { generatorTimeline, isLoadingGeneratorTimeline, fetchGeneratorTimeline } = useAdminDashboardExtras();
            await fetchGeneratorTimeline(42);

            expect(generatorService.timeline).toHaveBeenCalledWith(42);
            expect(generatorTimeline.value).toEqual([{ id: 1, type: 'maintenance' }]);
            expect(isLoadingGeneratorTimeline.value).toBe(false);
        });

        it('swallows a rejected fetch and resets generatorTimeline to an empty array (error is not exposed)', async () => {
            generatorService.timeline.mockRejectedValue({ message: 'Network Error' });

            const { generatorTimeline, isLoadingGeneratorTimeline, fetchGeneratorTimeline } = useAdminDashboardExtras();
            await expect(fetchGeneratorTimeline(1)).resolves.toBeUndefined();

            expect(generatorTimeline.value).toEqual([]);
            expect(isLoadingGeneratorTimeline.value).toBe(false);
        });
    });

    describe('ensureGeneratorsForSelectLoaded', () => {
        it('fetches and stores the list (unwrapping data.data.data) the first time', async () => {
            generatorService.list.mockResolvedValue({ data: { data: { data: [{ id: 1 }, { id: 2 }] } } });

            const { allGeneratorsForSelect, ensureGeneratorsForSelectLoaded } = useAdminDashboardExtras();
            await ensureGeneratorsForSelectLoaded();

            expect(generatorService.list).toHaveBeenCalledWith({ per_page: 200 });
            expect(allGeneratorsForSelect.value).toEqual([{ id: 1 }, { id: 2 }]);
        });

        it('is a guarded no-op once already loaded (does not re-call the service)', async () => {
            generatorService.list.mockResolvedValue({ data: { data: { data: [{ id: 1 }] } } });

            const { allGeneratorsForSelect, ensureGeneratorsForSelectLoaded } = useAdminDashboardExtras();
            await ensureGeneratorsForSelectLoaded();
            await ensureGeneratorsForSelectLoaded();

            expect(generatorService.list).toHaveBeenCalledTimes(1);
            expect(allGeneratorsForSelect.value).toEqual([{ id: 1 }]);
        });
    });

    describe('scheduleMaintenance', () => {
        it('returns true on success', async () => {
            generatorScheduleService.create.mockResolvedValue({ data: { data: { id: 5 } } });

            const { scheduleMaintenance, isSavingMaintenance, maintenanceError } = useAdminDashboardExtras();
            const result = await scheduleMaintenance(7, { starts_at: '2026-01-01' });

            expect(generatorScheduleService.create).toHaveBeenCalledWith(7, { starts_at: '2026-01-01' });
            expect(result).toBe(true);
            expect(isSavingMaintenance.value).toBe(false);
            expect(maintenanceError.value).toBeNull();
        });

        it('sets maintenanceError to the server message on a 422', async () => {
            generatorScheduleService.create.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { starts_at: ['حقل مطلوب.'] } } },
            });

            const { scheduleMaintenance, maintenanceError } = useAdminDashboardExtras();
            const result = await scheduleMaintenance(7, {});

            expect(result).toBe(false);
            expect(maintenanceError.value).toBe('Invalid.');
        });

        it('falls back to the translated message on a network error', async () => {
            generatorScheduleService.create.mockRejectedValue({ message: 'Network Error' });

            const { scheduleMaintenance, maintenanceError } = useAdminDashboardExtras();
            await scheduleMaintenance(7, {});

            expect(maintenanceError.value).toBe('تعذرت جدولة الصيانة.');
        });
    });

    describe('generatorsExportUrl', () => {
        it('delegates straight to generatorService.exportUrl and returns its result', () => {
            generatorService.exportUrl.mockReturnValue('/api/v1/generators/export?city=Gaza');

            const { generatorsExportUrl } = useAdminDashboardExtras();
            const url = generatorsExportUrl({ city: 'Gaza' });

            expect(generatorService.exportUrl).toHaveBeenCalledWith({ city: 'Gaza' });
            expect(url).toBe('/api/v1/generators/export?city=Gaza');
        });
    });
});
