import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { defineComponent } from 'vue';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';

vi.mock('@/utils/playNotificationSound', () => ({
    playNotificationSound: vi.fn(),
}));

const { useRealtimeNotifications, forceLeaveRealtimeChannel } = await import('./useRealtimeNotifications');
const { useAuthStore } = await import('@/stores/auth');
const { useNotificationStore } = await import('@/stores/notification');
const { useToastStore } = await import('@/stores/toast');
const { playNotificationSound } = await import('@/utils/playNotificationSound');

function mountRealtimeNotifications() {
    let api;
    const wrapper = mount(defineComponent({
        setup() {
            api = useRealtimeNotifications();
            return () => null;
        },
    }));
    return { wrapper, api };
}

describe('useRealtimeNotifications', () => {
    let echoPrivate;
    let echoLeave;
    let channels;

    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        // module-level channel-tracking state (activeChannel/subscribedUserId) is a
        // singleton shared across every useRealtimeNotifications() call in the process
        // (mirrors production: one websocket connection regardless of component count).
        // Reset it between tests via the same exported hook the real auth store uses on logout.
        forceLeaveRealtimeChannel();

        channels = [];
        echoPrivate = vi.fn((channelName) => {
            const channel = { name: channelName, notification: vi.fn() };
            channels.push(channel);
            return channel;
        });
        echoLeave = vi.fn();
        window.Echo = { private: echoPrivate, leave: echoLeave };
    });

    afterEach(() => {
        delete window.Echo;
    });

    it('does nothing when there is no authenticated user, even with Echo available', () => {
        const { api, wrapper } = mountRealtimeNotifications();
        api.subscribe();
        wrapper.unmount();

        expect(echoPrivate).not.toHaveBeenCalled();
    });

    it('does nothing when window.Echo is unavailable, even for an authenticated user', () => {
        delete window.Echo;
        useAuthStore().user = { id: 1 };

        const { api, wrapper } = mountRealtimeNotifications();
        api.subscribe();
        wrapper.unmount();

        expect(echoPrivate).not.toHaveBeenCalled();
    });

    it('auto-subscribes on mount to the user-scoped private channel when already authenticated', () => {
        useAuthStore().user = { id: 5 };

        const { wrapper } = mountRealtimeNotifications();

        expect(echoPrivate).toHaveBeenCalledTimes(1);
        expect(echoPrivate).toHaveBeenCalledWith('App.Models.User.5');
        expect(channels[0].notification).toHaveBeenCalledTimes(1);

        wrapper.unmount();
    });

    it('does not auto-subscribe on mount when not authenticated', () => {
        const { wrapper } = mountRealtimeNotifications();

        expect(echoPrivate).not.toHaveBeenCalled();
        wrapper.unmount();
    });

    it('calling subscribe() again for the same user is idempotent (no duplicate channel subscription)', () => {
        useAuthStore().user = { id: 5 };
        const { api, wrapper } = mountRealtimeNotifications(); // auto-subscribes once

        api.subscribe();
        api.subscribe();

        expect(echoPrivate).toHaveBeenCalledTimes(1);
        wrapper.unmount();
    });

    it('an incoming notification event adds it to the notification store, shows a toast, and plays a sound', () => {
        useAuthStore().user = { id: 5 };
        const { wrapper } = mountRealtimeNotifications();
        const notificationStore = useNotificationStore();
        const toastStore = useToastStore();

        const handler = channels[0].notification.mock.calls[0][0];
        handler({
            id: 100,
            type: 'fault_reported',
            title: 'عطل جديد',
            message: 'تم الإبلاغ عن عطل',
            link_type: 'fault',
            link_id: 55,
        });

        expect(notificationStore.notifications).toHaveLength(1);
        expect(notificationStore.notifications[0]).toMatchObject({
            id: 100,
            type: 'fault_reported',
            title: 'عطل جديد',
            message: 'تم الإبلاغ عن عطل',
            link_type: 'fault',
            link_id: 55,
            is_read: false,
        });
        expect(notificationStore.unreadCount).toBe(1);
        expect(toastStore.toasts).toHaveLength(1);
        expect(toastStore.toasts[0]).toMatchObject({ title: 'عطل جديد', message: 'تم الإبلاغ عن عطل', type: 'info' });
        expect(playNotificationSound).toHaveBeenCalledTimes(1);

        wrapper.unmount();
    });

    it('defaults type/link_type/link_id when the incoming event omits them', () => {
        useAuthStore().user = { id: 5 };
        const { wrapper } = mountRealtimeNotifications();
        const notificationStore = useNotificationStore();

        const handler = channels[0].notification.mock.calls[0][0];
        handler({ id: 101, title: 'عنوان', message: 'رسالة' });

        expect(notificationStore.notifications[0]).toMatchObject({
            type: 'notification',
            link_type: null,
            link_id: null,
        });

        wrapper.unmount();
    });

    it('switching to a different authenticated user leaves the old channel and subscribes to the new one', () => {
        useAuthStore().user = { id: 5 };
        const { api, wrapper } = mountRealtimeNotifications();

        useAuthStore().user = { id: 6 };
        api.subscribe();

        expect(echoLeave).toHaveBeenCalledWith('App.Models.User.5');
        expect(echoPrivate).toHaveBeenCalledTimes(2);
        expect(echoPrivate).toHaveBeenLastCalledWith('App.Models.User.6');

        wrapper.unmount();
    });

    it('unsubscribe() only leaves the channel once every mounted consumer has unmounted (shared subscriber count)', () => {
        useAuthStore().user = { id: 5 };

        const first = mountRealtimeNotifications(); // subscriberCount 1 -> subscribes
        const second = mountRealtimeNotifications(); // subscriberCount 2 -> already subscribed, no new channel

        expect(echoPrivate).toHaveBeenCalledTimes(1);

        first.wrapper.unmount(); // subscriberCount back to 1 -> must NOT leave yet
        expect(echoLeave).not.toHaveBeenCalled();

        second.wrapper.unmount(); // subscriberCount 0 -> leaves for real
        expect(echoLeave).toHaveBeenCalledWith('App.Models.User.5');
    });

    it('forceLeaveRealtimeChannel() leaves the channel immediately regardless of the subscriber count (used by the auth store on logout)', () => {
        useAuthStore().user = { id: 5 };
        const { wrapper } = mountRealtimeNotifications();

        forceLeaveRealtimeChannel();

        expect(echoLeave).toHaveBeenCalledWith('App.Models.User.5');

        wrapper.unmount(); // subscriberCount drops to 0, but the channel is already gone — no second leave
        expect(echoLeave).toHaveBeenCalledTimes(1);
    });
});
