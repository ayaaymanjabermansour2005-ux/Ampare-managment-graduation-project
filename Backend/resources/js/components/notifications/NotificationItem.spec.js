import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import 'dayjs/locale/ar';
import { useAuthStore } from '@/stores/auth';
import NotificationItem from './NotificationItem.vue';

dayjs.extend(relativeTime);

const pushMock = vi.fn();
vi.mock('vue-router', () => ({
    useRouter: () => ({ push: pushMock }),
}));

const i18n = createI18n({ legacy: false, locale: 'ar', messages: { ar: {} } });

function baseNotification(overrides = {}) {
    return {
        id: 1,
        type: 'InvoiceDueSoonNotification',
        title: 'فاتورة مستحقة قريبًا',
        message: 'فاتورتك مستحقة خلال 3 أيام.',
        created_at: '2024-01-01T10:00:00.000Z',
        is_read: false,
        link_type: 'invoice',
        link_id: 55,
        ...overrides,
    };
}

function mountItem(notification, roles = ['subscriber']) {
    useAuthStore().roles = roles;
    return mount(NotificationItem, {
        props: { notification },
        global: { plugins: [i18n] },
    });
}

describe('NotificationItem', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('renders the title and message from the prop', () => {
        const wrapper = mountItem(baseNotification());

        expect(wrapper.text()).toContain('فاتورة مستحقة قريبًا');
        expect(wrapper.text()).toContain('فاتورتك مستحقة خلال 3 أيام.');
    });

    it('renders the relative time exactly as dayjs(locale=ar).fromNow() would produce it', () => {
        const createdAt = '2024-01-01T09:30:00.000Z';
        const wrapper = mountItem(baseNotification({ created_at: createdAt }));

        const expected = dayjs(createdAt).locale('ar').fromNow();
        expect(wrapper.text()).toContain(expected);
    });

    it('renders a different relative time for a different created_at (prop-driven variation)', () => {
        const older = mountItem(baseNotification({ created_at: '2020-01-01T00:00:00.000Z' }));
        const newer = mountItem(baseNotification({ created_at: new Date().toISOString() }));

        const olderText = older.find('span.block').text();
        const newerText = newer.find('span.block').text();
        expect(olderText).not.toBe(newerText);
    });

    it('shows the unread dot and "unread" class when is_read is false', () => {
        const wrapper = mountItem(baseNotification({ is_read: false }));

        expect(wrapper.find('span.rounded-full').exists()).toBe(true);
        expect(wrapper.find('button').classes()).toContain('unread');
    });

    it('hides the unread dot and drops the "unread" class when is_read is true', () => {
        const wrapper = mountItem(baseNotification({ is_read: true }));

        expect(wrapper.find('span.rounded-full').exists()).toBe(false);
        expect(wrapper.find('button').classes()).not.toContain('unread');
    });

    it('uses a different icon background color for a different notification type (prop-driven variation)', () => {
        const fault = mountItem(baseNotification({ type: 'FaultReportedNotification' }));
        const unknown = mountItem(baseNotification({ type: 'SomeTotallyUnknownType' }));

        const faultStyle = fault.find('[style]').attributes('style');
        const unknownStyle = unknown.find('[style]').attributes('style');
        expect(faultStyle).not.toBe(unknownStyle);
    });

    it('emits "read" then "close", and navigates to the role-specific route, when an unread invoice notification is clicked', async () => {
        const wrapper = mountItem(baseNotification({ is_read: false, link_type: 'invoice', link_id: 55 }), ['subscriber']);

        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('read')).toEqual([[1]]);
        expect(pushMock).toHaveBeenCalledWith({ name: 'subscriber.invoices', query: { highlight: 55 } });
        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('does not emit "read" for an already-read notification, but still navigates and closes', async () => {
        const wrapper = mountItem(baseNotification({ is_read: true, link_type: 'invoice', link_id: 55 }), ['subscriber']);

        await wrapper.find('button').trigger('click');

        expect(wrapper.emitted('read')).toBeUndefined();
        expect(pushMock).toHaveBeenCalled();
        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('routes a "payment" notification for a generator_owner to owner.invoices with the payments tab', async () => {
        const wrapper = mountItem(baseNotification({ link_type: 'payment', link_id: 9 }), ['generator_owner']);

        await wrapper.find('button').trigger('click');

        expect(pushMock).toHaveBeenCalledWith({ name: 'owner.invoices', query: { tab: 'payments', highlight: 9 } });
    });

    it('routes a "complaint" notification for a subscriber to subscriber.support with the complaints tab', async () => {
        const wrapper = mountItem(baseNotification({ link_type: 'complaint', link_id: 3 }), ['subscriber']);

        await wrapper.find('button').trigger('click');

        expect(pushMock).toHaveBeenCalledWith({ name: 'subscriber.support', query: { tab: 'complaints', highlight: 3 } });
    });

    it('does not navigate when link_type has no matching route for the current role, but still closes', async () => {
        // LINK_ROUTE_MAP.complaint only has entries for 'subscriber' and 'admin'.
        const wrapper = mountItem(baseNotification({ link_type: 'complaint', link_id: 3 }), ['technician']);

        await wrapper.find('button').trigger('click');

        expect(pushMock).not.toHaveBeenCalled();
        expect(wrapper.emitted('close')).toHaveLength(1);
    });

    it('does not navigate for a "conversation" link_type (explicitly unmapped), but still closes', async () => {
        const wrapper = mountItem(baseNotification({ link_type: 'conversation', link_id: 3 }), ['subscriber']);

        await wrapper.find('button').trigger('click');

        expect(pushMock).not.toHaveBeenCalled();
        expect(wrapper.emitted('close')).toHaveLength(1);
    });
});
