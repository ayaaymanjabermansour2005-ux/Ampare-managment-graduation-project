import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/paymentService', () => ({
    default: {
        list: vi.fn(),
        resubmit: vi.fn(),
        cancel: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            my_invoices_page: {
                payments_load_error: 'تعذر تحميل الدفعات',
                resubmit_error: 'تعذر إعادة إرسال الدفعة',
                cancel_error: 'تعذر إلغاء الدفعة',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useSubscriberPayments } = await import('./useSubscriberPayments');
const paymentService = (await import('@/services/paymentService')).default;

describe('useSubscriberPayments', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('has the correct default reactive state', () => {
        const { payments, pagination, isLoading, error, isResubmitting, resubmitError, isCancelling } = useSubscriberPayments();

        expect(payments.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(isResubmitting.value).toBe(false);
        expect(resubmitError.value).toBeNull();
        expect(isCancelling.value).toBe(false);
    });

    describe('fetchPayments()', () => {
        it('defaults to page 1 and populates payments/pagination on success', async () => {
            paymentService.list.mockResolvedValue({
                data: {
                    data: {
                        data: [{ id: 1, status: 'pending' }],
                        meta: { current_page: 1, last_page: 2, total: 20, per_page: 15 },
                    },
                },
            });

            const { fetchPayments, payments, pagination, isLoading, error } = useSubscriberPayments();
            await fetchPayments();

            expect(paymentService.list).toHaveBeenCalledWith({ page: 1 });
            expect(payments.value).toEqual([{ id: 1, status: 'pending' }]);
            expect(pagination.value).toEqual({ current_page: 1, last_page: 2, total: 20, per_page: 15 });
            expect(isLoading.value).toBe(false);
            expect(error.value).toBeNull();
        });

        it('derives pagination defaults from a non-paginated (raw array) payload with no meta', async () => {
            paymentService.list.mockResolvedValue({ data: { data: [{ id: 1 }] } });

            const { fetchPayments, pagination } = useSubscriberPayments();
            await fetchPayments();

            expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 1, per_page: 15 });
        });

        it('handles an empty payments list without throwing', async () => {
            paymentService.list.mockResolvedValue({ data: { data: { data: [], meta: { total: 0 } } } });

            const { fetchPayments, payments } = useSubscriberPayments();
            await fetchPayments();

            expect(payments.value).toEqual([]);
        });

        it('reshapes a rejection into error.value using the translated fallback message', async () => {
            paymentService.list.mockRejectedValue({ message: 'Network Error' });

            const { fetchPayments, error, isLoading } = useSubscriberPayments();
            await fetchPayments();

            expect(error.value).toBe('تعذر تحميل الدفعات');
            expect(isLoading.value).toBe(false);
        });
    });

    describe('resubmitPayment()', () => {
        it('builds a FormData with the given fields and attachments, updates the matching payment, and returns true', async () => {
            paymentService.list.mockResolvedValue({ data: { data: { data: [{ id: 5, status: 'rejected' }], meta: {} } } });
            paymentService.resubmit.mockResolvedValue({ data: { data: { id: 5, status: 'pending' } } });

            const { fetchPayments, resubmitPayment, payments, isResubmitting } = useSubscriberPayments();
            await fetchPayments();

            const file1 = new File(['a'], 'a.png', { type: 'image/png' });
            const file2 = new File(['b'], 'b.png', { type: 'image/png' });
            const result = await resubmitPayment(5, {
                amount: 150,
                transaction_reference: 'REF-1',
                note: 'ملاحظة',
                attachments: [file1, file2],
            });

            expect(result).toBe(true);
            expect(isResubmitting.value).toBe(false);
            expect(paymentService.resubmit).toHaveBeenCalledTimes(1);
            const [id, formData] = paymentService.resubmit.mock.calls[0];
            expect(id).toBe(5);
            expect(formData).toBeInstanceOf(FormData);
            expect(formData.get('amount')).toBe('150');
            expect(formData.get('transaction_reference')).toBe('REF-1');
            expect(formData.get('note')).toBe('ملاحظة');
            expect(formData.getAll('attachments[]')).toHaveLength(2);
            expect(payments.value[0]).toEqual({ id: 5, status: 'pending' });
        });

        it('omits falsy fields and appends no attachments when attachments is undefined', async () => {
            paymentService.resubmit.mockResolvedValue({ data: { data: { id: 5, status: 'pending' } } });

            const { resubmitPayment } = useSubscriberPayments();
            await resubmitPayment(5, { amount: null, transaction_reference: '', note: undefined, attachments: undefined });

            const [, formData] = paymentService.resubmit.mock.calls[0];
            expect(formData.has('amount')).toBe(false);
            expect(formData.has('transaction_reference')).toBe(false);
            expect(formData.has('note')).toBe(false);
            expect(formData.getAll('attachments[]')).toHaveLength(0);
        });

        it('does not crash when the payment id is not found in the current list (leaves payments untouched)', async () => {
            paymentService.resubmit.mockResolvedValue({ data: { data: { id: 999, status: 'pending' } } });

            const { resubmitPayment, payments } = useSubscriberPayments();
            const result = await resubmitPayment(999, { amount: 10 });

            expect(result).toBe(true);
            expect(payments.value).toEqual([]);
        });

        it('reshapes a 422 response into resubmitError.value as the raw response data (not normalizeApiError)', async () => {
            paymentService.resubmit.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { amount: ['غير صالح.'] } } },
            });

            const { resubmitPayment, resubmitError } = useSubscriberPayments();
            const result = await resubmitPayment(5, { amount: -1 });

            expect(result).toBe(false);
            expect(resubmitError.value).toEqual({ message: 'Invalid.', errors: { amount: ['غير صالح.'] } });
        });

        it('reshapes a network error into the translated fallback message', async () => {
            paymentService.resubmit.mockRejectedValue({ message: 'Network Error' });

            const { resubmitPayment, resubmitError, isResubmitting } = useSubscriberPayments();
            const result = await resubmitPayment(5, {});

            expect(result).toBe(false);
            expect(resubmitError.value).toEqual({ message: 'تعذر إعادة إرسال الدفعة' });
            expect(isResubmitting.value).toBe(false);
        });
    });

    describe('cancelPayment()', () => {
        it('updates the matching payment and returns true on success', async () => {
            paymentService.list.mockResolvedValue({ data: { data: { data: [{ id: 5, status: 'pending' }], meta: {} } } });
            paymentService.cancel.mockResolvedValue({ data: { data: { id: 5, status: 'cancelled' } } });

            const { fetchPayments, cancelPayment, payments, isCancelling } = useSubscriberPayments();
            await fetchPayments();
            const result = await cancelPayment(5);

            expect(result).toBe(true);
            expect(payments.value[0]).toEqual({ id: 5, status: 'cancelled' });
            expect(isCancelling.value).toBe(false);
        });

        it('sets a translated cancelError via normalizeApiError, resets isCancelling, and returns false on failure', async () => {
            paymentService.cancel.mockRejectedValue({ message: 'Network Error' });

            const { cancelPayment, cancelError, isCancelling } = useSubscriberPayments();
            const result = await cancelPayment(5);

            expect(result).toBe(false);
            expect(cancelError.value).toBe('تعذر إلغاء الدفعة');
            expect(isCancelling.value).toBe(false);
        });

        it('clears a previous cancelError at the start of a new attempt', async () => {
            paymentService.cancel.mockRejectedValueOnce({ message: 'Network Error' });
            const { cancelPayment, cancelError } = useSubscriberPayments();
            await cancelPayment(5);
            expect(cancelError.value).toBe('تعذر إلغاء الدفعة');

            paymentService.cancel.mockResolvedValueOnce({ data: { data: { id: 5, status: 'cancelled' } } });
            await cancelPayment(5);

            expect(cancelError.value).toBeNull();
        });

        it('does not crash when the payment id is not found in the current list', async () => {
            paymentService.cancel.mockResolvedValue({ data: { data: { id: 999, status: 'cancelled' } } });

            const { cancelPayment, payments } = useSubscriberPayments();
            const result = await cancelPayment(999);

            expect(result).toBe(true);
            expect(payments.value).toEqual([]);
        });
    });
});
