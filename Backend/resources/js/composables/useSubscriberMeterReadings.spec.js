import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/meterReadingService', () => ({
    default: {
        list: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            subscriber_meter_readings_page: {
                load_error: 'تعذر تحميل القراءات',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useSubscriberMeterReadings } = await import('./useSubscriberMeterReadings');
const meterReadingService = (await import('@/services/meterReadingService')).default;

describe('useSubscriberMeterReadings', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('has the correct default reactive state', () => {
        const { readings, pagination, isLoading, error } = useSubscriberMeterReadings();

        expect(readings.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
    });

    describe('fetchReadings()', () => {
        it('defaults to page 1 and populates readings/pagination on success', async () => {
            meterReadingService.list.mockResolvedValue({
                data: {
                    data: {
                        data: [{ id: 1, status: 'approved' }, { id: 2, status: 'pending_approval' }],
                        meta: { current_page: 1, last_page: 3, total: 40, per_page: 15 },
                    },
                },
            });

            const { fetchReadings, readings, pagination, isLoading, error } = useSubscriberMeterReadings();
            await fetchReadings();

            expect(meterReadingService.list).toHaveBeenCalledWith({ page: 1 });
            expect(readings.value).toHaveLength(2);
            expect(pagination.value).toEqual({ current_page: 1, last_page: 3, total: 40, per_page: 15 });
            expect(isLoading.value).toBe(false);
            expect(error.value).toBeNull();
        });

        it('requests the given page number', async () => {
            meterReadingService.list.mockResolvedValue({ data: { data: { data: [], meta: {} } } });

            const { fetchReadings } = useSubscriberMeterReadings();
            await fetchReadings(2);

            expect(meterReadingService.list).toHaveBeenCalledWith({ page: 2 });
        });

        it('derives pagination defaults from a non-paginated (raw array) payload with no meta', async () => {
            meterReadingService.list.mockResolvedValue({ data: { data: [{ id: 1 }, { id: 2 }] } });

            const { fetchReadings, readings, pagination } = useSubscriberMeterReadings();
            await fetchReadings();

            expect(readings.value).toHaveLength(2);
            expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 2, per_page: 15 });
        });

        it('handles an empty readings list without throwing', async () => {
            meterReadingService.list.mockResolvedValue({ data: { data: { data: [], meta: { total: 0 } } } });

            const { fetchReadings, readings } = useSubscriberMeterReadings();
            await fetchReadings();

            expect(readings.value).toEqual([]);
        });

        it('reshapes a rejection into error.value using the translated fallback message', async () => {
            meterReadingService.list.mockRejectedValue({ message: 'Network Error' });

            const { fetchReadings, error, isLoading } = useSubscriberMeterReadings();
            await fetchReadings();

            expect(error.value).toBe('تعذر تحميل القراءات');
            expect(isLoading.value).toBe(false);
        });

        it('reshapes a 422 response into error.value using the backend message', async () => {
            meterReadingService.list.mockRejectedValue({ response: { status: 422, data: { message: 'خطأ تحقق' } } });

            const { fetchReadings, error } = useSubscriberMeterReadings();
            await fetchReadings();

            expect(error.value).toBe('خطأ تحقق');
        });
    });
});
