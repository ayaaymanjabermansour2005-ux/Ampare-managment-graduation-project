import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/articleService', () => ({
    default: {
        listForAdmin: vi.fn(),
        create: vi.fn(),
        update: vi.fn(),
        destroy: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            articles_page: {
                load_error: 'تعذر تحميل المقالات',
                create_error: 'تعذر إنشاء المقال',
                update_error: 'تعذر تعديل المقال',
                delete_error: 'تعذر حذف المقال',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useArticles } = await import('./useArticles');
const articleService = (await import('@/services/articleService')).default;

describe('useArticles', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts loading with empty articles and a default pagination shape', () => {
        const { articles, pagination, isLoading, error, isSaving } = useArticles();

        expect(articles.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(true);
        expect(error.value).toBeNull();
        expect(isSaving.value).toBe(false);
    });

    describe('fetchArticles()', () => {
        it('populates articles and pagination from a full meta payload', async () => {
            articleService.listForAdmin.mockResolvedValue({
                data: { data: { data: [{ id: 1 }, { id: 2 }], meta: { current_page: 2, last_page: 5, total: 42, per_page: 10 } } },
            });

            const { fetchArticles, articles, pagination, isLoading } = useArticles();
            await fetchArticles(2);

            expect(articleService.listForAdmin).toHaveBeenCalledWith({ page: 2 });
            expect(articles.value).toEqual([{ id: 1 }, { id: 2 }]);
            expect(pagination.value).toEqual({ current_page: 2, last_page: 5, total: 42, per_page: 10 });
            expect(isLoading.value).toBe(false);
        });

        it('falls back to array length for total and page 1 defaults when there is no .meta', async () => {
            articleService.listForAdmin.mockResolvedValue({
                data: { data: { data: [{ id: 1 }, { id: 2 }, { id: 3 }] } },
            });

            const { fetchArticles, pagination } = useArticles();
            await fetchArticles();

            expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 3, per_page: 15 });
        });

        it('sets the translated fallback message on a network error', async () => {
            articleService.listForAdmin.mockRejectedValue({ message: 'Network Error' });

            const { fetchArticles, error, isLoading } = useArticles();
            await fetchArticles();

            expect(error.value).toBe('تعذر تحميل المقالات');
            expect(isLoading.value).toBe(false);
        });
    });

    describe('createArticle()', () => {
        it('creates then refetches page 1, returning true', async () => {
            articleService.create.mockResolvedValue({});
            articleService.listForAdmin.mockResolvedValue({
                data: { data: { data: [{ id: 9 }], meta: { current_page: 1, last_page: 1, total: 1, per_page: 15 } } },
            });

            const { createArticle, isSaving, articles } = useArticles();
            const result = await createArticle({ title: 'New' });

            expect(result).toBe(true);
            expect(articleService.create).toHaveBeenCalledWith({ title: 'New' });
            expect(articleService.listForAdmin).toHaveBeenCalledWith({ page: 1 });
            expect(articles.value).toEqual([{ id: 9 }]);
            expect(isSaving.value).toBe(false);
        });

        it('sets the translated error and returns false on failure, without refetching', async () => {
            articleService.create.mockRejectedValue({ message: 'Network Error' });

            const { createArticle, error, isSaving } = useArticles();
            const result = await createArticle({});

            expect(result).toBe(false);
            expect(error.value).toBe('تعذر إنشاء المقال');
            expect(isSaving.value).toBe(false);
            expect(articleService.listForAdmin).not.toHaveBeenCalled();
        });
    });

    describe('updateArticle()', () => {
        it('refetches the current pagination page (not page 1) on success', async () => {
            articleService.listForAdmin.mockResolvedValueOnce({
                data: { data: { data: [{ id: 1 }], meta: { current_page: 3, last_page: 5, total: 50, per_page: 10 } } },
            });
            const { fetchArticles, updateArticle, pagination } = useArticles();
            await fetchArticles(3);
            expect(pagination.value.current_page).toBe(3);

            articleService.update.mockResolvedValue({});
            articleService.listForAdmin.mockResolvedValueOnce({
                data: { data: { data: [{ id: 1 }], meta: { current_page: 3, last_page: 5, total: 50, per_page: 10 } } },
            });

            const result = await updateArticle(1, { title: 'Updated' });

            expect(result).toBe(true);
            expect(articleService.update).toHaveBeenCalledWith(1, { title: 'Updated' });
            expect(articleService.listForAdmin).toHaveBeenLastCalledWith({ page: 3 });
        });

        it('sets the translated error and returns false on failure', async () => {
            articleService.update.mockRejectedValue({ message: 'Network Error' });

            const { updateArticle, error } = useArticles();
            const result = await updateArticle(1, {});

            expect(result).toBe(false);
            expect(error.value).toBe('تعذر تعديل المقال');
        });
    });

    describe('deleteArticle()', () => {
        it('destroys then refetches the current page (isSaving is not toggled for delete)', async () => {
            articleService.destroy.mockResolvedValue({});
            articleService.listForAdmin.mockResolvedValue({
                data: { data: { data: [], meta: { current_page: 1, last_page: 1, total: 0, per_page: 15 } } },
            });

            const { deleteArticle, isSaving } = useArticles();
            await deleteArticle(5);

            expect(articleService.destroy).toHaveBeenCalledWith(5);
            expect(articleService.listForAdmin).toHaveBeenCalledWith({ page: 1 });
            expect(isSaving.value).toBe(false);
        });

        it('sets the translated error message on failure', async () => {
            articleService.destroy.mockRejectedValue({ message: 'Network Error' });

            const { deleteArticle, error } = useArticles();
            await deleteArticle(5);

            expect(error.value).toBe('تعذر حذف المقال');
        });
    });
});
