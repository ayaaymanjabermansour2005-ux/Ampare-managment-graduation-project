import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/faultService', () => ({
    default: {
        list: vi.fn(),
        create: vi.fn(),
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
            support_center_page: {
                faults_load_error: 'تعذر تحميل الأعطال',
                fault_submit_error: 'تعذر إرسال العطل',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useSubscriberFaults } = await import('./useSubscriberFaults');
const faultService = (await import('@/services/faultService')).default;
const subscriptionService = (await import('@/services/subscriptionService')).default;

describe('useSubscriberFaults', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('has the correct default reactive state', () => {
        const { faults, isLoading, error, myGeneratorId, myGeneratorName, isSubmitting, submitError } = useSubscriberFaults();

        expect(faults.value).toEqual([]);
        expect(isLoading.value).toBe(true);
        expect(error.value).toBeNull();
        expect(myGeneratorId.value).toBeNull();
        expect(myGeneratorName.value).toBeNull();
        expect(isSubmitting.value).toBe(false);
        expect(submitError.value).toBeNull();
    });

    describe('fetchFaults()', () => {
        it('populates faults from a paginated payload on success', async () => {
            faultService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, status: 'pending_verification' }] } } });

            const { fetchFaults, faults, isLoading, error } = useSubscriberFaults();
            await fetchFaults();

            expect(faults.value).toEqual([{ id: 1, status: 'pending_verification' }]);
            expect(isLoading.value).toBe(false);
            expect(error.value).toBeNull();
            expect(faultService.list).toHaveBeenCalledWith({ per_page: 30 });
        });

        it('accepts a non-paginated (raw array) payload', async () => {
            faultService.list.mockResolvedValue({ data: { data: [{ id: 2 }] } });

            const { fetchFaults, faults } = useSubscriberFaults();
            await fetchFaults();

            expect(faults.value).toEqual([{ id: 2 }]);
        });

        it('handles an empty faults list without throwing', async () => {
            faultService.list.mockResolvedValue({ data: { data: { data: [] } } });

            const { fetchFaults, faults } = useSubscriberFaults();
            await fetchFaults();

            expect(faults.value).toEqual([]);
        });

        it('reshapes a rejection into error.value using the translated fallback message', async () => {
            faultService.list.mockRejectedValue({ message: 'Network Error' });

            const { fetchFaults, error, isLoading } = useSubscriberFaults();
            await fetchFaults();

            expect(error.value).toBe('تعذر تحميل الأعطال');
            expect(isLoading.value).toBe(false);
        });

        it('reshapes a 422 response into error.value using the backend message', async () => {
            faultService.list.mockRejectedValue({ response: { status: 422, data: { message: 'خطأ تحقق' } } });

            const { fetchFaults, error } = useSubscriberFaults();
            await fetchFaults();

            expect(error.value).toBe('خطأ تحقق');
        });
    });

    describe('loadMyGenerator()', () => {
        it('sets myGeneratorId/myGeneratorName from the first active subscription', async () => {
            subscriptionService.list.mockResolvedValue({
                data: { data: { data: [{ id: 1, generator: { id: 55, name: 'مولد أ' } }] } },
            });

            const { loadMyGenerator, myGeneratorId, myGeneratorName } = useSubscriberFaults();
            await loadMyGenerator();

            expect(myGeneratorId.value).toBe(55);
            expect(myGeneratorName.value).toBe('مولد أ');
            expect(subscriptionService.list).toHaveBeenCalledWith({ per_page: 1 });
        });

        it('leaves myGeneratorId/myGeneratorName untouched when there is no subscription', async () => {
            subscriptionService.list.mockResolvedValue({ data: { data: { data: [] } } });

            const { loadMyGenerator, myGeneratorId, myGeneratorName } = useSubscriberFaults();
            await loadMyGenerator();

            expect(myGeneratorId.value).toBeNull();
            expect(myGeneratorName.value).toBeNull();
        });

        // loadMyGenerator swallows a rejection (leaving myGeneratorId/Name at their
        // previous value) rather than propagating it — SupportCenterView.vue chains
        // `.then(() => faults.fetchFaults())` after this call with no `.catch()`, so a
        // rejection here must never block fetchFaults() from running.
        it('does not reject on failure, so a caller can safely chain .then() after it', async () => {
            subscriptionService.list.mockRejectedValue(new Error('Request failed'));

            const { loadMyGenerator, myGeneratorId, myGeneratorName } = useSubscriberFaults();

            await expect(loadMyGenerator()).resolves.toBeUndefined();
            expect(myGeneratorId.value).toBeNull();
            expect(myGeneratorName.value).toBeNull();
        });
    });

    describe('reportFault()', () => {
        it('submits with the loaded generator_id, unshifts the new fault, and returns true', async () => {
            subscriptionService.list.mockResolvedValue({
                data: { data: { data: [{ id: 1, generator: { id: 55, name: 'مولد أ' } }] } },
            });
            faultService.create.mockResolvedValue({ data: { data: { id: 200, description: 'وصف' } } });

            const { loadMyGenerator, reportFault, faults } = useSubscriberFaults();
            await loadMyGenerator();
            const result = await reportFault({ description: 'وصف' });

            expect(result).toBe(true);
            expect(faultService.create).toHaveBeenCalledWith({ description: 'وصف', generator_id: 55 });
            expect(faults.value[0]).toEqual({ id: 200, description: 'وصف' });
        });

        it('reshapes a 422 response into submitError.value as the raw response data (not normalizeApiError)', async () => {
            faultService.create.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { description: ['مطلوب.'] } } },
            });

            const { reportFault, submitError } = useSubscriberFaults();
            const result = await reportFault({});

            expect(result).toBe(false);
            expect(submitError.value).toEqual({ message: 'Invalid.', errors: { description: ['مطلوب.'] } });
        });

        it('reshapes a network error into the translated fallback message', async () => {
            faultService.create.mockRejectedValue({ message: 'Network Error' });

            const { reportFault, submitError, isSubmitting } = useSubscriberFaults();
            const result = await reportFault({});

            expect(result).toBe(false);
            expect(submitError.value).toEqual({ message: 'تعذر إرسال العطل' });
            expect(isSubmitting.value).toBe(false);
        });
    });
});
