import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

vi.mock('@/utils/offlineQueue', () => ({
    queueCount: vi.fn(),
    queueCountForUser: vi.fn(),
    clearQueueForUser: vi.fn(),
}));
vi.mock('@/utils/syncQueue', () => ({
    flushQueue: vi.fn(),
}));
vi.mock('./auth', () => ({
    useAuthStore: vi.fn(),
}));

const { useConnectivityStore } = await import('./connectivity');
const { queueCount, queueCountForUser, clearQueueForUser } = await import('@/utils/offlineQueue');
const { flushQueue } = await import('@/utils/syncQueue');
const { useAuthStore } = await import('./auth');

function setOnline(value) {
    Object.defineProperty(window.navigator, 'onLine', {
        value,
        writable: true,
        configurable: true,
    });
}

describe('useConnectivityStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        useAuthStore.mockReturnValue({ user: null });
        queueCount.mockResolvedValue(0);
        queueCountForUser.mockResolvedValue(0);
        setOnline(true);
    });

    afterEach(() => {
        setOnline(true);
    });

    it('initializes isOnline from navigator.onLine, with pendingCount/isSyncing/needsReauth at rest', () => {
        setOnline(false);
        const store = useConnectivityStore();

        expect(store.isOnline).toBe(false);
        expect(store.pendingCount).toBe(0);
        expect(store.isSyncing).toBe(false);
        expect(store.needsReauth).toBe(false);
    });

    describe('refreshPendingCount()', () => {
        it('uses queueCount() (global) when no user is logged in', async () => {
            useAuthStore.mockReturnValue({ user: null });
            queueCount.mockResolvedValue(3);

            const store = useConnectivityStore();
            await store.refreshPendingCount();

            expect(queueCount).toHaveBeenCalled();
            expect(queueCountForUser).not.toHaveBeenCalled();
            expect(store.pendingCount).toBe(3);
        });

        it('uses queueCountForUser(id) (scoped) when a user is logged in', async () => {
            useAuthStore.mockReturnValue({ user: { id: 42 } });
            queueCountForUser.mockResolvedValue(5);

            const store = useConnectivityStore();
            await store.refreshPendingCount();

            expect(queueCountForUser).toHaveBeenCalledWith(42);
            expect(queueCount).not.toHaveBeenCalled();
            expect(store.pendingCount).toBe(5);
        });
    });

    describe('sync()', () => {
        it('does nothing when there is no logged-in user', async () => {
            useAuthStore.mockReturnValue({ user: null });
            const store = useConnectivityStore();

            await store.sync();

            expect(flushQueue).not.toHaveBeenCalled();
            expect(store.isSyncing).toBe(false);
        });

        it('does nothing while offline', async () => {
            setOnline(false);
            useAuthStore.mockReturnValue({ user: { id: 1 } });
            const store = useConnectivityStore();
            expect(store.isOnline).toBe(false);

            await store.sync();

            expect(flushQueue).not.toHaveBeenCalled();
        });

        it('does nothing when a sync is already in progress (re-entrancy guard)', async () => {
            useAuthStore.mockReturnValue({ user: { id: 1 } });
            const store = useConnectivityStore();
            store.isSyncing = true;

            await store.sync();

            expect(flushQueue).not.toHaveBeenCalled();
        });

        it('flushes the queue for the current user, applies needsReauth, and refreshes pendingCount when done', async () => {
            useAuthStore.mockReturnValue({ user: { id: 9 } });
            flushQueue.mockResolvedValue({ needsReauth: true });
            queueCountForUser.mockResolvedValue(2);

            const store = useConnectivityStore();
            await store.sync();

            expect(flushQueue).toHaveBeenCalledWith(9);
            expect(store.needsReauth).toBe(true);
            expect(store.pendingCount).toBe(2);
            expect(store.isSyncing).toBe(false);
        });

        it('resets isSyncing even if flushQueue throws', async () => {
            useAuthStore.mockReturnValue({ user: { id: 9 } });
            flushQueue.mockRejectedValue(new Error('boom'));

            const store = useConnectivityStore();
            await expect(store.sync()).rejects.toThrow('boom');

            expect(store.isSyncing).toBe(false);
        });
    });

    describe('retryAfterReauth()', () => {
        it('clears needsReauth and re-runs sync()', async () => {
            useAuthStore.mockReturnValue({ user: { id: 3 } });
            flushQueue.mockResolvedValue({ needsReauth: false });

            const store = useConnectivityStore();
            store.needsReauth = true;

            await store.retryAfterReauth();

            expect(store.needsReauth).toBe(false);
            expect(flushQueue).toHaveBeenCalledWith(3);
        });
    });

    describe('clearOwnQueueOnLogout()', () => {
        it('does nothing when there is no logged-in user', async () => {
            useAuthStore.mockReturnValue({ user: null });
            const store = useConnectivityStore();
            store.pendingCount = 7;

            await store.clearOwnQueueOnLogout();

            expect(clearQueueForUser).not.toHaveBeenCalled();
            expect(store.pendingCount).toBe(7);
        });

        it('clears the queue for the current user and zeroes pendingCount', async () => {
            useAuthStore.mockReturnValue({ user: { id: 11 } });
            const store = useConnectivityStore();
            store.pendingCount = 4;

            await store.clearOwnQueueOnLogout();

            expect(clearQueueForUser).toHaveBeenCalledWith(11);
            expect(store.pendingCount).toBe(0);
        });
    });

    describe('init()', () => {
        it('refreshes pendingCount and syncs immediately when online', async () => {
            useAuthStore.mockReturnValue({ user: { id: 1 } });
            flushQueue.mockResolvedValue({ needsReauth: false });
            queueCountForUser.mockResolvedValue(0);

            const store = useConnectivityStore();
            store.init();
            await vi.waitFor(() => expect(flushQueue).toHaveBeenCalled());

            expect(queueCountForUser).toHaveBeenCalled();
        });

        it('does not sync immediately when offline', () => {
            setOnline(false);
            useAuthStore.mockReturnValue({ user: { id: 1 } });

            const store = useConnectivityStore();
            store.init();

            expect(flushQueue).not.toHaveBeenCalled();
        });

        it('going online fires the "online" listener: sets isOnline and triggers sync()', async () => {
            useAuthStore.mockReturnValue({ user: { id: 1 } });
            flushQueue.mockResolvedValue({ needsReauth: false });
            setOnline(false);

            const store = useConnectivityStore();
            store.init();
            expect(flushQueue).not.toHaveBeenCalled();

            setOnline(true);
            window.dispatchEvent(new Event('online'));

            expect(store.isOnline).toBe(true);
            await vi.waitFor(() => expect(flushQueue).toHaveBeenCalled());
        });

        it('going offline fires the "offline" listener: sets isOnline false without syncing', () => {
            useAuthStore.mockReturnValue({ user: { id: 1 } });
            const store = useConnectivityStore();
            store.init();
            vi.clearAllMocks();
            useAuthStore.mockReturnValue({ user: { id: 1 } });

            window.dispatchEvent(new Event('offline'));

            expect(store.isOnline).toBe(false);
            expect(flushQueue).not.toHaveBeenCalled();
        });
    });
});
