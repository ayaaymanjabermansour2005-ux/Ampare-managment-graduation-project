import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/paymentMethodService', () => ({
    default: {
        list: vi.fn(),
        destroy: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            payment_methods_page: {
                load_error: 'تعذر تحميل وسائل الدفع',
                delete_error: 'تعذر حذف وسيلة الدفع',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminPaymentMethods } = await import('./useAdminPaymentMethods');
const paymentMethodService = (await import('@/services/paymentMethodService')).default;

function samplePage(items, meta = {}) {
    return {
        data: { data: { data: items, meta: { current_page: 1, last_page: 1, total: items.length, per_page: 15, ...meta } } },
    };
}

describe('useAdminPaymentMethods', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with default reactive state (isLoading true initially)', () => {
        const { methods, pagination, isLoading, error, search, deletingId, deleteError } = useAdminPaymentMethods();

        expect(methods.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(true);
        expect(error.value).toBeNull();
        expect(search.value).toBe('');
        expect(deletingId.value).toBeNull();
        expect(deleteError.value).toBeNull();
    });

    it('fetchMethods populates methods and pagination on success', async () => {
        paymentMethodService.list.mockResolvedValue(samplePage([{ id: 1 }], { total: 1 }));

        const { fetchMethods, methods, pagination, isLoading } = useAdminPaymentMethods();
        await fetchMethods(1);

        expect(paymentMethodService.list).toHaveBeenCalledWith({ page: 1, search: undefined });
        expect(methods.value).toEqual([{ id: 1 }]);
        expect(pagination.value.total).toBe(1);
        expect(isLoading.value).toBe(false);
    });

    it('fetchMethods sends the search term when set', async () => {
        paymentMethodService.list.mockResolvedValue(samplePage([]));
        const { fetchMethods, search } = useAdminPaymentMethods();

        search.value = 'Visa';
        await fetchMethods(2);

        expect(paymentMethodService.list).toHaveBeenCalledWith({ page: 2, search: 'Visa' });
    });

    it('fetchMethods handles an empty result list', async () => {
        paymentMethodService.list.mockResolvedValue(samplePage([], { total: 0 }));
        const { fetchMethods, methods } = useAdminPaymentMethods();

        await fetchMethods();

        expect(methods.value).toEqual([]);
    });

    it('fetchMethods sets a translated error message via normalizeApiError on network failure', async () => {
        paymentMethodService.list.mockRejectedValue({ message: 'Network Error' });
        const { fetchMethods, error } = useAdminPaymentMethods();

        await fetchMethods();

        expect(error.value).toBe('تعذر تحميل وسائل الدفع');
    });

    it('onSearchInput debounces fetchMethods by 300ms', async () => {
        vi.useFakeTimers();
        paymentMethodService.list.mockResolvedValue(samplePage([]));
        const { onSearchInput } = useAdminPaymentMethods();

        onSearchInput();
        onSearchInput();
        expect(paymentMethodService.list).not.toHaveBeenCalled();

        await vi.advanceTimersByTimeAsync(300);
        expect(paymentMethodService.list).toHaveBeenCalledTimes(1);
        vi.useRealTimers();
    });

    it('deleteMethod removes it by refetching the current page on success', async () => {
        paymentMethodService.destroy.mockResolvedValue({});
        paymentMethodService.list.mockResolvedValue(samplePage([{ id: 2 }], { current_page: 1, total: 1 }));
        const { deleteMethod, methods, deletingId } = useAdminPaymentMethods();

        const result = await deleteMethod(1);

        expect(result).toBe(true);
        expect(paymentMethodService.destroy).toHaveBeenCalledWith(1);
        expect(methods.value).toEqual([{ id: 2 }]);
        expect(deletingId.value).toBeNull();
    });

    it('deleteMethod sets deleteError via normalizeApiError on failure', async () => {
        paymentMethodService.destroy.mockRejectedValue({
            response: { status: 422, data: { message: 'لا يمكن الحذف', errors: {} } },
        });
        const { deleteMethod, deleteError } = useAdminPaymentMethods();

        const result = await deleteMethod(1);

        expect(result).toBe(false);
        expect(deleteError.value).toBe('لا يمكن الحذف');
    });

    it('deleteMethod falls back to the translated message on a network error', async () => {
        paymentMethodService.destroy.mockRejectedValue({ message: 'Network Error' });
        const { deleteMethod, deleteError } = useAdminPaymentMethods();

        await deleteMethod(1);

        expect(deleteError.value).toBe('تعذر حذف وسيلة الدفع');
    });
});
