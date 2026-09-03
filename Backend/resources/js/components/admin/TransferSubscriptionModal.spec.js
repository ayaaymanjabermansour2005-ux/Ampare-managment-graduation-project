import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import TransferSubscriptionModal from './TransferSubscriptionModal.vue';

vi.mock('@/services/subscriptionService', () => ({
    default: { transfer: vi.fn() },
}));
vi.mock('@/services/generatorService', () => ({
    default: { list: vi.fn() },
}));

import subscriptionService from '@/services/subscriptionService';
import generatorService from '@/services/generatorService';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: { close: 'إغلاق' },
            transfer_subscription_modal: {
                title: 'نقل الاشتراك لمولد آخر',
                current_generator_label: 'المولد الحالي: {name}',
                loading: 'جارٍ التحميل...',
                select_placeholder: 'اختاري المولد الجديد',
                cancel: 'إلغاء',
                transfer: 'نقل',
                transferring: 'جارٍ النقل...',
                generic_error: 'تعذّر نقل الاشتراك.',
            },
        },
    },
});

const SUBSCRIPTION = { id: 7, generator: { id: 1, name: 'مولد الحي الأول' } };

function mountModal() {
    return mount(TransferSubscriptionModal, {
        props: { open: false, subscription: null },
        global: { plugins: [i18n], stubs: { Teleport: true } },
    });
}

function resolveGeneratorList(items) {
    generatorService.list.mockResolvedValue({ data: { data: { data: items } } });
}

async function openWithSubscription(wrapper, subscription = SUBSCRIPTION) {
    await wrapper.setProps({ open: true, subscription });
}

describe('TransferSubscriptionModal', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders nothing while closed and never calls generatorService.list', () => {
        const wrapper = mountModal();

        expect(wrapper.find('.modal-panel-pop').exists()).toBe(false);
        expect(generatorService.list).not.toHaveBeenCalled();
    });

    it('shows a loading state while generators are being fetched, then the dropdown once resolved', async () => {
        let resolveList;
        generatorService.list.mockReturnValue(new Promise((r) => { resolveList = r; }));
        const wrapper = mountModal();

        await openWithSubscription(wrapper);
        expect(wrapper.text()).toContain('جارٍ التحميل...');

        resolveList({ data: { data: { data: [{ id: 1, name: 'مولد الحي الأول' }, { id: 2, name: 'مولد الحي الثاني' }] } } });
        await flushPromises();

        expect(wrapper.text()).not.toContain('جارٍ التحميل...');
        expect(wrapper.find('button.field-input').text()).toContain('اختاري المولد الجديد');
    });

    it('shows the current generator name in the subtitle and excludes it from the transfer options', async () => {
        resolveGeneratorList([
            { id: 1, name: 'مولد الحي الأول' },
            { id: 2, name: 'مولد الحي الثاني' },
        ]);
        const wrapper = mountModal();
        await openWithSubscription(wrapper);
        await flushPromises();

        expect(wrapper.text()).toContain('المولد الحالي: مولد الحي الأول');

        await wrapper.find('button.field-input').trigger('click');
        const options = wrapper.findAll('[role="option"]');
        expect(options).toHaveLength(1);
        expect(options[0].text()).toBe('مولد الحي الثاني');
    });

    it('keeps the transfer button disabled until a target generator is selected, then enables it', async () => {
        resolveGeneratorList([{ id: 2, name: 'مولد الحي الثاني' }]);
        const wrapper = mountModal();
        await openWithSubscription(wrapper);
        await flushPromises();

        const transferBtn = wrapper.findAll('button').find((b) => b.text().includes('نقل') && !b.text().includes('النقل'));
        expect(transferBtn.attributes('disabled')).toBeDefined();

        await wrapper.find('button.field-input').trigger('click');
        await wrapper.find('[role="option"]').trigger('click');

        expect(transferBtn.attributes('disabled')).toBeUndefined();
    });

    it('submits the selected generator id and emits transferred + close on success', async () => {
        resolveGeneratorList([{ id: 2, name: 'مولد الحي الثاني' }]);
        subscriptionService.transfer.mockResolvedValue({ data: { data: { id: 7, generator_id: 2 } } });
        const wrapper = mountModal();
        await openWithSubscription(wrapper);
        await flushPromises();

        await wrapper.find('button.field-input').trigger('click');
        await wrapper.find('[role="option"]').trigger('click');
        await wrapper.findAll('button').find((b) => b.text().includes('نقل') && !b.text().includes('النقل')).trigger('click');
        await flushPromises();

        expect(subscriptionService.transfer).toHaveBeenCalledWith(7, 2);
        expect(wrapper.emitted('transferred')).toEqual([[{ id: 7, generator_id: 2 }]]);
        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('shows the field error message from a failed transfer and does not emit close/transferred', async () => {
        resolveGeneratorList([{ id: 2, name: 'مولد الحي الثاني' }]);
        subscriptionService.transfer.mockRejectedValue({
            response: { status: 422, data: { message: 'لا يمكن النقل لهذا المولد.' } },
        });
        const wrapper = mountModal();
        await openWithSubscription(wrapper);
        await flushPromises();

        await wrapper.find('button.field-input').trigger('click');
        await wrapper.find('[role="option"]').trigger('click');
        await wrapper.findAll('button').find((b) => b.text().includes('نقل') && !b.text().includes('النقل')).trigger('click');
        await flushPromises();

        expect(wrapper.text()).toContain('لا يمكن النقل لهذا المولد.');
        expect(wrapper.emitted('close')).toBeUndefined();
        expect(wrapper.emitted('transferred')).toBeUndefined();
    });

    it('resets the selected generator each time the modal re-opens', async () => {
        resolveGeneratorList([{ id: 2, name: 'مولد الحي الثاني' }]);
        const wrapper = mountModal();
        await openWithSubscription(wrapper);
        await flushPromises();
        await wrapper.find('button.field-input').trigger('click');
        await wrapper.find('[role="option"]').trigger('click');
        let transferBtn = wrapper.findAll('button').find((b) => b.text().includes('نقل') && !b.text().includes('النقل'));
        expect(transferBtn.attributes('disabled')).toBeUndefined();

        await wrapper.setProps({ open: false });
        resolveGeneratorList([{ id: 2, name: 'مولد الحي الثاني' }]);
        await wrapper.setProps({ open: true });
        await flushPromises();

        transferBtn = wrapper.findAll('button').find((b) => b.text().includes('نقل') && !b.text().includes('النقل'));
        expect(transferBtn.attributes('disabled')).toBeDefined();
    });

    it('emits close without calling the service when cancel is clicked', async () => {
        resolveGeneratorList([]);
        const wrapper = mountModal();
        await openWithSubscription(wrapper);
        await flushPromises();

        await wrapper.findAll('button').find((b) => b.text().includes('إلغاء')).trigger('click');

        expect(wrapper.emitted('close')).toHaveLength(1);
        expect(subscriptionService.transfer).not.toHaveBeenCalled();
    });
});
