import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import { nextTick } from 'vue';
import ConfirmDialog from './ConfirmDialog.vue';
import { useConfirm, useConfirmDialogState } from '@/composables/useConfirm';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: { close: 'إغلاق' },
        },
    },
});

function mountDialog() {
    return mount(ConfirmDialog, { global: { plugins: [i18n], stubs: { Teleport: true } } });
}

describe('ConfirmDialog', () => {
    beforeEach(() => {
        // state is a module-level singleton shared by every caller — drain any pending
        // resolver and reset it between tests so assertions don't leak across specs
        // (same convention as useConfirm.spec.js).
        const { state, handleCancel } = useConfirmDialogState();
        handleCancel();
        state.isOpen = false;
        state.title = '';
        state.message = '';
        state.confirmLabel = '';
        state.cancelLabel = '';
        state.variant = 'default';
        state.hideCancel = false;
    });

    it('renders nothing while the dialog is closed', () => {
        const wrapper = mountDialog();
        expect(wrapper.find('[role="alertdialog"]').exists()).toBe(false);
    });

    it('renders the title, message and labels once confirm() opens it, with the default (gold) header', async () => {
        const { confirm } = useConfirm();
        confirm({ title: 'حذف الفني', message: 'لن يمكن التراجع.', confirmLabel: 'حذف', cancelLabel: 'تراجع' });

        const wrapper = mountDialog();
        await nextTick();

        expect(wrapper.find('[role="alertdialog"]').exists()).toBe(true);
        expect(wrapper.find('h3').text()).toBe('حذف الفني');
        expect(wrapper.find('p').text()).toBe('لن يمكن التراجع.');
        expect(wrapper.find('.modal-head-brand').classes()).toContain('modal-head-brand--gold');
        expect(wrapper.find('.modal-head-brand').classes()).not.toContain('modal-head-brand--danger');
        const buttonTexts = wrapper.findAll('button').map((b) => b.text());
        expect(buttonTexts).toContain('حذف');
        expect(buttonTexts).toContain('تراجع');
    });

    it('switches to the danger header/confirm-button styling when variant is "danger"', async () => {
        const { confirm } = useConfirm();
        confirm({ title: 'حذف نهائي', message: 'م', confirmLabel: 'حذف' });
        // second call demonstrates the variant option specifically
        confirm({ title: 'حذف نهائي', message: 'م', confirmLabel: 'حذف', variant: 'danger' });

        const wrapper = mountDialog();
        await nextTick();

        expect(wrapper.find('.modal-head-brand').classes()).toContain('modal-head-brand--danger');
        const confirmBtn = wrapper.findAll('button').find((b) => b.text() === 'حذف');
        expect(confirmBtn.classes()).toContain('btn-fill-brand--danger');
    });

    it('hides the cancel button and the header close button when hideCancel is true', async () => {
        const { confirm } = useConfirm();
        confirm({ title: 'تنبيه', message: 'م', confirmLabel: 'حسناً', hideCancel: true });

        const wrapper = mountDialog();
        await nextTick();

        expect(wrapper.findAll('button')).toHaveLength(1);
        expect(wrapper.find('[aria-label="إغلاق"]').exists()).toBe(false);
    });

    it('omits the message paragraph when message is empty', async () => {
        const { confirm } = useConfirm();
        confirm({ title: 'تنبيه بدون رسالة', confirmLabel: 'حسناً' });

        const wrapper = mountDialog();
        await nextTick();

        expect(wrapper.find('p').exists()).toBe(false);
    });

    it('clicking the confirm button resolves the pending promise with true and closes the dialog', async () => {
        const { confirm } = useConfirm();
        const pending = confirm({ title: 'ت', message: 'م', confirmLabel: 'تأكيد', cancelLabel: 'إلغاء' });

        const wrapper = mountDialog();
        await nextTick();
        await wrapper.findAll('button').find((b) => b.text() === 'تأكيد').trigger('click');

        await expect(pending).resolves.toBe(true);
        await nextTick();
        expect(wrapper.find('[role="alertdialog"]').exists()).toBe(false);
    });

    it('clicking the cancel button resolves the pending promise with false and closes the dialog', async () => {
        const { confirm } = useConfirm();
        const pending = confirm({ title: 'ت', message: 'م', confirmLabel: 'تأكيد', cancelLabel: 'إلغاء' });

        const wrapper = mountDialog();
        await nextTick();
        await wrapper.findAll('button').find((b) => b.text() === 'إلغاء').trigger('click');

        await expect(pending).resolves.toBe(false);
        await nextTick();
        expect(wrapper.find('[role="alertdialog"]').exists()).toBe(false);
    });

    it('clicking the header close (X) button behaves like cancel', async () => {
        const { confirm } = useConfirm();
        const pending = confirm({ title: 'ت', message: 'م', confirmLabel: 'تأكيد' });

        const wrapper = mountDialog();
        await nextTick();
        await wrapper.find('[aria-label="إغلاق"]').trigger('click');

        await expect(pending).resolves.toBe(false);
    });

    it('clicking the backdrop (outside the panel) cancels the dialog', async () => {
        const { confirm } = useConfirm();
        const pending = confirm({ title: 'ت', message: 'م', confirmLabel: 'تأكيد' });

        const wrapper = mountDialog();
        await nextTick();
        await wrapper.find('.fixed.inset-0').trigger('click');

        await expect(pending).resolves.toBe(false);
    });

    it('pressing Escape cancels the dialog', async () => {
        const { confirm } = useConfirm();
        const pending = confirm({ title: 'ت', message: 'م', confirmLabel: 'تأكيد' });

        const wrapper = mountDialog();
        await nextTick();
        await wrapper.find('.fixed.inset-0').trigger('keydown.esc');

        await expect(pending).resolves.toBe(false);
    });
});
