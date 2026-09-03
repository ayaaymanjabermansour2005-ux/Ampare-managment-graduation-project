import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import ToastContainer from './ToastContainer.vue';
import { useToastStore } from '@/stores/toast';
import AppIcon from '@/components/ui/AppIcon.vue';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: { common: { close: 'إغلاق' } },
        en: { common: { close: 'Close' } },
    },
});

function mountContainer() {
    setActivePinia(createPinia());
    const wrapper = mount(ToastContainer, { global: { plugins: [i18n], stubs: { Teleport: true } } });
    return { wrapper, store: useToastStore() };
}

describe('ToastContainer', () => {
    beforeEach(() => {
        i18n.global.locale.value = 'ar';
    });

    it('renders no toast cards when the store is empty', () => {
        const { wrapper } = mountContainer();
        expect(wrapper.findAll('.toast-card')).toHaveLength(0);
    });

    it("renders a toast's title and message once pushed to the store", async () => {
        const { wrapper, store } = mountContainer();
        store.show({ title: 'تم الحفظ', message: 'تم حفظ التغييرات بنجاح', type: 'success', duration: 0 });
        await wrapper.vm.$nextTick();

        expect(wrapper.findAll('.toast-card')).toHaveLength(1);
        expect(wrapper.text()).toContain('تم الحفظ');
        expect(wrapper.text()).toContain('تم حفظ التغييرات بنجاح');
    });

    it('omits the title line when a toast has no title', async () => {
        const { wrapper, store } = mountContainer();
        store.show({ message: 'رسالة بدون عنوان', type: 'info', duration: 0 });
        await wrapper.vm.$nextTick();

        expect(wrapper.find('.toast-card p.font-bold').exists()).toBe(false);
        expect(wrapper.text()).toContain('رسالة بدون عنوان');
    });

    it('stacks multiple toasts and dismisses only the one that was clicked', async () => {
        const { wrapper, store } = mountContainer();
        store.show({ title: 'أولى', message: 'م1', type: 'info', duration: 0 });
        store.show({ title: 'ثانية', message: 'م2', type: 'warning', duration: 0 });
        await wrapper.vm.$nextTick();
        expect(wrapper.findAll('.toast-card')).toHaveLength(2);

        await wrapper.findAll('.toast-card')[0].trigger('click');
        await wrapper.vm.$nextTick();

        expect(store.toasts).toHaveLength(1);
        expect(wrapper.text()).toContain('ثانية');
        expect(wrapper.text()).not.toContain('أولى');
    });

    it('dismisses via the close (X) button without needing to click the card body', async () => {
        const { wrapper, store } = mountContainer();
        store.show({ title: 'ت', message: 'م', type: 'danger', duration: 0 });
        await wrapper.vm.$nextTick();

        await wrapper.find('button[aria-label="إغلاق"]').trigger('click');
        await wrapper.vm.$nextTick();

        expect(store.toasts).toHaveLength(0);
    });

    it('resolves an unknown toast type to the "info" icon/accent fallback', async () => {
        const { wrapper, store } = mountContainer();
        store.show({ message: 'نوع غير معروف', type: 'does-not-exist', duration: 0 });
        await wrapper.vm.$nextTick();

        expect(wrapper.findComponent(AppIcon).props('name')).toBe('fa-bell');
        expect(wrapper.find('.toast-card').attributes('style')).toContain('#8A6D1F');
    });

    it('anchors the container to the left in Arabic and the right in English', async () => {
        const { wrapper } = mountContainer();
        expect(wrapper.find('div').classes()).toContain('left-4');

        i18n.global.locale.value = 'en';
        await wrapper.vm.$nextTick();
        expect(wrapper.find('div').classes()).toContain('right-4');
    });
});
