import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/ownerRatingService', () => ({
    default: {
        listForOwner: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_ratings: {
                load_error: 'تعذر تحميل التقييمات',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useOwnerRatings } = await import('./useOwnerRatings');
const ownerRatingService = (await import('@/services/ownerRatingService')).default;

describe('useOwnerRatings', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with empty ratings, null average, zero count and not loading', () => {
        const { ratings, averageRating, ratingsCount, isLoading, error } = useOwnerRatings();

        expect(ratings.value).toEqual([]);
        expect(averageRating.value).toBeNull();
        expect(ratingsCount.value).toBe(0);
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(ownerRatingService.listForOwner).not.toHaveBeenCalled();
    });

    it('does nothing when ownerId is falsy (no request made, no loading toggled)', async () => {
        const { fetchRatings, isLoading } = useOwnerRatings();

        await fetchRatings(null);

        expect(ownerRatingService.listForOwner).not.toHaveBeenCalled();
        expect(isLoading.value).toBe(false);
    });

    it('fetchRatings populates ratings/averageRating/ratingsCount from the payload on success', async () => {
        ownerRatingService.listForOwner.mockResolvedValue({
            data: {
                data: {
                    ratings: { data: [{ id: 1, stars: 5 }, { id: 2, stars: 4 }] },
                    average_rating: 4.5,
                    ratings_count: 2,
                },
            },
        });

        const { fetchRatings, ratings, averageRating, ratingsCount, isLoading } = useOwnerRatings();
        await fetchRatings(7, { page: 2 });

        expect(ownerRatingService.listForOwner).toHaveBeenCalledWith(7, { page: 2 });
        expect(ratings.value).toEqual([{ id: 1, stars: 5 }, { id: 2, stars: 4 }]);
        expect(averageRating.value).toBe(4.5);
        expect(ratingsCount.value).toBe(2);
        expect(isLoading.value).toBe(false);
    });

    it('defaults ratings to an empty array and average/count to null/0 when the payload omits them', async () => {
        ownerRatingService.listForOwner.mockResolvedValue({ data: { data: {} } });

        const { fetchRatings, ratings, averageRating, ratingsCount } = useOwnerRatings();
        await fetchRatings(7);

        expect(ratings.value).toEqual([]);
        expect(averageRating.value).toBeNull();
        expect(ratingsCount.value).toBe(0);
    });

    it('reshapes a network error into the translated fallback message', async () => {
        ownerRatingService.listForOwner.mockRejectedValue({ message: 'Network Error' });

        const { fetchRatings, error, isLoading } = useOwnerRatings();
        await fetchRatings(7);

        expect(error.value).toBe('تعذر تحميل التقييمات');
        expect(isLoading.value).toBe(false);
    });

    it('reshapes a backend error response into its own message', async () => {
        ownerRatingService.listForOwner.mockRejectedValue({
            response: { status: 500, data: { message: 'خطأ في الخادم' } },
        });

        const { fetchRatings, error } = useOwnerRatings();
        await fetchRatings(7);

        expect(error.value).toBe('خطأ في الخادم');
    });
});
