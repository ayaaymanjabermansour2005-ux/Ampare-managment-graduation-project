import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/generatorService', () => ({
    default: { available: vi.fn() },
}));
vi.mock('@/services/subscriberMeterService', () => ({
    default: { list: vi.fn(), create: vi.fn() },
}));
vi.mock('@/services/subscriptionService', () => ({
    default: { create: vi.fn() },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            browse_generators_page: {
                load_error: 'تعذر تحميل المولدات',
                add_meter_error: 'تعذر إضافة العداد',
                subscribe_error: 'تعذر إرسال طلب الاشتراك',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useSubscriberBrowseGenerators } = await import('./useSubscriberBrowseGenerators');
const subscriberMeterService = (await import('@/services/subscriberMeterService')).default;
const subscriptionService = (await import('@/services/subscriptionService')).default;

describe('useSubscriberBrowseGenerators', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('createMeter — raw .errors if present, else synthetic "meter_number" field', () => {
        it('uses the raw fieldErrors object when present', async () => {
            subscriberMeterService.create.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { meter_number: ['هذا الرقم مستخدم مسبقًا.'] } } },
            });

            const { createMeter, createMeterError } = useSubscriberBrowseGenerators();
            const result = await createMeter({});

            expect(result).toBeNull();
            expect(createMeterError.value).toEqual({ meter_number: ['هذا الرقم مستخدم مسبقًا.'] });
        });

        it('synthesizes a meter_number field error from the generic message otherwise', async () => {
            subscriberMeterService.create.mockRejectedValue({
                response: { status: 500, data: { message: 'خطأ في الخادم.' } },
            });

            const { createMeter, createMeterError } = useSubscriberBrowseGenerators();
            await createMeter({});

            expect(createMeterError.value).toEqual({ meter_number: ['خطأ في الخادم.'] });
        });
    });

    describe('subscribe — 422 routes to subscribeErrors (field map), everything else to subscribeError (message)', () => {
        it('routes a 422 into subscribeErrors as the raw field-errors map, leaving subscribeError null', async () => {
            subscriptionService.create.mockRejectedValue({
                response: {
                    status: 422,
                    data: { message: 'بيانات غير صالحة', errors: { requested_capacity_kw: ['السعة المطلوبة إلزامية.'] } },
                },
            });

            const { subscribe, subscribeError, subscribeErrors } = useSubscriberBrowseGenerators();
            const result = await subscribe({});

            expect(result).toBeNull();
            expect(subscribeError.value).toBeNull();
            expect(subscribeErrors.value).toEqual({ requested_capacity_kw: ['السعة المطلوبة إلزامية.'] });
        });

        it('routes a non-422 failure (e.g. 409 capacity conflict) into subscribeError as a message, leaving subscribeErrors empty', async () => {
            subscriptionService.create.mockRejectedValue({
                response: { status: 409, data: { message: 'السعة المتاحة على هذا المولد ممتلئة حاليًا.' } },
            });

            const { subscribe, subscribeError, subscribeErrors } = useSubscriberBrowseGenerators();
            await subscribe({});

            expect(subscribeError.value).toBe('السعة المتاحة على هذا المولد ممتلئة حاليًا.');
            expect(subscribeErrors.value).toEqual({});
        });

        it('routes a network error into subscribeError using the translated fallback', async () => {
            subscriptionService.create.mockRejectedValue({ message: 'Network Error' });

            const { subscribe, subscribeError } = useSubscriberBrowseGenerators();
            await subscribe({});

            expect(subscribeError.value).toBe('تعذر إرسال طلب الاشتراك');
        });

        it('resets both subscribeError and subscribeErrors at the start of a new attempt', async () => {
            subscriptionService.create.mockRejectedValueOnce({
                response: { status: 422, data: { message: 'Invalid.', errors: { x: ['y'] } } },
            });
            const { subscribe, subscribeError, subscribeErrors } = useSubscriberBrowseGenerators();
            await subscribe({});
            expect(subscribeErrors.value).toEqual({ x: ['y'] });

            subscriptionService.create.mockResolvedValueOnce({ data: {} });
            await subscribe({});

            expect(subscribeErrors.value).toEqual({});
            expect(subscribeError.value).toBeNull();
        });

        it('returns the response data on success', async () => {
            subscriptionService.create.mockResolvedValue({ data: { data: { id: 3 } } });

            const { subscribe } = useSubscriberBrowseGenerators();
            const result = await subscribe({});

            expect(result).toEqual({ data: { id: 3 } });
        });
    });

    it('fetchGenerators sets the generic load error via normalizeApiError on failure', async () => {
        const generatorService = (await import('@/services/generatorService')).default;
        generatorService.available.mockRejectedValue({ message: 'Network Error' });

        const { fetchGenerators, error } = useSubscriberBrowseGenerators();
        await fetchGenerators();

        expect(error.value).toBe('تعذر تحميل المولدات');
    });
});
