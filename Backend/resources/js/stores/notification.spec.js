import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

vi.mock('@/services/notificationService', () => ({
    default: {
        getAll: vi.fn(),
        unreadCount: vi.fn(),
        markAsRead: vi.fn(),
        markAllAsRead: vi.fn(),
        delete: vi.fn(),
    },
}));

const { useNotificationStore } = await import('./notification');
const notificationService = (await import('@/services/notificationService')).default;

describe('useNotificationStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('starts empty with the default pagination shape', () => {
        const store = useNotificationStore();

        expect(store.notifications).toEqual([]);
        expect(store.unreadCount).toBe(0);
        expect(store.loading).toBe(false);
        expect(store.pagination).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
    });

    describe('unreadNotifications getter', () => {
        it('returns only the notifications with is_read false', () => {
            const store = useNotificationStore();
            store.notifications = [
                { id: 1, is_read: true },
                { id: 2, is_read: false },
                { id: 3, is_read: false },
            ];

            expect(store.unreadNotifications.map((n) => n.id)).toEqual([2, 3]);
        });
    });

    describe('fetchNotifications()', () => {
        it('loads a page, derives pagination from meta, and also refreshes unreadCount', async () => {
            notificationService.getAll.mockResolvedValue({
                data: {
                    data: [{ id: 1, is_read: false }],
                    meta: { current_page: 2, last_page: 5, total: 42, per_page: 10 },
                },
            });
            notificationService.unreadCount.mockResolvedValue({ data: { unread_count: 6 } });

            const store = useNotificationStore();
            await store.fetchNotifications(2);

            expect(notificationService.getAll).toHaveBeenCalledWith({ page: 2 });
            expect(store.notifications).toEqual([{ id: 1, is_read: false }]);
            expect(store.pagination).toEqual({ current_page: 2, last_page: 5, total: 42, per_page: 10 });
            expect(notificationService.unreadCount).toHaveBeenCalled();
            expect(store.unreadCount).toBe(6);
            expect(store.loading).toBe(false);
        });

        it('defaults to page 1 when called with no argument', async () => {
            notificationService.getAll.mockResolvedValue({ data: { data: [], meta: {} } });
            notificationService.unreadCount.mockResolvedValue({ data: { unread_count: 0 } });

            const store = useNotificationStore();
            await store.fetchNotifications();

            expect(notificationService.getAll).toHaveBeenCalledWith({ page: 1 });
        });

        it('sets loading true during the request and false again once it settles, even on failure', async () => {
            let capturedDuringLoad;
            notificationService.getAll.mockImplementation(() => {
                capturedDuringLoad = store.loading;
                return Promise.reject(new Error('network down'));
            });

            const store = useNotificationStore();
            await expect(store.fetchNotifications()).rejects.toThrow('network down');

            expect(capturedDuringLoad).toBe(true);
            expect(store.loading).toBe(false);
        });

        it('propagates a fetch failure to the caller (no local catch in this action)', async () => {
            const originalError = new Error('server exploded');
            notificationService.getAll.mockRejectedValue(originalError);

            const store = useNotificationStore();
            await expect(store.fetchNotifications()).rejects.toBe(originalError);
        });
    });

    describe('fetchUnreadCount()', () => {
        it('updates unreadCount from the endpoint', async () => {
            notificationService.unreadCount.mockResolvedValue({ data: { unread_count: 9 } });

            const store = useNotificationStore();
            await store.fetchUnreadCount();

            expect(store.unreadCount).toBe(9);
        });

        it('silently keeps the previous unreadCount when the request fails (no throw)', async () => {
            notificationService.unreadCount.mockRejectedValue(new Error('network down'));

            const store = useNotificationStore();
            store.unreadCount = 5;

            await expect(store.fetchUnreadCount()).resolves.toBeUndefined();
            expect(store.unreadCount).toBe(5);
        });
    });

    describe('addNotification()', () => {
        it('prepends a new notification and increments unreadCount', () => {
            const store = useNotificationStore();
            store.notifications = [{ id: 1, is_read: false }];
            store.unreadCount = 1;

            store.addNotification({ id: 2, is_read: false });

            expect(store.notifications.map((n) => n.id)).toEqual([2, 1]);
            expect(store.unreadCount).toBe(2);
        });

        it('ignores a duplicate notification id (guards against double-delivery)', () => {
            const store = useNotificationStore();
            store.notifications = [{ id: 1, is_read: false }];
            store.unreadCount = 1;

            store.addNotification({ id: 1, is_read: false });

            expect(store.notifications).toHaveLength(1);
            expect(store.unreadCount).toBe(1);
        });
    });

    describe('markAsRead()', () => {
        it('marks the matching notification read and decrements unreadCount', async () => {
            notificationService.markAsRead.mockResolvedValue({});
            const store = useNotificationStore();
            store.notifications = [{ id: 1, is_read: false }];
            store.unreadCount = 1;

            await store.markAsRead(1);

            expect(notificationService.markAsRead).toHaveBeenCalledWith(1);
            expect(store.notifications[0].is_read).toBe(true);
            expect(store.unreadCount).toBe(0);
        });

        it('does not decrement unreadCount again if the notification was already read', async () => {
            notificationService.markAsRead.mockResolvedValue({});
            const store = useNotificationStore();
            store.notifications = [{ id: 1, is_read: true }];
            store.unreadCount = 0;

            await store.markAsRead(1);

            expect(store.unreadCount).toBe(0);
        });

        it('does nothing to state when the id is not found locally, but still calls the API', async () => {
            notificationService.markAsRead.mockResolvedValue({});
            const store = useNotificationStore();
            store.notifications = [];
            store.unreadCount = 3;

            await store.markAsRead(999);

            expect(notificationService.markAsRead).toHaveBeenCalledWith(999);
            expect(store.unreadCount).toBe(3);
        });
    });

    describe('markAllAsRead()', () => {
        it('marks every notification read and zeroes unreadCount', async () => {
            notificationService.markAllAsRead.mockResolvedValue({});
            const store = useNotificationStore();
            store.notifications = [
                { id: 1, is_read: false },
                { id: 2, is_read: false },
            ];
            store.unreadCount = 2;

            await store.markAllAsRead();

            expect(notificationService.markAllAsRead).toHaveBeenCalled();
            expect(store.notifications.every((n) => n.is_read)).toBe(true);
            expect(store.unreadCount).toBe(0);
        });
    });

    describe('deleteNotification()', () => {
        it('removes the matching notification after the API call succeeds', async () => {
            notificationService.delete.mockResolvedValue({});
            const store = useNotificationStore();
            store.notifications = [{ id: 1 }, { id: 2 }];

            await store.deleteNotification(1);

            expect(notificationService.delete).toHaveBeenCalledWith(1);
            expect(store.notifications).toEqual([{ id: 2 }]);
        });
    });
});
