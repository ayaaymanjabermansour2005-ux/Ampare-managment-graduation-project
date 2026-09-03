import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import InvoicesPaymentsPanel from './InvoicesPaymentsPanel.vue';

vi.mock('@/services/invoiceService', () => ({
    default: { downloadPdfUrl: vi.fn((id) => `/api/v1/invoices/${id}/pdf`) },
}));
vi.mock('@/services/paymentService', () => ({
    default: { exportUrl: vi.fn(() => '/api/v1/payments/export') },
}));

const confirmMock = vi.fn();
vi.mock('@/composables/useConfirm', () => ({
    useConfirm: () => ({ confirm: confirmMock }),
}));

const routerPush = vi.fn();
const routerReplace = vi.fn();
let mockRouteQuery = {};
vi.mock('vue-router', () => ({
    useRouter: () => ({ push: routerPush, replace: routerReplace }),
    useRoute: () => ({ query: mockRouteQuery }),
}));

import invoiceService from '@/services/invoiceService';
import paymentService from '@/services/paymentService';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: { invoice_hash: 'فاتورة #{id}', loading: 'جارِ التحميل...' },
            invoices_payments_panel: {
                title: 'الفواتير والدفعات',
                cancel_payment_title: 'إلغاء الدفعة',
                cancel_payment_confirm_message: 'لا يمكن التراجع عن هذا الإجراء.',
                cancel_payment_confirm_label: 'نعم، إلغاء',
                due_prefix: 'عليك دفع', due_suffix: 'حالياً',
                no_due_message: 'ما في أي مبلغ مستحق عليك حالياً',
                export_excel: 'تصدير Excel',
                tab_invoices: 'الفواتير', tab_payments: 'سجل الدفعات',
                status_pending: 'بانتظار الدفع', status_partially_paid: 'مدفوعة جزئيًا',
                status_paid: 'مدفوعة بالكامل', status_overdue: 'متأخرة', status_cancelled: 'ملغاة',
                payment_status_pending: 'قيد المراجعة', payment_status_paid: 'مدفوعة',
                payment_status_rejected: 'مرفوضة', payment_status_needs_correction: 'بحاجة تصحيح',
                method_wallet: 'محفظة', method_bank: 'تحويل بنكي', method_cash: 'نقدي',
                no_invoices_title: 'ما في فواتير بعد', no_payments_title: 'ما في دفعات بعد',
                due_date_prefix: 'تستحق', pay_now: 'ادفع الآن', details_title: 'التفاصيل',
                download_pdf: 'تحميل PDF', remaining_prefix: 'متبقي',
                rejection_reason_label: 'سبب الرفض:', needs_correction_label: 'مطلوب تصحيح:',
                resubmit_action: 'تصحيح وإعادة الإرسال', cancel_payment_action: 'إلغاء الدفعة',
                detail_generator_label: 'المولد', detail_due_date_label: 'تاريخ الاستحقاق',
                detail_consumption_label: 'الاستهلاك', detail_unit_price_label: 'سعر الوحدة',
                total_label: 'الإجمالي', qr_alt: 'رمز QR للفاتورة', qr_hint: 'hint',
            },
        },
    },
});

const BASE_PROPS = {
    invoices: [], invoicesPagination: { current_page: 1, last_page: 1, total: 0 },
    isLoadingInvoices: false, invoicesError: null, totalDue: 0, isLoadingInvoiceDetail: false,
    payments: [], paymentsPagination: { current_page: 1, last_page: 1, total: 0 },
    isLoadingPayments: false, paymentsError: null, isResubmitting: false, resubmitError: null,
    isCancelling: false,
};

function mountPanel(props = {}) {
    return mount(InvoicesPaymentsPanel, {
        props: { ...BASE_PROPS, ...props },
        global: { plugins: [i18n], stubs: { Teleport: true } },
    });
}

const INVOICE = {
    id: 101, status: 'overdue', final_amount: '350.00', currency: 'ILS',
    generator: { name: 'مولد حي الرمال' }, due_date: '2026-01-01', remaining_balance_ils: 350,
};

const PAYMENT = {
    id: 55, status: 'rejected', payment_method_type: 'wallet', amount: '200.00', currency: 'ILS',
    invoice_id: 101, generator: { name: 'مولد حي الرمال' }, rejection_reason: 'إيصال غير واضح',
};

describe('InvoicesPaymentsPanel', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockRouteQuery = {};
    });

    it('shows a loading skeleton while invoices are loading', () => {
        const wrapper = mountPanel({ isLoadingInvoices: true });
        expect(wrapper.findAll('.thumb-loading').length).toBeGreaterThan(0);
    });

    it('shows the invoices error state', () => {
        const wrapper = mountPanel({ invoicesError: 'تعذّر تحميل الفواتير.' });
        expect(wrapper.text()).toContain('تعذّر تحميل الفواتير.');
    });

    it('shows the empty invoices state', () => {
        const wrapper = mountPanel({ invoices: [] });
        expect(wrapper.text()).toContain('ما في فواتير بعد');
    });

    it('shows the due-amount banner when totalDue is positive, and the all-clear message otherwise', () => {
        const withDue = mountPanel({ totalDue: 120.5 });
        expect(withDue.text()).toContain('120.50');

        const noDue = mountPanel({ totalDue: 0 });
        expect(noDue.text()).toContain('ما في أي مبلغ مستحق عليك حالياً');
    });

    it('renders each invoice with its status, amount, and generator name', () => {
        const wrapper = mountPanel({ invoices: [INVOICE] });

        expect(wrapper.text()).toContain('فاتورة #101');
        expect(wrapper.text()).toContain('متأخرة');
        expect(wrapper.text()).toContain('350.00 ILS');
        expect(wrapper.text()).toContain('مولد حي الرمال');
    });

    it('shows the "pay now" action only for payable invoice statuses', () => {
        const payable = mountPanel({ invoices: [{ ...INVOICE, status: 'pending' }] });
        expect(payable.text()).toContain('ادفع الآن');

        const paid = mountPanel({ invoices: [{ ...INVOICE, status: 'paid' }] });
        expect(paid.text()).not.toContain('ادفع الآن');
    });

    it('navigates to the payment gateway when "pay now" is clicked', async () => {
        const wrapper = mountPanel({ invoices: [{ ...INVOICE, status: 'pending' }] });
        await wrapper.findAll('button').find((b) => b.text().includes('ادفع الآن')).trigger('click');

        expect(routerPush).toHaveBeenCalledWith({ name: 'payments.gateway', params: { id: 101 } });
    });

    it('links the PDF download button to invoiceService.downloadPdfUrl(invoice.id)', () => {
        const wrapper = mountPanel({ invoices: [INVOICE] });
        const pdfLink = wrapper.findAll('a').find((a) => a.attributes('href') === '/api/v1/invoices/101/pdf');

        expect(pdfLink).toBeTruthy();
        expect(invoiceService.downloadPdfUrl).toHaveBeenCalledWith(101);
    });

    it('opens the invoice detail modal and renders the detail once the async fetch resolves via the callback', async () => {
        const wrapper = mountPanel({ invoices: [INVOICE] });
        // The details trigger is an icon-only button — "التفاصيل" is its aria-label/title, not visible text.
        await wrapper.find('[aria-label="التفاصيل"]').trigger('click');

        expect(wrapper.emitted('fetch-invoice-detail')).toBeTruthy();
        const [id, callback] = wrapper.emitted('fetch-invoice-detail')[0];
        expect(id).toBe(101);

        callback({ ...INVOICE, consumed_kw: 40, price_per_kw: '2.5', items: [{ label: 'استهلاك', amount: '100' }], final_amount: '350.00' });
        await flushPromises();

        expect(wrapper.text()).toContain('40 kW');
        expect(wrapper.text()).toContain('استهلاك');
    });

    it('emits fetch-invoices with the clicked page number', async () => {
        const wrapper = mountPanel({ invoices: [INVOICE], invoicesPagination: { current_page: 1, last_page: 2, total: 5 } });
        const pageButtons = wrapper.findAll('button').filter((b) => /^\d+$/.test(b.text()));
        await pageButtons[1].trigger('click');

        expect(wrapper.emitted('fetch-invoices')).toEqual([[2]]);
    });

    it('switches to the payments tab on click and updates the route query', async () => {
        const wrapper = mountPanel({ invoices: [INVOICE], payments: [PAYMENT] });
        expect(wrapper.text()).toContain('مولد حي الرمال');
        expect(wrapper.text()).not.toContain('محفظة');

        await wrapper.findAll('button').find((b) => b.text().includes('سجل الدفعات')).trigger('click');

        expect(routerReplace).toHaveBeenCalledWith({ query: { tab: 'payments' } });
        expect(wrapper.text()).toContain('محفظة');
        expect(wrapper.text()).not.toContain('ادفع الآن');
    });

    it('starts on the payments tab when the route query already requests it', () => {
        mockRouteQuery = { tab: 'payments' };
        const wrapper = mountPanel({ payments: [PAYMENT] });

        expect(wrapper.text()).toContain('سجل الدفعات');
        expect(wrapper.text()).toContain('محفظة');
    });

    it('shows the empty payments state', () => {
        mockRouteQuery = { tab: 'payments' };
        const wrapper = mountPanel({ payments: [] });
        expect(wrapper.text()).toContain('ما في دفعات بعد');
    });

    it('shows the rejection reason for a rejected payment', () => {
        mockRouteQuery = { tab: 'payments' };
        const wrapper = mountPanel({ payments: [PAYMENT] });
        expect(wrapper.text()).toContain('إيصال غير واضح');
    });

    it('cancels a pending payment after confirmation', async () => {
        mockRouteQuery = { tab: 'payments' };
        confirmMock.mockResolvedValue(true);
        const pending = { ...PAYMENT, status: 'pending', rejection_reason: null };
        const wrapper = mountPanel({ payments: [pending] });

        await wrapper.findAll('button').find((b) => b.text().includes('إلغاء الدفعة')).trigger('click');
        await flushPromises();

        expect(wrapper.emitted('cancel-payment')).toEqual([[55]]);
    });

    it('does not cancel the payment when the confirmation is dismissed', async () => {
        mockRouteQuery = { tab: 'payments' };
        confirmMock.mockResolvedValue(false);
        const pending = { ...PAYMENT, status: 'pending', rejection_reason: null };
        const wrapper = mountPanel({ payments: [pending] });

        await wrapper.findAll('button').find((b) => b.text().includes('إلغاء الدفعة')).trigger('click');
        await flushPromises();

        expect(wrapper.emitted('cancel-payment')).toBeUndefined();
    });

    it('shows the export-excel link only on the payments tab when payments exist, using paymentService.exportUrl', () => {
        const invoicesTab = mountPanel({ invoices: [INVOICE], payments: [PAYMENT] });
        expect(invoicesTab.text()).not.toContain('تصدير Excel');

        mockRouteQuery = { tab: 'payments' };
        const paymentsTab = mountPanel({ payments: [PAYMENT] });
        expect(paymentsTab.text()).toContain('تصدير Excel');
        expect(paymentService.exportUrl).toHaveBeenCalled();
    });
});
