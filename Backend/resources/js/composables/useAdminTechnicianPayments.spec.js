import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/technicianPaymentService', () => ({
    default: {
        list: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            admin_payments_page: {
                technician_payments_load_error: 'تعذر تحميل مدفوعات الفنيين',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminTechnicianPayments } = await import('./useAdminTechnicianPayments');
const technicianPaymentService = (await import('@/services/technicianPaymentService')).default;

function samplePage(items, meta = {}) {
    return {
        data: { data: { data: items, meta: { current_page: 1, last_page: 1, total: items.length, per_page: 15, ...meta } } },
    };
}

describe('useAdminTechnicianPayments', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with default reactive state', () => {
        const { payments, pagination, isLoading, error, statusFilter, search } = useAdminTechnicianPayments();

        expect(payments.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(statusFilter.value).toBe('');
        expect(search.value).toBe('');
    });

    it('exposes no approve/reject actions — admin view is strictly read/audit-only by design', () => {
        const result = useAdminTechnicianPayments();

        expect(result.approve).toBeUndefined();
        expect(result.reject).toBeUndefined();
        expect(result.approvePayment).toBeUndefined();
        expect(result.rejectPayment).toBeUndefined();
    });

    it('fetchPayments populates payments and pagination on success', async () => {
        technicianPaymentService.list.mockResolvedValue(samplePage([{ id: 1 }], { total: 1 }));

        const { fetchPayments, payments, pagination, isLoading } = useAdminTechnicianPayments();
        const promise = fetchPayments(1);
        expect(isLoading.value).toBe(true);
        await promise;

        expect(technicianPaymentService.list).toHaveBeenCalledWith({ page: 1, status: undefined, search: undefined });
        expect(payments.value).toEqual([{ id: 1 }]);
        expect(pagination.value.total).toBe(1);
        expect(isLoading.value).toBe(false);
    });

    it('fetchPayments sends status/search only when set', async () => {
        technicianPaymentService.list.mockResolvedValue(samplePage([]));
        const { fetchPayments, statusFilter, search } = useAdminTechnicianPayments();

        statusFilter.value = 'approved';
        search.value = 'Ahmad';
        await fetchPayments(2);

        expect(technicianPaymentService.list).toHaveBeenCalledWith({ page: 2, status: 'approved', search: 'Ahmad' });
    });

    it('fetchPayments handles an empty result list', async () => {
        technicianPaymentService.list.mockResolvedValue(samplePage([], { total: 0 }));
        const { fetchPayments, payments } = useAdminTechnicianPayments();

        await fetchPayments();

        expect(payments.value).toEqual([]);
    });

    it('fetchPayments sets a translated error message via normalizeApiError on network failure', async () => {
        technicianPaymentService.list.mockRejectedValue({ message: 'Network Error' });
        const { fetchPayments, error, isLoading } = useAdminTechnicianPayments();

        await fetchPayments();

        expect(error.value).toBe('تعذر تحميل مدفوعات الفنيين');
        expect(isLoading.value).toBe(false);
    });

    it('onSearchInput debounces fetchPayments by 300ms', async () => {
        vi.useFakeTimers();
        technicianPaymentService.list.mockResolvedValue(samplePage([]));
        const { onSearchInput } = useAdminTechnicianPayments();

        onSearchInput();
        onSearchInput();
        expect(technicianPaymentService.list).not.toHaveBeenCalled();

        await vi.advanceTimersByTimeAsync(300);
        expect(technicianPaymentService.list).toHaveBeenCalledTimes(1);
        vi.useRealTimers();
    });
});
