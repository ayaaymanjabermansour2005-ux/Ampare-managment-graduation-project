import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/generatorService', () => ({
    default: {
        cities: vi.fn(),
        list: vi.fn(),
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
            generators_management_page: {
                cities_load_error: 'تعذّر تحميل قائمة المناطق.',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useGeneratorsTable } = await import('./useGeneratorsTable');
const generatorService = (await import('@/services/generatorService')).default;

describe('useGeneratorsTable', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('fetchGenerators sets a simple generic error message on failure', async () => {
        generatorService.list.mockRejectedValue({ message: 'Network Error' });

        const { fetchGenerators, error } = useGeneratorsTable();
        await fetchGenerators();

        expect(error.value).toBe('تعذر تحميل المولدات');
    });

    it('fetchCities populates cities on success', async () => {
        generatorService.cities.mockResolvedValue({ data: { data: ['Ramallah', 'Nablus'] } });

        const { fetchCities, cities } = useGeneratorsTable();
        await fetchCities();

        expect(cities.value).toEqual(['Ramallah', 'Nablus']);
    });

    it('fetchCities does not throw on failure (regression: used to reject unhandled)', async () => {
        generatorService.cities.mockRejectedValue({ message: 'Network Error' });

        const { fetchCities, cities } = useGeneratorsTable();

        await expect(fetchCities()).resolves.toBeUndefined();
        expect(cities.value).toEqual([]);
    });

    describe('createGenerator — reshaped to { message, errors }', () => {
        it('reshapes a 422 response', async () => {
            generatorService.create.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { name: ['الاسم مطلوب.'] } } },
            });

            const { createGenerator, saveError } = useGeneratorsTable();
            const result = await createGenerator({});

            expect(result).toBe(false);
            expect(saveError.value).toEqual({ message: 'Invalid.', errors: { name: ['الاسم مطلوب.'] } });
        });

        it('reshapes a network error with an empty errors object', async () => {
            generatorService.create.mockRejectedValue({ message: 'Network Error' });

            const { createGenerator, saveError } = useGeneratorsTable();
            await createGenerator({});

            expect(saveError.value).toEqual({ message: 'تعذر إنشاء المولد', errors: {} });
        });
    });

    describe('updateGenerator — reshaped to { message, errors }', () => {
        it('reshapes a 422 response', async () => {
            generatorService.update.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { price_per_kw: ['السعر مطلوب.'] } } },
            });

            const { updateGenerator, saveError } = useGeneratorsTable();
            const result = await updateGenerator(1, {});

            expect(result).toBe(false);
            expect(saveError.value).toEqual({ message: 'Invalid.', errors: { price_per_kw: ['السعر مطلوب.'] } });
        });
    });

    describe('deleteGenerator — field-level "generator" error takes priority', () => {
        it('prioritizes errors.generator (active-subscriptions block) over the generic message', async () => {
            generatorService.destroy.mockRejectedValue({
                response: {
                    status: 422,
                    data: { message: 'بيانات غير صالحة', errors: { generator: ['لا يمكن حذف مولد لديه اشتراكات فعّالة.'] } },
                },
            });

            const { deleteGenerator, deleteError } = useGeneratorsTable();
            const result = await deleteGenerator(1);

            expect(result).toBe(false);
            expect(deleteError.value).toBe('لا يمكن حذف مولد لديه اشتراكات فعّالة.');
        });

        it('falls back to the generic message when there is no generator field error', async () => {
            generatorService.destroy.mockRejectedValue({
                response: { status: 500, data: { message: 'خطأ في الخادم.' } },
            });

            const { deleteGenerator, deleteError } = useGeneratorsTable();
            await deleteGenerator(1);

            expect(deleteError.value).toBe('خطأ في الخادم.');
        });
    });
});
