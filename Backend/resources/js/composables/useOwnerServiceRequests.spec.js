import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/subscriptionServiceRequestService', () => ({
    default: {
        list: vi.fn(),
        review: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_service_requests: {
                load_error: 'تعذر تحميل الطلبات',
                review_error: 'تعذر مراجعة الطلب',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useOwnerServiceRequests } = await import('./useOwnerServiceRequests');
const subscriptionServiceRequestService = (await import('@/services/subscriptionServiceRequestService')).default;

describe('useOwnerServiceRequests', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with an empty request list, default pagination, and hasRequests false', () => {
        const { requests, pagination, isLoading, error, hasRequests } = useOwnerServiceRequests();

        expect(requests.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(hasRequests.value).toBe(false);
    });

    it('fetchRequests populates requests/pagination from a paginated payload and hasRequests becomes true', async () => {
        subscriptionServiceRequestService.list.mockResolvedValue({
            data: {
                data: {
                    data: [{ id: 1 }, { id: 2 }],
                    meta: { current_page: 2, last_page: 5, total: 42, per_page: 15 },
                },
            },
        });

        const { fetchRequests, requests, pagination, hasRequests } = useOwnerServiceRequests();
        await fetchRequests(2);

        expect(subscriptionServiceRequestService.list).toHaveBeenCalledWith({ page: 2, per_page: 15 });
        expect(requests.value).toEqual([{ id: 1 }, { id: 2 }]);
        expect(pagination.value).toEqual({ current_page: 2, last_page: 5, total: 42, per_page: 15 });
        expect(hasRequests.value).toBe(true);
    });

    it('falls back to the raw payload as the list and derives total from its length when meta is absent', async () => {
        subscriptionServiceRequestService.list.mockResolvedValue({
            data: { data: [{ id: 9 }] },
        });

        const { fetchRequests, requests, pagination } = useOwnerServiceRequests();
        await fetchRequests();

        expect(requests.value).toEqual([{ id: 9 }]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 1, per_page: 15 });
    });

    it('reshapes a load failure into the translated fallback message', async () => {
        subscriptionServiceRequestService.list.mockRejectedValue({ message: 'Network Error' });

        const { fetchRequests, error, isLoading } = useOwnerServiceRequests();
        await fetchRequests();

        expect(error.value).toBe('تعذر تحميل الطلبات');
        expect(isLoading.value).toBe(false);
    });

    it('startReviewing sets reviewingId and clears any previous reviewError', () => {
        const { startReviewing, reviewingId, reviewError } = useOwnerServiceRequests();
        reviewError.value = 'previous error';

        startReviewing(5);

        expect(reviewingId.value).toBe(5);
        expect(reviewError.value).toBeNull();
    });

    it('cancelReviewing clears reviewingId and reviewError', () => {
        const { startReviewing, cancelReviewing, reviewingId, reviewError } = useOwnerServiceRequests();
        startReviewing(5);
        reviewError.value = 'oops';

        cancelReviewing();

        expect(reviewingId.value).toBeNull();
        expect(reviewError.value).toBeNull();
    });

    it('reviewRequest replaces the matching request in the list, clears reviewingId, and returns true on success', async () => {
        subscriptionServiceRequestService.review.mockResolvedValue({
            data: { data: { id: 5, status: 'approved' } },
        });

        const { fetchRequests, startReviewing, reviewRequest, requests, reviewingId, isReviewing } = useOwnerServiceRequests();
        subscriptionServiceRequestService.list.mockResolvedValue({
            data: { data: { data: [{ id: 5, status: 'pending' }], meta: {} } },
        });
        await fetchRequests();
        startReviewing(5);

        const result = await reviewRequest(5, { decision: 'approve', review_note: 'ok', fee_amount: 10, fee_currency: 'ILS' });

        expect(result).toBe(true);
        expect(subscriptionServiceRequestService.review).toHaveBeenCalledWith(5, {
            decision: 'approve',
            review_note: 'ok',
            fee_amount: 10,
            fee_currency: 'ILS',
        });
        expect(requests.value[0]).toEqual({ id: 5, status: 'approved' });
        expect(reviewingId.value).toBeNull();
        expect(isReviewing.value).toBe(false);
    });

    it('reviewRequest omits fee_amount/fee_currency/review_note when falsy (fee_currency dropped even if provided, since fee_amount is falsy)', async () => {
        subscriptionServiceRequestService.review.mockResolvedValue({ data: { data: { id: 5 } } });

        const { reviewRequest } = useOwnerServiceRequests();
        await reviewRequest(5, { decision: 'reject', review_note: '', fee_amount: 0, fee_currency: 'ILS' });

        expect(subscriptionServiceRequestService.review).toHaveBeenCalledWith(5, {
            decision: 'reject',
            review_note: undefined,
            fee_amount: undefined,
            fee_currency: undefined,
        });
    });

    it('reviewRequest leaves the list untouched when the reviewed id is not found, and reports a reshaped error on failure', async () => {
        subscriptionServiceRequestService.review.mockRejectedValue({
            response: { status: 422, data: { message: 'غير صالح' } },
        });

        const { reviewRequest, requests, reviewError, isReviewing } = useOwnerServiceRequests();
        const result = await reviewRequest(999, { decision: 'approve' });

        expect(result).toBe(false);
        expect(requests.value).toEqual([]);
        expect(reviewError.value).toBe('غير صالح');
        expect(isReviewing.value).toBe(false);
    });
});
