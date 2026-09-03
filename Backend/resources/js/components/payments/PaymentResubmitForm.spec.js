import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import PaymentResubmitForm from './PaymentResubmitForm.vue';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: { close: 'إغلاق', cancel: 'إلغاء' },
            payment_resubmit_form: {
                title: 'إعادة إرسال الدفعة',
                amount_label: 'المبلغ',
                transaction_reference_label: 'رقم العملية',
                optional_suffix: '(اختياري)',
                note_label: 'ملاحظة',
                new_proof_label: 'إثبات دفع جديد',
                max_files_suffix: '(حتى 5 ملفات)',
                files_selected_count: '{count} ملف مختار',
                submit_idle: 'إعادة الإرسال',
                submit_loading: 'جارٍ الإرسال...',
            },
        },
    },
});

const PAYMENT = { id: 7, amount: '150.00' };

function mountForm(props = {}) {
    return mount(PaymentResubmitForm, {
        props: { open: false, payment: null, isSubmitting: false, serverError: null, ...props },
        global: { plugins: [i18n], stubs: { Teleport: true } },
    });
}

describe('PaymentResubmitForm', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders nothing while closed', () => {
        const wrapper = mountForm({ open: false });

        expect(wrapper.find('form, input[type="number"]').exists()).toBe(false);
        expect(wrapper.text()).not.toContain('إعادة إرسال الدفعة');
    });

    it('fills the amount field from payment.amount and clears reference/note when opened', async () => {
        // The form only (re)populates from `payment` on the false -> true edge of `open`
        // (see the `watch(() => props.open, ...)` in the component) — matching how the
        // real parent always keeps this component mounted and toggles `open` reactively
        // instead of re-creating it. Mounting straight into `open: true` never fires that watcher.
        const wrapper = mountForm({ open: false, payment: PAYMENT });
        await wrapper.setProps({ open: true });

        expect(wrapper.find('input[type="number"]').element.value).toBe('150.00');
        expect(wrapper.find('input[type="text"]').element.value).toBe('');
        expect(wrapper.find('textarea').element.value).toBe('');
    });

    it('shows the per-field server error under the amount input', () => {
        const wrapper = mountForm({
            open: true,
            payment: PAYMENT,
            serverError: { errors: { amount: ['المبلغ غير صحيح.'] } },
        });

        expect(wrapper.text()).toContain('المبلغ غير صحيح.');
    });

    it('shows the generic server error message when there is no errors object', () => {
        const wrapper = mountForm({
            open: true,
            payment: PAYMENT,
            serverError: { message: 'تعذّر إعادة الإرسال.' },
        });

        expect(wrapper.text()).toContain('تعذّر إعادة الإرسال.');
    });

    it('does not show the generic message when a field errors object is present', () => {
        const wrapper = mountForm({
            open: true,
            payment: PAYMENT,
            serverError: { message: 'رسالة عامة', errors: { amount: ['خطأ'] } },
        });

        expect(wrapper.text()).not.toContain('رسالة عامة');
    });

    it('caps selected files at 5 and shows the selected-files count', async () => {
        const wrapper = mountForm({ open: true, payment: PAYMENT });
        const files = Array.from({ length: 7 }, (_, i) => new File(['x'], `f${i}.png`, { type: 'image/png' }));
        const input = wrapper.find('input[type="file"]');
        Object.defineProperty(input.element, 'files', { value: files, configurable: true });

        await input.trigger('change');

        expect(wrapper.text()).toContain('5 ملف مختار');
    });

    it('emits close when the header close button is clicked', async () => {
        const wrapper = mountForm({ open: true, payment: PAYMENT });

        await wrapper.find('button[aria-label="إغلاق"]').trigger('click');

        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('emits close when the footer cancel button is clicked', async () => {
        const wrapper = mountForm({ open: true, payment: PAYMENT });

        await wrapper.findAll('button').find((b) => b.text() === 'إلغاء').trigger('click');

        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('emits close when the backdrop itself is clicked', async () => {
        const wrapper = mountForm({ open: true, payment: PAYMENT });

        await wrapper.find('.fixed.inset-0').trigger('click');

        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('emits submit with the current form fields and selected attachments', async () => {
        const wrapper = mountForm({ open: true, payment: PAYMENT });

        await wrapper.find('input[type="number"]').setValue('200.50');
        await wrapper.find('input[type="text"]').setValue('TXN-99');
        await wrapper.find('textarea').setValue('ملاحظة تجريبية');
        const file = new File(['x'], 'proof.png', { type: 'image/png' });
        const input = wrapper.find('input[type="file"]');
        Object.defineProperty(input.element, 'files', { value: [file], configurable: true });
        await input.trigger('change');

        await wrapper.findAll('button').find((b) => b.text().includes('إعادة الإرسال')).trigger('click');

        expect(wrapper.emitted('submit')).toHaveLength(1);
        expect(wrapper.emitted('submit')[0][0]).toEqual({
            // Vue 3 auto-applies the `.number` v-model modifier for a static type="number"
            // input, so the emitted amount is a Number, not the typed string.
            amount: 200.5,
            transaction_reference: 'TXN-99',
            note: 'ملاحظة تجريبية',
            attachments: [file],
        });
    });

    it('disables the submit button and shows the loading label while isSubmitting is true', () => {
        const wrapper = mountForm({ open: true, payment: PAYMENT, isSubmitting: true });

        const submitBtn = wrapper.findAll('button').find((b) => b.text().includes('جارٍ الإرسال'));
        expect(submitBtn.attributes('disabled')).toBeDefined();
        expect(wrapper.text()).not.toContain('إعادة الإرسال');
    });
});
