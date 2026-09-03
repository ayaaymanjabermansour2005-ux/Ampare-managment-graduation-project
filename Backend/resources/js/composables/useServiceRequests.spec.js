import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/subscriptionServiceRequestService', () => ({
    default: {
        list: vi.fn(),
        create: vi.fn(),
        cancel: vi.fn(),
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
            support_center_page: {
                requests_load_error: 'تعذر تحميل طلبات الخدمة',
                request_submit_error: 'تعذر إرسال الطلب',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useServiceRequests } = await import('./useServiceRequests');
const subscriptionServiceRequestService = (await import('@/services/subscriptionServiceRequestService')).default;
const subscriptionService = (await import('@/services/subscriptionService')).default;

describe('useServiceRequests', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts loading with no requests, no subscription id, and no errors', () => {
        const { requests, isLoading, error, mySubscriptionId, isSubmitting, submitError, cancellingId } = useServiceRequests();

        expect(requests.value).toEqual([]);
        expect(isLoading.value).toBe(true);
        expect(error.value).toBeNull();
        expect(mySubscriptionId.value).toBeNull();
        expect(isSubmitting.value).toBe(false);
        expect(submitError.value).toBeNull();
        expect(cancellingId.value).toBeNull();
    });

    describe('fetchRequests()', () => {
        it('unwraps a paginated payload and requests per_page: 30', async () => {
            subscriptionServiceRequestService.list.mockResolvedValue({
                data: { data: { data: [{ id: 1, status: 'pending' }], meta: { total: 1 } } },
            });

            const { fetchRequests, requests, isLoading } = useServiceRequests();
            await fetchRequests();

            expect(subscriptionServiceRequestService.list).toHaveBeenCalledWith({ per_page: 30 });
            expect(requests.value).toEqual([{ id: 1, status: 'pending' }]);
            expect(isLoading.value).toBe(false);
        });

        it('falls back to a flat array payload when there is no nested .data', async () => {
            subscriptionServiceRequestService.list.mockResolvedValue({ data: { data: [{ id: 2 }] } });

            const { fetchRequests, requests } = useServiceRequests();
            await fetchRequests();

            expect(requests.value).toEqual([{ id: 2 }]);
        });

        it('sets the translated fallback message on a network error', async () => {
            subscriptionServiceRequestService.list.mockRejectedValue({ message: 'Network Error' });

            const { fetchRequests, error, isLoading } = useServiceRequests();
            await fetchRequests();

            expect(error.value).toBe('تعذر تحميل طلبات الخدمة');
            expect(isLoading.value).toBe(false);
        });
    });

    describe('loadMySubscription()', () => {
        it('sets mySubscriptionId from the first subscription in the (paginated) result', async () => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [{ id: 77 }] } } });

            const { loadMySubscription, mySubscriptionId } = useServiceRequests();
            await loadMySubscription();

            expect(subscriptionService.list).toHaveBeenCalledWith({ per_page: 1 });
            expect(mySubscriptionId.value).toBe(77);
        });

        it('sets mySubscriptionId to null when the user has no subscriptions', async () => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [] } } });

            const { loadMySubscription, mySubscriptionId } = useServiceRequests();
            await loadMySubscription();

            expect(mySubscriptionId.value).toBeNull();
        });

        it('does not reject on failure, so a caller can safely chain .then() after it', async () => {
            // SupportCenterView.vue chains this with
            // `.then(() => serviceRequests.fetchRequests())` inside a Promise.all with no
            // `.catch` — a rejection here must never block fetchRequests() from running.
            subscriptionService.list.mockRejectedValue({ message: 'Network Error' });

            const { loadMySubscription, mySubscriptionId } = useServiceRequests();

            await expect(loadMySubscription()).resolves.toBeUndefined();
            expect(mySubscriptionId.value).toBeNull();
        });
    });

    describe('submitRequest()', () => {
        it('merges mySubscriptionId into the payload, unshifts the created request, and returns true', async () => {
            subscriptionServiceRequestService.create.mockResolvedValue({ data: { data: { id: 5, status: 'pending' } } });
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [{ id: 77 }] } } });

            const { loadMySubscription, submitRequest, requests } = useServiceRequests();
            await loadMySubscription();
            const result = await submitRequest({ type: 'maintenance', note: 'x' });

            expect(result).toBe(true);
            expect(subscriptionServiceRequestService.create).toHaveBeenCalledWith({
                type: 'maintenance',
                note: 'x',
                subscription_id: 77,
            });
            expect(requests.value[0]).toEqual({ id: 5, status: 'pending' });
        });

        it('stores the raw 422 response payload on validation failure', async () => {
            subscriptionServiceRequestService.create.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { type: ['نوع غير صالح.'] } } },
            });

            const { submitRequest, submitError } = useServiceRequests();
            const result = await submitRequest({});

            expect(result).toBe(false);
            expect(submitError.value).toEqual({ message: 'Invalid.', errors: { type: ['نوع غير صالح.'] } });
        });

        it('falls back to a bare { message } object on a network error', async () => {
            subscriptionServiceRequestService.create.mockRejectedValue({ message: 'Network Error' });

            const { submitRequest, submitError, isSubmitting } = useServiceRequests();
            await submitRequest({});

            expect(submitError.value).toEqual({ message: 'تعذر إرسال الطلب' });
            expect(isSubmitting.value).toBe(false);
        });
    });

    describe('cancelRequest()', () => {
        it('replaces the matching request in place and returns true, clearing cancellingId', async () => {
            subscriptionServiceRequestService.list.mockResolvedValue({
                data: { data: { data: [{ id: 1, status: 'pending' }, { id: 2, status: 'pending' }] } },
            });
            subscriptionServiceRequestService.cancel.mockResolvedValue({ data: { data: { id: 2, status: 'cancelled' } } });

            const { fetchRequests, cancelRequest, requests, cancellingId } = useServiceRequests();
            await fetchRequests();
            const result = await cancelRequest(2);

            expect(result).toBe(true);
            expect(requests.value).toEqual([
                { id: 1, status: 'pending' },
                { id: 2, status: 'cancelled' },
            ]);
            expect(cancellingId.value).toBeNull();
        });

        it('leaves the list untouched (but still returns true) when the id is not found', async () => {
            subscriptionServiceRequestService.list.mockResolvedValue({
                data: { data: { data: [{ id: 1, status: 'pending' }] } },
            });
            subscriptionServiceRequestService.cancel.mockResolvedValue({ data: { data: { id: 999, status: 'cancelled' } } });

            const { fetchRequests, cancelRequest, requests } = useServiceRequests();
            await fetchRequests();
            const result = await cancelRequest(999);

            expect(result).toBe(true);
            expect(requests.value).toEqual([{ id: 1, status: 'pending' }]);
        });

        it('swallows the error on failure — returns false with no error state exposed', async () => {
            subscriptionServiceRequestService.cancel.mockRejectedValue({ message: 'Network Error' });

            const { cancelRequest, cancellingId } = useServiceRequests();
            const result = await cancelRequest(2);

            expect(result).toBe(false);
            expect(cancellingId.value).toBeNull();
        });
    });
});
