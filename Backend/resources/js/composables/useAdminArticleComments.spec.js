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

        await approve(1);

        expect(comments.value).toEqual([{ id: 2 }]);
        expect(isActing.value).toBe(false);
    });

    it('reject removes the comment from the list on success', async () => {
        articleCommentService.reject.mockResolvedValue({});
        const { reject, comments } = useAdminArticleComments();
        comments.value = [{ id: 1 }, { id: 2 }];

        await reject(2);

        expect(comments.value).toEqual([{ id: 1 }]);
    });

    it('destroy removes the comment from the list on success', async () => {
        articleCommentService.destroy.mockResolvedValue({});
        const { destroy, comments } = useAdminArticleComments();
        comments.value = [{ id: 1 }];

        await destroy(1);

        expect(comments.value).toEqual([]);
    });

    it('reply replaces the comment with the server response on success and returns true', async () => {
        articleCommentService.reply.mockResolvedValue({ data: { data: { id: 1, admin_reply: 'شكرًا' } } });
        const { reply, comments } = useAdminArticleComments();
        comments.value = [{ id: 1, admin_reply: null }];

        const result = await reply(1, 'شكرًا');

        expect(result).toBe(true);
        expect(comments.value[0]).toEqual({ id: 1, admin_reply: 'شكرًا' });
    });

    // Note: the source's reply() catch block swallows the error entirely (no error
    // ref is set) and only returns false — this is the composable's actual behavior,
    // not a gap in this test, so we only assert what it does, not an error message.
    it('reply returns false on failure without throwing and resets isActing', async () => {
        articleCommentService.reply.mockRejectedValue({ message: 'Network Error' });
        const { reply, isActing } = useAdminArticleComments();

        const result = await reply(1, 'x');

        expect(result).toBe(false);
        expect(isActing.value).toBe(false);
    });

    it('deleteReply replaces the comment with the server response on success', async () => {
        articleCommentService.deleteReply.mockResolvedValue({ data: { data: { id: 1, admin_reply: null } } });
        const { deleteReply, comments } = useAdminArticleComments();
        comments.value = [{ id: 1, admin_reply: 'قديم' }];

        await deleteReply(1);

        expect(comments.value[0]).toEqual({ id: 1, admin_reply: null });
    });
});
