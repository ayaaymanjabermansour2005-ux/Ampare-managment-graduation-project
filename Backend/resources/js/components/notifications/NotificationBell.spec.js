import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import { useNotificationStore } from '@/stores/notification';
import NotificationBell from './NotificationBell.vue';

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
    messages: { ar: { common: { notifications: 'الإشعارات' } } },
});

// NotificationDropdown has its own store/service wiring and its own dedicated spec —
// stub it here so this file stays focused on NotificationBell's own toggle/shake logic.
const dropdownStub = {
    name: 'NotificationDropdown',
    emits: ['close'],
    template: '<div class="dropdown-stub" @click="$emit(\'close\')"></div>',
};

function mountComponent() {
    return mount(NotificationBell, {
        global: { plugins: [i18n], stubs: { NotificationDropdown: dropdownStub } },
    });
}

describe('NotificationBell', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        notificationService.getAll.mockResolvedValue({ data: { data: [], meta: { current_page: 1, last_page: 1, total: 0, per_page: 15 } } });
        notificationService.unreadCount.mockResolvedValue({ data: { unread_count: 0 } });
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('fetches notifications on mount', async () => {
        mountComponent();
        await flushPromises();

        expect(notificationService.getAll).toHaveBeenCalled();
    });

    it('shows the unread dot once the store reports unread notifications', async () => {
        notificationService.unreadCount.mockResolvedValue({ data: { unread_count: 2 } });

        const wrapper = mountComponent();
        await flushPromises();

        expect(wrapper.find('.notif-dot').exists()).toBe(true);
    });

    it('has no unread dot when there are no unread notifications', async () => {
        const wrapper = mountComponent();
        await flushPromises();

        expect(wrapper.find('.notif-dot').exists()).toBe(false);
    });

    it('toggles the dropdown open and closed on repeated bell clicks', async () => {
        const wrapper = mountComponent();
        await flushPromises();
        const bell = wrapper.find('button');

        await bell.trigger('click');
        expect(wrapper.find('.dropdown-stub').exists()).toBe(true);

        await bell.trigger('click');
        expect(wrapper.find('.dropdown-stub').exists()).toBe(false);
    });

    it('closes the dropdown when it emits "close"', async () => {
        const wrapper = mountComponent();
        await flushPromises();

        await wrapper.find('button').trigger('click');
        expect(wrapper.find('.dropdown-stub').exists()).toBe(true);

        await wrapper.find('.dropdown-stub').trigger('click'); // stub emits close on click
        expect(wrapper.find('.dropdown-stub').exists()).toBe(false);
    });

    it('shakes the bell for 2s when unreadCount rises, then stops', async () => {
        vi.useFakeTimers();
        const wrapper = mountComponent();
        await flushPromises();

        const store = useNotificationStore();
        expect(wrapper.find('svg').classes()).not.toContain('fa-shake');

        store.unreadCount = 1; // 0 -> 1, watcher's newVal > oldVal fires
        await flushPromises();
        expect(wrapper.find('svg').classes()).toContain('fa-shake');

        await vi.advanceTimersByTimeAsync(2000);
        expect(wrapper.find('svg').classes()).not.toContain('fa-shake');
    });

    it('does not shake when unreadCount drops', async () => {
        vi.useFakeTimers();
        const wrapper = mountComponent();
        await flushPromises();
        const store = useNotificationStore();

        store.unreadCount = 5; // a rise first, so the shake timer runs its course
        await flushPromises();
        await vi.advanceTimersByTimeAsync(2000);
        expect(wrapper.find('svg').classes()).not.toContain('fa-shake');

        store.unreadCount = 2; // decreasing -> newVal > oldVal is false -> no shake
        await flushPromises();

        expect(wrapper.find('svg').classes()).not.toContain('fa-shake');
    });
});
