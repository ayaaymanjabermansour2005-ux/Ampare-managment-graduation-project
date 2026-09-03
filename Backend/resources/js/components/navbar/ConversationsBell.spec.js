import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import { useAuthStore } from '@/stores/auth';
import ConversationsBell from './ConversationsBell.vue';

vi.mock('@/services/conversationService', () => ({
    default: { list: vi.fn(), unreadCount: vi.fn() },
}));
import conversationService from '@/services/conversationService';

const pushMock = vi.fn();
vi.mock('vue-router', () => ({
    useRouter: () => ({ push: pushMock }),
    RouterLink: { template: '<a><slot /></a>' },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: {
                conversations: 'المحادثات',
                view_all: 'عرض الكل',
                loading: 'جارِ التحميل...',
                no_conversations: 'لا توجد محادثات',
            },
            subscribers_page: {
                time_now: 'الآن',
                time_mins_ago: 'قبل {mins} دقيقة',
                time_hours_ago: 'قبل {hours} ساعة',
                time_days_ago: 'قبل {days} يوم',
            },
        },
    },
});

function mountComponent() {
    return mount(ConversationsBell, { global: { plugins: [i18n] } });
}

function pad(n) {
    return String(n).padStart(2, '0');
}
function fmt(d) {
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

describe('ConversationsBell', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        conversationService.list.mockResolvedValue({ data: { data: { data: [] } } });
        conversationService.unreadCount.mockResolvedValue({ data: { data: { unread_count: 0 } } });
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('renders nothing and fetches nothing when the user lacks conversations.view', () => {
        useAuthStore().permissions = [];
        useAuthStore().roles = ['subscriber'];

        const wrapper = mountComponent();

        expect(wrapper.find('button').exists()).toBe(false);
        expect(conversationService.list).not.toHaveBeenCalled();
        expect(conversationService.unreadCount).not.toHaveBeenCalled();
    });

    it('fetches the conversation list and unread count on mount when permitted, and shows the unread dot', async () => {
        useAuthStore().permissions = ['conversations.view'];
        useAuthStore().roles = ['subscriber'];
        conversationService.unreadCount.mockResolvedValue({ data: { data: { unread_count: 3 } } });

        const wrapper = mountComponent();
        await flushPromises();

        expect(conversationService.list).toHaveBeenCalledWith({ per_page: 6 });
        expect(wrapper.find('.notif-dot').exists()).toBe(true);
    });

    it('shows the empty state when there are no conversations', async () => {
        useAuthStore().permissions = ['conversations.view'];
        useAuthStore().roles = ['subscriber'];

        const wrapper = mountComponent();
        await wrapper.find('button.icon-btn').trigger('click');
        await flushPromises();

        expect(wrapper.text()).toContain('لا توجد محادثات');
    });

    it('renders each conversation with a relative "time ago" derived from its own latest_message', async () => {
        vi.useFakeTimers();
        const now = new Date('2024-01-10T12:00:00');
        vi.setSystemTime(now);

        useAuthStore().permissions = ['conversations.view'];
        useAuthStore().roles = ['subscriber'];
        conversationService.list.mockResolvedValue({
            data: {
                data: {
                    data: [
                        { id: 1, other_participant: { name: 'أحمد' }, latest_message: { text: 'مرحبا', created_at: fmt(new Date(now - 30 * 1000)) }, unread_count: 1 },
                        { id: 2, other_participant: { name: 'سارة' }, latest_message: { text: 'شكرا', created_at: fmt(new Date(now - 5 * 60 * 1000)) }, unread_count: 0 },
                        { id: 3, other_participant: { name: 'خالد' }, latest_message: { text: 'تمام', created_at: fmt(new Date(now - 3 * 60 * 60 * 1000)) }, unread_count: 0 },
                        { id: 4, other_participant: { name: 'ليلى' }, latest_message: { text: 'اوك', created_at: fmt(new Date(now - 2 * 24 * 60 * 60 * 1000)) }, unread_count: 0 },
                    ],
                },
            },
        });

        const wrapper = mountComponent();
        await wrapper.find('button.icon-btn').trigger('click');
        await flushPromises();

        expect(wrapper.findAll('button.dropdown-row')).toHaveLength(4);
        expect(wrapper.text()).toContain('أحمد');
        expect(wrapper.text()).toContain('مرحبا');
        expect(wrapper.text()).toContain('الآن');
        expect(wrapper.text()).toContain('قبل 5 دقيقة');
        expect(wrapper.text()).toContain('قبل 3 ساعة');
        expect(wrapper.text()).toContain('قبل 2 يوم');
        // The unread conversation's row carries the "unread" style hook.
        expect(wrapper.findAll('button.dropdown-row')[0].classes()).toContain('unread');
        expect(wrapper.findAll('button.dropdown-row')[1].classes()).not.toContain('unread');
    });

    it('navigates to the role-specific messages route and closes the dropdown when a conversation is clicked', async () => {
        useAuthStore().permissions = ['conversations.view'];
        useAuthStore().roles = ['subscriber'];
        conversationService.list.mockResolvedValue({
            data: { data: { data: [{ id: 42, other_participant: { name: 'أحمد' }, latest_message: null, unread_count: 0 }] } },
        });

        const wrapper = mountComponent();
        await wrapper.find('button.icon-btn').trigger('click');
        await flushPromises();

        await wrapper.find('button.dropdown-row').trigger('click');

        expect(pushMock).toHaveBeenCalledWith({ name: 'subscriber.messages', query: { open: 42 } });
        expect(wrapper.find('.glass-card').exists()).toBe(false);
    });

    it('toggles the dropdown open and closed on repeated bell clicks', async () => {
        useAuthStore().permissions = ['conversations.view'];
        useAuthStore().roles = ['subscriber'];

        const wrapper = mountComponent();
        const bell = wrapper.find('button.icon-btn');

        await bell.trigger('click');
        expect(wrapper.find('.glass-card').exists()).toBe(true);

        await bell.trigger('click');
        expect(wrapper.find('.glass-card').exists()).toBe(false);
    });
});
