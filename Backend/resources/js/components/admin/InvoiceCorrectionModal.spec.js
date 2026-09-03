import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import InvoiceCorrectionModal from './InvoiceCorrectionModal.vue';

vi.mock('@/services/invoiceService', () => ({
    default: { correct: vi.fn() },
}));

import invoiceService from '@/services/invoiceService';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: { close: 'إغلاق' },
            invoice_correction_modal: {
                title: 'تصحيح فاتورة #{id}',
                current_amount_label: 'المبلغ الحالي: {amount} {currency}',
                new_amount_label: 'المبلغ الصحيح الجديد',
                reason_label: 'سبب التصحيح',
                reason_placeholder: 'مثلًا: خطأ بحساب الاستهلاك',
                cancel: 'إلغاء',
                submit: 'تصحيح',
                submitting: 'جارٍ الحفظ...',
                generic_error: 'تعذّر تصحيح الفاتورة.',
            },
        },
    },
});

const INVOICE = { id: 42, final_amount: 150.5, currency: 'ILS' };

function mountModal() {
    return mount(InvoiceCorrectionModal, {
        props: { open: false, invoice: null },
        global: { plugins: [i18n], stubs: { Teleport: true } },
    });
}

async function openWithInvoice(wrapper, invoice = INVOICE) {
    await wrapper.setProps({ open: true, invoice });
}

describe('InvoiceCorrectionModal', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders nothing while closed', () => {
        const wrapper = mountModal();

        expect(wrapper.find('.modal-panel-pop').exists()).toBe(false);
    });

    it('opening with an invoice fills the amount field from invoice.final_amount and shows the header', async () => {
        const wrapper = mountModal();

        await openWithInvoice(wrapper);

        expect(wrapper.find('.modal-panel-pop').exists()).toBe(true);
        expect(wrapper.text()).toContain('تصحيح فاتورة #42');
        expect(wrapper.text()).toContain('المبلغ الحالي: 150.5 ILS');
        expect(wrapper.find('input[type="number"]').element.value).toBe('150.5');
    });

    it('the submit button is disabled until a reason is entered', async () => {
        const wrapper = mountModal();
        await openWithInvoice(wrapper);

        const submitBtn = wrapper.findAll('button').find((b) => b.text().includes('تصحيح'));
        expect(submitBtn.attributes('disabled')).toBeDefined();

        await wrapper.find('textarea').setValue('خطأ بالحساب');
        expect(submitBtn.attributes('disabled')).toBeUndefined();
    });

    it('submits the corrected amount and reason, then emits updated and close on success', async () => {
        invoiceService.correct.mockResolvedValue({ data: { data: { id: 42, final_amount: 200 } } });
        const wrapper = mountModal();
        await openWithInvoice(wrapper);

        await wrapper.find('input[type="number"]').setValue('200');
        await wrapper.find('textarea').setValue('تصحيح استهلاك');
        await wrapper.findAll('button').find((b) => b.text().includes('تصحيح')).trigger('click');
        await flushPromises();

        expect(invoiceService.correct).toHaveBeenCalledWith(42, 200, 'تصحيح استهلاك');
        expect(wrapper.emitted('updated')).toEqual([[{ id: 42, final_amount: 200 }]]);
        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('shows the field error message from a 422 response and does not emit close/updated', async () => {
        invoiceService.correct.mockRejectedValue({
            response: { status: 422, data: { message: 'قيمة غير صالحة.' } },
        });
        const wrapper = mountModal();
        await openWithInvoice(wrapper);

        await wrapper.find('textarea').setValue('سبب ما');
        await wrapper.findAll('button').find((b) => b.text().includes('تصحيح')).trigger('click');
        await flushPromises();

        expect(wrapper.text()).toContain('قيمة غير صالحة.');
        expect(wrapper.emitted('close')).toBeUndefined();
        expect(wrapper.emitted('updated')).toBeUndefined();
    });

    it('falls back to the translated generic error message on a network error', async () => {
        invoiceService.correct.mockRejectedValue({ message: 'Network Error' });
        const wrapper = mountModal();
        await openWithInvoice(wrapper);

        await wrapper.find('textarea').setValue('سبب ما');
        await wrapper.findAll('button').find((b) => b.text().includes('تصحيح')).trigger('click');
        await flushPromises();

        expect(wrapper.text()).toContain('تعذّر تصحيح الفاتورة.');
    });

    it('emits close without calling the service when cancel is clicked', async () => {
        const wrapper = mountModal();
        await openWithInvoice(wrapper);

        await wrapper.findAll('button').find((b) => b.text().includes('إلغاء')).trigger('click');

        expect(wrapper.emitted('close')).toHaveLength(1);
        expect(invoiceService.correct).not.toHaveBeenCalled();
    });

    it('emits close when clicking the backdrop itself', async () => {
        const wrapper = mountModal();
        await openWithInvoice(wrapper);

        await wrapper.find('.fixed.inset-0').trigger('click');

        expect(wrapper.emitted('close')).toHaveLength(1);
    });
});
