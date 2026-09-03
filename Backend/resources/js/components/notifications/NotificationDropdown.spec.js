import { describe, it, expect, vi, beforeEach } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import { useNotificationStore } from '@/stores/notification';
import NotificationDropdown from './NotificationDropdown.vue';

vi.mock('@/services/notificationService', () => ({
    default: {
        getAll: vi.fn(),
        unreadCount: vi.fn(),
        markAsRead: vi.fn(),
        markAllAsRead: vi.fn(),
        delete: vi.fn(),
    },
}));
import notificationService from '@/services/notificationService';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: {
                notifications: 'الإشعارات',
                mark_all_read: 'تعليم الكل كمقروء',
                loading: 'جارِ التحميل...',
                no_notifications: 'لا توجد إشعارات',
            },
        },
    },
});

// NotificationItem has its own routing/store wiring and its own dedicated spec —
// stub it here so this file stays focused on NotificationDropdown's own
// loading/empty/list rendering and read/close event wiring.
const itemStub = {
    name: 'NotificationItem',
    props: ['notification'],
    emits: ['read', 'close'],
    template: `<button class="item-stub" @click="$emit('read', notification.id)">{{ notification.title }}</button>`,
};

function mountComponent(options = {}) {
    return mount(NotificationDropdown, {
        global: { plugins: [i18n], stubs: { NotificationItem: itemStub } },
        ...options,
    });
}

describe('NotificationDropdown', () => {
    let store;

    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        store = useNotificationStore();
        notificationService.markAsRead.mockResolvedValue({});
        notificationService.markAllAsRead.mockResolvedValue({});
    });

    it('shows the loading state and no items while notificationStore.loading is true', () => {
        store.loading = true;
        store.notifications = [{ id: 1, title: 'x', message: 'y', is_read: false }];

        const wrapper = mountComponent();

        expect(wrapper.text()).toContain('جارِ التحميل...');
        expect(wrapper.findAll('.item-stub')).toHaveLength(0);
    });

    it('shows the empty state when not loading and there are no notifications', () => {
        const wrapper = mountComponent();

        expect(wrapper.text()).toContain('لا توجد إشعارات');
    });

    it('renders one item per notification, and the "mark all read" button only when unreadCount > 0', () => {
        store.notifications = [
            { id: 1, title: 'فاتورة جديدة', message: 'm1', is_read: false },
            { id: 2, title: 'دفعة مستلمة', message: 'm2', is_read: true },
        ];
        store.unreadCount = 1;

        const wrapper = mountComponent();

        const items = wrapper.findAll('.item-stub');
        expect(items).toHaveLength(2);
        expect(items[0].text()).toBe('فاتورة جديدة');
        expect(wrapper.text()).toContain('تعليم الكل كمقروء');
    });

    it('hides the "mark all read" button when unreadCount is 0', () => {
        store.notifications = [{ id: 1, title: 'x', message: 'y', is_read: true }];
        store.unreadCount = 0;

        const wrapper = mountComponent();

        expect(wrapper.text()).not.toContain('تعليم الكل كمقروء');
    });

    it('marks everything read via the store/service and hides the button afterwards', async () => {
        store.notifications = [
            { id: 1, title: 'x', message: 'm', is_read: false },
            { id: 2, title: 'y', message: 'm', is_read: false },
        ];
        store.unreadCount = 2;

        const wrapper = mountComponent();
        const markAllButton = wrapper.findAll('button').find((b) => b.text() === 'تعليم الكل كمقروء');
        await markAllButton.trigger('click');
        await flushPromises();

        expect(notificationService.markAllAsRead).toHaveBeenCalledTimes(1);
        expect(store.notifications.every((n) => n.is_read)).toBe(true);
        expect(store.unreadCount).toBe(0);
        expect(wrapper.text()).not.toContain('تعليم الكل كمقروء');
    });

    it('marks a single notification read via the store/service when the item emits "read"', async () => {
        store.notifications = [{ id: 7, title: 'x', message: 'm', is_read: false }];
        store.unreadCount = 1;

        const wrapper = mountComponent();
        await wrapper.find('.item-stub').trigger('click'); // stub emits read(7) on click
        await flushPromises();

        expect(notificationService.markAsRead).toHaveBeenCalledWith(7);
        expect(store.notifications[0].is_read).toBe(true);
        expect(store.unreadCount).toBe(0);
    });

    it('emits "close" when a child item emits "close"', async () => {
        store.notifications = [{ id: 1, title: 'x', message: 'm', is_read: false }];
        const wrapper = mountComponent();

        await wrapper.findComponent(itemStub).vm.$emit('close');

        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('emits "close" on an outside click but not on a click inside the dropdown', async () => {
        const wrapper = mountComponent({ attachTo: document.body });

        // Inside: the dropdown root div contains itself, so this must NOT close it.
        await wrapper.trigger('click');
        expect(wrapper.emitted('close')).toBeUndefined();

        // Outside: <body> is an ancestor of the dropdown div, not a descendant, so
        // dropdownRef.value.contains(body) is false -> treated as an outside click.
        document.body.click();
        await flushPromises();
        expect(wrapper.emitted('close')).toHaveLength(1);

        wrapper.unmount();
    });
});
