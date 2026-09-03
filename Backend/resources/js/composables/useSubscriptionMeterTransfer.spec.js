import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/subscriptionMeterTransferService', () => ({
    default: {
        list: vi.fn(),
        create: vi.fn(),
    },
}));
vi.mock('@/services/subscriberMeterService', () => ({
    default: {
        list: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            my_subscription_page: {
                load_error: 'تعذر تحميل البيانات',
                transfer_error: 'تعذر إرسال طلب النقل',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useSubscriptionMeterTransfer } = await import('./useSubscriptionMeterTransfer');
const subscriptionMeterTransferService = (await import('@/services/subscriptionMeterTransferService')).default;
const subscriberMeterService = (await import('@/services/subscriberMeterService')).default;

describe('useSubscriptionMeterTransfer', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('has the correct default reactive state', () => {
        const { meters, isLoadingMeters, metersError, requests, isLoadingRequests, requestsError, isCreating, createError } = useSubscriptionMeterTransfer();

        expect(meters.value).toEqual([]);
        expect(isLoadingMeters.value).toBe(false);
        expect(metersError.value).toBeNull();
        expect(requests.value).toEqual([]);
        expect(isLoadingRequests.value).toBe(false);
        expect(requestsError.value).toBeNull();
        expect(isCreating.value).toBe(false);
        expect(createError.value).toBeNull();
    });

    describe('loadMeters()', () => {
        it('populates meters on success', async () => {
            subscriberMeterService.list.mockResolvedValue({ data: { data: [{ id: 1, status: 'active' }] } });

            const { loadMeters, meters, isLoadingMeters, metersError } = useSubscriptionMeterTransfer();
            await loadMeters();

            expect(meters.value).toEqual([{ id: 1, status: 'active' }]);
            expect(isLoadingMeters.value).toBe(false);
            expect(metersError.value).toBeNull();
        });

        it('handles an empty/missing payload by defaulting to an empty array', async () => {
            subscriberMeterService.list.mockResolvedValue({ data: { data: null } });

            const { loadMeters, meters } = useSubscriptionMeterTransfer();
            await loadMeters();

            expect(meters.value).toEqual([]);
        });

        it('resets meters to [] and sets metersError on rejection (even if meters were previously populated)', async () => {
            subscriberMeterService.list.mockResolvedValueOnce({ data: { data: [{ id: 1, status: 'active' }] } });
            const { loadMeters, meters, metersError } = useSubscriptionMeterTransfer();
            await loadMeters();
            expect(meters.value).toHaveLength(1);

            subscriberMeterService.list.mockRejectedValueOnce({ message: 'Network Error' });
            await loadMeters();

            expect(meters.value).toEqual([]);
            expect(metersError.value).toBe('تعذر تحميل البيانات');
        });
    });

    describe('loadMyRequests()', () => {
        it('populates requests on success', async () => {
            subscriptionMeterTransferService.list.mockResolvedValue({ data: { data: [{ id: 1, subscription_id: 10, status: 'pending' }] } });

            const { loadMyRequests, requests, isLoadingRequests, requestsError } = useSubscriptionMeterTransfer();
            await loadMyRequests();

            expect(requests.value).toEqual([{ id: 1, subscription_id: 10, status: 'pending' }]);
            expect(isLoadingRequests.value).toBe(false);
            expect(requestsError.value).toBeNull();
        });

        it('resets requests to [] and sets requestsError on rejection', async () => {
            subscriptionMeterTransferService.list.mockResolvedValueOnce({ data: { data: [{ id: 1, subscription_id: 10 }] } });
            const { loadMyRequests, requests, requestsError } = useSubscriptionMeterTransfer();
            await loadMyRequests();
            expect(requests.value).toHaveLength(1);

            subscriptionMeterTransferService.list.mockRejectedValueOnce({ message: 'Network Error' });
            await loadMyRequests();

            expect(requests.value).toEqual([]);
            expect(requestsError.value).toBe('تعذر تحميل البيانات');
        });
    });

    describe('latestRequestFor()', () => {
        it('finds the request matching the given subscription id', async () => {
            subscriptionMeterTransferService.list.mockResolvedValue({
                data: { data: [{ id: 1, subscription_id: 10, status: 'pending' }, { id: 2, subscription_id: 20, status: 'approved' }] },
            });
            const { loadMyRequests, latestRequestFor } = useSubscriptionMeterTransfer();
            await loadMyRequests();

            expect(latestRequestFor(20)).toEqual({ id: 2, subscription_id: 20, status: 'approved' });
        });

        it('returns null when there is no matching request', () => {
            const { latestRequestFor } = useSubscriptionMeterTransfer();
            expect(latestRequestFor(999)).toBeNull();
        });
    });

    describe('eligibleMetersFor()', () => {
        it('returns only active meters excluding the current meter id', async () => {
            subscriberMeterService.list.mockResolvedValue({
                data: {
                    data: [
                        { id: 1, status: 'active' },
                        { id: 2, status: 'active' },
                        { id: 3, status: 'inactive' },
                    ],
                },
            });
            const { loadMeters, eligibleMetersFor } = useSubscriptionMeterTransfer();
            await loadMeters();

            expect(eligibleMetersFor(1)).toEqual([{ id: 2, status: 'active' }]);
        });

        it('returns an empty array when there are no meters loaded', () => {
            const { eligibleMetersFor } = useSubscriptionMeterTransfer();
            expect(eligibleMetersFor(1)).toEqual([]);
        });
    });

    describe('createRequest()', () => {
        it('replaces any existing request for the same subscription, unshifts the new one, and returns the created request object', async () => {
            subscriptionMeterTransferService.list.mockResolvedValue({
                data: { data: [{ id: 1, subscription_id: 10, status: 'rejected' }] },
            });
            subscriptionMeterTransferService.create.mockResolvedValue({ data: { data: { id: 2, subscription_id: 10, status: 'pending' } } });

            const { loadMyRequests, createRequest, requests, isCreating } = useSubscriptionMeterTransfer();
            await loadMyRequests();

            const payload = { subscription_id: 10, to_subscriber_meter_id: 5 };
            const result = await createRequest(payload);

            expect(result).toEqual({ id: 2, subscription_id: 10, status: 'pending' });
            expect(subscriptionMeterTransferService.create).toHaveBeenCalledWith(payload);
            expect(requests.value).toEqual([{ id: 2, subscription_id: 10, status: 'pending' }]);
            expect(isCreating.value).toBe(false);
        });

        it('reshapes a 422 response into createError.value as the raw response data (not normalizeApiError) and returns null', async () => {
            subscriptionMeterTransferService.create.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { to_subscriber_meter_id: ['مطلوب.'] } } },
            });

            const { createRequest, createError } = useSubscriptionMeterTransfer();
            const result = await createRequest({ subscription_id: 10 });

            expect(result).toBeNull();
            expect(createError.value).toEqual({ message: 'Invalid.', errors: { to_subscriber_meter_id: ['مطلوب.'] } });
        });

        it('reshapes a network error into the translated fallback message', async () => {
            subscriptionMeterTransferService.create.mockRejectedValue({ message: 'Network Error' });

            const { createRequest, createError, isCreating } = useSubscriptionMeterTransfer();
            const result = await createRequest({ subscription_id: 10 });

            expect(result).toBeNull();
            expect(createError.value).toEqual({ message: 'تعذر إرسال طلب النقل' });
            expect(isCreating.value).toBe(false);
        });
    });
});
