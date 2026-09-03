import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

vi.mock('@/services/sidebarService', () => ({
    default: { badgeCounts: vi.fn() },
}));
vi.mock('@/services/conversationService', () => ({
    default: { unreadCount: vi.fn() },
}));

const { useSidebarBadgesStore } = await import('./sidebarBadges');
const sidebarService = (await import('@/services/sidebarService')).default;
const conversationService = (await import('@/services/conversationService')).default;

describe('useSidebarBadgesStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
        vi.spyOn(console, 'error').mockImplementation(() => {});
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.useRealTimers();
    });

    it('starts with all counts at 0 and isLoaded false', () => {
        const store = useSidebarBadgesStore();

        expect(store.isLoaded).toBe(false);
        expect(store.counts).toEqual({
            owner_applications_pending: 0,
            payments_pending: 0,
            complaints_open: 0,
            faults_pending: 0,
            contact_messages_new: 0,
            messages_unread: 0,
            generators_pending_verification: 0,
            users_locked: 0,
            invoices_overdue: 0,
            article_comments_pending: 0,
        });
    });

    it('fetchCounts() merges badge counts with the unread conversation count and sets isLoaded', async () => {
        sidebarService.badgeCounts.mockResolvedValue({
            data: { data: { payments_pending: 4, complaints_open: 2 } },
        });
        conversationService.unreadCount.mockResolvedValue({
            data: { data: { unread_count: 7 } },
        });

        const store = useSidebarBadgesStore();
        await store.fetchCounts();

        expect(store.counts).toEqual({
            payments_pending: 4,
            complaints_open: 2,
            messages_unread: 7,
        });
        expect(store.isLoaded).toBe(true);
    });

    it('fetchCounts() defaults messages_unread to 0 when unread_count is missing', async () => {
        sidebarService.badgeCounts.mockResolvedValue({ data: { data: { faults_pending: 1 } } });
        conversationService.unreadCount.mockResolvedValue({ data: { data: {} } });

        const store = useSidebarBadgesStore();
        await store.fetchCounts();

        expect(store.counts.messages_unread).toBe(0);
        expect(store.counts.faults_pending).toBe(1);
    });

    it('fetchCounts() on failure logs the error, keeps the previous counts, and still sets isLoaded (silent-failure convention)', async () => {
        sidebarService.badgeCounts.mockResolvedValueOnce({
            data: { data: { payments_pending: 9 } },
        });
        conversationService.unreadCount.mockResolvedValueOnce({
            data: { data: { unread_count: 1 } },
        });

        const store = useSidebarBadgesStore();
        await store.fetchCounts();
        expect(store.counts.payments_pending).toBe(9);

        sidebarService.badgeCounts.mockRejectedValueOnce(new Error('network down'));

        await expect(store.fetchCounts()).resolves.toBeUndefined();

        expect(store.counts.payments_pending).toBe(9);
        expect(store.isLoaded).toBe(true);
        expect(console.error).toHaveBeenCalled();
    });

    it('startAutoRefresh() fetches immediately and again every 60s, and stopAutoRefresh() halts it', async () => {
        vi.useFakeTimers();
        sidebarService.badgeCounts.mockResolvedValue({ data: { data: {} } });
        conversationService.unreadCount.mockResolvedValue({ data: { data: { unread_count: 0 } } });

        const store = useSidebarBadgesStore();
        store.startAutoRefresh();
        await vi.advanceTimersByTimeAsync(0);
        expect(sidebarService.badgeCounts).toHaveBeenCalledTimes(1);

        await vi.advanceTimersByTimeAsync(60_000);
        expect(sidebarService.badgeCounts).toHaveBeenCalledTimes(2);

        store.stopAutoRefresh();
        await vi.advanceTimersByTimeAsync(120_000);
        expect(sidebarService.badgeCounts).toHaveBeenCalledTimes(2);
    });

    it('startAutoRefresh() called twice does not register a second interval (double-start guard)', async () => {
        vi.useFakeTimers();
        sidebarService.badgeCounts.mockResolvedValue({ data: { data: {} } });
        conversationService.unreadCount.mockResolvedValue({ data: { data: { unread_count: 0 } } });

        const store = useSidebarBadgesStore();
        store.startAutoRefresh();
        await vi.advanceTimersByTimeAsync(0);
        store.startAutoRefresh();
        await vi.advanceTimersByTimeAsync(0);

        // Only the first startAutoRefresh() call's immediate fetchCounts() ran —
        // the second call returned early because refreshTimer was already set.
        expect(sidebarService.badgeCounts).toHaveBeenCalledTimes(1);

        await vi.advanceTimersByTimeAsync(60_000);
        // A single interval firing once, not two intervals firing in parallel.
        expect(sidebarService.badgeCounts).toHaveBeenCalledTimes(2);
    });
});
