import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import PaymentReviewPanel from './PaymentReviewPanel.vue';
import AttachmentPreviewModal from '@/components/ui/AttachmentPreviewModal.vue';
import { useAuthStore } from '@/stores/auth';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: {
                close: 'إغلاق', view: 'عرض', download: 'تنزيل',
                open_in_new_tab: 'فتح في نافذة جديدة', preview_unavailable: 'لا يمكن معاينة هذا النوع من الملفات داخل التطبيق.',
            },
            owner_payments: {
                review_title: 'مراجعة الدفعة',
                generator_label: 'المولد',
                subscriber_note_label: 'ملاحظة المشترك',
                proof_of_payment: 'إثبات الدفع',
                no_attachments: 'لم يُرفق أي إثبات دفع مع هذه العملية.',
                amount_sent_label: 'المبلغ المُرسَل',
                reject_reason_label: 'سبب الرفض',
                correction_reason_label: 'ما الذي يحتاج تصحيحًا؟',
                cancel: 'إلغاء',
                sending_ellipsis: 'جارٍ الإرسال...',
                confirm_send: 'تأكيد الإرسال',
                request_correction: 'طلب تصحيح',
                reject: 'رفض',
                approve: 'قبول',
                status: { pending: 'بانتظار المراجعة', paid: 'مقبولة', rejected: 'مرفوضة', needs_correction: 'بحاجة تصحيح' },
                method: { wallet: 'محفظة', bank: 'حساب بنكي', cash: 'نقدًا' },
            },
        },
    },
});

const PAYMENT = {
    id: 1,
    amount: '250.00',
    currency: 'ILS',
    status: 'pending',
    payment_method_type: 'wallet',
    subscriber: { name: 'أحمد' },
    generator: { name: 'مولد الحي' },
    note: 'ملاحظة المشترك هنا',
    attachments: [
        { id: 1, mime_type: 'image/png', preview_url: '/img.png', original_name: 'proof.png' },
        { id: 2, mime_type: 'application/pdf', preview_url: '/doc.pdf', original_name: 'doc.pdf' },
    ],
};

function mountPanel(props = {}, permissions = ['payments.approve', 'payments.reject']) {
    const authStore = useAuthStore();
    authStore.permissions = permissions;

    return mount(PaymentReviewPanel, {
        props: { open: false, payment: null, isLoadingDetail: false, isActing: false, actionError: null, ...props },
        global: { plugins: [i18n], stubs: { Teleport: true } },
    });
}

describe('PaymentReviewPanel', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        setActivePinia(createPinia());
    });

    it('renders nothing while closed', () => {
        const wrapper = mountPanel({ open: false, payment: PAYMENT });

        expect(wrapper.find('[role="dialog"]').exists()).toBe(false);
    });

    it('shows a loading spinner and no payment content while isLoadingDetail is true', () => {
        const wrapper = mountPanel({ open: true, isLoadingDetail: true, payment: PAYMENT });

        expect(wrapper.find('.animate-spin').exists()).toBe(true);
        expect(wrapper.text()).not.toContain('المبلغ المُرسَل');
    });

    it('shows the amount, status, payment method, and subscriber name', () => {
        const wrapper = mountPanel({ open: true, payment: PAYMENT });

        expect(wrapper.text()).toContain('250.00');
        expect(wrapper.text()).toContain('ILS');
        expect(wrapper.text()).toContain('بانتظار المراجعة');
        expect(wrapper.text()).toContain('محفظة');
        expect(wrapper.text()).toContain('أحمد');
    });

    it('shows the generator name and subscriber note when present', () => {
        const wrapper = mountPanel({ open: true, payment: PAYMENT });

        expect(wrapper.text()).toContain('مولد الحي');
        expect(wrapper.text()).toContain('ملاحظة المشترك هنا');
    });

    it('hides the generator/note card when neither is present', () => {
        const wrapper = mountPanel({ open: true, payment: { ...PAYMENT, generator: null, note: null } });

        expect(wrapper.text()).not.toContain('المولد');
        expect(wrapper.text()).not.toContain('ملاحظة المشترك هنا');
    });

    it('shows the no-attachments message when there are none', () => {
        const wrapper = mountPanel({ open: true, payment: { ...PAYMENT, attachments: [] } });

        expect(wrapper.text()).toContain('لم يُرفق أي إثبات دفع مع هذه العملية.');
    });

    it('renders one button per attachment, split between image and non-image', () => {
        const wrapper = mountPanel({ open: true, payment: PAYMENT });

        expect(wrapper.find('img[alt="proof.png"]').exists()).toBe(true);
        expect(wrapper.text()).toContain('doc.pdf');
    });

    it('opens the attachment preview modal with the clicked attachment', async () => {
        const wrapper = mountPanel({ open: true, payment: PAYMENT });

        expect(wrapper.findComponent(AttachmentPreviewModal).props('attachment')).toBe(null);

        await wrapper.find('img[alt="proof.png"]').trigger('click');

        expect(wrapper.findComponent(AttachmentPreviewModal).props('attachment')).toEqual(PAYMENT.attachments[0]);
    });

    it('shows the action error message when set', () => {
        const wrapper = mountPanel({ open: true, payment: PAYMENT, actionError: 'تعذّر تنفيذ العملية.' });

        expect(wrapper.text()).toContain('تعذّر تنفيذ العملية.');
    });

    it('shows the pending footer actions only when payment.status is pending', () => {
        const pending = mountPanel({ open: true, payment: PAYMENT });
        expect(pending.text()).toContain('قبول');
        expect(pending.text()).toContain('رفض');

        const paid = mountPanel({ open: true, payment: { ...PAYMENT, status: 'paid' } });
        expect(paid.find('footer').exists()).toBe(false);
    });

    it('emits approve directly when the approve button is clicked', async () => {
        const wrapper = mountPanel({ open: true, payment: PAYMENT });

        await wrapper.findAll('button').find((b) => b.text() === 'قبول').trigger('click');

        expect(wrapper.emitted('approve')).toHaveLength(1);
    });

    it('opens the reject reason form, keeps the confirm button disabled until text is entered, then emits reject', async () => {
        const wrapper = mountPanel({ open: true, payment: PAYMENT });

        await wrapper.findAll('button').find((b) => b.text() === 'رفض').trigger('click');
        expect(wrapper.text()).toContain('سبب الرفض');

        const confirmBtn = wrapper.findAll('button').find((b) => b.text() === 'تأكيد الإرسال');
        expect(confirmBtn.attributes('disabled')).toBeDefined();

        await wrapper.find('textarea').setValue('البيانات غير مطابقة');
        expect(confirmBtn.attributes('disabled')).toBeUndefined();

        await confirmBtn.trigger('click');

        expect(wrapper.emitted('reject')).toEqual([['البيانات غير مطابقة']]);
    });

    it('opens the correction reason form and emits request-correction', async () => {
        const wrapper = mountPanel({ open: true, payment: PAYMENT });

        await wrapper.findAll('button').find((b) => b.text() === 'طلب تصحيح').trigger('click');
        expect(wrapper.text()).toContain('ما الذي يحتاج تصحيحًا؟');

        await wrapper.find('textarea').setValue('يرجى تعديل رقم العملية');
        await wrapper.findAll('button').find((b) => b.text() === 'تأكيد الإرسال').trigger('click');

        expect(wrapper.emitted('request-correction')).toEqual([['يرجى تعديل رقم العملية']]);
    });

    it('cancelling the reason form returns to the default footer actions', async () => {
        const wrapper = mountPanel({ open: true, payment: PAYMENT });

        await wrapper.findAll('button').find((b) => b.text() === 'رفض').trigger('click');
        await wrapper.findAll('button').find((b) => b.text() === 'إلغاء').trigger('click');

        expect(wrapper.text()).not.toContain('سبب الرفض');
        expect(wrapper.text()).toContain('قبول');
    });

    it('disables the footer actions while isActing is true', () => {
        const wrapper = mountPanel({ open: true, payment: PAYMENT, isActing: true });

        const approveBtn = wrapper.findAll('button').find((b) => b.text() === '...');
        expect(approveBtn.attributes('disabled')).toBeDefined();
    });

    it('resets activeAction and reasonText each time the panel reopens', async () => {
        const wrapper = mountPanel({ open: true, payment: PAYMENT });
        await wrapper.findAll('button').find((b) => b.text() === 'رفض').trigger('click');
        await wrapper.find('textarea').setValue('نص مؤقت');

        await wrapper.setProps({ open: false });
        await wrapper.setProps({ open: true });

        expect(wrapper.text()).not.toContain('سبب الرفض');
        expect(wrapper.text()).toContain('قبول');
    });

    it('emits close from the header close button', async () => {
        const wrapper = mountPanel({ open: true, payment: PAYMENT });

        await wrapper.find('button[aria-label="إغلاق"]').trigger('click');

        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('hides approve/correction but keeps reject when the user only has payments.reject', () => {
        const wrapper = mountPanel({ open: true, payment: PAYMENT }, ['payments.reject']);

        expect(wrapper.text()).not.toContain('قبول');
        expect(wrapper.text()).not.toContain('طلب تصحيح');
        expect(wrapper.text()).toContain('رفض');
    });

    it('hides reject but keeps approve/correction when the user only has payments.approve', () => {
        const wrapper = mountPanel({ open: true, payment: PAYMENT }, ['payments.approve']);

        expect(wrapper.text()).toContain('قبول');
        expect(wrapper.text()).toContain('طلب تصحيح');
        expect(wrapper.text()).not.toContain('رفض');
    });

    it('hides the entire footer when the user has neither payments.approve nor payments.reject', () => {
        const wrapper = mountPanel({ open: true, payment: PAYMENT }, []);

        expect(wrapper.find('footer').exists()).toBe(false);
    });
});
