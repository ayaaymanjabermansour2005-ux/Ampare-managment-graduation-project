import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/technicianPaymentService', () => ({
    default: {
        list: vi.fn(),
        create: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_technician_payments: {
                load_error: 'تعذر تحميل الدفعات',
                create_error: 'تعذر إنشاء الدفعة',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useOwnerTechnicianPayments } = await import('./useOwnerTechnicianPayments');
const technicianPaymentService = (await import('@/services/technicianPaymentService')).default;

describe('useOwnerTechnicianPayments', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with an empty list, default pagination, empty filters, and no error', () => {
        const { payments, pagination, isLoading, error, technicianFilter, statusFilter, isCreating, createError } = useOwnerTechnicianPayments();

        expect(payments.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(technicianFilter.value).toBe('');
        expect(statusFilter.value).toBe('');
        expect(isCreating.value).toBe(false);
        expect(createError.value).toBeNull();
    });

    it('fetchPayments sends technician_id/status as undefined when filters are empty, and populates state on success', async () => {
        technicianPaymentService.list.mockResolvedValue({
            data: {
                data: {
                    data: [{ id: 1, amount: 100 }],
                    meta: { current_page: 1, last_page: 3, total: 40, per_page: 15 },
                },
            },
        });

        const { fetchPayments, payments, pagination } = useOwnerTechnicianPayments();
        await fetchPayments();

        expect(technicianPaymentService.list).toHaveBeenCalledWith({ page: 1, technician_id: undefined, status: undefined });
        expect(payments.value).toEqual([{ id: 1, amount: 100 }]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 3, total: 40, per_page: 15 });
    });

    it('fetchPayments forwards the current technicianFilter/statusFilter values', async () => {
        technicianPaymentService.list.mockResolvedValue({ data: { data: [] } });

        const { fetchPayments, technicianFilter, statusFilter } = useOwnerTechnicianPayments();
        technicianFilter.value = 3;
        statusFilter.value = 'pending';
        await fetchPayments(2);

        expect(technicianPaymentService.list).toHaveBeenCalledWith({ page: 2, technician_id: 3, status: 'pending' });
    });

    it('onFilterChange re-fetches from page 1', () => {
        technicianPaymentService.list.mockResolvedValue({ data: { data: [] } });

        const { onFilterChange } = useOwnerTechnicianPayments();
        onFilterChange();

        expect(technicianPaymentService.list).toHaveBeenCalledWith({ page: 1, technician_id: undefined, status: undefined });
    });

    it('reshapes a fetchPayments network failure into the translated fallback message', async () => {
        technicianPaymentService.list.mockRejectedValue({ message: 'Network Error' });

        const { fetchPayments, error, isLoading } = useOwnerTechnicianPayments();
        await fetchPayments();

        expect(error.value).toBe('تعذر تحميل الدفعات');
        expect(isLoading.value).toBe(false);
    });

    it('createPayment refetches page 1 and returns true on success', async () => {
        technicianPaymentService.create.mockResolvedValue({ data: {} });
        technicianPaymentService.list.mockResolvedValue({
            data: { data: { data: [{ id: 5 }], meta: {} } },
        });

        const { createPayment, payments, isCreating, createError } = useOwnerTechnicianPayments();
        const result = await createPayment({ technician_id: 1, amount: 50 });

        expect(result).toBe(true);
        expect(technicianPaymentService.create).toHaveBeenCalledWith({ technician_id: 1, amount: 50 });
        expect(technicianPaymentService.list).toHaveBeenCalledWith({ page: 1, technician_id: undefined, status: undefined });
        expect(payments.value).toEqual([{ id: 5 }]);
        expect(isCreating.value).toBe(false);
        expect(createError.value).toBeNull();
    });

    it('createPayment on a 422 sets createError to the raw response.data (not normalizeApiError\'s reshaped form)', async () => {
        const responseData = { message: 'Invalid.', errors: { amount: ['المبلغ غير صالح.'] } };
        technicianPaymentService.create.mockRejectedValue({ response: { status: 422, data: responseData } });

        const { createPayment, createError, isCreating } = useOwnerTechnicianPayments();
        const result = await createPayment({});

        expect(result).toBe(false);
        // createError.value is a ref, so Vue auto-wraps the assigned object in a reactive
        // proxy — compare structurally (toEqual), not by reference (toBe).
        expect(createError.value).toEqual({ message: 'Invalid.', errors: { amount: ['المبلغ غير صالح.'] } });
        expect(isCreating.value).toBe(false);
    });

    it('createPayment on a network error (no response) falls back to a translated { message } object', async () => {
        technicianPaymentService.create.mockRejectedValue({ message: 'Network Error' });

        const { createPayment, createError } = useOwnerTechnicianPayments();
        await createPayment({});

        expect(createError.value).toEqual({ message: 'تعذر إنشاء الدفعة' });
    });
});
