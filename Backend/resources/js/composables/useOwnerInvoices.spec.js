import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/invoiceService', () => ({
    default: {
        list: vi.fn(),
        cancel: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_invoices: {
                load_error: 'تعذر تحميل الفواتير',
                cancel_error: 'تعذر إلغاء الفاتورة',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useOwnerInvoices } = await import('./useOwnerInvoices');
const invoiceService = (await import('@/services/invoiceService')).default;

describe('useOwnerInvoices', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('has the expected default reactive state', () => {
        const { invoices, pagination, isLoading, error, search, statusFilter, overdueCount, cancellingId, cancelError } =
            useOwnerInvoices();

        expect(invoices.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(search.value).toBe('');
        expect(statusFilter.value).toBe('');
        expect(overdueCount.value).toBe(0);
        expect(cancellingId.value).toBeNull();
        expect(cancelError.value).toBeNull();
    });

    describe('fetchInvoices', () => {
        it('populates invoices/pagination from a paginated response', async () => {
            invoiceService.list.mockResolvedValue({
                data: {
                    data: {
                        data: [{ id: 1, status: 'paid' }, { id: 2, status: 'overdue' }],
                        meta: { current_page: 1, last_page: 2, total: 20, per_page: 2 },
                    },
                },
            });

            const { fetchInvoices, invoices, pagination, overdueCount, isLoading } = useOwnerInvoices();
            await fetchInvoices();

            expect(invoiceService.list).toHaveBeenCalledWith({ page: 1, search: undefined, status: undefined });
            expect(invoices.value).toHaveLength(2);
            expect(pagination.value).toEqual({ current_page: 1, last_page: 2, total: 20, per_page: 2 });
            expect(overdueCount.value).toBe(1);
            expect(isLoading.value).toBe(false);
        });

        it('passes search and statusFilter through when set', async () => {
            invoiceService.list.mockResolvedValue({ data: { data: [] } });

            const { fetchInvoices, search, statusFilter } = useOwnerInvoices();
            search.value = 'ali';
            statusFilter.value = 'overdue';
            await fetchInvoices(2);

            expect(invoiceService.list).toHaveBeenCalledWith({ page: 2, search: 'ali', status: 'overdue' });
        });

        it('handles an empty result and derives pagination defaults', async () => {
            invoiceService.list.mockResolvedValue({ data: { data: [] } });

            const { fetchInvoices, invoices, pagination, overdueCount } = useOwnerInvoices();
            await fetchInvoices();

            expect(invoices.value).toEqual([]);
            expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
            expect(overdueCount.value).toBe(0);
        });

        it('sets a translated error message on a network error', async () => {
            invoiceService.list.mockRejectedValue({ message: 'Network Error' });

            const { fetchInvoices, error, isLoading } = useOwnerInvoices();
            await fetchInvoices();

            expect(error.value).toBe('تعذر تحميل الفواتير');
            expect(isLoading.value).toBe(false);
        });

        it('surfaces the backend message on a response error', async () => {
            invoiceService.list.mockRejectedValue({ response: { status: 500, data: { message: 'خطأ في الخادم' } } });

            const { fetchInvoices, error } = useOwnerInvoices();
            await fetchInvoices();

            expect(error.value).toBe('خطأ في الخادم');
        });
    });

    describe('onSearchInput / onFilterChange', () => {
        it('onSearchInput debounces to a single fetchInvoices call', async () => {
            vi.useFakeTimers();
            invoiceService.list.mockResolvedValue({ data: { data: [] } });

            const { onSearchInput } = useOwnerInvoices();
            onSearchInput();
            onSearchInput();
            onSearchInput();

            expect(invoiceService.list).not.toHaveBeenCalled();
            await vi.advanceTimersByTimeAsync(300);

            expect(invoiceService.list).toHaveBeenCalledTimes(1);
        });

        it('onFilterChange fetches page 1 immediately', async () => {
            invoiceService.list.mockResolvedValue({ data: { data: [] } });

            const { onFilterChange } = useOwnerInvoices();
            onFilterChange();
            await Promise.resolve();
            await Promise.resolve();

            expect(invoiceService.list).toHaveBeenCalledWith({ page: 1, search: undefined, status: undefined });
        });
    });

    describe('cancelInvoice', () => {
        it('replaces the matching invoice in the list and returns true', async () => {
            invoiceService.list.mockResolvedValue({
                data: { data: { data: [{ id: 1, status: 'pending' }] } },
            });
            invoiceService.cancel.mockResolvedValue({ data: { data: { id: 1, status: 'cancelled' } } });

            const { fetchInvoices, cancelInvoice, invoices, cancellingId, cancelError } = useOwnerInvoices();
            await fetchInvoices();
            const result = await cancelInvoice(1);

            expect(invoiceService.cancel).toHaveBeenCalledWith(1);
            expect(result).toBe(true);
            expect(invoices.value[0]).toEqual({ id: 1, status: 'cancelled' });
            expect(cancellingId.value).toBeNull();
            expect(cancelError.value).toBeNull();
        });

        it('sets cancellingId to the invoice id while the request is in flight', async () => {
            let resolvePromise;
            invoiceService.cancel.mockReturnValue(
                new Promise((resolve) => {
                    resolvePromise = resolve;
                }),
            );

            const { cancelInvoice, cancellingId } = useOwnerInvoices();
            const promise = cancelInvoice(5);
            await Promise.resolve();

            expect(cancellingId.value).toBe(5);

            resolvePromise({ data: { data: { id: 5, status: 'cancelled' } } });
            await promise;

            expect(cancellingId.value).toBeNull();
        });

        it('does not throw when the invoice id is not present in the current list', async () => {
            invoiceService.cancel.mockResolvedValue({ data: { data: { id: 999, status: 'cancelled' } } });

            const { cancelInvoice, invoices } = useOwnerInvoices();
            const result = await cancelInvoice(999);

            expect(result).toBe(true);
            expect(invoices.value).toEqual([]);
        });

        it('sets a translated error and returns false on failure', async () => {
            invoiceService.cancel.mockRejectedValue({ message: 'Network Error' });

            const { cancelInvoice, cancelError, cancellingId } = useOwnerInvoices();
            const result = await cancelInvoice(1);

            expect(result).toBe(false);
            expect(cancelError.value).toBe('تعذر إلغاء الفاتورة');
            expect(cancellingId.value).toBeNull();
        });
    });
});
