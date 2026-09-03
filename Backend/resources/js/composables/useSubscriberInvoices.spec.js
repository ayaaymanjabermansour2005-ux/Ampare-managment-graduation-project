import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/invoiceService', () => ({
    default: {
        list: vi.fn(),
        getInvoice: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            my_invoices_page: {
                load_error: 'تعذر تحميل الفواتير',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useSubscriberInvoices } = await import('./useSubscriberInvoices');
const invoiceService = (await import('@/services/invoiceService')).default;

describe('useSubscriberInvoices', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('has the correct default reactive state', () => {
        const { invoices, pagination, isLoading, error, totalDue, isLoadingDetail, detailError } = useSubscriberInvoices();

        expect(invoices.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(totalDue.value).toBe(0);
        expect(isLoadingDetail.value).toBe(false);
        expect(detailError.value).toBeNull();
    });

    describe('fetchInvoices()', () => {
        it('defaults to page 1 and populates invoices/pagination/totalDue on success', async () => {
            invoiceService.list.mockResolvedValue({
                data: {
                    data: {
                        data: [
                            { id: 1, status: 'pending', remaining_balance_ils: 100 },
                            { id: 2, status: 'partially_paid', remaining_balance_ils: 30 },
                            { id: 3, status: 'paid', remaining_balance_ils: 0 },
                        ],
                        meta: { current_page: 1, last_page: 2, total: 25, per_page: 15 },
                    },
                },
            });

            const { fetchInvoices, invoices, pagination, totalDue, isLoading, error } = useSubscriberInvoices();
            await fetchInvoices();

            expect(invoiceService.list).toHaveBeenCalledWith({ page: 1 });
            expect(invoices.value).toHaveLength(3);
            expect(pagination.value).toEqual({ current_page: 1, last_page: 2, total: 25, per_page: 15 });
            expect(totalDue.value).toBe(130);
            expect(isLoading.value).toBe(false);
            expect(error.value).toBeNull();
        });

        it('requests the given page number', async () => {
            invoiceService.list.mockResolvedValue({ data: { data: { data: [], meta: {} } } });

            const { fetchInvoices } = useSubscriberInvoices();
            await fetchInvoices(3);

            expect(invoiceService.list).toHaveBeenCalledWith({ page: 3 });
        });

        it('derives pagination defaults from a non-paginated (raw array) payload with no meta', async () => {
            invoiceService.list.mockResolvedValue({ data: { data: [{ id: 1, status: 'pending', remaining_balance_ils: 10 }] } });

            const { fetchInvoices, invoices, pagination } = useSubscriberInvoices();
            await fetchInvoices();

            expect(invoices.value).toHaveLength(1);
            // meta falls back to `payload` itself (an array), whose .current_page/.total
            // are undefined, so the composable falls back to its own defaults.
            expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 1, per_page: 15 });
        });

        it('handles an empty invoices list without throwing', async () => {
            invoiceService.list.mockResolvedValue({ data: { data: { data: [], meta: { total: 0 } } } });

            const { fetchInvoices, invoices, totalDue } = useSubscriberInvoices();
            await fetchInvoices();

            expect(invoices.value).toEqual([]);
            expect(totalDue.value).toBe(0);
        });

        it('reshapes a rejection into error.value using the translated fallback message', async () => {
            invoiceService.list.mockRejectedValue({ message: 'Network Error' });

            const { fetchInvoices, error, isLoading } = useSubscriberInvoices();
            await fetchInvoices();

            expect(error.value).toBe('تعذر تحميل الفواتير');
            expect(isLoading.value).toBe(false);
        });

        it('reshapes a 422 response into error.value using the backend message', async () => {
            invoiceService.list.mockRejectedValue({ response: { status: 422, data: { message: 'خطأ تحقق' } } });

            const { fetchInvoices, error } = useSubscriberInvoices();
            await fetchInvoices();

            expect(error.value).toBe('خطأ تحقق');
        });
    });

    describe('fetchInvoiceDetail()', () => {
        it('returns the invoice detail on success and leaves detailError null', async () => {
            invoiceService.getInvoice.mockResolvedValue({ data: { data: { id: 7, status: 'paid' } } });

            const { fetchInvoiceDetail, detailError, isLoadingDetail } = useSubscriberInvoices();
            const result = await fetchInvoiceDetail(7);

            expect(result).toEqual({ id: 7, status: 'paid' });
            expect(invoiceService.getInvoice).toHaveBeenCalledWith(7);
            expect(detailError.value).toBeNull();
            expect(isLoadingDetail.value).toBe(false);
        });

        it('returns null and reshapes a rejection into detailError.value', async () => {
            invoiceService.getInvoice.mockRejectedValue({ message: 'Network Error' });

            const { fetchInvoiceDetail, detailError, isLoadingDetail } = useSubscriberInvoices();
            const result = await fetchInvoiceDetail(999);

            expect(result).toBeNull();
            expect(detailError.value).toBe('تعذر تحميل الفواتير');
            expect(isLoadingDetail.value).toBe(false);
        });
    });
});
