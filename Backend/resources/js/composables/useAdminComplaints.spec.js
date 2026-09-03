import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/complaintService', () => ({
    default: {
        list: vi.fn(),
        updateStatus: vi.fn(),
        destroy: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            complaints_page: {
                load_error: 'تعذر تحميل الشكاوى',
                resolve_error: 'تعذر تحديث الشكوى',
                delete_error: 'تعذر حذف الشكوى',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminComplaints } = await import('./useAdminComplaints');
const complaintService = (await import('@/services/complaintService')).default;

function samplePage(items, meta = {}) {
    return {
        data: { data: { data: items, meta: { current_page: 1, last_page: 1, total: items.length, per_page: 15, ...meta } } },
    };
}

describe('useAdminComplaints', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with default reactive state (isLoading true initially)', () => {
        const { complaints, pagination, isLoading, error, search, statusFilter, isResolving, deletingId } = useAdminComplaints();

        expect(complaints.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(true);
        expect(error.value).toBeNull();
        expect(search.value).toBe('');
        expect(statusFilter.value).toBe('');
        expect(isResolving.value).toBe(false);
        expect(deletingId.value).toBeNull();
    });

    it('fetchComplaints populates complaints and pagination on success', async () => {
        complaintService.list.mockResolvedValue(samplePage([{ id: 1 }], { total: 1 }));

        const { fetchComplaints, complaints, pagination, isLoading } = useAdminComplaints();
        await fetchComplaints(1);

        expect(complaints.value).toEqual([{ id: 1 }]);
        expect(pagination.value.total).toBe(1);
        expect(isLoading.value).toBe(false);
    });

    it('fetchComplaints sends search/status only when set (omitted as undefined otherwise)', async () => {
        complaintService.list.mockResolvedValue(samplePage([]));
        const { fetchComplaints, search, statusFilter } = useAdminComplaints();

        await fetchComplaints(2);
        expect(complaintService.list).toHaveBeenCalledWith({ page: 2, search: undefined, status: undefined });

        search.value = 'abc';
        statusFilter.value = 'open';
        await fetchComplaints(1);
        expect(complaintService.list).toHaveBeenCalledWith({ page: 1, search: 'abc', status: 'open' });
    });

    it('fetchComplaints handles an empty result list', async () => {
        complaintService.list.mockResolvedValue(samplePage([], { total: 0 }));
        const { fetchComplaints, complaints, pagination } = useAdminComplaints();

        await fetchComplaints();

        expect(complaints.value).toEqual([]);
        expect(pagination.value.total).toBe(0);
    });

    it('fetchComplaints sets a translated error message via normalizeApiError on network failure', async () => {
        complaintService.list.mockRejectedValue({ message: 'Network Error' });
        const { fetchComplaints, error } = useAdminComplaints();

        await fetchComplaints();

        expect(error.value).toBe('تعذر تحميل الشكاوى');
    });

    it('onSearchInput debounces fetchComplaints by 300ms', async () => {
        vi.useFakeTimers();
        complaintService.list.mockResolvedValue(samplePage([]));
        const { onSearchInput } = useAdminComplaints();

        onSearchInput();
        onSearchInput();
        expect(complaintService.list).not.toHaveBeenCalled();

        await vi.advanceTimersByTimeAsync(300);
        expect(complaintService.list).toHaveBeenCalledTimes(1);
        expect(complaintService.list).toHaveBeenCalledWith({ page: 1, search: undefined, status: undefined });
        vi.useRealTimers();
    });

    it('onFilterChange refetches page 1', () => {
        complaintService.list.mockResolvedValue(samplePage([]));
        const { onFilterChange } = useAdminComplaints();

        onFilterChange();

        expect(complaintService.list).toHaveBeenCalledWith({ page: 1, search: undefined, status: undefined });
    });

    it('resolveComplaint replaces the complaint in the list on success', async () => {
        complaintService.updateStatus.mockResolvedValue({ data: { data: { id: 1, status: 'resolved' } } });
        const { resolveComplaint, complaints } = useAdminComplaints();
        complaints.value = [{ id: 1, status: 'open' }];

        const result = await resolveComplaint(1, { status: 'resolved' });

        expect(result).toBe(true);
        expect(complaints.value[0]).toEqual({ id: 1, status: 'resolved' });
    });

    it('resolveComplaint sets resolveError via normalizeApiError on failure', async () => {
        complaintService.updateStatus.mockRejectedValue({
            response: { status: 422, data: { message: 'بيانات غير صالحة', errors: {} } },
        });
        const { resolveComplaint, resolveError, isResolving } = useAdminComplaints();

        const result = await resolveComplaint(1, {});

        expect(result).toBe(false);
        expect(resolveError.value).toBe('بيانات غير صالحة');
        expect(isResolving.value).toBe(false);
    });

    it('deleteComplaint removes it by refetching the current page on success', async () => {
        complaintService.destroy.mockResolvedValue({});
        complaintService.list.mockResolvedValue(samplePage([{ id: 2 }], { current_page: 1, total: 1 }));
        const { deleteComplaint, complaints, deletingId, pagination } = useAdminComplaints();
        pagination.value.current_page = 1;

        const result = await deleteComplaint(1);

        expect(result).toBe(true);
        expect(complaintService.destroy).toHaveBeenCalledWith(1);
        expect(complaintService.list).toHaveBeenCalledWith({ page: 1, search: undefined, status: undefined });
        expect(complaints.value).toEqual([{ id: 2 }]);
        expect(deletingId.value).toBeNull();
    });

    it('deleteComplaint sets deleteError via normalizeApiError on failure', async () => {
        complaintService.destroy.mockRejectedValue({ message: 'Network Error' });
        const { deleteComplaint, deleteError } = useAdminComplaints();

        const result = await deleteComplaint(1);

        expect(result).toBe(false);
        expect(deleteError.value).toBe('تعذر حذف الشكوى');
    });
});
