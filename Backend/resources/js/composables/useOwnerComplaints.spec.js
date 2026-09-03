import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/complaintService', () => ({
    default: {
        list: vi.fn(),
        create: vi.fn(),
        updateStatus: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_complaints: {
                load_error: 'تعذر تحميل الشكاوى',
                create_error: 'تعذر إرسال الشكوى',
                resolve_error: 'تعذر حل الشكوى',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useOwnerComplaints } = await import('./useOwnerComplaints');
const complaintService = (await import('@/services/complaintService')).default;

describe('useOwnerComplaints', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('has the expected default reactive state', () => {
        const {
            complaints, pagination, isLoading, error, search, statusFilter, hasComplaints,
            isSubmittingNew, newComplaintError, resolvingId, isResolving, resolveError,
        } = useOwnerComplaints();

        expect(complaints.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(search.value).toBe('');
        expect(statusFilter.value).toBe('');
        expect(hasComplaints.value).toBe(false);
        expect(isSubmittingNew.value).toBe(false);
        expect(newComplaintError.value).toBeNull();
        expect(resolvingId.value).toBeNull();
        expect(isResolving.value).toBe(false);
        expect(resolveError.value).toBeNull();
    });

    describe('fetchComplaints', () => {
        it('populates complaints/pagination from a paginated response and updates hasComplaints', async () => {
            complaintService.list.mockResolvedValue({
                data: {
                    data: {
                        data: [{ id: 1, subject: 'no power' }],
                        meta: { current_page: 2, last_page: 5, total: 42, per_page: 10 },
                    },
                },
            });

            const { fetchComplaints, complaints, pagination, hasComplaints, isLoading } = useOwnerComplaints();
            await fetchComplaints(2);

            expect(complaintService.list).toHaveBeenCalledWith({ page: 2, search: undefined, status: undefined });
            expect(complaints.value).toEqual([{ id: 1, subject: 'no power' }]);
            expect(pagination.value).toEqual({ current_page: 2, last_page: 5, total: 42, per_page: 10 });
            expect(hasComplaints.value).toBe(true);
            expect(isLoading.value).toBe(false);
        });

        it('passes search and statusFilter through when set', async () => {
            complaintService.list.mockResolvedValue({ data: { data: [] } });

            const { fetchComplaints, search, statusFilter } = useOwnerComplaints();
            search.value = 'power';
            statusFilter.value = 'open';
            await fetchComplaints(1);

            expect(complaintService.list).toHaveBeenCalledWith({ page: 1, search: 'power', status: 'open' });
        });

        it('handles an empty flat array result and derives pagination meta defaults', async () => {
            complaintService.list.mockResolvedValue({ data: { data: [] } });

            const { fetchComplaints, complaints, pagination, hasComplaints } = useOwnerComplaints();
            await fetchComplaints(1);

            expect(complaints.value).toEqual([]);
            expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
            expect(hasComplaints.value).toBe(false);
        });

        it('sets a translated error message on failure (network error)', async () => {
            complaintService.list.mockRejectedValue({ message: 'Network Error' });

            const { fetchComplaints, error, isLoading } = useOwnerComplaints();
            await fetchComplaints();

            expect(error.value).toBe('تعذر تحميل الشكاوى');
            expect(isLoading.value).toBe(false);
        });

        it('surfaces the backend message on a validation-style error response', async () => {
            complaintService.list.mockRejectedValue({ response: { status: 500, data: { message: 'خطأ في الخادم' } } });

            const { fetchComplaints, error } = useOwnerComplaints();
            await fetchComplaints();

            expect(error.value).toBe('خطأ في الخادم');
        });
    });

    describe('onSearchInput', () => {
        it('debounces rapid input so fetchComplaints only runs once after 300ms', async () => {
            vi.useFakeTimers();
            complaintService.list.mockResolvedValue({ data: { data: [] } });

            const { onSearchInput } = useOwnerComplaints();
            onSearchInput();
            onSearchInput();
            onSearchInput();

            expect(complaintService.list).not.toHaveBeenCalled();

            await vi.advanceTimersByTimeAsync(300);

            expect(complaintService.list).toHaveBeenCalledTimes(1);
            expect(complaintService.list).toHaveBeenCalledWith({ page: 1, search: undefined, status: undefined });
        });
    });

    describe('onFilterChange', () => {
        it('immediately refetches page 1', async () => {
            complaintService.list.mockResolvedValue({ data: { data: [] } });

            const { onFilterChange } = useOwnerComplaints();
            onFilterChange();
            await Promise.resolve();
            await Promise.resolve();

            expect(complaintService.list).toHaveBeenCalledWith({ page: 1, search: undefined, status: undefined });
        });
    });

    describe('submitComplaint', () => {
        it('creates the complaint, refetches page 1, and returns true on success', async () => {
            complaintService.create.mockResolvedValue({ data: { data: { id: 9 } } });
            complaintService.list.mockResolvedValue({ data: { data: [{ id: 9 }] } });

            const { submitComplaint, complaints, isSubmittingNew, newComplaintError } = useOwnerComplaints();
            const result = await submitComplaint({ subject: 's', description: 'd' });

            expect(complaintService.create).toHaveBeenCalledWith({ subject: 's', description: 'd' });
            expect(complaintService.list).toHaveBeenCalledWith({ page: 1, search: undefined, status: undefined });
            expect(result).toBe(true);
            expect(complaints.value).toEqual([{ id: 9 }]);
            expect(isSubmittingNew.value).toBe(false);
            expect(newComplaintError.value).toBeNull();
        });

        it('sets a translated error and returns false on failure', async () => {
            complaintService.create.mockRejectedValue({ message: 'Network Error' });

            const { submitComplaint, newComplaintError, isSubmittingNew } = useOwnerComplaints();
            const result = await submitComplaint({ subject: 's', description: 'd' });

            expect(result).toBe(false);
            expect(newComplaintError.value).toBe('تعذر إرسال الشكوى');
            expect(isSubmittingNew.value).toBe(false);
        });
    });

    describe('startResolving / cancelResolving', () => {
        it('startResolving sets resolvingId and clears any previous resolveError', () => {
            const { startResolving, resolvingId, resolveError } = useOwnerComplaints();
            resolveError.value = 'previous error';

            startResolving(7);

            expect(resolvingId.value).toBe(7);
            expect(resolveError.value).toBeNull();
        });

        it('cancelResolving clears both resolvingId and resolveError', () => {
            const { startResolving, cancelResolving, resolvingId, resolveError } = useOwnerComplaints();
            startResolving(7);

            cancelResolving();

            expect(resolvingId.value).toBeNull();
            expect(resolveError.value).toBeNull();
        });
    });

    describe('resolveComplaint', () => {
        it('replaces the matching complaint in the list, clears resolvingId, and returns true', async () => {
            complaintService.list.mockResolvedValue({
                data: { data: { data: [{ id: 1, status: 'open' }], meta: { current_page: 1, last_page: 1, total: 1, per_page: 15 } } },
            });
            complaintService.updateStatus.mockResolvedValue({ data: { data: { id: 1, status: 'resolved' } } });

            const { fetchComplaints, resolveComplaint, complaints, resolvingId, isResolving } = useOwnerComplaints();
            await fetchComplaints(1);
            const result = await resolveComplaint(1, { status: 'resolved', resolution_note: 'fixed' });

            expect(complaintService.updateStatus).toHaveBeenCalledWith(1, { status: 'resolved', resolution_note: 'fixed' });
            expect(result).toBe(true);
            expect(complaints.value[0]).toEqual({ id: 1, status: 'resolved' });
            expect(resolvingId.value).toBeNull();
            expect(isResolving.value).toBe(false);
        });

        it('does not throw when the complaint id is not present in the current list', async () => {
            complaintService.updateStatus.mockResolvedValue({ data: { data: { id: 999, status: 'resolved' } } });

            const { resolveComplaint, complaints } = useOwnerComplaints();
            const result = await resolveComplaint(999, { status: 'resolved', resolution_note: 'n/a' });

            expect(result).toBe(true);
            expect(complaints.value).toEqual([]);
        });

        it('sets a translated error and returns false on failure', async () => {
            complaintService.updateStatus.mockRejectedValue({ message: 'Network Error' });

            const { resolveComplaint, resolveError, isResolving } = useOwnerComplaints();
            const result = await resolveComplaint(1, { status: 'resolved', resolution_note: 'n' });

            expect(result).toBe(false);
            expect(resolveError.value).toBe('تعذر حل الشكوى');
            expect(isResolving.value).toBe(false);
        });
    });
});
