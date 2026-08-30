import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref } from 'vue';
import { createI18n } from 'vue-i18n';

// Chart.js needs a real <canvas> 2D context, unavailable in jsdom. The render
// functions are exercised live in the browser (see FRONT-004a's report entry);
// this suite covers the pure derived-analytics logic that doesn't touch Chart.js.
vi.mock('chart.js/auto', () => {
    class MockChart {
        constructor() {}
        destroy() {}
    }
    MockChart.defaults = { font: {}, color: null };
    return { default: MockChart };
});

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            subscriptions_page: {
                status_pending: 'قيد المراجعة', status_active: 'نشط', status_suspended: 'موقوف',
                status_cancelled: 'ملغاة', status_rejected: 'مرفوضة',
                plan_full: 'خطة كاملة', plan_hours: 'خطة بالساعات',
                derived_expiring: 'على وشك الانتهاء', derived_expired: 'منتهي',
                alert_pending_title: 't', alert_pending_desc: 'p:{count}',
                alert_expiring_title: 't', alert_expiring_desc: 'e:{count}:{days}',
                alert_suspended_title: 't', alert_suspended_desc: 's:{count}',
                alert_expired_title: 't', alert_expired_desc: 'x:{count}',
                alert_active_title: 't', alert_active_desc: 'a:{count}',
                tag_alert: 'تنبيه', tag_info: 'معلومة', tag_good: 'جيد',
                total_subscriptions: 'إجمالي الاشتراكات',
                no_requests_awaiting_review: 'لا طلبات', requests_awaiting_review: 'طلبات:{count}',
                expired_marker: 'منتهي', days_remaining: '{d} يوم',
                time_just_now: 'الآن', time_mins_ago_short: '{mins}د',
            },
            users_page: { status_active: 'نشط', status_suspended: 'موقوف' },
            owners_page: { alert_tag_critical: 'حرج' },
            common: { all: 'الكل', this_page_suffix: ' (هذه الصفحة)' },
            subscribers_page: { time_hours_ago: '{hours}س', time_days_ago: '{days}ي' },
        },
    },
});

vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: i18n.global.locale }) };
});

const { useSubscriptionsAnalytics } = await import('./useSubscriptionsAnalytics');

function daysFromNow(days) {
    const d = new Date();
    d.setDate(d.getDate() + days);
    return d.toISOString().slice(0, 10);
}

function makeInputs(subs = []) {
    return {
        subscriptions: ref(subs),
        subscriptionPagination: ref({ total: subs.length }),
        monthlyGrowth: ref({ labels: [], newCounts: [], cancelledCounts: [], isEstimate: true }),
        computeMonthlyGrowthFromPage: vi.fn(),
    };
}

describe('useSubscriptionsAnalytics', () => {
    describe('derivedStatusKey classification', () => {
        it('classifies a suspended subscription as suspended regardless of dates', () => {
            const { derivedStatusKey } = useSubscriptionsAnalytics(makeInputs());
            expect(derivedStatusKey({ status: 'suspended', end_date: daysFromNow(100) })).toBe('suspended');
        });
        it('classifies cancelled/rejected as expired', () => {
            const { derivedStatusKey } = useSubscriptionsAnalytics(makeInputs());
            expect(derivedStatusKey({ status: 'cancelled', end_date: daysFromNow(100) })).toBe('expired');
            expect(derivedStatusKey({ status: 'rejected', end_date: daysFromNow(100) })).toBe('expired');
        });
        it('classifies an active subscription past its end_date as expired', () => {
            const { derivedStatusKey } = useSubscriptionsAnalytics(makeInputs());
            expect(derivedStatusKey({ status: 'active', end_date: daysFromNow(-1) })).toBe('expired');
        });
        it('classifies within EXPIRING_SOON_DAYS as expiring', () => {
            const { derivedStatusKey, EXPIRING_SOON_DAYS } = useSubscriptionsAnalytics(makeInputs());
            expect(EXPIRING_SOON_DAYS).toBe(14);
            expect(derivedStatusKey({ status: 'active', end_date: daysFromNow(10) })).toBe('expiring');
        });
        it('classifies a far-future active subscription as active', () => {
            const { derivedStatusKey } = useSubscriptionsAnalytics(makeInputs());
            expect(derivedStatusKey({ status: 'active', end_date: daysFromNow(100) })).toBe('active');
        });
    });

    describe('derivedStatusCounts / statusDistribution', () => {
        it('tallies each classification bucket correctly', () => {
            const subs = [
                { status: 'suspended', end_date: daysFromNow(100) },
                { status: 'cancelled', end_date: daysFromNow(100) },
                { status: 'active', end_date: daysFromNow(5) },
                { status: 'active', end_date: daysFromNow(100) },
                { status: 'active', end_date: daysFromNow(100) },
            ];
            const { derivedStatusCounts, statusDistribution } = useSubscriptionsAnalytics(makeInputs(subs));
            expect(derivedStatusCounts.value).toEqual({ active: 2, expiring: 1, expired: 1, suspended: 1 });

            const total = statusDistribution.value.find((d) => d.key === 'active');
            expect(total.count).toBe(2);
            expect(total.pct).toBe(40); // 2/5
        });
    });

    describe('applyDerivedFilter', () => {
        it('toggles the filter on and back off for the same key', () => {
            const { derivedFilter, applyDerivedFilter } = useSubscriptionsAnalytics(makeInputs());
            expect(derivedFilter.value).toBeNull();
            applyDerivedFilter('active');
            expect(derivedFilter.value).toBe('active');
            applyDerivedFilter('active');
            expect(derivedFilter.value).toBeNull();
        });
        it('switches to a different key without needing to toggle off first', () => {
            const { derivedFilter, applyDerivedFilter } = useSubscriptionsAnalytics(makeInputs());
            applyDerivedFilter('active');
            applyDerivedFilter('expired');
            expect(derivedFilter.value).toBe('expired');
        });
    });

    describe('subsAlerts', () => {
        it('only shows alerts whose condition is true, and always shows the active alert', () => {
            const subs = [{ status: 'active', end_date: daysFromNow(100) }];
            const { subsAlerts } = useSubscriptionsAnalytics(makeInputs(subs));
            const keys = subsAlerts.value.map((a) => a.title);
            // No pending, no expiring, no suspended, no expired in this dataset — only the "active" alert (always shown).
            expect(subsAlerts.value.length).toBe(1);
        });
        it('includes the pending alert once a pending subscription exists on the page', () => {
            const subs = [{ status: 'pending', end_date: daysFromNow(100) }];
            const { subsAlerts, countOnPage } = useSubscriptionsAnalytics(makeInputs(subs));
            expect(countOnPage('pending')).toBe(1);
            expect(subsAlerts.value.length).toBe(2); // pending alert + always-on active alert
        });
    });

    describe('topExpiring', () => {
        it('excludes suspended/cancelled/rejected and sorts by soonest end_date, capped at 5', () => {
            const subs = [
                { id: 1, status: 'active', end_date: daysFromNow(30) },
                { id: 2, status: 'suspended', end_date: daysFromNow(1) },
                { id: 3, status: 'active', end_date: daysFromNow(5) },
                { id: 4, status: 'cancelled', end_date: daysFromNow(2) },
                { id: 5, status: 'active', end_date: daysFromNow(10) },
                { id: 6, status: 'active', end_date: daysFromNow(1) },
                { id: 7, status: 'active', end_date: daysFromNow(2) },
                { id: 8, status: 'active', end_date: daysFromNow(3) },
            ];
            const { topExpiring } = useSubscriptionsAnalytics(makeInputs(subs));
            const ids = topExpiring.value.map((s) => s.id);
            expect(ids).toEqual([6, 7, 8, 3, 5]); // soonest 5 active-eligible, sorted ascending
        });
    });

    describe('SUBSCRIPTION_KPI_CARDS', () => {
        it('uses subscriptionPagination.total for the first card and countOnPage for the rest', () => {
            const subs = [
                { status: 'active', end_date: daysFromNow(100) },
                { status: 'active', end_date: daysFromNow(100) },
                { status: 'pending', end_date: daysFromNow(100) },
                { status: 'suspended', end_date: daysFromNow(100) },
            ];
            const inputs = makeInputs(subs);
            inputs.subscriptionPagination.value = { total: 42 }; // total can differ from page length
            const { SUBSCRIPTION_KPI_CARDS } = useSubscriptionsAnalytics(inputs);
            const cards = SUBSCRIPTION_KPI_CARDS.value;
            expect(cards[0].value).toBe(42);
            expect(cards[1].value).toBe(2); // active
            expect(cards[2].value).toBe(1); // pending
            expect(cards[3].value).toBe(1); // suspended
        });
    });

    describe('systemStatusInfo', () => {
        it('reports "no requests" when there are no pending subscriptions on the page', () => {
            const { systemStatusInfo } = useSubscriptionsAnalytics(makeInputs([{ status: 'active', end_date: daysFromNow(100) }]));
            expect(systemStatusInfo.value.color).toBe('#28A745');
        });
        it('reports pending count when pending subscriptions exist', () => {
            const { systemStatusInfo } = useSubscriptionsAnalytics(makeInputs([{ status: 'pending', end_date: daysFromNow(100) }]));
            expect(systemStatusInfo.value.color).toBe('#FFC107');
        });
    });

    describe('label/formatting helpers', () => {
        it('subscriptionStatusLabel/statusChip/statusChipStyle match STATUS_META', () => {
            const { subscriptionStatusLabel, statusChip, statusChipStyle, STATUS_META } = useSubscriptionsAnalytics(makeInputs());
            expect(subscriptionStatusLabel('active')).toBe('نشط');
            expect(statusChip('active')).toBe('chip-success');
            expect(statusChip('cancelled')).toBeNull(); // cancelled has no chip class, by design
            expect(statusChipStyle('cancelled')).toEqual({ color: '#9a9d97', background: '#9a9d971A' });
            expect(STATUS_META.rejected.color).toBe('#D9534F');
        });
        it('planLabel/planMeta handle known and unknown plans', () => {
            const { planLabel, planMeta } = useSubscriptionsAnalytics(makeInputs());
            expect(planLabel('full')).toBe('خطة كاملة');
            expect(planLabel('nonexistent')).toBe('-');
            expect(planMeta('nonexistent')).toBeNull();
        });
    });
});
