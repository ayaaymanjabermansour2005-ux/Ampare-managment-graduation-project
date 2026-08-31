import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/ownerApplicationService', () => ({
    default: {
        list: vi.fn(),
        approve: vi.fn(),
        reject: vi.fn(),
        bulkApprove: vi.fn(),
        bulkReject: vi.fn(),
        updateInternalNote: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_applications_page: {
                date_range_error: 'تاريخ البداية يجب أن يسبق تاريخ النهاية',
                load_error: 'تعذر تحميل الطلبات',
                approve_error: 'تعذر الموافقة على الطلب',
                reject_error: 'تعذر رفض الطلب',
                bulk_approve_error: 'تعذر الموافقة الجماعية',
                bulk_reject_error: 'تعذر الرفض الجماعي',
                note_save_error: 'تعذر حفظ الملاحظة',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminOwnerApplications } = await import('./useAdminOwnerApplications');
const ownerApplicationService = (await import('@/services/ownerApplicationService')).default;

function samplePage(overrides = {}) {
    return {
        data: {
            data: {
                data: [],
                meta: { current_page: 1, last_page: 1, total: 0, per_page: 15, ...overrides },
            },
        },
    };
}

describe('useAdminOwnerApplications — date-range field errors routed to a dedicated ref', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('local guard: blocks the request entirely and sets dateRangeError when from > to (no API call)', async () => {
        const { fetchApplications, dateFrom, dateTo, dateRangeError } = useAdminOwnerApplications();
        dateFrom.value = '2026-02-01';
        dateTo.value = '2026-01-01';

        await fetchApplications();

        expect(ownerApplicationService.list).not.toHaveBeenCalled();
        expect(dateRangeError.value).toBe('تاريخ البداية يجب أن يسبق تاريخ النهاية');
    });

    it('routes a server-side to_date field error into dateRangeError, not the generic error ref', async () => {
        ownerApplicationService.list.mockRejectedValue({
            response: { status: 422, data: { message: 'Invalid.', errors: { to_date: ['صيغة التاريخ غير صحيحة.'] } } },
        });

        const { fetchApplications, dateRangeError, error } = useAdminOwnerApplications();
        await fetchApplications();

        expect(dateRangeError.value).toBe('صيغة التاريخ غير صحيحة.');
        expect(error.value).toBe('Invalid.');
    });

    it('falls back to a from_date field error when to_date has none', async () => {
        ownerApplicationService.list.mockRejectedValue({
            response: { status: 422, data: { message: 'Invalid.', errors: { from_date: ['تاريخ غير صالح.'] } } },
        });

        const { fetchApplications, dateRangeError } = useAdminOwnerApplications();
        await fetchApplications();

        expect(dateRangeError.value).toBe('تاريخ غير صالح.');
    });

    it('leaves dateRangeError untouched (clears to null) when the failure has no date field error', async () => {
        ownerApplicationService.list.mockRejectedValue({
            response: { status: 500, data: { message: 'خطأ في الخادم.' } },
        });

        const { fetchApplications, dateRangeError, error } = useAdminOwnerApplications();
        await fetchApplications();

        expect(dateRangeError.value).toBeNull();
        expect(error.value).toBe('خطأ في الخادم.');
    });

    it('fetchApplications succeeds and populates statusCounts from meta', async () => {
        ownerApplicationService.list.mockResolvedValue({
            data: { data: { data: [{ id: 1 }], meta: { current_page: 1, last_page: 1, total: 1, per_page: 15, status_counts: { pending: 3, approved: 2, rejected: 1, all: 6 } } } },
        });

        const { fetchApplications, statusCounts, applications } = useAdminOwnerApplications();
        await fetchApplications();

        expect(applications.value).toHaveLength(1);
        expect(statusCounts.value).toEqual({ pending: 3, approved: 2, rejected: 1, all: 6 });
    });

    it('approveApplication sets reviewError via normalizeApiError on failure', async () => {
        ownerApplicationService.approve.mockRejectedValue({
            response: { status: 500, data: { message: 'تعذر فعليًا.' } },
        });

        const { approveApplication, reviewError } = useAdminOwnerApplications();
        const result = await approveApplication(1);

        expect(result).toBeNull();
        expect(reviewError.value).toBe('تعذر فعليًا.');
    });

    it('rejectApplication sets reviewError via normalizeApiError on failure', async () => {
        ownerApplicationService.reject.mockRejectedValue({ message: 'Network Error' });

        const { rejectApplication, reviewError } = useAdminOwnerApplications();
        const result = await rejectApplication(1, 'reason');

        expect(result).toBeNull();
        expect(reviewError.value).toBe('تعذر رفض الطلب');
    });

    it('bulkApproveApplications sets bulkError via normalizeApiError on failure', async () => {
        ownerApplicationService.bulkApprove.mockRejectedValue({
            response: { status: 500, data: { message: 'فشل جماعي.' } },
        });

        const { bulkApproveApplications, bulkError } = useAdminOwnerApplications();
        const result = await bulkApproveApplications([1, 2]);

        expect(result).toBeNull();
        expect(bulkError.value).toBe('فشل جماعي.');
    });

    it('bulkRejectApplications sets bulkError via normalizeApiError on failure', async () => {
        ownerApplicationService.bulkReject.mockRejectedValue({ message: 'Network Error' });

        const { bulkRejectApplications, bulkError } = useAdminOwnerApplications();
        const result = await bulkRejectApplications([1, 2], 'reason');

        expect(result).toBeNull();
        expect(bulkError.value).toBe('تعذر الرفض الجماعي');
    });

    it('updateInternalNote sets reviewError via normalizeApiError on failure', async () => {
        ownerApplicationService.updateInternalNote.mockRejectedValue({
            response: { status: 500, data: { message: 'فشل الحفظ.' } },
        });

        const { updateInternalNote, reviewError } = useAdminOwnerApplications();
        const result = await updateInternalNote(1, 'note');

        expect(result).toBe(false);
        expect(reviewError.value).toBe('فشل الحفظ.');
    });
});
