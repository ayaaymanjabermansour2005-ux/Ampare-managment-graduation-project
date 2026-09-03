import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/paymentService', () => ({
    default: {
        list: vi.fn(),
        show: vi.fn(),
        approve: vi.fn(),
        reject: vi.fn(),
        needsCorrection: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_payments: {
                load_error: 'تعذر تحميل المدفوعات',
                approve_error: 'تعذر اعتماد الدفعة',
                reject_error: 'تعذر رفض الدفعة',
                correction_error: 'تعذر طلب التصحيح',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useOwnerPayments } = await import('./useOwnerPayments');
const paymentService = (await import('@/services/paymentService')).default;

describe('useOwnerPayments', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('has the expected default reactive state (statusFilter defaults to "pending")', () => {
        const { payments, pagination, isLoading, error, statusFilter, pendingCount, isLoadingDetail, isActing, actionError } =
            useOwnerPayments();

        expect(payments.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(statusFilter.value).toBe('pending');
        expect(pendingCount.value).toBe(0);
        expect(isLoadingDetail.value).toBe(false);
        expect(isActing.value).toBe(false);
        expect(actionError.value).toBeNull();
    });

    describe('fetchPayments', () => {
        it('requests the current statusFilter and populates payments/pagination', async () => {
            paymentService.list.mockResolvedValue({
                data: {
                    data: {
                        data: [{ id: 1, status: 'pending' }, { id: 2, status: 'pending' }, { id: 3, status: 'approved' }],
                        meta: { current_page: 1, last_page: 2, total: 15, per_page: 3 },
                    },
                },
            });

            const { fetchPayments, payments, pagination, pendingCount, isLoading } = useOwnerPayments();
            await fetchPayments();

            expect(paymentService.list).toHaveBeenCalledWith({ page: 1, status: 'pending' });
            expect(payments.value).toHaveLength(3);
            expect(pagination.value).toEqual({ current_page: 1, last_page: 2, total: 15, per_page: 3 });
            expect(pendingCount.value).toBe(2);
            expect(isLoading.value).toBe(false);
        });

        it('sends status:undefined when statusFilter is "all"', async () => {
            paymentService.list.mockResolvedValue({ data: { data: [] } });

            const { fetchPayments, statusFilter } = useOwnerPayments();
            statusFilter.value = 'all';
            await fetchPayments(2);

            expect(paymentService.list).toHaveBeenCalledWith({ page: 2, status: undefined });
        });

        it('handles an empty result and derives pagination defaults', async () => {
            paymentService.list.mockResolvedValue({ data: { data: [] } });

            const { fetchPayments, payments, pagination } = useOwnerPayments();
            await fetchPayments();

            expect(payments.value).toEqual([]);
            expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        });

        it('sets a translated error message on failure', async () => {
            paymentService.list.mockRejectedValue({ message: 'Network Error' });

            const { fetchPayments, error, isLoading } = useOwnerPayments();
            await fetchPayments();

            expect(error.value).toBe('تعذر تحميل المدفوعات');
            expect(isLoading.value).toBe(false);
        });
    });

    describe('onFilterChange', () => {
        it('refetches page 1 with the current statusFilter', async () => {
            paymentService.list.mockResolvedValue({ data: { data: [] } });

            const { onFilterChange, statusFilter } = useOwnerPayments();
            statusFilter.value = 'approved';
            onFilterChange();
            await Promise.resolve();
            await Promise.resolve();

            expect(paymentService.list).toHaveBeenCalledWith({ page: 1, status: 'approved' });
        });
    });

    describe('fetchPaymentDetail', () => {
        it('fetches the detail by id and returns it (detailCache is internal, not part of the public API)', async () => {
            paymentService.show.mockResolvedValue({ data: { data: { id: 3, amount: 500 } } });

            const { fetchPaymentDetail, isLoadingDetail } = useOwnerPayments();
            const result = await fetchPaymentDetail(3);

            expect(paymentService.show).toHaveBeenCalledWith(3);
            expect(result).toEqual({ id: 3, amount: 500 });
            expect(isLoadingDetail.value).toBe(false);
        });

        it('propagates a rejection but still clears isLoadingDetail (finally)', async () => {
            paymentService.show.mockRejectedValue(new Error('boom'));

            const { fetchPaymentDetail, isLoadingDetail } = useOwnerPayments();

            await expect(fetchPaymentDetail(3)).rejects.toThrow('boom');
            expect(isLoadingDetail.value).toBe(false);
        });
    });

    describe('approvePayment', () => {
        it('merges the returned fields into the existing list entry (does not fully replace it)', async () => {
            paymentService.list.mockResolvedValue({
                data: { data: { data: [{ id: 1, status: 'pending', amount: 200, note: 'original note' }] } },
            });
            // service intentionally returns a partial object (no `note` field)
            paymentService.approve.mockResolvedValue({ data: { data: { id: 1, status: 'approved' } } });

            const { fetchPayments, approvePayment, payments, isActing, actionError } = useOwnerPayments();
            await fetchPayments();
            const result = await approvePayment(1);

            expect(paymentService.approve).toHaveBeenCalledWith(1);
            expect(result).toBe(true);
            // merged: status updated, but amount/note preserved from the original entry
            expect(payments.value[0]).toEqual({ id: 1, status: 'approved', amount: 200, note: 'original note' });
            expect(isActing.value).toBe(false);
            expect(actionError.value).toBeNull();
        });

        it('sets a translated error and returns false on failure', async () => {
            paymentService.approve.mockRejectedValue({ message: 'Network Error' });

            const { approvePayment, actionError } = useOwnerPayments();
            const result = await approvePayment(1);

            expect(result).toBe(false);
            expect(actionError.value).toBe('تعذر اعتماد الدفعة');
        });
    });

    describe('rejectPayment', () => {
        it('sends the reason and merges the update into the list', async () => {
            paymentService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, status: 'pending' }] } } });
            paymentService.reject.mockResolvedValue({ data: { data: { id: 1, status: 'rejected' } } });

            const { fetchPayments, rejectPayment, payments } = useOwnerPayments();
            await fetchPayments();
            const result = await rejectPayment(1, 'invalid receipt');

            expect(paymentService.reject).toHaveBeenCalledWith(1, { reason: 'invalid receipt' });
            expect(result).toBe(true);
            expect(payments.value[0]).toEqual({ id: 1, status: 'rejected' });
        });

        it('sets a translated error and returns false on failure', async () => {
            paymentService.reject.mockRejectedValue({ message: 'Network Error' });

            const { rejectPayment, actionError } = useOwnerPayments();
            const result = await rejectPayment(1, 'reason');

            expect(result).toBe(false);
            expect(actionError.value).toBe('تعذر رفض الدفعة');
        });
    });

    describe('requestCorrection', () => {
        it('sends the note and merges the update into the list', async () => {
            paymentService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, status: 'pending' }] } } });
            paymentService.needsCorrection.mockResolvedValue({ data: { data: { id: 1, status: 'needs_correction' } } });

            const { fetchPayments, requestCorrection, payments } = useOwnerPayments();
            await fetchPayments();
            const result = await requestCorrection(1, 'blurry receipt');

            expect(paymentService.needsCorrection).toHaveBeenCalledWith(1, { note: 'blurry receipt' });
            expect(result).toBe(true);
            expect(payments.value[0]).toEqual({ id: 1, status: 'needs_correction' });
        });

        it('sets a translated error and returns false on failure', async () => {
            paymentService.needsCorrection.mockRejectedValue({ message: 'Network Error' });

            const { requestCorrection, actionError } = useOwnerPayments();
            const result = await requestCorrection(1, 'note');

            expect(result).toBe(false);
            expect(actionError.value).toBe('تعذر طلب التصحيح');
        });
    });
});
