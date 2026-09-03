import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/generatorService', () => ({
    default: {
        list: vi.fn(),
        stats: vi.fn(),
        cities: vi.fn(),
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
            owner_generators: {
                load_error: 'تعذر تحميل المولدات',
                create_error: 'تعذر إنشاء المولد',
                update_error: 'تعذر تعديل المولد',
                delete_error: 'تعذر حذف المولد',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminGenerators } = await import('./useAdminGenerators');
const generatorService = (await import('@/services/generatorService')).default;

function samplePage(items, meta = {}) {
    return {
        data: { data: { data: items, meta: { current_page: 1, last_page: 1, total: items.length, per_page: 12, ...meta } } },
    };
}

describe('useAdminGenerators', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with default reactive state, including table state spread in and stats loading', () => {
        const { generators, pagination, isLoading, stats, isLoadingStats } = useAdminGenerators();

        expect(generators.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 12 });
        expect(isLoading.value).toBe(true);
        expect(stats.value).toBeNull();
        expect(isLoadingStats.value).toBe(true);
    });

    it('fetchStats populates stats and clears isLoadingStats on success', async () => {
        generatorService.stats.mockResolvedValue({ data: { data: { total: 10, active: 8 } } });
        const { fetchStats, stats, isLoadingStats } = useAdminGenerators();

        await fetchStats();

        expect(stats.value).toEqual({ total: 10, active: 8 });
        expect(isLoadingStats.value).toBe(false);
    });

    it('fetchGenerators sorts by fuel_percentage ascending by default, treating a missing value as -1 (sorts first)', async () => {
        generatorService.list.mockResolvedValue(
            samplePage([
                { id: 1, name: 'A', fuel_percentage: 50 },
                { id: 2, name: 'B' }, // no fuel_percentage -> falls back to -1
                { id: 3, name: 'C', fuel_percentage: 20 },
            ])
        );

        const { fetchGenerators, generators } = useAdminGenerators();
        await fetchGenerators(1);

        expect(generators.value.map((g) => g.id)).toEqual([2, 3, 1]);
    });

    it('fetchGenerators handles an empty result list', async () => {
        generatorService.list.mockResolvedValue(samplePage([]));
        const { fetchGenerators, generators, pagination } = useAdminGenerators();

        await fetchGenerators();

        expect(generators.value).toEqual([]);
        expect(pagination.value.total).toBe(0);
    });

    it('fetchGenerators sets a translated error message via normalizeApiError on failure', async () => {
        generatorService.list.mockRejectedValue({ message: 'Network Error' });
        const { fetchGenerators, error } = useAdminGenerators();

        await fetchGenerators();

        expect(error.value).toBe('تعذر تحميل المولدات');
    });

    it('createGenerator refetches the list and stats on success', async () => {
        generatorService.create.mockResolvedValue({});
        generatorService.list.mockResolvedValue(samplePage([{ id: 1, name: 'New' }]));
        generatorService.stats.mockResolvedValue({ data: { data: { total: 1 } } });

        const { createGenerator, generators, stats } = useAdminGenerators();
        const result = await createGenerator({ name: 'New' });

        expect(result).toBe(true);
        expect(generatorService.list).toHaveBeenCalled();
        expect(generators.value).toEqual([{ id: 1, name: 'New' }]);
        expect(stats.value).toEqual({ total: 1 });
    });

    it('createGenerator does not refetch stats when the underlying create fails', async () => {
        generatorService.create.mockRejectedValue({
            response: { status: 422, data: { message: 'بيانات غير صالحة', errors: { name: ['الاسم مطلوب.'] } } },
        });

        const { createGenerator, stats } = useAdminGenerators();
        const result = await createGenerator({});

        expect(result).toBe(false);
        expect(generatorService.stats).not.toHaveBeenCalled();
        expect(stats.value).toBeNull();
    });

    it('updateGenerator refetches stats on success', async () => {
        generatorService.update.mockResolvedValue({ data: { data: { id: 1, name: 'Updated' } } });
        generatorService.stats.mockResolvedValue({ data: { data: { total: 5 } } });

        const { updateGenerator, stats } = useAdminGenerators();
        const result = await updateGenerator(1, { name: 'Updated' });

        expect(result).toBe(true);
        expect(stats.value).toEqual({ total: 5 });
    });

    it('deleteGenerator refetches the list and stats on success', async () => {
        generatorService.destroy.mockResolvedValue({});
        generatorService.list.mockResolvedValue(samplePage([]));
        generatorService.stats.mockResolvedValue({ data: { data: { total: 0 } } });

        const { deleteGenerator, generators, stats } = useAdminGenerators();
        const result = await deleteGenerator(1);

        expect(result).toBe(true);
        expect(generators.value).toEqual([]);
        expect(stats.value).toEqual({ total: 0 });
    });

    it('deleteGenerator surfaces the errors.generator field error instead of the generic message', async () => {
        generatorService.destroy.mockRejectedValue({
            response: { status: 422, data: { message: 'بيانات غير صالحة', errors: { generator: ['لا يمكن حذف مولد لديه اشتراكات فعّالة.'] } } },
        });

        const { deleteGenerator, deleteError } = useAdminGenerators();
        const result = await deleteGenerator(1);

        expect(result).toBe(false);
        expect(deleteError.value).toBe('لا يمكن حذف مولد لديه اشتراكات فعّالة.');
    });

    it('loadAll fetches generators and stats concurrently', async () => {
        generatorService.list.mockResolvedValue(samplePage([{ id: 1 }]));
        generatorService.stats.mockResolvedValue({ data: { data: { total: 1 } } });

        const { loadAll, generators, stats } = useAdminGenerators();
        await loadAll();

        expect(generators.value).toEqual([{ id: 1 }]);
        expect(stats.value).toEqual({ total: 1 });
    });
});
