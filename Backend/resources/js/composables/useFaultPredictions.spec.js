import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/faultPredictionService', () => ({
    default: {
        list: vi.fn(),
        confirm: vi.fn(),
        dismiss: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            fault_predictions: {
                load_error: 'تعذر تحميل التوقعات',
                confirm_error: 'تعذر تأكيد التوقع',
                dismiss_error: 'تعذر تجاهل التوقع',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useFaultPredictions } = await import('./useFaultPredictions');
const faultPredictionService = (await import('@/services/faultPredictionService')).default;

describe('useFaultPredictions', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with an empty list, default pagination, no error, and idle decision state', () => {
        const { predictions, pagination, isLoading, error, decidingId, decisionError } = useFaultPredictions();

        expect(predictions.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
        expect(decidingId.value).toBeNull();
        expect(decisionError.value).toBeNull();
    });

    it('fetchPredictions populates predictions/pagination on success', async () => {
        faultPredictionService.list.mockResolvedValue({
            data: {
                data: {
                    data: [{ id: 1 }, { id: 2 }],
                    meta: { current_page: 2, last_page: 4, total: 50, per_page: 15 },
                },
            },
        });

        const { fetchPredictions, predictions, pagination, isLoading } = useFaultPredictions();
        await fetchPredictions(2);

        expect(faultPredictionService.list).toHaveBeenCalledWith({ page: 2 });
        expect(predictions.value).toEqual([{ id: 1 }, { id: 2 }]);
        expect(pagination.value).toEqual({ current_page: 2, last_page: 4, total: 50, per_page: 15 });
        expect(isLoading.value).toBe(false);
    });

    it('reshapes a fetchPredictions failure into the translated fallback message', async () => {
        faultPredictionService.list.mockRejectedValue({ message: 'Network Error' });

        const { fetchPredictions, error, isLoading } = useFaultPredictions();
        await fetchPredictions();

        expect(error.value).toBe('تعذر تحميل التوقعات');
        expect(isLoading.value).toBe(false);
    });

    it('confirmPrediction tracks decidingId while in flight, removes the prediction from the list, and returns true', async () => {
        faultPredictionService.list.mockResolvedValue({
            data: { data: { data: [{ id: 1 }, { id: 2 }], meta: {} } },
        });
        faultPredictionService.confirm.mockResolvedValue({ data: {} });

        const { fetchPredictions, confirmPrediction, predictions, decidingId, decisionError } = useFaultPredictions();
        await fetchPredictions();

        const promise = confirmPrediction(1);
        expect(decidingId.value).toBe(1);
        const result = await promise;

        expect(result).toBe(true);
        expect(predictions.value).toEqual([{ id: 2 }]);
        expect(decidingId.value).toBeNull();
        expect(decisionError.value).toBeNull();
    });

    it('confirmPrediction reshapes a failure into decisionError, returns false, and leaves the list untouched', async () => {
        faultPredictionService.list.mockResolvedValue({
            data: { data: { data: [{ id: 1 }], meta: {} } },
        });
        faultPredictionService.confirm.mockRejectedValue({ message: 'Network Error' });

        const { fetchPredictions, confirmPrediction, predictions, decisionError, decidingId } = useFaultPredictions();
        await fetchPredictions();
        const result = await confirmPrediction(1);

        expect(result).toBe(false);
        expect(predictions.value).toEqual([{ id: 1 }]);
        expect(decisionError.value).toBe('تعذر تأكيد التوقع');
        expect(decidingId.value).toBeNull();
    });

    it('dismissPrediction removes the prediction from the list and returns true on success', async () => {
        faultPredictionService.list.mockResolvedValue({
            data: { data: { data: [{ id: 5 }], meta: {} } },
        });
        faultPredictionService.dismiss.mockResolvedValue({ data: {} });

        const { fetchPredictions, dismissPrediction, predictions } = useFaultPredictions();
        await fetchPredictions();
        const result = await dismissPrediction(5);

        expect(faultPredictionService.dismiss).toHaveBeenCalledWith(5);
        expect(result).toBe(true);
        expect(predictions.value).toEqual([]);
    });

    it('dismissPrediction reshapes a failure into decisionError and returns false', async () => {
        faultPredictionService.dismiss.mockRejectedValue({ message: 'Network Error' });

        const { dismissPrediction, decisionError } = useFaultPredictions();
        const result = await dismissPrediction(5);

        expect(result).toBe(false);
        expect(decisionError.value).toBe('تعذر تجاهل التوقع');
    });

    it('confirming/dismissing an id that is not in the list is a harmless no-op that still reports success', async () => {
        faultPredictionService.confirm.mockResolvedValue({ data: {} });

        const { confirmPrediction, predictions } = useFaultPredictions();
        const result = await confirmPrediction(999);

        expect(result).toBe(true);
        expect(predictions.value).toEqual([]);
    });
});
