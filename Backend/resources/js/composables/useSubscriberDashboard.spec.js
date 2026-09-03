import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/subscriptionService', () => ({
    default: {
        list: vi.fn(),
    },
}));
vi.mock('@/services/invoiceService', () => ({
    default: {
        list: vi.fn(),
    },
}));
vi.mock('@/services/roleDashboardService', () => ({
    default: {
        subscriberStats: vi.fn(),
    },
}));
vi.mock('@/services/notificationService', () => ({
    default: {
        getAll: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_dashboard: {
                load_error: 'تعذر تحميل لوحة التحكم',
                load_extra_stats_error: 'تعذر تحميل الإحصائيات الإضافية',
            },
            subscriber_dashboard: {
                scheduled_period_label: 'فترة مجدولة',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useSubscriberDashboard } = await import('./useSubscriberDashboard');
const subscriptionService = (await import('@/services/subscriptionService')).default;
const invoiceService = (await import('@/services/invoiceService')).default;
const roleDashboardService = (await import('@/services/roleDashboardService')).default;
const notificationService = (await import('@/services/notificationService')).default;

describe('useSubscriberDashboard', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('has the correct default reactive state before load()', () => {
        const { subscription, activeSubscription, recentInvoices, recentNotifications, latestInvoice, outstandingTotal, isLoading, error, extraStats, isLoadingExtraStats, extraStatsError, generatorStatus, upcomingSchedule } = useSubscriberDashboard();

        expect(subscription.value).toBeNull();
        expect(activeSubscription.value).toBeNull();
        expect(recentInvoices.value).toEqual([]);
        expect(recentNotifications.value).toEqual([]);
        expect(latestInvoice.value).toBeNull();
        expect(outstandingTotal.value).toBe(0);
        expect(isLoading.value).toBe(true);
        expect(error.value).toBeNull();
        expect(extraStats.value).toBeNull();
        expect(isLoadingExtraStats.value).toBe(true);
        expect(extraStatsError.value).toBeNull();
        expect(generatorStatus.value).toBeNull();
        expect(upcomingSchedule.value).toEqual([]);
    });

    describe('load()', () => {
        it('populates subscription/invoices/notifications from paginated payloads on success', async () => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, status: 'active' }] } } });
            invoiceService.list.mockResolvedValue({
                data: {
                    data: {
                        data: [
                            { id: 10, status: 'pending', remaining_balance_ils: 100 },
                            { id: 11, status: 'paid', remaining_balance_ils: 0 },
                            { id: 12, status: 'overdue', remaining_balance_ils: 50 },
                        ],
                    },
                },
            });
            notificationService.getAll.mockResolvedValue({ data: [{ id: 100 }] });

            const { load, subscription, activeSubscription, recentInvoices, latestInvoice, outstandingTotal, recentNotifications, isLoading, error } = useSubscriberDashboard();
            await load();

            expect(isLoading.value).toBe(false);
            expect(error.value).toBeNull();
            expect(subscription.value).toEqual({ id: 1, status: 'active' });
            expect(activeSubscription.value).toEqual({ id: 1, status: 'active' });
            expect(recentInvoices.value).toHaveLength(3);
            expect(latestInvoice.value).toEqual({ id: 10, status: 'pending', remaining_balance_ils: 100 });
            expect(outstandingTotal.value).toBe(150);
            expect(recentNotifications.value).toEqual([{ id: 100 }]);
        });

        it('activeSubscription is null when the subscription status is not active', async () => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, status: 'suspended' }] } } });
            invoiceService.list.mockResolvedValue({ data: { data: { data: [] } } });
            notificationService.getAll.mockResolvedValue({ data: [] });

            const { load, subscription, activeSubscription } = useSubscriberDashboard();
            await load();

            expect(subscription.value).toEqual({ id: 1, status: 'suspended' });
            expect(activeSubscription.value).toBeNull();
        });

        it('handles an empty subscriptions list (no active subscription) without throwing', async () => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [] } } });
            invoiceService.list.mockResolvedValue({ data: { data: { data: [] } } });
            notificationService.getAll.mockResolvedValue({ data: [] });

            const { load, subscription, recentInvoices, latestInvoice, outstandingTotal } = useSubscriberDashboard();
            await load();

            expect(subscription.value).toBeNull();
            expect(recentInvoices.value).toEqual([]);
            expect(latestInvoice.value).toBeNull();
            expect(outstandingTotal.value).toBe(0);
        });

        it('accepts a non-paginated (raw array) notifications payload', async () => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [] } } });
            invoiceService.list.mockResolvedValue({ data: { data: { data: [] } } });
            notificationService.getAll.mockResolvedValue({ data: [{ id: 5 }, { id: 6 }] });

            const { load, recentNotifications } = useSubscriberDashboard();
            await load();

            expect(recentNotifications.value).toEqual([{ id: 5 }, { id: 6 }]);
        });

        it('reshapes a rejection into error.value using the translated fallback message', async () => {
            subscriptionService.list.mockRejectedValue({ message: 'Network Error' });
            invoiceService.list.mockResolvedValue({ data: { data: { data: [] } } });
            notificationService.getAll.mockResolvedValue({ data: [] });

            const { load, error, isLoading } = useSubscriberDashboard();
            await load();

            expect(error.value).toBe('تعذر تحميل لوحة التحكم');
            expect(isLoading.value).toBe(false);
        });

        it('reshapes a 422 response into error.value using the backend message', async () => {
            subscriptionService.list.mockRejectedValue({ response: { status: 422, data: { message: 'خطأ تحقق' } } });
            invoiceService.list.mockResolvedValue({ data: { data: { data: [] } } });
            notificationService.getAll.mockResolvedValue({ data: [] });

            const { load, error } = useSubscriberDashboard();
            await load();

            expect(error.value).toBe('خطأ تحقق');
        });
    });

    describe('loadExtraStats()', () => {
        it('populates extraStats/generatorStatus on success', async () => {
            roleDashboardService.subscriberStats.mockResolvedValue({
                data: { data: { generator_status: { is_running: true }, open_complaints_count: 2 } },
            });

            const { loadExtraStats, extraStats, generatorStatus, isLoadingExtraStats, extraStatsError } = useSubscriberDashboard();
            await loadExtraStats();

            expect(isLoadingExtraStats.value).toBe(false);
            expect(extraStatsError.value).toBeNull();
            expect(extraStats.value).toEqual({ generator_status: { is_running: true }, open_complaints_count: 2 });
            expect(generatorStatus.value).toEqual({ is_running: true });
        });

        it('upcomingSchedule is empty when there is no scheduled slot', async () => {
            roleDashboardService.subscriberStats.mockResolvedValue({ data: { data: {} } });

            const { loadExtraStats, upcomingSchedule } = useSubscriberDashboard();
            await loadExtraStats();

            expect(upcomingSchedule.value).toEqual([]);
        });

        it('upcomingSchedule wraps the single upcoming slot, falling back to a translated label when note is empty', async () => {
            roleDashboardService.subscriberStats.mockResolvedValue({
                data: { data: { upcoming_schedule: { note: '', starts_at: '2026-01-01T00:00:00', ends_at: '2026-01-01T06:00:00' } } },
            });

            const { loadExtraStats, upcomingSchedule } = useSubscriberDashboard();
            await loadExtraStats();

            expect(upcomingSchedule.value).toEqual([
                { label: 'فترة مجدولة', starts_at: '2026-01-01T00:00:00', ends_at: '2026-01-01T06:00:00' },
            ]);
        });

        it('reshapes a rejection into extraStatsError.value', async () => {
            roleDashboardService.subscriberStats.mockRejectedValue({ message: 'Network Error' });

            const { loadExtraStats, extraStatsError, isLoadingExtraStats } = useSubscriberDashboard();
            await loadExtraStats();

            expect(extraStatsError.value).toBe('تعذر تحميل الإحصائيات الإضافية');
            expect(isLoadingExtraStats.value).toBe(false);
        });
    });
});
