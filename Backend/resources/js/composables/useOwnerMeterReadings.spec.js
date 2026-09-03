import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/meterReadingService', () => ({
    default: {
        list: vi.fn(),
        createReading: vi.fn(),
        storeAttachment: vi.fn(),
        approve: vi.fn(),
        reject: vi.fn(),
        overdueSubscribers: vi.fn(),
        history: vi.fn(),
    },
}));
vi.mock('@/services/generatorService', () => ({
    default: {
        list: vi.fn(),
    },
}));
vi.mock('@/services/subscriptionService', () => ({
    default: {
        list: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_meter_readings: {
                load_error: 'تعذر تحميل القراءات',
                attachment_upload_error: 'تعذر رفع الصورة',
                submit_error: 'تعذر إرسال القراءة',
                approve_error: 'تعذر اعتماد القراءة',
                reject_error: 'تعذر رفض القراءة',
                overdue_load_error: 'تعذر تحميل المتأخرين',
                history_load_error: 'تعذر تحميل السجل',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useOwnerMeterReadings } = await import('./useOwnerMeterReadings');
const meterReadingService = (await import('@/services/meterReadingService')).default;
const generatorService = (await import('@/services/generatorService')).default;
const subscriptionService = (await import('@/services/subscriptionService')).default;

describe('useOwnerMeterReadings', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('has the expected default reactive state', () => {
        const {
            readings, pagination, isLoading, error, search, statusFilter,
            generators, subscriptionsForGenerator, isLoadingSubscriptions,
            isSubmitting, submitError, attachmentError,
            approvingId, approveError, rejectingId, rejectError,
            overdueSubscribers, isLoadingOverdue, overdueError,
            subscriberHistory, isLoadingHistory, historyError,
        } = useOwnerMeterReadings();

        expect(readings.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(search.value).toBe('');
        expect(statusFilter.value).toBe('');
        expect(generators.value).toEqual([]);
        expect(subscriptionsForGenerator.value).toEqual([]);
        expect(isLoadingSubscriptions.value).toBe(false);
        expect(isSubmitting.value).toBe(false);
        expect(submitError.value).toBeNull();
        expect(attachmentError.value).toBeNull();
        expect(approvingId.value).toBeNull();
        expect(approveError.value).toBeNull();
        expect(rejectingId.value).toBeNull();
        expect(rejectError.value).toBeNull();
        expect(overdueSubscribers.value).toEqual([]);
        expect(isLoadingOverdue.value).toBe(false);
        expect(overdueError.value).toBeNull();
        expect(subscriberHistory.value).toEqual([]);
        expect(isLoadingHistory.value).toBe(false);
        expect(historyError.value).toBeNull();
    });

    describe('fetchReadings', () => {
        it('populates readings/pagination from a paginated response', async () => {
            meterReadingService.list.mockResolvedValue({
                data: {
                    data: {
                        data: [{ id: 1, status: 'pending' }],
                        meta: { current_page: 1, last_page: 3, total: 30, per_page: 10 },
                    },
                },
            });

            const { fetchReadings, readings, pagination } = useOwnerMeterReadings();
            await fetchReadings();

            expect(meterReadingService.list).toHaveBeenCalledWith({ page: 1, search: undefined, status: undefined });
            expect(readings.value).toEqual([{ id: 1, status: 'pending' }]);
            expect(pagination.value).toEqual({ current_page: 1, last_page: 3, total: 30, per_page: 10 });
        });

        it('sets a translated error message on failure', async () => {
            meterReadingService.list.mockRejectedValue({ message: 'Network Error' });

            const { fetchReadings, error, isLoading } = useOwnerMeterReadings();
            await fetchReadings();

            expect(error.value).toBe('تعذر تحميل القراءات');
            expect(isLoading.value).toBe(false);
        });
    });

    describe('onSearchInput / onFilterChange', () => {
        it('onSearchInput debounces to a single fetchReadings call', async () => {
            vi.useFakeTimers();
            meterReadingService.list.mockResolvedValue({ data: { data: [] } });

            const { onSearchInput } = useOwnerMeterReadings();
            onSearchInput();
            onSearchInput();

            expect(meterReadingService.list).not.toHaveBeenCalled();
            await vi.advanceTimersByTimeAsync(300);

            expect(meterReadingService.list).toHaveBeenCalledTimes(1);
        });

        it('onFilterChange fetches page 1 immediately', async () => {
            meterReadingService.list.mockResolvedValue({ data: { data: [] } });

            const { onFilterChange } = useOwnerMeterReadings();
            onFilterChange();
            await Promise.resolve();
            await Promise.resolve();

            expect(meterReadingService.list).toHaveBeenCalledWith({ page: 1, search: undefined, status: undefined });
        });
    });

    describe('loadGenerators', () => {
        it('populates generators from payload.data', async () => {
            generatorService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, name: 'Gen A' }] } } });

            const { loadGenerators, generators } = useOwnerMeterReadings();
            await loadGenerators();

            expect(generatorService.list).toHaveBeenCalledWith({ per_page: 100 });
            expect(generators.value).toEqual([{ id: 1, name: 'Gen A' }]);
        });

        it('propagates a rejection (no internal error handling)', async () => {
            generatorService.list.mockRejectedValue(new Error('boom'));

            const { loadGenerators } = useOwnerMeterReadings();

            await expect(loadGenerators()).rejects.toThrow('boom');
        });
    });

    describe('loadSubscriptionsFor', () => {
        it('filters to only active subscriptions for the given generator', async () => {
            subscriptionService.list.mockResolvedValue({
                data: {
                    data: {
                        data: [
                            { id: 1, status: 'active', generator: { id: 10 } },
                            { id: 2, status: 'active', generator: { id: 20 } },
                            { id: 3, status: 'cancelled', generator: { id: 10 } },
                            { id: 4, status: 'active', generator: null },
                        ],
                    },
                },
            });

            const { loadSubscriptionsFor, subscriptionsForGenerator, isLoadingSubscriptions } = useOwnerMeterReadings();
            await loadSubscriptionsFor(10);

            expect(subscriptionService.list).toHaveBeenCalledWith({ per_page: 100 });
            expect(subscriptionsForGenerator.value).toEqual([{ id: 1, status: 'active', generator: { id: 10 } }]);
            expect(isLoadingSubscriptions.value).toBe(false);
        });

        it('resets isLoadingSubscriptions even when the request rejects, but propagates the error', async () => {
            subscriptionService.list.mockRejectedValue(new Error('boom'));

            const { loadSubscriptionsFor, isLoadingSubscriptions } = useOwnerMeterReadings();

            await expect(loadSubscriptionsFor(10)).rejects.toThrow('boom');
            expect(isLoadingSubscriptions.value).toBe(false);
        });
    });

    describe('submitReading', () => {
        it('creates the reading, refetches page 1, and returns success with no attachment error when there is no image', async () => {
            meterReadingService.createReading.mockResolvedValue({ data: { queued: false, data: { id: 1 } } });
            meterReadingService.list.mockResolvedValue({ data: { data: [] } });

            const { submitReading, isSubmitting, submitError, attachmentError } = useOwnerMeterReadings();
            const result = await submitReading({ value: 100 }, 'idem-key');

            expect(meterReadingService.createReading).toHaveBeenCalledWith({ value: 100 }, 'idem-key');
            expect(meterReadingService.storeAttachment).not.toHaveBeenCalled();
            expect(meterReadingService.list).toHaveBeenCalledTimes(1);
            expect(result).toEqual({ success: true, isQueued: false, attachmentError: null });
            expect(isSubmitting.value).toBe(false);
            expect(submitError.value).toBeNull();
            expect(attachmentError.value).toBeNull();
        });

        it('uploads the attachment as a separate request when a meter image file is provided', async () => {
            meterReadingService.createReading.mockResolvedValue({ data: { queued: false, data: { id: 42 } } });
            meterReadingService.storeAttachment.mockResolvedValue({ data: {} });
            meterReadingService.list.mockResolvedValue({ data: { data: [] } });

            const { submitReading } = useOwnerMeterReadings();
            const file = new File(['x'], 'meter.jpg', { type: 'image/jpeg' });
            const result = await submitReading({ value: 100 }, 'idem-key', file);

            expect(meterReadingService.storeAttachment).toHaveBeenCalledTimes(1);
            const [id, formData] = meterReadingService.storeAttachment.mock.calls[0];
            expect(id).toBe(42);
            expect(formData.get('file')).toBe(file);
            expect(result).toEqual({ success: true, isQueued: false, attachmentError: null });
        });

        it('skips the attachment upload and the refetch entirely when the reading is queued', async () => {
            meterReadingService.createReading.mockResolvedValue({ data: { queued: true, data: { id: 42 } } });

            const { submitReading } = useOwnerMeterReadings();
            const file = new File(['x'], 'meter.jpg', { type: 'image/jpeg' });
            const result = await submitReading({ value: 100 }, 'idem-key', file);

            expect(meterReadingService.storeAttachment).not.toHaveBeenCalled();
            expect(meterReadingService.list).not.toHaveBeenCalled();
            expect(result).toEqual({ success: true, isQueued: true, attachmentError: null });
        });

        it('reports an attachment error without failing the overall submission (reading already saved)', async () => {
            meterReadingService.createReading.mockResolvedValue({ data: { queued: false, data: { id: 1 } } });
            meterReadingService.storeAttachment.mockRejectedValue({ message: 'Network Error' });
            meterReadingService.list.mockResolvedValue({ data: { data: [] } });

            const { submitReading, attachmentError } = useOwnerMeterReadings();
            const file = new File(['x'], 'meter.jpg', { type: 'image/jpeg' });
            const result = await submitReading({ value: 100 }, 'idem-key', file);

            expect(result).toEqual({ success: true, isQueued: false, attachmentError: 'تعذر رفع الصورة' });
            expect(attachmentError.value).toBe('تعذر رفع الصورة');
            // the reading fetch still happens even though the attachment failed
            expect(meterReadingService.list).toHaveBeenCalledTimes(1);
        });

        it('sets a translated submitError and returns success:false when reading creation fails', async () => {
            meterReadingService.createReading.mockRejectedValue({ message: 'Network Error' });

            const { submitReading, submitError, isSubmitting } = useOwnerMeterReadings();
            const result = await submitReading({ value: 100 }, 'idem-key');

            expect(result).toEqual({ success: false });
            expect(submitError.value).toBe('تعذر إرسال القراءة');
            expect(isSubmitting.value).toBe(false);
        });
    });

    describe('approveReading', () => {
        it('replaces the matching reading and returns true', async () => {
            meterReadingService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, status: 'pending' }] } } });
            meterReadingService.approve.mockResolvedValue({ data: { data: { id: 1, status: 'approved' } } });

            const { fetchReadings, approveReading, readings, approvingId, approveError } = useOwnerMeterReadings();
            await fetchReadings();
            const result = await approveReading(1);

            expect(meterReadingService.approve).toHaveBeenCalledWith(1);
            expect(result).toBe(true);
            expect(readings.value[0]).toEqual({ id: 1, status: 'approved' });
            expect(approvingId.value).toBeNull();
            expect(approveError.value).toBeNull();
        });

        it('sets approvingId while in flight, then clears it', async () => {
            let resolvePromise;
            meterReadingService.approve.mockReturnValue(
                new Promise((resolve) => {
                    resolvePromise = resolve;
                }),
            );

            const { approveReading, approvingId } = useOwnerMeterReadings();
            const promise = approveReading(7);
            await Promise.resolve();

            expect(approvingId.value).toBe(7);

            resolvePromise({ data: { data: { id: 7, status: 'approved' } } });
            await promise;

            expect(approvingId.value).toBeNull();
        });

        it('sets a translated error and returns false on failure', async () => {
            meterReadingService.approve.mockRejectedValue({ message: 'Network Error' });

            const { approveReading, approveError } = useOwnerMeterReadings();
            const result = await approveReading(1);

            expect(result).toBe(false);
            expect(approveError.value).toBe('تعذر اعتماد القراءة');
        });
    });

    describe('rejectReading', () => {
        it('replaces the matching reading and returns true', async () => {
            meterReadingService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, status: 'pending' }] } } });
            meterReadingService.reject.mockResolvedValue({ data: { data: { id: 1, status: 'rejected' } } });

            const { fetchReadings, rejectReading, readings, rejectingId } = useOwnerMeterReadings();
            await fetchReadings();
            const result = await rejectReading(1, 'unclear photo');

            expect(meterReadingService.reject).toHaveBeenCalledWith(1, 'unclear photo');
            expect(result).toBe(true);
            expect(readings.value[0]).toEqual({ id: 1, status: 'rejected' });
            expect(rejectingId.value).toBeNull();
        });

        it('sets a translated error and returns false on failure', async () => {
            meterReadingService.reject.mockRejectedValue({ message: 'Network Error' });

            const { rejectReading, rejectError } = useOwnerMeterReadings();
            const result = await rejectReading(1, 'reason');

            expect(result).toBe(false);
            expect(rejectError.value).toBe('تعذر رفض القراءة');
        });
    });

    describe('loadOverdueSubscribers', () => {
        it('populates overdueSubscribers on success', async () => {
            meterReadingService.overdueSubscribers.mockResolvedValue({ data: { data: [{ id: 1 }] } });

            const { loadOverdueSubscribers, overdueSubscribers, isLoadingOverdue, overdueError } = useOwnerMeterReadings();
            await loadOverdueSubscribers();

            expect(overdueSubscribers.value).toEqual([{ id: 1 }]);
            expect(isLoadingOverdue.value).toBe(false);
            expect(overdueError.value).toBeNull();
        });

        it('resets to an empty array and sets a translated error on failure', async () => {
            meterReadingService.overdueSubscribers.mockRejectedValue({ message: 'Network Error' });

            const { loadOverdueSubscribers, overdueSubscribers, overdueError } = useOwnerMeterReadings();
            await loadOverdueSubscribers();

            expect(overdueSubscribers.value).toEqual([]);
            expect(overdueError.value).toBe('تعذر تحميل المتأخرين');
        });
    });

    describe('loadSubscriberHistory', () => {
        it('populates subscriberHistory on success', async () => {
            meterReadingService.history.mockResolvedValue({ data: { data: [{ month: '2026-01', consumed_kw: 120 }] } });

            const { loadSubscriberHistory, subscriberHistory, isLoadingHistory, historyError } = useOwnerMeterReadings();
            await loadSubscriberHistory(5);

            expect(meterReadingService.history).toHaveBeenCalledWith(5);
            expect(subscriberHistory.value).toEqual([{ month: '2026-01', consumed_kw: 120 }]);
            expect(isLoadingHistory.value).toBe(false);
            expect(historyError.value).toBeNull();
        });

        it('falls back to an empty array when data.data is missing', async () => {
            meterReadingService.history.mockResolvedValue({ data: {} });

            const { loadSubscriberHistory, subscriberHistory } = useOwnerMeterReadings();
            await loadSubscriberHistory(5);

            expect(subscriberHistory.value).toEqual([]);
        });

        it('sets a translated error on failure', async () => {
            meterReadingService.history.mockRejectedValue({ message: 'Network Error' });

            const { loadSubscriberHistory, historyError, isLoadingHistory } = useOwnerMeterReadings();
            await loadSubscriberHistory(5);

            expect(historyError.value).toBe('تعذر تحميل السجل');
            expect(isLoadingHistory.value).toBe(false);
        });
    });
});
