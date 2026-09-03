import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/invoiceService', () => ({
    default: {
        list: vi.fn(),
        cancel: vi.fn(),
        reissue: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            invoices_page: {
                load_error: 'تعذر تحميل الفواتير',
                cancel_error: 'تعذر إلغاء الفاتورة',
                reissue_error: 'تعذر إعادة إصدار الفاتورة',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminInvoices } = await import('./useAdminInvoices');
const invoiceService = (await import('@/services/invoiceService')).default;

function samplePage(items, meta = {}) {
    return {
        data: { data: { data: items, meta: { current_page: 1, last_page: 1, ...meta } } },
    };
}

describe('useAdminInvoices', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with default reactive state (pagination has only current_page/last_page, no total/per_page)', () => {
        const { invoices, pagination, isLoading, error, statusFilter, search } = useAdminInvoices();

        expect(invoices.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(statusFilter.value).toBe('all');
        expect(search.value).toBe('');
    });

    it('fetchInvoices populates invoices and pagination on success, omitting status when filter is "all" and search is empty', async () => {
        invoiceService.list.mockResolvedValue(samplePage([{ id: 1 }], { last_page: 4 }));

        const { fetchInvoices, invoices, pagination, isLoading } = useAdminInvoices();
        const promise = fetchInvoices(1);
        expect(isLoading.value).toBe(true);
        await promise;

        expect(invoiceService.list).toHaveBeenCalledWith({ page: 1 });
        expect(invoices.value).toEqual([{ id: 1 }]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 4 });
        expect(isLoading.value).toBe(false);
    });

    it('fetchInvoices includes status and search params only when set', async () => {
        invoiceService.list.mockResolvedValue(samplePage([]));
        const { fetchInvoices, statusFilter, search } = useAdminInvoices();

        statusFilter.value = 'paid';
        search.value = 'INV-1';
        await fetchInvoices(2);

        expect(invoiceService.list).toHaveBeenCalledWith({ page: 2, status: 'paid', search: 'INV-1' });
    });

    it('fetchInvoices handles an empty result list', async () => {
        invoiceService.list.mockResolvedValue(samplePage([]));
        const { fetchInvoices, invoices } = useAdminInvoices();

        await fetchInvoices();

        expect(invoices.value).toEqual([]);
    });

    it('fetchInvoices sets a translated error message via normalizeApiError on network failure', async () => {
        invoiceService.list.mockRejectedValue({ message: 'Network Error' });
        const { fetchInvoices, error } = useAdminInvoices();

        await fetchInvoices();

        expect(error.value).toBe('تعذر تحميل الفواتير');
    });

    it('onSearchInput debounces fetchInvoices by 350ms', async () => {
        vi.useFakeTimers();
        invoiceService.list.mockResolvedValue(samplePage([]));
        const { onSearchInput } = useAdminInvoices();

        onSearchInput();
        onSearchInput();
        expect(invoiceService.list).not.toHaveBeenCalled();

        await vi.advanceTimersByTimeAsync(350);
        expect(invoiceService.list).toHaveBeenCalledTimes(1);
        vi.useRealTimers();
    });

    it('applyFilter sets statusFilter and refetches page 1', () => {
        invoiceService.list.mockResolvedValue(samplePage([]));
        const { applyFilter, statusFilter } = useAdminInvoices();

        applyFilter('overdue');

        expect(statusFilter.value).toBe('overdue');
        expect(invoiceService.list).toHaveBeenCalledWith({ page: 1, status: 'overdue' });
    });

    it('replaceInvoice replaces the matching invoice by id, and is a no-op when the id is not found', () => {
        const { replaceInvoice, invoices } = useAdminInvoices();
        invoices.value = [{ id: 1, status: 'draft' }, { id: 2, status: 'paid' }];

        replaceInvoice({ id: 2, status: 'cancelled' });
        expect(invoices.value[1]).toEqual({ id: 2, status: 'cancelled' });

        replaceInvoice({ id: 99, status: 'paid' });
        expect(invoices.value).toHaveLength(2);
    });

    it('cancelInvoice replaces the invoice in place on success', async () => {
        invoiceService.cancel.mockResolvedValue({ data: { data: { id: 1, status: 'cancelled' } } });
        const { cancelInvoice, invoices, cancellingId } = useAdminInvoices();
        invoices.value = [{ id: 1, status: 'draft' }];

        const result = await cancelInvoice(1);

        expect(result).toBe(true);
        expect(invoices.value[0]).toEqual({ id: 1, status: 'cancelled' });
        expect(cancellingId.value).toBeNull();
    });

    it('cancelInvoice sets cancelError via normalizeApiError on failure', async () => {
        invoiceService.cancel.mockRejectedValue({
            response: { status: 422, data: { message: 'لا يمكن الإلغاء', errors: {} } },
        });
        const { cancelInvoice, cancelError } = useAdminInvoices();

        const result = await cancelInvoice(1);

        expect(result).toBe(false);
        expect(cancelError.value).toBe('لا يمكن الإلغاء');
    });

    it('reissueInvoice unshifts the newly issued invoice onto the front of the list (does not replace)', async () => {
        invoiceService.reissue.mockResolvedValue({ data: { data: { id: 2, status: 'issued' } } });
        const { reissueInvoice, invoices, reissuingId } = useAdminInvoices();
        invoices.value = [{ id: 1, status: 'cancelled' }];

        const result = await reissueInvoice(1);

        expect(result).toBe(true);
        expect(invoices.value).toEqual([{ id: 2, status: 'issued' }, { id: 1, status: 'cancelled' }]);
        expect(reissuingId.value).toBeNull();
    });

    it('reissueInvoice sets reissueError via normalizeApiError on failure', async () => {
        invoiceService.reissue.mockRejectedValue({ message: 'Network Error' });
        const { reissueInvoice, reissueError } = useAdminInvoices();

        const result = await reissueInvoice(1);

        expect(result).toBe(false);
        expect(reissueError.value).toBe('تعذر إعادة إصدار الفاتورة');
    });
});
