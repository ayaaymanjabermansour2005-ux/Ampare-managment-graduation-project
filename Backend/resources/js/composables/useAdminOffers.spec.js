import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/offerService', () => ({
    default: {
        list: vi.fn(),
        cancel: vi.fn(),
        destroy: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            offers_page: {
                load_error: 'تعذر تحميل العروض',
                cancel_error: 'تعذر إلغاء العرض',
                delete_error: 'تعذر حذف العرض',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminOffers } = await import('./useAdminOffers');
const offerService = (await import('@/services/offerService')).default;

function samplePage(items, meta = {}) {
    return {
        data: { data: { data: items, meta: { current_page: 1, last_page: 1, total: items.length, per_page: 15, ...meta } } },
    };
}

describe('useAdminOffers', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with default reactive state, including includeExpired defaulting to true', () => {
        const { offers, pagination, isLoading, error, search, includeExpired, cancellingId, deletingId } = useAdminOffers();

        expect(offers.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(true);
        expect(error.value).toBeNull();
        expect(search.value).toBe('');
        expect(includeExpired.value).toBe(true);
        expect(cancellingId.value).toBeNull();
        expect(deletingId.value).toBeNull();
    });

    it('fetchOffers populates offers and pagination, sending include_expired as 1 by default', async () => {
        offerService.list.mockResolvedValue(samplePage([{ id: 1 }], { total: 1 }));

        const { fetchOffers, offers, pagination } = useAdminOffers();
        await fetchOffers(1);

        expect(offerService.list).toHaveBeenCalledWith({ page: 1, search: undefined, include_expired: 1 });
        expect(offers.value).toEqual([{ id: 1 }]);
        expect(pagination.value.total).toBe(1);
    });

    it('toggling includeExpired off sends include_expired as undefined on the next fetch', async () => {
        offerService.list.mockResolvedValue(samplePage([]));
        const { fetchOffers, includeExpired } = useAdminOffers();

        includeExpired.value = false;
        await fetchOffers(1);

        expect(offerService.list).toHaveBeenCalledWith({ page: 1, search: undefined, include_expired: undefined });
    });

    it('fetchOffers handles an empty result list', async () => {
        offerService.list.mockResolvedValue(samplePage([], { total: 0 }));
        const { fetchOffers, offers } = useAdminOffers();

        await fetchOffers();

        expect(offers.value).toEqual([]);
    });

    it('fetchOffers sets a translated error message via normalizeApiError on network failure', async () => {
        offerService.list.mockRejectedValue({ message: 'Network Error' });
        const { fetchOffers, error } = useAdminOffers();

        await fetchOffers();

        expect(error.value).toBe('تعذر تحميل العروض');
    });

    it('onSearchInput debounces fetchOffers by 300ms', async () => {
        vi.useFakeTimers();
        offerService.list.mockResolvedValue(samplePage([]));
        const { onSearchInput } = useAdminOffers();

        onSearchInput();
        onSearchInput();
        expect(offerService.list).not.toHaveBeenCalled();

        await vi.advanceTimersByTimeAsync(300);
        expect(offerService.list).toHaveBeenCalledTimes(1);
        vi.useRealTimers();
    });

    it('cancelOffer replaces the offer in the list on success', async () => {
        offerService.cancel.mockResolvedValue({ data: { data: { id: 1, status: 'cancelled' } } });
        const { cancelOffer, offers, cancellingId } = useAdminOffers();
        offers.value = [{ id: 1, status: 'active' }];

        const result = await cancelOffer(1);

        expect(result).toBe(true);
        expect(offers.value[0]).toEqual({ id: 1, status: 'cancelled' });
        expect(cancellingId.value).toBeNull();
    });

    it('cancelOffer sets cancelError via normalizeApiError on failure', async () => {
        offerService.cancel.mockRejectedValue({ message: 'Network Error' });
        const { cancelOffer, cancelError } = useAdminOffers();

        const result = await cancelOffer(1);

        expect(result).toBe(false);
        expect(cancelError.value).toBe('تعذر إلغاء العرض');
    });

    it('deleteOffer removes it by refetching the current page on success', async () => {
        offerService.destroy.mockResolvedValue({});
        offerService.list.mockResolvedValue(samplePage([{ id: 2 }], { current_page: 1, total: 1 }));
        const { deleteOffer, offers, deletingId } = useAdminOffers();

        const result = await deleteOffer(1);

        expect(result).toBe(true);
        expect(offerService.destroy).toHaveBeenCalledWith(1);
        expect(offers.value).toEqual([{ id: 2 }]);
        expect(deletingId.value).toBeNull();
    });

    it('deleteOffer sets deleteError via normalizeApiError on failure', async () => {
        offerService.destroy.mockRejectedValue({
            response: { status: 422, data: { message: 'لا يمكن الحذف', errors: {} } },
        });
        const { deleteOffer, deleteError } = useAdminOffers();

        const result = await deleteOffer(1);

        expect(result).toBe(false);
        expect(deleteError.value).toBe('لا يمكن الحذف');
    });
});
