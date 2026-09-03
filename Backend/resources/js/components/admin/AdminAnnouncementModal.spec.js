import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import AdminAnnouncementModal from './AdminAnnouncementModal.vue';

vi.mock('@/services/adminOpsService', () => ({
    default: { sendAnnouncement: vi.fn() },
}));

import adminOpsService from '@/services/adminOpsService';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: { close: 'إغلاق' },
            announcement: {
                audience_all: 'الجميع',
                audience_subscriber: 'المشتركون فقط',
                audience_owner: 'أصحاب المولدات فقط',
                audience_technician: 'الفنيون فقط',
                send_error: 'تعذّر إرسال الإشعار.',
                modal_title: 'إرسال إشعار جماعي',
                sent_to_count: 'تم الإرسال إلى {count} مستخدم',
                title_label: 'العنوان',
                message_label: 'نص الرسالة',
                audience_label: 'الفئة المستهدفة',
                sending: 'جارِ الإرسال...',
                send: 'إرسال',
            },
        },
    },
});

function mountModal() {
    return mount(AdminAnnouncementModal, {
        global: { plugins: [i18n], stubs: { Teleport: true } },
    });
}

async function openAndFill(wrapper, { title = 'صيانة مجدولة', message = 'سيتم قطع الكهرباء غدًا' } = {}) {
    wrapper.vm.open();
    await flushPromises();
    if (title !== null) await wrapper.find('input[type="text"]').setValue(title);
    if (message !== null) await wrapper.find('textarea').setValue(message);
}

describe('AdminAnnouncementModal', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('renders nothing before open() is called', () => {
        const wrapper = mountModal();

        expect(wrapper.find('.modal-panel-pop').exists()).toBe(false);
    });

    it('open() shows the modal with a reset form defaulting audience to "all"', async () => {
        const wrapper = mountModal();

        wrapper.vm.open();
        await flushPromises();

        expect(wrapper.find('.modal-panel-pop').exists()).toBe(true);
        expect(wrapper.text()).toContain('إرسال إشعار جماعي');
        expect(wrapper.find('input[type="text"]').element.value).toBe('');
        expect(wrapper.text()).toContain('الجميع');
    });

    it('does not call the service when title or message is blank', async () => {
        const wrapper = mountModal();
        await openAndFill(wrapper, { title: '   ', message: null });

        await wrapper.find('form').trigger('submit.prevent');
        await flushPromises();

        expect(adminOpsService.sendAnnouncement).not.toHaveBeenCalled();
    });

    it('submits the trimmed title/message with the selected audience and shows the recipient count on success', async () => {
        adminOpsService.sendAnnouncement.mockResolvedValue({ data: { data: { recipients_count: 12 } } });
        const wrapper = mountModal();
        await openAndFill(wrapper, { title: '  صيانة مجدولة  ', message: '  سيتم قطع الكهرباء غدًا  ' });

        await wrapper.find('form').trigger('submit.prevent');
        await flushPromises();

        expect(adminOpsService.sendAnnouncement).toHaveBeenCalledWith({
            title: 'صيانة مجدولة',
            message: 'سيتم قطع الكهرباء غدًا',
            audience: 'all',
        });
        expect(wrapper.text()).toContain('تم الإرسال إلى 12 مستخدم');
        expect(wrapper.find('form').exists()).toBe(false);
    });

    it('sends the chosen audience value when a different option is selected', async () => {
        adminOpsService.sendAnnouncement.mockResolvedValue({ data: { data: { recipients_count: 3 } } });
        const wrapper = mountModal();
        await openAndFill(wrapper);

        const dropdownTrigger = wrapper.find('button.field-input');
        await dropdownTrigger.trigger('click');
        const ownerOption = wrapper.findAll('[role="option"]').find((o) => o.text() === 'أصحاب المولدات فقط');
        await ownerOption.trigger('click');

        await wrapper.find('form').trigger('submit.prevent');
        await flushPromises();

        expect(adminOpsService.sendAnnouncement).toHaveBeenCalledWith(
            expect.objectContaining({ audience: 'generator_owner' }),
        );
    });

    it('closes automatically ~1.8s after a successful send', async () => {
        vi.useFakeTimers();
        adminOpsService.sendAnnouncement.mockResolvedValue({ data: { data: { recipients_count: 1 } } });
        const wrapper = mountModal();
        await openAndFill(wrapper);

        await wrapper.find('form').trigger('submit.prevent');
        await vi.advanceTimersByTimeAsync(0);

        expect(wrapper.find('.modal-panel-pop').exists()).toBe(true);

        await vi.advanceTimersByTimeAsync(1800);

        expect(wrapper.find('.modal-panel-pop').exists()).toBe(false);
    });

    it('shows the normalized error message on a failed send and keeps the form visible', async () => {
        adminOpsService.sendAnnouncement.mockRejectedValue({
            response: { status: 500, data: { message: 'تعذر الإرسال فعليًا.' } },
        });
        const wrapper = mountModal();
        await openAndFill(wrapper);

        await wrapper.find('form').trigger('submit.prevent');
        await flushPromises();

        expect(wrapper.text()).toContain('تعذر الإرسال فعليًا.');
        expect(wrapper.find('form').exists()).toBe(true);
    });

    it('falls back to the translated generic error message on a network error', async () => {
        adminOpsService.sendAnnouncement.mockRejectedValue({ message: 'Network Error' });
        const wrapper = mountModal();
        await openAndFill(wrapper);

        await wrapper.find('form').trigger('submit.prevent');
        await flushPromises();

        expect(wrapper.text()).toContain('تعذّر إرسال الإشعار.');
    });

    it('the close (X) button does nothing while a send is in flight, but closes once idle', async () => {
        let resolveSend;
        adminOpsService.sendAnnouncement.mockReturnValue(new Promise((r) => { resolveSend = r; }));
        const wrapper = mountModal();
        await openAndFill(wrapper);

        await wrapper.find('form').trigger('submit.prevent');
        await wrapper.find('.modal-head-brand__close').trigger('click');

        expect(wrapper.find('.modal-panel-pop').exists()).toBe(true);

        resolveSend({ data: { data: { recipients_count: 1 } } });
        await flushPromises();
        await wrapper.find('.modal-head-brand__close').trigger('click');

        expect(wrapper.find('.modal-panel-pop').exists()).toBe(false);
    });
});
