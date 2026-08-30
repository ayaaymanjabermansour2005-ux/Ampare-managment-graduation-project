import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/subscriptionService', () => ({
    default: {
        exportUrl(params = {}) {
            const query = new URLSearchParams(
                Object.fromEntries(Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== '')),
            ).toString();
            return `/api/v1/subscriptions/export${query ? `?${query}` : ''}`;
        },
        // Real-shape regression guard: exportExcelUrl calls `this.exportUrl(...)` internally,
        // exactly like the real service — this is what exposed FRONT-004-BUG-002 (see spec below).
        exportExcelUrl(params) {
            return this.exportUrl(params);
        },
    },
}));
vi.mock('@/services/generatorService', () => ({ default: { list: vi.fn() } }));
vi.mock('@/services/userService', () => ({ default: { list: vi.fn() } }));

const i18n = createI18n({ legacy: false, locale: 'ar', messages: { ar: {} } });
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminSubscriptionsData } = await import('./useAdminSubscriptionsData');

describe('useAdminSubscriptionsData — exportUrl', () => {
    it('FRONT-004-BUG-002 regression: calls exportExcelUrl bound to subscriptionService, not detached', () => {
        // Before the fix, exportUrl() destructured the method off subscriptionService before
        // calling it (`const fn = subscriptionService.exportExcelUrl; fn(params)`), so its
        // internal `this.exportUrl(...)` call threw and the silent catch{} always returned "#".
        const { exportUrl } = useAdminSubscriptionsData();
        const url = exportUrl('excel', { status: 'active' });

        expect(url).not.toBe('#');
        expect(url).toBe('/api/v1/subscriptions/export?status=active');
    });

    it('omits undefined/empty params from the query string', () => {
        const { exportUrl } = useAdminSubscriptionsData();
        const url = exportUrl('excel', { status: undefined, search: '' });

        expect(url).toBe('/api/v1/subscriptions/export');
    });

    it('falls back to "#" for a kind with no matching service method', () => {
        const { exportUrl } = useAdminSubscriptionsData();
        const url = exportUrl('pdf', {}); // subscriptionService.exportPdfUrl does not exist (FIX-031)

        expect(url).toBe('#');
    });
});
