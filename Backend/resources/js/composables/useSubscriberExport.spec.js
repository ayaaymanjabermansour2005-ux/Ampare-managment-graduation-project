import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref } from 'vue';
import { createI18n } from 'vue-i18n';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            dashboard: { name: 'الاسم', phone: 'الهاتف', status: 'الحالة' },
            users_page: { email_label: 'البريد الإلكتروني' },
            subscribers_page: {
                current_subscription_col: 'الاشتراك الحالي',
                expires_on_col: 'ينتهي في',
                joined_col: 'تاريخ الانضمام',
                no_subscription: 'بدون اشتراك',
            },
        },
    },
});

vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useSubscriberExport } = await import('./useSubscriberExport');

function makeInputs(subs = []) {
    return {
        subscribers: ref(subs),
        statusFilter: ref('all'),
        subscriptionSearchTerm: ref(''),
        exportUrl: vi.fn((kind, params) => `/fake/export/${kind}?${JSON.stringify(params)}`),
        statusLabel: vi.fn((s) => (s.is_locked ? 'موقوف' : 'نشط')),
        formatDate: vi.fn((d) => (d ? `fmt(${d})` : '')),
    };
}

describe('useSubscriberExport', () => {
    describe('exportExcelUrl', () => {
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

    describe('handleExportCsv', () => {
        let createObjectURLSpy;
        let revokeObjectURLSpy;
        let clickSpy;

        beforeEach(() => {
            createObjectURLSpy = vi.fn(() => 'blob:fake-url');
            revokeObjectURLSpy = vi.fn();
            vi.stubGlobal('URL', { ...URL, createObjectURL: createObjectURLSpy, revokeObjectURL: revokeObjectURLSpy });
            clickSpy = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => {});
        });

        it('builds a CSV blob with a UTF-8 BOM, one row per subscriber, and triggers a download named for today', async () => {
            const subs = [
                { name: 'Ahmad', email: 'a@example.test', phone: '0599', is_locked: false, active_subscription: { generator_name: 'Gen A', ends_at: '2026-09-01' }, created_at: '2026-01-01' },
                { name: 'Sara', email: 's@example.test', phone: null, is_locked: true, active_subscription: null, created_at: '2026-02-02' },
            ];
            const inputs = makeInputs(subs);
            const { handleExportCsv } = useSubscriberExport(inputs);

            handleExportCsv();

            expect(createObjectURLSpy).toHaveBeenCalledTimes(1);
            const blob = createObjectURLSpy.mock.calls[0][0];
            expect(blob.type).toBe('text/csv;charset=utf-8;');
            const firstBytes = new Uint8Array(await blob.slice(0, 3).arrayBuffer());
            expect(Array.from(firstBytes)).toEqual([0xef, 0xbb, 0xbf]); // UTF-8 BOM, raw bytes
            // Blob#text() decodes via TextDecoder, which strips a leading BOM by spec — so the
            // decoded string starts directly at the real content, not at U+FEFF.
            const text = await blob.text();
            const rows = text.split('\n');
            expect(rows[0]).toBe('الاسم,البريد الإلكتروني,الهاتف,الحالة,الاشتراك الحالي,ينتهي في,تاريخ الانضمام');
            expect(rows[1]).toBe('Ahmad,a@example.test,0599,نشط,Gen A,fmt(2026-09-01),fmt(2026-01-01)');
            expect(rows[2]).toBe('Sara,s@example.test,,موقوف,بدون اشتراك,,fmt(2026-02-02)');

            expect(clickSpy).toHaveBeenCalledTimes(1);
            expect(revokeObjectURLSpy).toHaveBeenCalledWith('blob:fake-url');
        });

        it('escapes commas, quotes, and newlines per RFC 4180', async () => {
            const subs = [{ name: 'A, "B"\nC', email: 'x@example.test', phone: '', is_locked: false, active_subscription: null, created_at: null }];
            const inputs = makeInputs(subs);
            const { handleExportCsv } = useSubscriberExport(inputs);

            handleExportCsv();

            const blob = createObjectURLSpy.mock.calls[0][0];
            const text = await blob.text();
            expect(text).toContain('"A, ""B""\nC"');
        });
    });
});
