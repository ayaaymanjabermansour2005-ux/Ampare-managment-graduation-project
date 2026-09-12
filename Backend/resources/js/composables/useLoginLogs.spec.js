import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/loginLogService', () => ({
    default: { list: vi.fn() },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            login_logs_page: {
                load_error: 'تعذّر تحميل سجل تسجيل الدخول.',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useLoginLogs } = await import('./useLoginLogs');
const loginLogService = (await import('@/services/loginLogService')).default;

describe('useLoginLogs', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts loading with an empty list and page 1/1 pagination', () => {
        const { logs, pagination, isLoading, error } = useLoginLogs();

        expect(logs.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0 });
        expect(isLoading.value).toBe(true);
        expect(error.value).toBeNull();
    });

    it('fetchLogs merges the page with the given filters into the request and stores the unwrapped list + pagination', async () => {
        loginLogService.list.mockResolvedValue({
            data: { data: { data: [{ id: 1, ip: '1.2.3.4' }], meta: { current_page: 3, last_page: 9, total: 81 } } },
        });

        const { logs, pagination, isLoading, fetchLogs } = useLoginLogs();
        await fetchLogs(3, { status: 'failed' });

        expect(loginLogService.list).toHaveBeenCalledWith({ page: 3, status: 'failed' });
        expect(logs.value).toEqual([{ id: 1, ip: '1.2.3.4' }]);
        expect(pagination.value).toEqual({ current_page: 3, last_page: 9, total: 81 });
        expect(isLoading.value).toBe(false);
    });

    it('defaults to page 1 with no filters when called with no arguments', async () => {
        loginLogService.list.mockResolvedValue({ data: { data: [] } });

        const { fetchLogs } = useLoginLogs();
        await fetchLogs();

        expect(loginLogService.list).toHaveBeenCalledWith({ page: 1 });
    });

    it('handles an empty result set (empty page is a valid state)', async () => {
        loginLogService.list.mockResolvedValue({ data: { data: { data: [], meta: { current_page: 1, last_page: 1 } } } });

        const { logs, fetchLogs } = useLoginLogs();
        await fetchLogs();

        expect(logs.value).toEqual([]);
    });

    it('sets error to the server message on a 422', async () => {
        loginLogService.list.mockRejectedValue({
            response: { status: 422, data: { message: 'معطى غير صالح.' } },
        });

        const { error, fetchLogs } = useLoginLogs();
        await fetchLogs();

        expect(error.value).toBe('معطى غير صالح.');
    });

    it('falls back to the translated message on a network error', async () => {
        loginLogService.list.mockRejectedValue({ message: 'Network Error' });

        const { error, isLoading, fetchLogs } = useLoginLogs();
        await fetchLogs();

        expect(error.value).toBe('تعذّر تحميل سجل تسجيل الدخول.');
        expect(isLoading.value).toBe(false);
    });
});
