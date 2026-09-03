import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/technicianPaymentService', () => ({
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
            technician_payments: {
                load_error: 'تعذر تحميل الدفعات',
                approve_error: 'تعذر الموافقة على الدفعة',
                reject_error: 'تعذر رفض الدفعة',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useTechnicianPayments } = await import('./useTechnicianPayments');
const technicianPaymentService = (await import('@/services/technicianPaymentService')).default;

describe('useTechnicianPayments', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with an empty list, default pagination, no error, and pendingCount 0', () => {
        const { payments, pagination, isLoading, error, pendingCount, actingId, actionError } = useTechnicianPayments();

        expect(payments.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(pendingCount.value).toBe(0);
        expect(actingId.value).toBeNull();
        expect(actionError.value).toBeNull();
    });

    it('fetchPayments populates payments/pagination and pendingCount reflects only "pending" statuses', async () => {
        technicianPaymentService.list.mockResolvedValue({
            data: {
                data: {
                    data: [
                        { id: 1, status: 'pending' },
                        { id: 2, status: 'approved' },
                        { id: 3, status: 'pending' },
                    ],
                    meta: { current_page: 1, last_page: 2, total: 20, per_page: 15 },
                },
            },
        });

        const { fetchPayments, payments, pagination, pendingCount, isLoading } = useTechnicianPayments();
        await fetchPayments();

        expect(technicianPaymentService.list).toHaveBeenCalledWith({ page: 1 });
        expect(payments.value).toHaveLength(3);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 2, total: 20, per_page: 15 });
        expect(pendingCount.value).toBe(2);
        expect(isLoading.value).toBe(false);
    });

    it('reshapes a fetchPayments network failure into the translated fallback message', async () => {
        technicianPaymentService.list.mockRejectedValue({ message: 'Network Error' });

        const { fetchPayments, error, isLoading } = useTechnicianPayments();
        await fetchPayments(2);

        expect(technicianPaymentService.list).toHaveBeenCalledWith({ page: 2 });
        expect(error.value).toBe('تعذر تحميل الدفعات');
        expect(isLoading.value).toBe(false);
    });

    it('reshapes a fetchPayments 422 into the backend message', async () => {
        technicianPaymentService.list.mockRejectedValue({
            response: { status: 422, data: { message: 'صفحة غير صالحة.' } },
        });

        const { fetchPayments, error } = useTechnicianPayments();
        await fetchPayments();

        expect(error.value).toBe('صفحة غير صالحة.');
    });

    it('approve tracks actingId while in flight, replaces the payment in the list, and clears actingId/actionError on success', async () => {
        technicianPaymentService.list.mockResolvedValue({
            data: { data: { data: [{ id: 1, status: 'pending' }], meta: {} } },
        });
        technicianPaymentService.approve.mockResolvedValue({ data: { data: { id: 1, status: 'approved' } } });

        const { fetchPayments, approve, payments, actingId, actionError } = useTechnicianPayments();
        await fetchPayments();

        const promise = approve(1);
        expect(actingId.value).toBe(1);
        const result = await promise;

        expect(result).toBe(true);
        expect(payments.value[0]).toEqual({ id: 1, status: 'approved' });
        expect(actingId.value).toBeNull();
        expect(actionError.value).toBeNull();
    });

    it('approve reshapes a failure into actionError, returns false, and clears actingId', async () => {
        technicianPaymentService.approve.mockRejectedValue({ message: 'Network Error' });

        const { approve, actionError, actingId } = useTechnicianPayments();
        const result = await approve(1);

        expect(result).toBe(false);
        expect(actionError.value).toBe('تعذر الموافقة على الدفعة');
        expect(actingId.value).toBeNull();
    });

    it('approve is a harmless no-op on the list when the id is not present, but still reports success', async () => {
        technicianPaymentService.approve.mockResolvedValue({ data: { data: { id: 999, status: 'approved' } } });

        const { approve, payments } = useTechnicianPayments();
        const result = await approve(999);

        expect(result).toBe(true);
        expect(payments.value).toEqual([]);
    });

    it('reject forwards the reason to the service and replaces the payment in the list on success', async () => {
        technicianPaymentService.list.mockResolvedValue({
            data: { data: { data: [{ id: 4, status: 'pending' }], meta: {} } },
        });
        technicianPaymentService.reject.mockResolvedValue({ data: { data: { id: 4, status: 'rejected' } } });

        const { fetchPayments, reject, payments, actingId } = useTechnicianPayments();
        await fetchPayments();
        const result = await reject(4, 'مبلغ غير مطابق');

        expect(technicianPaymentService.reject).toHaveBeenCalledWith(4, 'مبلغ غير مطابق');
        expect(result).toBe(true);
        expect(payments.value[0]).toEqual({ id: 4, status: 'rejected' });
        expect(actingId.value).toBeNull();
    });

    it('reject reshapes a failure into actionError and returns false', async () => {
        technicianPaymentService.reject.mockRejectedValue({
            response: { status: 422, data: { message: 'السبب مطلوب.' } },
        });

        const { reject, actionError } = useTechnicianPayments();
        const result = await reject(4, '');

        expect(result).toBe(false);
        expect(actionError.value).toBe('السبب مطلوب.');
    });
});
