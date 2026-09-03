import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

vi.mock('@/services/authService', () => ({
    default: {
        register: vi.fn(),
        login: vi.fn(),
        logout: vi.fn(),
        me: vi.fn(),
    },
}));
vi.mock('./connectivity', () => ({
    useConnectivityStore: vi.fn(),
}));
vi.mock('./notification', () => ({
    useNotificationStore: vi.fn(),
}));
vi.mock('@/composables/useRealtimeNotifications', () => ({
    forceLeaveRealtimeChannel: vi.fn(),
}));

const { useAuthStore } = await import('./auth');
const authService = (await import('@/services/authService')).default;
const { useConnectivityStore } = await import('./connectivity');
const { useNotificationStore } = await import('./notification');
const { forceLeaveRealtimeChannel } = await import('@/composables/useRealtimeNotifications');

describe('useAuthStore', () => {
    let connectivityStoreMock;
    let notificationStoreMock;

    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();

        connectivityStoreMock = {
            pendingCount: 0,
            retryAfterReauth: vi.fn().mockResolvedValue(undefined),
            clearOwnQueueOnLogout: vi.fn().mockResolvedValue(undefined),
        };
        notificationStoreMock = { $reset: vi.fn() };
        useConnectivityStore.mockReturnValue(connectivityStoreMock);
        useNotificationStore.mockReturnValue(notificationStoreMock);
    });

    afterEach(() => {
        delete globalThis.caches;
    });

    it('starts logged out with empty roles/permissions and isAuthenticated false', () => {
        const store = useAuthStore();

        expect(store.user).toBeNull();
        expect(store.roles).toEqual([]);
        expect(store.permissions).toEqual([]);
        expect(store.isLoading).toBe(false);
        expect(store.errors).toBeNull();
        expect(store.errorMessage).toBeNull();
        expect(store.initialized).toBe(false);
        expect(store.isAuthenticated).toBe(false);
    });

    describe('hasRole() / can()', () => {
        it('checks membership in the current roles/permissions arrays', async () => {
            authService.login.mockResolvedValue({
                data: { id: 1, roles: [{ name: 'admin' }], permissions: ['users.view'] },
            });
            const store = useAuthStore();
            await store.login({ email: 'a@a.com', password: 'x' });

            expect(store.hasRole('admin')).toBe(true);
            expect(store.hasRole('owner')).toBe(false);
            expect(store.can('users.view')).toBe(true);
            expect(store.can('users.delete')).toBe(false);
        });
    });

    describe('login()', () => {
        it('on success: stores the user, derives roles/permissions, flips isAuthenticated, and triggers a reauth retry-sync', async () => {
            const responseData = {
                data: {
                    id: 7,
                    name: 'Sara',
                    roles: [{ name: 'owner' }, { name: 'subscriber' }],
                    permissions: ['invoices.view'],
                },
            };
            authService.login.mockResolvedValue(responseData);

            const store = useAuthStore();
            const result = await store.login({ email: 'sara@example.com', password: 'secret' });

            expect(result).toBe(responseData);
            expect(store.user).toEqual(responseData.data);
            expect(store.roles).toEqual(['owner', 'subscriber']);
            expect(store.permissions).toEqual(['invoices.view']);
            expect(store.isAuthenticated).toBe(true);
            expect(store.isLoading).toBe(false);
            expect(connectivityStoreMock.retryAfterReauth).toHaveBeenCalled();
        });

        it('on a 422 validation error: sets errors/errorMessage from the response and re-throws', async () => {
            const originalError = {
                response: { status: 422, data: { message: 'بيانات غير صالحة.', errors: { email: ['البريد مطلوب.'] } } },
            };
            authService.login.mockRejectedValue(originalError);

            const store = useAuthStore();
            await expect(store.login({ email: '', password: '' })).rejects.toBe(originalError);

            expect(store.errors).toEqual({ email: ['البريد مطلوب.'] });
            expect(store.errorMessage).toBe('بيانات غير صالحة.');
            expect(store.isLoading).toBe(false);
            expect(store.user).toBeNull();
        });

        it('on a network error (no response): sets errors to {} (not null) and still re-throws', async () => {
            const originalError = { message: 'Network Error' };
            authService.login.mockRejectedValue(originalError);

            const store = useAuthStore();
            await expect(store.login({ email: 'a@a.com', password: 'x' })).rejects.toBe(originalError);

            expect(store.errors).toEqual({});
            expect(typeof store.errorMessage).toBe('string');
            expect(store.errorMessage.length).toBeGreaterThan(0);
        });

        it('clears previous errors at the start of a new attempt', async () => {
            authService.login.mockRejectedValueOnce({
                response: { status: 422, data: { message: 'x', errors: { email: ['x'] } } },
            });
            const store = useAuthStore();
            await expect(store.login({})).rejects.toBeTruthy();
            expect(store.errors).toEqual({ email: ['x'] });

            authService.login.mockResolvedValueOnce({ data: { id: 1, roles: [], permissions: [] } });
            await store.login({ email: 'a@a.com', password: 'x' });

            expect(store.errors).toBeNull();
            expect(store.errorMessage).toBeNull();
        });
    });

    describe('register()', () => {
        it('on success: returns the response data without touching user/roles', async () => {
            const responseData = { message: 'تم التسجيل بنجاح.' };
            authService.register.mockResolvedValue(responseData);

            const store = useAuthStore();
            const result = await store.register({ email: 'new@example.com' });

            expect(result).toBe(responseData);
            expect(store.user).toBeNull();
            expect(store.isLoading).toBe(false);
        });

        it('on a 422 validation error: sets errors/errorMessage and re-throws the original error', async () => {
            const originalError = {
                response: { status: 422, data: { message: 'فشل التحقق.', errors: { password: ['قصيرة جدًا.'] } } },
            };
            authService.register.mockRejectedValue(originalError);

            const store = useAuthStore();
            await expect(store.register({ password: '1' })).rejects.toBe(originalError);

            expect(store.errors).toEqual({ password: ['قصيرة جدًا.'] });
            expect(store.errorMessage).toBe('فشل التحقق.');
        });
    });

    describe('fetchUser()', () => {
        it('on success: populates the user and marks initialized', async () => {
            authService.me.mockResolvedValue({
                data: { id: 3, roles: [{ name: 'admin' }], permissions: [] },
            });

            const store = useAuthStore();
            await store.fetchUser();

            expect(store.user).toEqual({ id: 3, roles: [{ name: 'admin' }], permissions: [] });
            expect(store.roles).toEqual(['admin']);
            expect(store.initialized).toBe(true);
        });

        it('on failure (e.g. 401): clears local user data but still marks initialized (does not throw)', async () => {
            authService.me.mockRejectedValue({ response: { status: 401 } });

            const store = useAuthStore();
            await expect(store.fetchUser()).resolves.toBeUndefined();

            expect(store.user).toBeNull();
            expect(store.roles).toEqual([]);
            expect(store.initialized).toBe(true);
            expect(notificationStoreMock.$reset).toHaveBeenCalled();
        });
    });

    describe('clearLocalSession()', () => {
        it('clears the user and resets the notification store, synchronously', async () => {
            authService.login.mockResolvedValue({ data: { id: 1, roles: [], permissions: [] } });
            const store = useAuthStore();
            await store.login({ email: 'a@a.com', password: 'x' });

            store.clearLocalSession();

            expect(store.user).toBeNull();
            expect(store.isAuthenticated).toBe(false);
            expect(notificationStoreMock.$reset).toHaveBeenCalled();
        });
    });

    describe('logout()', () => {
        it('on success: clears the session, leaves the realtime channel, and reports hadPendingSync from the queue at call time', async () => {
            authService.login.mockResolvedValue({
                data: { id: 5, roles: [], permissions: [] },
            });
            const store = useAuthStore();
            await store.login({ email: 'a@a.com', password: 'x' });
            connectivityStoreMock.pendingCount = 2;
            authService.logout.mockResolvedValue({});

            const result = await store.logout();

            expect(authService.logout).toHaveBeenCalled();
            expect(connectivityStoreMock.clearOwnQueueOnLogout).toHaveBeenCalled();
            expect(store.user).toBeNull();
            expect(store.isLoading).toBe(false);
            expect(notificationStoreMock.$reset).toHaveBeenCalled();
            expect(forceLeaveRealtimeChannel).toHaveBeenCalled();
            expect(result).toEqual({ hadPendingSync: true });
        });

        it('hadPendingSync is false when there was no logged-in user at call time', async () => {
            const store = useAuthStore();
            connectivityStoreMock.pendingCount = 5;
            authService.logout.mockResolvedValue({});

            const result = await store.logout();

            expect(result).toEqual({ hadPendingSync: false });
        });

        it('still clears local state and resolves even if the logout API call fails', async () => {
            authService.login.mockResolvedValue({ data: { id: 1, roles: [], permissions: [] } });
            const store = useAuthStore();
            await store.login({ email: 'a@a.com', password: 'x' });
            authService.logout.mockRejectedValue(new Error('server unreachable'));
            const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {});

            const result = await store.logout();

            expect(store.user).toBeNull();
            expect(store.isLoading).toBe(false);
            expect(connectivityStoreMock.clearOwnQueueOnLogout).toHaveBeenCalled();
            expect(result).toEqual({ hadPendingSync: false });
            expect(warnSpy).toHaveBeenCalled();

            warnSpy.mockRestore();
        });

        it('purges only the named Workbox API caches when the Cache Storage API is available', async () => {
            const deleteMock = vi.fn().mockResolvedValue(true);
            globalThis.caches = { delete: deleteMock };
            authService.logout.mockResolvedValue({});

            const store = useAuthStore();
            await store.logout();

            expect(deleteMock).toHaveBeenCalledWith('ampare-api-cache');
            expect(deleteMock).toHaveBeenCalledWith('ampare-technician-api-cache');
            expect(deleteMock).toHaveBeenCalledTimes(2);
        });
    });
});
