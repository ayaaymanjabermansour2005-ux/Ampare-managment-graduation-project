import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/subscriptionMeterTransferService', () => ({
    default: {
        list: vi.fn(),
        approve: vi.fn(),
        reject: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_subscribers: {
                load_error: 'تعذر تحميل الطلبات',
                transfer_error: 'تعذر تنفيذ عملية النقل',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useOwnerSubscriptionMeterTransfers } = await import('./useOwnerSubscriptionMeterTransfers');
const subscriptionMeterTransferService = (await import('@/services/subscriptionMeterTransferService')).default;

describe('useOwnerSubscriptionMeterTransfers', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts loading (isLoading true by default), with an empty list, default pagination, and a "pending" status filter', () => {
        const { requests, pagination, isLoading, error, statusFilter, isApproving, isRejecting } = useOwnerSubscriptionMeterTransfers();

        expect(requests.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(true);
        expect(error.value).toBeNull();
        expect(statusFilter.value).toBe('pending');
        expect(isApproving.value).toBe(false);
        expect(isRejecting.value).toBe(false);
    });

    it('fetchRequests sends the current statusFilter and populates requests/pagination on success', async () => {
        subscriptionMeterTransferService.list.mockResolvedValue({
            data: {
                data: {
                    data: [{ id: 1, status: 'pending' }],
                    meta: { current_page: 1, last_page: 2, total: 20, per_page: 15 },
                },
            },
        });

        const { fetchRequests, requests, pagination, isLoading } = useOwnerSubscriptionMeterTransfers();
        await fetchRequests(1);

        expect(subscriptionMeterTransferService.list).toHaveBeenCalledWith({ page: 1, status: 'pending' });
        expect(requests.value).toEqual([{ id: 1, status: 'pending' }]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 2, total: 20, per_page: 15 });
        expect(isLoading.value).toBe(false);
    });

    it('sends status: undefined when statusFilter is cleared out', async () => {
        subscriptionMeterTransferService.list.mockResolvedValue({ data: { data: [] } });

        const { fetchRequests, statusFilter } = useOwnerSubscriptionMeterTransfers();
        statusFilter.value = '';
        await fetchRequests();

        expect(subscriptionMeterTransferService.list).toHaveBeenCalledWith({ page: 1, status: undefined });
    });

    it('onFilterChange re-fetches starting at page 1', () => {
        subscriptionMeterTransferService.list.mockResolvedValue({ data: { data: [] } });

        const { onFilterChange } = useOwnerSubscriptionMeterTransfers();
        onFilterChange();

        expect(subscriptionMeterTransferService.list).toHaveBeenCalledWith({ page: 1, status: 'pending' });
    });

    it('reshapes a load failure into the translated fallback message', async () => {
        subscriptionMeterTransferService.list.mockRejectedValue({ message: 'Network Error' });

        const { fetchRequests, error, isLoading } = useOwnerSubscriptionMeterTransfers();
        await fetchRequests();

        expect(error.value).toBe('تعذر تحميل الطلبات');
        expect(isLoading.value).toBe(false);
    });

    it('approveRequest replaces the item in-place when it stays "pending" filtered but is no longer pending is false (still same status)', async () => {
        subscriptionMeterTransferService.list.mockResolvedValue({
            data: { data: { data: [{ id: 1, status: 'pending' }], meta: {} } },
        });
        subscriptionMeterTransferService.approve.mockResolvedValue({
            data: { data: { id: 1, status: 'approved' } },
        });

        const { fetchRequests, approveRequest, requests, isApproving } = useOwnerSubscriptionMeterTransfers();
        await fetchRequests();

        const result = await approveRequest(1);

        expect(result).toEqual({ id: 1, status: 'approved' });
        // statusFilter defaults to "pending" and the item moved away from pending -> it is spliced out
        expect(requests.value).toEqual([]);
        expect(isApproving.value).toBe(false);
    });

    it('replaceInList updates the item in place (not spliced) when the current statusFilter is not "pending"', async () => {
        subscriptionMeterTransferService.list.mockResolvedValue({
            data: { data: { data: [{ id: 1, status: 'approved' }], meta: {} } },
        });
        subscriptionMeterTransferService.reject.mockResolvedValue({
            data: { data: { id: 1, status: 'rejected' } },
        });

        const { fetchRequests, statusFilter, rejectRequest, requests } = useOwnerSubscriptionMeterTransfers();
        statusFilter.value = 'approved';
        await fetchRequests();

        await rejectRequest(1, 'لا يوجد سبب كافٍ');

        expect(subscriptionMeterTransferService.reject).toHaveBeenCalledWith(1, 'لا يوجد سبب كافٍ');
        expect(requests.value).toEqual([{ id: 1, status: 'rejected' }]);
    });

    it('approveRequest/rejectRequest are no-ops on the list when the id is not present (replaceInList returns early)', async () => {
        subscriptionMeterTransferService.approve.mockResolvedValue({ data: { data: { id: 999, status: 'approved' } } });

        const { approveRequest, requests } = useOwnerSubscriptionMeterTransfers();
        const result = await approveRequest(999);

        expect(result).toEqual({ id: 999, status: 'approved' });
        expect(requests.value).toEqual([]);
    });

    it('approveRequest reshapes a failure into approveError and returns null, leaving rejectError untouched', async () => {
        subscriptionMeterTransferService.approve.mockRejectedValue({
            response: { status: 422, data: { message: 'لا يمكن تنفيذ النقل' } },
        });

        const { approveRequest, approveError, rejectError, isApproving } = useOwnerSubscriptionMeterTransfers();
        const result = await approveRequest(1);

        expect(result).toBeNull();
        expect(approveError.value).toBe('لا يمكن تنفيذ النقل');
        expect(rejectError.value).toBeNull();
        expect(isApproving.value).toBe(false);
    });

    it('rejectRequest reshapes a network failure into the translated fallback message', async () => {
        subscriptionMeterTransferService.reject.mockRejectedValue({ message: 'Network Error' });

        const { rejectRequest, rejectError, isRejecting } = useOwnerSubscriptionMeterTransfers();
        const result = await rejectRequest(1, 'reason');

        expect(result).toBeNull();
        expect(rejectError.value).toBe('تعذر تنفيذ عملية النقل');
        expect(isRejecting.value).toBe(false);
    });
});
