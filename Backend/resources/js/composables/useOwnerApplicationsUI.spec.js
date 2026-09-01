import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref } from 'vue';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/ownerApplicationService', () => ({
    default: { show: vi.fn(), exportUrl: vi.fn((p) => `/fake/export?${JSON.stringify(p)}`) },
}));
vi.mock('@/services/activityLogService', () => ({
    default: { index: vi.fn() },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: { all: 'الكل' },
            users_page: { status_pending_review: 'قيد المراجعة' },
            owner_applications_page: {
                status_pending: 'قيد الانتظار', status_approved: 'مقبول', status_rejected: 'مرفوض',
                sort_newest: 'الأحدث', sort_oldest: 'الأقدم', sort_name_az: 'الاسم',
                total_requests: 'إجمالي الطلبات', approved_rejected_summary: 'مقبول:{approved} مرفوض:{rejected}',
                of_total_suffix: 'من الإجمالي', no_requests_yet: 'لا طلبات بعد',
                overdue_this_page: 'متأخر بهذه الصفحة', overdue_days_desc: 'أكثر من {days} أيام',
                approval_rate: 'نسبة القبول', approved_of_total: '{approved} من {total}', no_reviewed_requests_yet: 'لا طلبات مراجَعة بعد',
                overdue_alert_title: '{count} طلب متأخر', overdue_alert_desc: 'أكثر من {days} أيام',
                duplicate_alert_title: '{count} تكرار', duplicate_alert_desc: 'بريد أو هاتف مكرر',
                approve_confirm_title: 't', approve_confirm_message: 'اعتماد {name}؟', approve_action: 'اعتماد',
                whatsapp_approved_message: 'مرحبًا {name}، تم القبول: {loginUrl}',
                whatsapp_rejected_message: 'مرحبًا {name}، تم الرفض{reasonSuffix}',
                whatsapp_rejected_reason_suffix: ' - السبب: {reason}',
                bulk_reject_toast_title: 't', bulk_reject_toast_message: 'رُفض {rejected}{failedSuffix}', bulk_reject_failed_suffix: ' وفشل {count}',
                approve_selected_title: 't', approve_selected_message: 'اعتماد {count}؟', approve_all_action: 'اعتماد الكل',
                bulk_approve_toast_title: 't', bulk_approve_toast_message: 'قُبل {approved}{failedSuffix}', bulk_approve_failed_suffix: ' وفشل {count}',
                history_approved: 'تم القبول', history_rejected: 'تم الرفض', history_submitted: 'تم التقديم', history_update_fallback: 'تحديث',
            },
        },
    },
});

vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useOwnerApplicationsUI } = await import('./useOwnerApplicationsUI');
const ownerApplicationService = (await import('@/services/ownerApplicationService')).default;
const activityLogService = (await import('@/services/activityLogService')).default;

function daysAgoStr(days) {
    const d = new Date();
    d.setDate(d.getDate() - days);
    return d.toISOString().slice(0, 19).replace('T', ' ');
}

function makeInputs(overrides = {}) {
    return {
        applications: ref([]),
        statusCounts: ref(null),
        reviewingId: ref(null),
        isBulkProcessing: ref(false),
        approveApplication: vi.fn(),
        rejectApplication: vi.fn(),
        bulkApproveApplications: vi.fn(),
        bulkRejectApplications: vi.fn(),
        updateInternalNote: vi.fn(),
        searchQuery: ref(''),
        applicationStatusFilter: ref(''),
        applicationsSortBy: ref('created_desc'),
        dateFrom: ref(''),
        dateTo: ref(''),
        confirm: vi.fn().mockResolvedValue(true),
        toast: { show: vi.fn() },
        router: { push: vi.fn() },
        ...overrides,
    };
}

describe('useOwnerApplicationsUI', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('SLA / overdue detection', () => {
        it('flags a pending application older than SLA_DAYS as overdue, but not a recent one or a non-pending one', () => {
            const applications = ref([
                { status: 'pending', created_at: daysAgoStr(5) },
                { status: 'pending', created_at: daysAgoStr(1) },
                { status: 'approved', created_at: daysAgoStr(10) },
            ]);
            const { overdueCountOnPage } = useOwnerApplicationsUI(makeInputs({ applications }));
            expect(overdueCountOnPage.value).toBe(1);
        });
    });

    describe('APPLICATION_KPI_CARDS', () => {
        it('shows "-" and the no-reviewed-requests fallback before any approvals/rejections', () => {
            const statusCounts = ref({ all: 5, pending: 5, approved: 0, rejected: 0 });
            const { APPLICATION_KPI_CARDS } = useOwnerApplicationsUI(makeInputs({ statusCounts }));
            expect(APPLICATION_KPI_CARDS.value[3].value).toBe('-');
        });

        it('computes a real approval rate once there is a reviewed total', () => {
            const statusCounts = ref({ all: 10, pending: 2, approved: 6, rejected: 2 });
            const { APPLICATION_KPI_CARDS } = useOwnerApplicationsUI(makeInputs({ statusCounts }));
            expect(APPLICATION_KPI_CARDS.value[3].value).toBe(75);
            expect(APPLICATION_KPI_CARDS.value[3].suffix).toBe('%');
        });
    });

    describe('alertItems', () => {
        it('is empty when nothing is overdue or duplicated', () => {
            const { alertItems } = useOwnerApplicationsUI(makeInputs());
            expect(alertItems.value).toEqual([]);
        });

        it('includes both overdue and duplicate alerts when both conditions are true', () => {
            const applications = ref([
                { status: 'pending', created_at: daysAgoStr(10), is_duplicate_email: true },
            ]);
            const { alertItems } = useOwnerApplicationsUI(makeInputs({ applications }));
            expect(alertItems.value.map((a) => a.type)).toEqual(['overdue', 'duplicate']);
        });
    });

    describe('handleApprove', () => {
        it('does nothing when the confirm dialog is dismissed', async () => {
            const inputs = makeInputs({ confirm: vi.fn().mockResolvedValue(false) });
            const { handleApprove, lastResult } = useOwnerApplicationsUI(inputs);
            await handleApprove({ id: 1, name: 'Test' });
            expect(inputs.approveApplication).not.toHaveBeenCalled();
            expect(lastResult.value).toBeNull();
        });

        it('approves and records lastResult (for the WhatsApp link) on success', async () => {
            const inputs = makeInputs();
            inputs.approveApplication.mockResolvedValue({ id: 1 });
            const { handleApprove, lastResult, lastResultWhatsAppLink } = useOwnerApplicationsUI(inputs);

            await handleApprove({ id: 1, name: 'Ahmad', phone: '0599123456' });

            expect(inputs.approveApplication).toHaveBeenCalledWith(1);
            expect(lastResult.value).toEqual({ type: 'approved', name: 'Ahmad', phone: '0599123456', reason: null });
            expect(lastResultWhatsAppLink.value).toBe(
                'https://wa.me/0599123456?text=' + encodeURIComponent('مرحبًا Ahmad، تم القبول: http://localhost:3000/login'),
            );
        });
    });

    describe('bulk selection', () => {
        it('toggleSelectAll selects all pending applications, then clears on a second call', () => {
            const applications = ref([{ id: 1, status: 'pending' }, { id: 2, status: 'pending' }, { id: 3, status: 'approved' }]);
            const { toggleSelectAll, selectedIds, isAllSelected } = useOwnerApplicationsUI(makeInputs({ applications }));

            toggleSelectAll();
            expect(selectedIds.value).toEqual([1, 2]);
            expect(isAllSelected.value).toBe(true);

            toggleSelectAll();
            expect(selectedIds.value).toEqual([]);
        });

        it('handleBulkReject opens the reject dialog with the current selection and does nothing when empty', () => {
            const { handleBulkReject, selectedIds, isRejectOpen, rejectBulkIds } = useOwnerApplicationsUI(makeInputs());

            handleBulkReject();
            expect(isRejectOpen.value).toBe(false);

            selectedIds.value = [5, 6];
            handleBulkReject();
            expect(isRejectOpen.value).toBe(true);
            expect(rejectBulkIds.value).toEqual([5, 6]);
        });
    });

    describe('document viewer', () => {
        it('cycles forward and backward through an application\'s documents, wrapping at the ends', () => {
            const { openDocViewer, nextDoc, prevDoc, activeDoc } = useOwnerApplicationsUI(makeInputs());
            const app = { documents: [{ id: 'a' }, { id: 'b' }, { id: 'c' }] };

            openDocViewer(app);
            expect(activeDoc.value.id).toBe('a');
            nextDoc();
            expect(activeDoc.value.id).toBe('b');
            nextDoc();
            nextDoc();
            expect(activeDoc.value.id).toBe('a'); // wrapped
            prevDoc();
            expect(activeDoc.value.id).toBe('c'); // wrapped backward
        });

        it('does not open when the application has no documents', () => {
            const { openDocViewer, isDocViewerOpen } = useOwnerApplicationsUI(makeInputs());
            openDocViewer({ documents: [] });
            expect(isDocViewerOpen.value).toBe(false);
        });
    });

    describe('openDetails', () => {
        it('loads the full application and its review history, replacing the summary row', async () => {
            ownerApplicationService.show.mockResolvedValue({ data: { data: { id: 1, name: 'Full Detail' } } });
            activityLogService.index.mockResolvedValue({ data: { data: [{ id: 'log1' }] } });
            const { openDetails, detailsApp, reviewHistory, isLoadingDetails } = useOwnerApplicationsUI(makeInputs());

            await openDetails({ id: 1, name: 'Summary Row' });

            expect(detailsApp.value).toEqual({ id: 1, name: 'Full Detail' });
            expect(reviewHistory.value).toEqual([{ id: 'log1' }]);
            expect(isLoadingDetails.value).toBe(false);
        });

        it('falls back to an empty history on a failed activity-log fetch, without crashing', async () => {
            ownerApplicationService.show.mockRejectedValue(new Error('network'));
            activityLogService.index.mockRejectedValue(new Error('network'));
            const { openDetails, reviewHistory, isLoadingHistory } = useOwnerApplicationsUI(makeInputs());

            await openDetails({ id: 1, name: 'X' });

            expect(reviewHistory.value).toEqual([]);
            expect(isLoadingHistory.value).toBe(false);
        });
    });

    describe('internal notes', () => {
        it('tracks unsaved changes against the original note, and clears after a successful save', async () => {
            const inputs = makeInputs();
            const { internalNoteDraft, setInternalNoteDraft, hasUnsavedInternalNote, saveInternalNote } = useOwnerApplicationsUI(inputs);
            const app = { id: 9, internal_note: 'original' };

            expect(internalNoteDraft(app)).toBe('original');
            expect(hasUnsavedInternalNote(app)).toBe(false);

            setInternalNoteDraft(app, 'edited note');
            expect(hasUnsavedInternalNote(app)).toBe(true);

            await saveInternalNote(app);
            expect(inputs.updateInternalNote).toHaveBeenCalledWith(9, 'edited note');
        });
    });

    describe('applicationsExportUrl', () => {
        it('builds the export URL from the current filters', () => {
            const inputs = makeInputs({
                searchQuery: ref('ahmad'),
                applicationStatusFilter: ref('pending'),
            });
            const { applicationsExportUrl } = useOwnerApplicationsUI(inputs);
            expect(ownerApplicationService.exportUrl).not.toHaveBeenCalled();
            applicationsExportUrl.value;
            expect(ownerApplicationService.exportUrl).toHaveBeenCalledWith({
                search: 'ahmad', status: 'pending', sort: 'created_desc', from_date: undefined, to_date: undefined,
            });
        });
    });
});
