import { describe, it, expect, vi } from 'vitest';
import { ref } from 'vue';

const subscribersExportUrlMock = vi.fn((params) => `/api/v1/users/subscribers-export?${JSON.stringify(params)}`);

vi.mock('@/services/userService', () => ({
    default: { subscribersExportUrl: (...args) => subscribersExportUrlMock(...args) },
}));

const { useSubscriberExport } = await import('./useSubscriberExport');

function makeInputs(overrides = {}) {
    return {
        statusFilter: ref('all'),
        subscriptionSearchTerm: ref(''),
        exportUrl: vi.fn((kind, params) => `/fake/export/${kind}?${JSON.stringify(params)}`),
        subscriberSearchTerm: ref(''),
        subscriberStatusFilter: ref(''),
        ...overrides,
    };
}

describe('useSubscriberExport', () => {
    describe('exportExcelUrl (subscriptions tab — backend export)', () => {
        it('omits status/search from the export params when unset', () => {
            const inputs = makeInputs();
            const { exportExcelUrl } = useSubscriberExport(inputs);
            exportExcelUrl.value; // force evaluation
            expect(inputs.exportUrl).toHaveBeenCalledWith('excel', { status: undefined, search: undefined });
        });

        it('reflects the active status filter and trimmed search term', () => {
            const inputs = makeInputs();
            inputs.statusFilter.value = 'active';
            inputs.subscriptionSearchTerm.value = '  ahmad  ';
            const { exportExcelUrl } = useSubscriberExport(inputs);
            exportExcelUrl.value;
            expect(inputs.exportUrl).toHaveBeenCalledWith('excel', { status: 'active', search: 'ahmad' });
        });
    });

    // FIX: subscribersExportExcelUrl replaces the old handleExportCsv() (client-side,
    // current-page-only CSV) — the subscribers tab now downloads the full server-side
    // export (GET /users/subscribers-export) like every other admin export button.
    describe('subscribersExportExcelUrl (subscribers tab — backend export)', () => {
        it('omits search/subscription_status from the export params when unset', () => {
            const inputs = makeInputs();
            const { subscribersExportExcelUrl } = useSubscriberExport(inputs);
            subscribersExportExcelUrl.value;
            expect(subscribersExportUrlMock).toHaveBeenCalledWith({ search: undefined, subscription_status: undefined });
        });

        it('reflects the subscriber search term and status filter', () => {
            const inputs = makeInputs({ subscriberSearchTerm: ref('sara'), subscriberStatusFilter: ref('locked') });
            const { subscribersExportExcelUrl } = useSubscriberExport(inputs);
            subscribersExportExcelUrl.value;
            expect(subscribersExportUrlMock).toHaveBeenCalledWith({ search: 'sara', subscription_status: 'locked' });
        });
    });
});
