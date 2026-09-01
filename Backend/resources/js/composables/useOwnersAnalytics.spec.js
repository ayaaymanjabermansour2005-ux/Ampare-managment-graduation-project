import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref } from 'vue';
import { createI18n } from 'vue-i18n';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: { all: 'الكل' },
            users_page: { status_active: 'نشط', status_inactive: 'غير نشط', status_suspended: 'موقوف' },
            owners_page: {
                status_pending_review_admin: 'قيد المراجعة',
                total_owners_kpi: 'إجمالي الملاك', active_owners_kpi: 'ملاك نشطون',
                owned_generators_kpi: 'مولدات مملوكة', total_revenue_kpi: 'إجمالي الإيرادات', avg_generators_kpi: 'متوسط المولدات',
                locked_accounts_n: '{n} حساب موقوف', no_locked_accounts: 'لا حسابات موقوفة',
                avg_generators_per_owner: '{n}', this_month_label: 'هذا الشهر', across_n_owners: '{n} مالك',
                alert_tag_critical: 'حرج', alert_tag_warning: 'تحذير', alert_tag_info: 'معلومة',
                distributed_revenue: 'إيرادات موزعة', pending_dues: 'مستحقات معلقة',
                new_owners_label: 'ملاك جدد',
                commission_tiered_label: 'شرائح تلقائية',
            },
        },
    },
});

vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useOwnersAnalytics } = await import('./useOwnersAnalytics');

function makeInputs(overrides = {}) {
    return {
        owners: ref([]),
        stats: ref(null),
        statusFilter: ref(''),
        onFilterChange: vi.fn(),
        router: { push: vi.fn() },
        revenueDistData: ref({ labels: [], revenue: [], due: [] }),
        availableYears: ref([]),
        fetchRevenueDistributionAction: vi.fn().mockResolvedValue(undefined),
        openView: vi.fn(),
        ...overrides,
    };
}

describe('useOwnersAnalytics', () => {
    describe('statusLabel / statusChip', () => {
        it('maps known statuses to their translated label and chip class', () => {
            const { statusLabel, statusChip } = useOwnersAnalytics(makeInputs());
            expect(statusLabel('active')).toBe('نشط');
            expect(statusChip('active')).toBe('chip-success');
            expect(statusChip('pending_review')).toBe('chip-warning');
        });
        it('falls back to the raw value / chip-info for an unknown status', () => {
            const { statusLabel, statusChip } = useOwnersAnalytics(makeInputs());
            expect(statusLabel('bogus')).toBe('bogus');
            expect(statusChip('bogus')).toBe('chip-info');
        });
    });

    describe('sortedOwners', () => {
        it('sorts by name ascending by default', () => {
            const owners = ref([{ name: 'زياد' }, { name: 'أحمد' }, { name: 'مريم' }]);
            const { sortedOwners } = useOwnersAnalytics(makeInputs({ owners }));
            expect(sortedOwners.value.map((o) => o.name)).toEqual(['أحمد', 'زياد', 'مريم']);
        });

        it('toggleSort switches the numeric column to descending, then back to ascending', () => {
            const owners = ref([{ name: 'A', generators_count: 1 }, { name: 'B', generators_count: 5 }]);
            const { sortedOwners, toggleSort, sortBy } = useOwnersAnalytics(makeInputs({ owners }));

            toggleSort('generators');
            expect(sortBy.value).toBe('generators-desc');
            expect(sortedOwners.value.map((o) => o.generators_count)).toEqual([5, 1]);

            toggleSort('generators');
            expect(sortBy.value).toBe('generators-asc');
            expect(sortedOwners.value.map((o) => o.generators_count)).toEqual([1, 5]);
        });
    });

    describe('commissionLabel', () => {
        it('shows the percentage when a fixed rate is set', () => {
            const { commissionLabel } = useOwnersAnalytics(makeInputs());
            expect(commissionLabel(5, 'fixed')).toBe('5%');
        });

        it('shows the auto-tiers label when mode is tiered and no fixed rate is set', () => {
            const { commissionLabel } = useOwnersAnalytics(makeInputs());
            expect(commissionLabel(null, 'tiered')).toBe('شرائح تلقائية');
        });

        it('falls back to a dash when neither rate nor mode is set', () => {
            const { commissionLabel } = useOwnersAnalytics(makeInputs());
            expect(commissionLabel(null, undefined)).toBe('-');
        });
    });

    describe('KPI_CARDS', () => {
        it('returns an empty array before stats load', () => {
            const { KPI_CARDS } = useOwnersAnalytics(makeInputs());
            expect(KPI_CARDS.value).toEqual([]);
        });

        it('computes the active-percentage suffix from real stats', () => {
            const stats = ref({ total: 4, active: 3, locked: 1, total_generators: 8, avg_generators_per_owner: 2, total_revenue_ils: 5000 });
            const { KPI_CARDS } = useOwnersAnalytics(makeInputs({ stats }));
            expect(KPI_CARDS.value[1].suffix).toBe('(75%)');
        });
    });

    describe('handleAlertClick', () => {
        it('navigates to overdue invoices for a pending_dues alert', () => {
            const inputs = makeInputs();
            const { handleAlertClick } = useOwnersAnalytics(inputs);
            handleAlertClick({ type: 'pending_dues' });
            expect(inputs.router.push).toHaveBeenCalledWith({ name: 'admin.invoices', query: { status: 'overdue' } });
            expect(inputs.onFilterChange).not.toHaveBeenCalled();
        });

        it('filters to pending_review for a pending_review alert', () => {
            const inputs = makeInputs();
            const { handleAlertClick } = useOwnersAnalytics(inputs);
            handleAlertClick({ type: 'pending_review' });
            expect(inputs.statusFilter.value).toBe('pending_review');
            expect(inputs.onFilterChange).toHaveBeenCalledTimes(1);
        });

        it('filters to suspended and opens the matching owner for a suspended_account alert with an owner_id', async () => {
            vi.useFakeTimers();
            const owners = ref([{ id: 7, name: 'Test Owner' }]);
            const inputs = makeInputs({ owners });
            const { handleAlertClick } = useOwnersAnalytics(inputs);

            handleAlertClick({ type: 'suspended_account', owner_id: 7 });
            expect(inputs.statusFilter.value).toBe('suspended');

            await vi.advanceTimersByTimeAsync(400);
            expect(inputs.openView).toHaveBeenCalledWith(owners.value[0]);
            vi.useRealTimers();
        });
    });

    describe('revenue distribution', () => {
        it('fetches with the current period, then defaults revenueYear to the first available year', async () => {
            const availableYears = ref([2026, 2025]);
            const inputs = makeInputs({ availableYears });
            const { fetchRevenueDistribution, revenueYear } = useOwnersAnalytics(inputs);

            await fetchRevenueDistribution();

            expect(inputs.fetchRevenueDistributionAction).toHaveBeenCalledWith({ period: '6' });
            expect(revenueYear.value).toBe(2026);
        });

        it('setRevenueYear switches the period to "year" and refetches with that year', async () => {
            const inputs = makeInputs();
            const { setRevenueYear } = useOwnersAnalytics(inputs);

            setRevenueYear(2024);
            await Promise.resolve();

            expect(inputs.fetchRevenueDistributionAction).toHaveBeenLastCalledWith({ year: 2024 });
        });
    });

    describe('chart data-shaping', () => {
        it('planChartData/growthChartData return empty shapes before stats load', () => {
            const { planChartData, growthChartData } = useOwnersAnalytics(makeInputs());
            expect(planChartData.value.labels).toEqual([]);
            expect(growthChartData.value.labels).toEqual([]);
        });

        it('planChartData/growthChartData shape real stats correctly', () => {
            const stats = ref({
                plan_distribution: { labels: ['كاملة', 'ساعات'], counts: [5, 3] },
                growth: { labels: ['يناير', 'فبراير'], counts: [1, 2] },
            });
            const { planChartData, growthChartData } = useOwnersAnalytics(makeInputs({ stats }));
            expect(planChartData.value.datasets[0].data).toEqual([5, 3]);
            expect(growthChartData.value.datasets[0].data).toEqual([1, 2]);
            expect(growthChartData.value.datasets[0].label).toBe('ملاك جدد');
        });
    });
});
