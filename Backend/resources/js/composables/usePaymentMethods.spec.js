import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/paymentMethodService', () => ({
    default: {
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
            owner_settings: {
                financial: {
                    load_error: 'تعذر تحميل طرق الدفع',
                    save_error: 'تعذر حفظ طريقة الدفع',
                    delete_error: 'تعذر حذف طريقة الدفع',
                },
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { usePaymentMethods } = await import('./usePaymentMethods');
const paymentMethodService = (await import('@/services/paymentMethodService')).default;

describe('usePaymentMethods', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with empty methods and isLoading false (no default load-on-mount)', () => {
        const { methods, isLoading, error, isSaving, saveError, isDeleting, deleteError, deletingId } = usePaymentMethods();

        expect(methods.value).toEqual([]);
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(isSaving.value).toBe(false);
        expect(saveError.value).toBeNull();
        expect(isDeleting.value).toBe(false);
        expect(deleteError.value).toBeNull();
        expect(deletingId.value).toBeNull();
    });

    describe('fetchMethods()', () => {
        it('unwraps a paginated payload (payload.data)', async () => {
            paymentMethodService.list.mockResolvedValue({
                data: { data: { data: [{ id: 1, type: 'bank' }], meta: { total: 1 } } },
            });

            const { fetchMethods, methods, isLoading } = usePaymentMethods();
            await fetchMethods();

            expect(methods.value).toEqual([{ id: 1, type: 'bank' }]);
            expect(isLoading.value).toBe(false);
        });

        it('falls back to a flat array payload when there is no nested .data', async () => {
            paymentMethodService.list.mockResolvedValue({
                data: { data: [{ id: 2, type: 'cash' }] },
            });

            const { fetchMethods, methods } = usePaymentMethods();
            await fetchMethods();

            expect(methods.value).toEqual([{ id: 2, type: 'cash' }]);
        });

        it('forwards params through to the service', async () => {
            paymentMethodService.list.mockResolvedValue({ data: { data: [] } });

            const { fetchMethods } = usePaymentMethods();
            await fetchMethods({ type: 'wallet' });

            expect(paymentMethodService.list).toHaveBeenCalledWith({ type: 'wallet' });
        });

        it('sets the translated fallback message on a network error', async () => {
            paymentMethodService.list.mockRejectedValue({ message: 'Network Error' });

            const { fetchMethods, error, isLoading } = usePaymentMethods();
            await fetchMethods();

            expect(error.value).toBe('تعذر تحميل طرق الدفع');
            expect(isLoading.value).toBe(false);
        });
    });

    describe('createMethod()', () => {
        it('returns true on success', async () => {
            paymentMethodService.create.mockResolvedValue({});

            const { createMethod, isSaving, saveError } = usePaymentMethods();
            const result = await createMethod({ type: 'bank', label: 'Bank A' });

            expect(result).toBe(true);
            expect(isSaving.value).toBe(false);
            expect(saveError.value).toBeNull();
        });

        it('stores the raw 422 response payload (not normalizeApiError-reshaped) on validation failure', async () => {
            paymentMethodService.create.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { label: ['الاسم مطلوب.'] } } },
            });

            const { createMethod, saveError } = usePaymentMethods();
            const result = await createMethod({});

            expect(result).toBe(false);
            expect(saveError.value).toEqual({ message: 'Invalid.', errors: { label: ['الاسم مطلوب.'] } });
        });

        it('falls back to a bare { message } object (no errors key) on a network error', async () => {
            paymentMethodService.create.mockRejectedValue({ message: 'Network Error' });

            const { createMethod, saveError } = usePaymentMethods();
            await createMethod({});

            expect(saveError.value).toEqual({ message: 'تعذر حفظ طريقة الدفع' });
        });
    });

    describe('updateMethod()', () => {
        it('returns true and calls the service with id/payload on success', async () => {
            paymentMethodService.update.mockResolvedValue({});

            const { updateMethod, saveError } = usePaymentMethods();
            const result = await updateMethod(7, { label: 'Updated' });

            expect(result).toBe(true);
            expect(paymentMethodService.update).toHaveBeenCalledWith(7, { label: 'Updated' });
            expect(saveError.value).toBeNull();
        });

        it('stores the raw response payload on failure', async () => {
            paymentMethodService.update.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { label: ['x'] } } },
            });

            const { updateMethod, saveError } = usePaymentMethods();
            const result = await updateMethod(7, {});

            expect(result).toBe(false);
            expect(saveError.value).toEqual({ message: 'Invalid.', errors: { label: ['x'] } });
        });
    });

    describe('deleteMethod()', () => {
        it('tracks deletingId while the request is in flight, then clears it on success', async () => {
            let resolveFn;
            paymentMethodService.destroy.mockReturnValue(
                new Promise((resolve) => {
                    resolveFn = resolve;
                }),
            );

            const { deleteMethod, isDeleting, deletingId } = usePaymentMethods();
            const pending = deleteMethod(9);

            expect(isDeleting.value).toBe(true);
            expect(deletingId.value).toBe(9);

            resolveFn();
            const result = await pending;

            expect(result).toBe(true);
            expect(isDeleting.value).toBe(false);
            expect(deletingId.value).toBeNull();
        });

        it('reshapes a network error to the translated fallback message (normalizeApiError) and clears deletingId', async () => {
            paymentMethodService.destroy.mockRejectedValue({ message: 'Network Error' });

            const { deleteMethod, deleteError, deletingId } = usePaymentMethods();
            const result = await deleteMethod(3);

            expect(result).toBe(false);
            expect(deleteError.value).toBe('تعذر حذف طريقة الدفع');
            expect(deletingId.value).toBeNull();
        });

        it('surfaces the backend message on a non-network failure (normalizeApiError)', async () => {
            paymentMethodService.destroy.mockRejectedValue({
                response: { status: 409, data: { message: 'لا يمكن حذف طريقة الدفع المستخدمة.' } },
            });

            const { deleteMethod, deleteError } = usePaymentMethods();
            await deleteMethod(3);

            expect(deleteError.value).toBe('لا يمكن حذف طريقة الدفع المستخدمة.');
        });
    });
});
