import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/offerService', () => ({
    default: {
        list: vi.fn(),
        create: vi.fn(),
        update: vi.fn(),
        cancel: vi.fn(),
    },
}));
vi.mock('@/services/subscriptionService', () => ({
    default: {
        list: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_offers: {
                load_error: 'تعذر تحميل العروض',
                create_error: 'تعذر إنشاء العرض',
                update_error: 'تعذر تحديث العرض',
                cancel_error: 'تعذر إلغاء العرض',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useOwnerOffers } = await import('./useOwnerOffers');
const offerService = (await import('@/services/offerService')).default;
const subscriptionService = (await import('@/services/subscriptionService')).default;

describe('useOwnerOffers', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('has the expected default reactive state (isLoading starts true)', () => {
        const { offers, pagination, isLoading, error, search, mySubscribers, isSaving, saveError, cancellingId, cancelError } =
            useOwnerOffers();

        expect(offers.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(true);
        expect(error.value).toBeNull();
        expect(search.value).toBe('');
        expect(mySubscribers.value).toEqual([]);
        expect(isSaving.value).toBe(false);
        expect(saveError.value).toBeNull();
        expect(cancellingId.value).toBeNull();
        expect(cancelError.value).toBeNull();
    });

    describe('fetchOffers', () => {
        it('populates offers/pagination from a paginated response', async () => {
            offerService.list.mockResolvedValue({
                data: {
                    data: {
                        data: [{ id: 1, title: 'Discount' }],
                        meta: { current_page: 1, last_page: 4, total: 40, per_page: 10 },
                    },
                },
            });

            const { fetchOffers, offers, pagination, isLoading } = useOwnerOffers();
            await fetchOffers();

            expect(offerService.list).toHaveBeenCalledWith({ page: 1, search: undefined });
            expect(offers.value).toEqual([{ id: 1, title: 'Discount' }]);
            expect(pagination.value).toEqual({ current_page: 1, last_page: 4, total: 40, per_page: 10 });
            expect(isLoading.value).toBe(false);
        });

        it('sets a translated error on failure', async () => {
            offerService.list.mockRejectedValue({ message: 'Network Error' });

            const { fetchOffers, error } = useOwnerOffers();
            await fetchOffers();

            expect(error.value).toBe('تعذر تحميل العروض');
        });
    });

    describe('onSearchInput', () => {
        it('debounces to a single fetchOffers call', async () => {
            vi.useFakeTimers();
            offerService.list.mockResolvedValue({ data: { data: [] } });

            const { onSearchInput } = useOwnerOffers();
            onSearchInput();
            onSearchInput();

            expect(offerService.list).not.toHaveBeenCalled();
            await vi.advanceTimersByTimeAsync(300);

            expect(offerService.list).toHaveBeenCalledTimes(1);
        });
    });

    describe('loadMySubscribers', () => {
        it('deduplicates subscribers by id', async () => {
            subscriptionService.list.mockResolvedValue({
                data: {
                    data: {
                        data: [
                            { id: 1, subscriber: { id: 100, name: 'Ali' } },
                            { id: 2, subscriber: { id: 100, name: 'Ali' } },
                            { id: 3, subscriber: { id: 200, name: 'Sara' } },
                            { id: 4, subscriber: null },
                        ],
                    },
                },
            });

            const { loadMySubscribers, mySubscribers } = useOwnerOffers();
            await loadMySubscribers();

            expect(subscriptionService.list).toHaveBeenCalledWith({ per_page: 100 });
            expect(mySubscribers.value).toEqual([
                { id: 100, name: 'Ali' },
                { id: 200, name: 'Sara' },
            ]);
        });

        it('propagates a rejection (no internal error handling)', async () => {
            subscriptionService.list.mockRejectedValue(new Error('boom'));

            const { loadMySubscribers } = useOwnerOffers();

            await expect(loadMySubscribers()).rejects.toThrow('boom');
        });
    });

    describe('createOffer', () => {
        it('creates the offer, refetches page 1, and returns true on success', async () => {
            offerService.create.mockResolvedValue({});
            offerService.list.mockResolvedValue({ data: { data: [{ id: 9 }] } });

            const { createOffer, offers, isSaving, saveError } = useOwnerOffers();
            const result = await createOffer({ title: 'New offer' });

            expect(offerService.create).toHaveBeenCalledWith({ title: 'New offer' });
            expect(offerService.list).toHaveBeenCalledWith({ page: 1, search: undefined });
            expect(result).toBe(true);
            expect(offers.value).toEqual([{ id: 9 }]);
            expect(isSaving.value).toBe(false);
            expect(saveError.value).toBeNull();
        });

        it('stores the raw 422 response payload as saveError (not normalizeApiError)', async () => {
            offerService.create.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { title: ['required'] } } },
            });

            const { createOffer, saveError } = useOwnerOffers();
            const result = await createOffer({});

            expect(result).toBe(false);
            expect(saveError.value).toEqual({ message: 'Invalid.', errors: { title: ['required'] } });
        });

        it('falls back to a translated message object on a network error', async () => {
            offerService.create.mockRejectedValue({ message: 'Network Error' });

            const { createOffer, saveError } = useOwnerOffers();
            await createOffer({});

            expect(saveError.value).toEqual({ message: 'تعذر إنشاء العرض' });
        });
    });

    describe('updateOffer', () => {
        it('replaces the matching offer in the list and returns true', async () => {
            offerService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, title: 'Old' }] } } });
            offerService.update.mockResolvedValue({ data: { data: { id: 1, title: 'New' } } });

            const { fetchOffers, updateOffer, offers } = useOwnerOffers();
            await fetchOffers();
            const result = await updateOffer(1, { title: 'New' });

            expect(offerService.update).toHaveBeenCalledWith(1, { title: 'New' });
            expect(result).toBe(true);
            expect(offers.value[0]).toEqual({ id: 1, title: 'New' });
        });

        it('stores the raw 422 response payload as saveError (not normalizeApiError)', async () => {
            offerService.update.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { title: ['required'] } } },
            });

            const { updateOffer, saveError } = useOwnerOffers();
            const result = await updateOffer(1, {});

            expect(result).toBe(false);
            expect(saveError.value).toEqual({ message: 'Invalid.', errors: { title: ['required'] } });
        });

        it('falls back to a translated message object on a network error', async () => {
            offerService.update.mockRejectedValue({ message: 'Network Error' });

            const { updateOffer, saveError } = useOwnerOffers();
            await updateOffer(1, {});

            expect(saveError.value).toEqual({ message: 'تعذر تحديث العرض' });
        });
    });

    describe('cancelOffer', () => {
        it('replaces the matching offer in the list and returns true', async () => {
            offerService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, status: 'active' }] } } });
            offerService.cancel.mockResolvedValue({ data: { data: { id: 1, status: 'cancelled' } } });

            const { fetchOffers, cancelOffer, offers, cancellingId, cancelError } = useOwnerOffers();
            await fetchOffers();
            const result = await cancelOffer(1);

            expect(offerService.cancel).toHaveBeenCalledWith(1);
            expect(result).toBe(true);
            expect(offers.value[0]).toEqual({ id: 1, status: 'cancelled' });
            expect(cancellingId.value).toBeNull();
            expect(cancelError.value).toBeNull();
        });

        it('sets a translated error (via normalizeApiError) and returns false on failure', async () => {
            offerService.cancel.mockRejectedValue({ message: 'Network Error' });

            const { cancelOffer, cancelError } = useOwnerOffers();
            const result = await cancelOffer(1);

            expect(result).toBe(false);
            expect(cancelError.value).toBe('تعذر إلغاء العرض');
        });
    });
});
