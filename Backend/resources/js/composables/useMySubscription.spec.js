import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/subscriptionService', () => ({
    default: {
        list: vi.fn(),
        downloadContractPdfUrl: vi.fn((id) => `/api/v1/subscriptions/${id}/contract-pdf?lang=ar`),
    },
}));
vi.mock('@/services/ownerRatingService', () => ({
    default: {
        submit: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            my_subscription_page: {
                load_error: 'تعذر تحميل الاشتراك',
                rating_submit_error: 'تعذر إرسال التقييم',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useMySubscription } = await import('./useMySubscription');
const subscriptionService = (await import('@/services/subscriptionService')).default;
const ownerRatingService = (await import('@/services/ownerRatingService')).default;

describe('useMySubscription', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('has the correct default reactive state', () => {
        const { subscription, isLoadingInitial, initialError, hasActiveOrPendingSubscription, contractDownloadUrl, canRateOwner, ratingForm, isSubmittingRating, ratingSubmitted, ratingError } = useMySubscription();

        expect(subscription.value).toBeNull();
        expect(isLoadingInitial.value).toBe(true);
        expect(initialError.value).toBeNull();
        expect(hasActiveOrPendingSubscription.value).toBeFalsy();
        expect(contractDownloadUrl.value).toBeNull();
        expect(canRateOwner.value).toBeFalsy();
        expect(ratingForm).toEqual({ rating: 5, comment: '' });
        expect(isSubmittingRating.value).toBe(false);
        expect(ratingSubmitted.value).toBe(false);
        expect(ratingError.value).toBeNull();
    });

    describe('loadInitial()', () => {
        it('populates subscription from the first item on success', async () => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, status: 'active' }] } } });

            const { loadInitial, subscription, isLoadingInitial, initialError } = useMySubscription();
            await loadInitial();

            expect(subscriptionService.list).toHaveBeenCalledWith({ per_page: 1 });
            expect(subscription.value).toEqual({ id: 1, status: 'active' });
            expect(isLoadingInitial.value).toBe(false);
            expect(initialError.value).toBeNull();
        });

        it('sets subscription to null when there is no subscription', async () => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [] } } });

            const { loadInitial, subscription } = useMySubscription();
            await loadInitial();

            expect(subscription.value).toBeNull();
        });

        it('reshapes a rejection into initialError.value using the translated fallback message', async () => {
            subscriptionService.list.mockRejectedValue({ message: 'Network Error' });

            const { loadInitial, initialError, isLoadingInitial } = useMySubscription();
            await loadInitial();

            expect(initialError.value).toBe('تعذر تحميل الاشتراك');
            expect(isLoadingInitial.value).toBe(false);
        });
    });

    describe('hasActiveOrPendingSubscription', () => {
        it.each(['pending', 'active', 'suspended'])('is true for status "%s"', async (status) => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, status }] } } });
            const { loadInitial, hasActiveOrPendingSubscription } = useMySubscription();
            await loadInitial();

            expect(hasActiveOrPendingSubscription.value).toBe(true);
        });

        it.each(['cancelled', 'rejected'])('is falsy for status "%s"', async (status) => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, status }] } } });
            const { loadInitial, hasActiveOrPendingSubscription } = useMySubscription();
            await loadInitial();

            expect(hasActiveOrPendingSubscription.value).toBeFalsy();
        });
    });

    describe('canRateOwner', () => {
        it.each(['active', 'suspended', 'cancelled'])('is true for status "%s"', async (status) => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, status }] } } });
            const { loadInitial, canRateOwner } = useMySubscription();
            await loadInitial();

            expect(canRateOwner.value).toBe(true);
        });

        it('is falsy for a pending subscription (must have actually started before rating the owner)', async () => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, status: 'pending' }] } } });
            const { loadInitial, canRateOwner } = useMySubscription();
            await loadInitial();

            expect(canRateOwner.value).toBeFalsy();
        });
    });

    describe('contractDownloadUrl', () => {
        it('is null when there is no subscription', () => {
            const { contractDownloadUrl } = useMySubscription();
            expect(contractDownloadUrl.value).toBeNull();
            expect(subscriptionService.downloadContractPdfUrl).not.toHaveBeenCalled();
        });

        it('delegates to subscriptionService.downloadContractPdfUrl once a subscription is loaded', async () => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [{ id: 42, status: 'active' }] } } });
            const { loadInitial, contractDownloadUrl } = useMySubscription();
            await loadInitial();

            expect(contractDownloadUrl.value).toBe('/api/v1/subscriptions/42/contract-pdf?lang=ar');
            expect(subscriptionService.downloadContractPdfUrl).toHaveBeenCalledWith(42);
        });
    });

    describe('submitOwnerRating()', () => {
        it('returns false immediately without calling the service when there is no subscription loaded', async () => {
            const { submitOwnerRating, isSubmittingRating } = useMySubscription();
            const result = await submitOwnerRating();

            expect(result).toBe(false);
            expect(ownerRatingService.submit).not.toHaveBeenCalled();
            expect(isSubmittingRating.value).toBe(false);
        });

        it('submits the rating/comment for the loaded subscription and sets ratingSubmitted on success', async () => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [{ id: 42, status: 'active' }] } } });
            ownerRatingService.submit.mockResolvedValue({ data: {} });

            const { loadInitial, submitOwnerRating, ratingForm, ratingSubmitted, isSubmittingRating } = useMySubscription();
            await loadInitial();
            ratingForm.rating = 4;
            ratingForm.comment = 'ممتاز';
            const result = await submitOwnerRating();

            expect(result).toBe(true);
            expect(ownerRatingService.submit).toHaveBeenCalledWith(42, { rating: 4, comment: 'ممتاز' });
            expect(ratingSubmitted.value).toBe(true);
            expect(isSubmittingRating.value).toBe(false);
        });

        it('sends comment as undefined when the comment field is empty', async () => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [{ id: 42, status: 'active' }] } } });
            ownerRatingService.submit.mockResolvedValue({ data: {} });

            const { loadInitial, submitOwnerRating, ratingForm } = useMySubscription();
            await loadInitial();
            ratingForm.comment = '';
            await submitOwnerRating();

            expect(ownerRatingService.submit).toHaveBeenCalledWith(42, { rating: 5, comment: undefined });
        });

        it('prioritizes the "subscription" field error over the generic message', async () => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [{ id: 42, status: 'active' }] } } });
            ownerRatingService.submit.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { subscription: ['لقد قيّمت هذا الاشتراك من قبل.'] } } },
            });

            const { loadInitial, submitOwnerRating, ratingError, ratingSubmitted } = useMySubscription();
            await loadInitial();
            const result = await submitOwnerRating();

            expect(result).toBe(false);
            expect(ratingError.value).toBe('لقد قيّمت هذا الاشتراك من قبل.');
            expect(ratingSubmitted.value).toBe(false);
        });

        it('falls back to the "rating" field error when there is no "subscription" field error', async () => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [{ id: 42, status: 'active' }] } } });
            ownerRatingService.submit.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { rating: ['التقييم مطلوب.'] } } },
            });

            const { loadInitial, submitOwnerRating, ratingError } = useMySubscription();
            await loadInitial();
            await submitOwnerRating();

            expect(ratingError.value).toBe('التقييم مطلوب.');
        });

        it('falls back to the translated generic message for a network error', async () => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [{ id: 42, status: 'active' }] } } });
            ownerRatingService.submit.mockRejectedValue({ message: 'Network Error' });

            const { loadInitial, submitOwnerRating, ratingError, isSubmittingRating } = useMySubscription();
            await loadInitial();
            const result = await submitOwnerRating();

            expect(result).toBe(false);
            expect(ratingError.value).toBe('تعذر إرسال التقييم');
            expect(isSubmittingRating.value).toBe(false);
        });
    });
});
