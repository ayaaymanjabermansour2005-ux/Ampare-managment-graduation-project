import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/offerService', () => ({
    default: {
        list: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            offers_page: {
                load_error: 'تعذر تحميل العروض',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useSubscriberOffers } = await import('./useSubscriberOffers');
const offerService = (await import('@/services/offerService')).default;

describe('useSubscriberOffers', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('has the correct default reactive state', () => {
        const { offers, isLoading, error } = useSubscriberOffers();

        expect(offers.value).toEqual([]);
        expect(isLoading.value).toBe(true);
        expect(error.value).toBeNull();
    });

    describe('fetchOffers()', () => {
        it('populates offers from a paginated payload on success', async () => {
            offerService.list.mockResolvedValue({ data: { data: { data: [{ id: 1 }, { id: 2 }] } } });

            const { fetchOffers, offers, isLoading, error } = useSubscriberOffers();
            await fetchOffers();

            expect(offerService.list).toHaveBeenCalledWith({ per_page: 20 });
            expect(offers.value).toEqual([{ id: 1 }, { id: 2 }]);
            expect(isLoading.value).toBe(false);
            expect(error.value).toBeNull();
        });

        it('accepts a non-paginated (raw array) payload', async () => {
            offerService.list.mockResolvedValue({ data: { data: [{ id: 3 }] } });

            const { fetchOffers, offers } = useSubscriberOffers();
            await fetchOffers();

            expect(offers.value).toEqual([{ id: 3 }]);
        });

        it('handles an empty offers list without throwing', async () => {
            offerService.list.mockResolvedValue({ data: { data: { data: [] } } });

            const { fetchOffers, offers } = useSubscriberOffers();
            await fetchOffers();

            expect(offers.value).toEqual([]);
        });

        it('reshapes a rejection into error.value using the translated fallback message', async () => {
            offerService.list.mockRejectedValue({ message: 'Network Error' });

            const { fetchOffers, error, isLoading } = useSubscriberOffers();
            await fetchOffers();

            expect(error.value).toBe('تعذر تحميل العروض');
            expect(isLoading.value).toBe(false);
        });

        it('reshapes a 422 response into error.value using the backend message', async () => {
            offerService.list.mockRejectedValue({ response: { status: 422, data: { message: 'خطأ تحقق' } } });

            const { fetchOffers, error } = useSubscriberOffers();
            await fetchOffers();

            expect(error.value).toBe('خطأ تحقق');
        });
    });
});
