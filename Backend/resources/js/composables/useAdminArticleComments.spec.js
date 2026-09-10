import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/articleCommentService', () => ({
    default: {
        adminList: vi.fn(),
        approve: vi.fn(),
        reject: vi.fn(),
        destroy: vi.fn(),
        reply: vi.fn(),
        deleteReply: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            article_comments_page: {
                load_error: 'تعذر تحميل التعليقات',
                action_error: 'تعذر تنفيذ العملية',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminArticleComments } = await import('./useAdminArticleComments');
const articleCommentService = (await import('@/services/articleCommentService')).default;

describe('useAdminArticleComments', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with default reactive state (no per_page in pagination, status filter defaults to pending)', () => {
        const { comments, pagination, isLoading, error, statusFilter, isActing } = useAdminArticleComments();

        expect(comments.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(statusFilter.value).toBe('pending');
        expect(isActing.value).toBe(false);
    });

    it('fetchComments populates comments and pagination from a Resource Collection payload', async () => {
        articleCommentService.adminList.mockResolvedValue({
            data: { data: { data: [{ id: 1 }, { id: 2 }], meta: { current_page: 1, last_page: 3, total: 30 } } },
        });

        const { fetchComments, comments, pagination, isLoading } = useAdminArticleComments();
        const promise = fetchComments(1);
        expect(isLoading.value).toBe(true);
        await promise;

        expect(comments.value).toEqual([{ id: 1 }, { id: 2 }]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 3, total: 30 });
        expect(isLoading.value).toBe(false);
    });

    it('fetchComments handles an empty result list', async () => {
        articleCommentService.adminList.mockResolvedValue({
            data: { data: { data: [], meta: { current_page: 1, last_page: 1, total: 0 } } },
        });

        const { fetchComments, comments } = useAdminArticleComments();
        await fetchComments();

        expect(comments.value).toEqual([]);
    });

    it('fetchComments sets a translated error message via normalizeApiError on network failure', async () => {
        articleCommentService.adminList.mockRejectedValue({ message: 'Network Error' });

        const { fetchComments, error, isLoading } = useAdminArticleComments();
        await fetchComments();

        expect(error.value).toBe('تعذر تحميل التعليقات');
        expect(isLoading.value).toBe(false);
    });

    it('onFilterChange refetches page 1 using the current statusFilter', () => {
        articleCommentService.adminList.mockResolvedValue({
            data: { data: { data: [], meta: { current_page: 1, last_page: 1, total: 0 } } },
        });

        const { onFilterChange, statusFilter } = useAdminArticleComments();
        statusFilter.value = 'approved';
        onFilterChange();

        expect(articleCommentService.adminList).toHaveBeenCalledWith({ page: 1, status: 'approved' });
    });

    it('approve removes the comment from the list on success', async () => {
        articleCommentService.approve.mockResolvedValue({});
        const { approve, comments, isActing } = useAdminArticleComments();
        comments.value = [{ id: 1 }, { id: 2 }];

        const result = await approve(1);

        expect(result).toBe(true);
        expect(comments.value).toEqual([{ id: 2 }]);
        expect(isActing.value).toBe(false);
    });

    // Regression (تدقيق شامل — الجولة السابعة): approve/reject/destroy/deleteReply
    // كانت بلا catch إطلاقًا — أي فشل كان يطلع Unhandled Promise Rejection صامت.
    it('approve does not throw on failure and sets actionError', async () => {
        articleCommentService.approve.mockRejectedValue({ message: 'Network Error' });
        const { approve, actionError, comments } = useAdminArticleComments();
        comments.value = [{ id: 1 }];

        const result = await approve(1);

        expect(result).toBe(false);
        expect(actionError.value).toBe('تعذر تنفيذ العملية');
        expect(comments.value).toEqual([{ id: 1 }]);
    });

    it('reject removes the comment from the list on success', async () => {
        articleCommentService.reject.mockResolvedValue({});
        const { reject, comments } = useAdminArticleComments();
        comments.value = [{ id: 1 }, { id: 2 }];

        await reject(2);

        expect(comments.value).toEqual([{ id: 1 }]);
    });

    it('reject does not throw on failure and sets actionError', async () => {
        articleCommentService.reject.mockRejectedValue({ message: 'Network Error' });
        const { reject, actionError } = useAdminArticleComments();

        const result = await reject(1);

        expect(result).toBe(false);
        expect(actionError.value).toBe('تعذر تنفيذ العملية');
    });

    it('destroy removes the comment from the list on success', async () => {
        articleCommentService.destroy.mockResolvedValue({});
        const { destroy, comments } = useAdminArticleComments();
        comments.value = [{ id: 1 }];

        await destroy(1);

        expect(comments.value).toEqual([]);
    });

    it('destroy does not throw on failure and sets actionError', async () => {
        articleCommentService.destroy.mockRejectedValue({ message: 'Network Error' });
        const { destroy, actionError } = useAdminArticleComments();

        const result = await destroy(1);

        expect(result).toBe(false);
        expect(actionError.value).toBe('تعذر تنفيذ العملية');
    });

    it('reply replaces the comment with the server response on success and returns true', async () => {
        articleCommentService.reply.mockResolvedValue({ data: { data: { id: 1, admin_reply: 'شكرًا' } } });
        const { reply, comments } = useAdminArticleComments();
        comments.value = [{ id: 1, admin_reply: null }];

        const result = await reply(1, 'شكرًا');

        expect(result).toBe(true);
        expect(comments.value[0]).toEqual({ id: 1, admin_reply: 'شكرًا' });
    });

    it('reply returns false and sets actionError on failure (regression: used to swallow the error silently)', async () => {
        articleCommentService.reply.mockRejectedValue({ message: 'Network Error' });
        const { reply, isActing, actionError } = useAdminArticleComments();

        const result = await reply(1, 'x');

        expect(result).toBe(false);
        expect(isActing.value).toBe(false);
        expect(actionError.value).toBe('تعذر تنفيذ العملية');
    });

    it('deleteReply replaces the comment with the server response on success', async () => {
        articleCommentService.deleteReply.mockResolvedValue({ data: { data: { id: 1, admin_reply: null } } });
        const { deleteReply, comments } = useAdminArticleComments();
        comments.value = [{ id: 1, admin_reply: 'قديم' }];

        const result = await deleteReply(1);

        expect(result).toBe(true);
        expect(comments.value[0]).toEqual({ id: 1, admin_reply: null });
    });

    it('deleteReply does not throw on failure and sets actionError', async () => {
        articleCommentService.deleteReply.mockRejectedValue({ message: 'Network Error' });
        const { deleteReply, actionError } = useAdminArticleComments();

        const result = await deleteReply(1);

        expect(result).toBe(false);
        expect(actionError.value).toBe('تعذر تنفيذ العملية');
    });
});
