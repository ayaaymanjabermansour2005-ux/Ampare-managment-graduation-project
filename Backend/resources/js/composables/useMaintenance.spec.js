import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/generatorService', () => ({
    default: { list: vi.fn() },
}));
vi.mock('@/services/generatorDiagnosticService', () => ({
    default: { create: vi.fn(), analyze: vi.fn() },
}));
vi.mock('@/services/fuelService', () => ({
    default: { storePurchase: vi.fn(), storeReading: vi.fn() },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            generator_diagnostics: {
                save_failed_message: 'تعذّر حفظ البيانات، حاول مرة أخرى.',
                analyze_failed_message: 'تعذّر تحليل القراءة، حاول مرة أخرى.',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useMaintenance } = await import('./useMaintenance');
const generatorService = (await import('@/services/generatorService')).default;
const generatorDiagnosticService = (await import('@/services/generatorDiagnosticService')).default;
const fuelService = (await import('@/services/fuelService')).default;

describe('useMaintenance', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts loading with an empty generator list and no selection', () => {
        const { generators, selectedGeneratorId, isLoadingGenerators } = useMaintenance();

        expect(generators.value).toEqual([]);
        expect(selectedGeneratorId.value).toBeNull();
        expect(isLoadingGenerators.value).toBe(true);
    });

    describe('loadGenerators', () => {
        it('stores the unwrapped list and auto-selects the first generator', async () => {
            generatorService.list.mockResolvedValue({ data: { data: { data: [{ id: 5 }, { id: 9 }] } } });

            const { generators, selectedGeneratorId, isLoadingGenerators, loadGenerators } = useMaintenance();
            await loadGenerators();

            expect(generatorService.list).toHaveBeenCalledWith({ per_page: 100 });
            expect(generators.value).toEqual([{ id: 5 }, { id: 9 }]);
            expect(selectedGeneratorId.value).toBe(5);
            expect(isLoadingGenerators.value).toBe(false);
        });

        it('leaves selectedGeneratorId as null when the list comes back empty (edge case)', async () => {
            generatorService.list.mockResolvedValue({ data: { data: [] } });

            const { generators, selectedGeneratorId, loadGenerators } = useMaintenance();
            await loadGenerators();

            expect(generators.value).toEqual([]);
            expect(selectedGeneratorId.value).toBeNull();
        });

        it('propagates a rejected fetch (no catch in the composable) while still clearing isLoadingGenerators', async () => {
            generatorService.list.mockRejectedValue(new Error('Network Error'));

            const { isLoadingGenerators, loadGenerators } = useMaintenance();
            await expect(loadGenerators()).rejects.toThrow('Network Error');

            expect(isLoadingGenerators.value).toBe(false);
        });
    });

    describe('submitDiagnostic', () => {
        it('submits against the currently selected generator and returns data.data on success', async () => {
            generatorDiagnosticService.create.mockResolvedValue({ data: { data: { id: 1, oil_pressure: 4 } } });

            const { selectedGeneratorId, submitDiagnostic, isSubmittingDiagnostic, diagnosticError, diagnosticSuccess } = useMaintenance();
            selectedGeneratorId.value = 5;
            const result = await submitDiagnostic({ oil_pressure: 4 });

            expect(generatorDiagnosticService.create).toHaveBeenCalledWith(5, { oil_pressure: 4 });
            expect(result).toEqual({ id: 1, oil_pressure: 4 });
            expect(diagnosticSuccess.value).toBe(true);
            expect(diagnosticError.value).toBeNull();
            expect(isSubmittingDiagnostic.value).toBe(false);
        });

        it('stores the raw response.data as the error on a 422 (not reshaped/normalized)', async () => {
            const responseData = { message: 'Invalid.', errors: { oil_pressure: ['قيمة غير صالحة.'] } };
            generatorDiagnosticService.create.mockRejectedValue({ response: { status: 422, data: responseData } });

            const { submitDiagnostic, diagnosticError, diagnosticSuccess } = useMaintenance();
            const result = await submitDiagnostic({});

            expect(result).toBeNull();
            expect(diagnosticSuccess.value).toBe(false);
            expect(diagnosticError.value).toEqual(responseData);
        });

        it('falls back to a translated { message } object on a network error (no response)', async () => {
            generatorDiagnosticService.create.mockRejectedValue({ message: 'Network Error' });

            const { submitDiagnostic, diagnosticError } = useMaintenance();
            await submitDiagnostic({});

            expect(diagnosticError.value).toEqual({ message: 'تعذّر حفظ البيانات، حاول مرة أخرى.' });
        });
    });

    describe('analyzeReading', () => {
        it('returns true and flags analyzeSuccess on success', async () => {
            generatorDiagnosticService.analyze.mockResolvedValue({ data: {} });

            const { analyzeReading, isAnalyzing, analyzeError, analyzeSuccess } = useMaintenance();
            const result = await analyzeReading(11);

            expect(generatorDiagnosticService.analyze).toHaveBeenCalledWith(11);
            expect(result).toBe(true);
            expect(analyzeSuccess.value).toBe(true);
            expect(analyzeError.value).toBeNull();
            expect(isAnalyzing.value).toBe(false);
        });

        it('stores the raw response.data as the error on a 422', async () => {
            const responseData = { message: 'تعذّر التحليل.' };
            generatorDiagnosticService.analyze.mockRejectedValue({ response: { status: 422, data: responseData } });

            const { analyzeReading, analyzeError, analyzeSuccess } = useMaintenance();
            const result = await analyzeReading(11);

            expect(result).toBe(false);
            expect(analyzeSuccess.value).toBe(false);
            expect(analyzeError.value).toEqual(responseData);
        });

        it('falls back to its own translated { message } object on a network error', async () => {
            generatorDiagnosticService.analyze.mockRejectedValue({ message: 'Network Error' });

            const { analyzeReading, analyzeError } = useMaintenance();
            await analyzeReading(11);

            expect(analyzeError.value).toEqual({ message: 'تعذّر تحليل القراءة، حاول مرة أخرى.' });
        });
    });

    describe('submitFuelPurchase', () => {
        it('builds a FormData against the selected generator, omitting null/undefined/empty-string fields', async () => {
            fuelService.storePurchase.mockResolvedValue({ data: {} });

            const { selectedGeneratorId, submitFuelPurchase, isSubmittingFuelPurchase, fuelPurchaseSuccess } = useMaintenance();
            selectedGeneratorId.value = 3;
            const result = await submitFuelPurchase({ liters: 50, note: null, invoice: undefined, ref: '' });

            expect(fuelService.storePurchase).toHaveBeenCalledTimes(1);
            const [generatorIdArg, formDataArg] = fuelService.storePurchase.mock.calls[0];
            expect(generatorIdArg).toBe(3);
            expect(formDataArg).toBeInstanceOf(FormData);
            expect(formDataArg.get('liters')).toBe('50');
            expect(formDataArg.has('note')).toBe(false);
            expect(formDataArg.has('invoice')).toBe(false);
            expect(formDataArg.has('ref')).toBe(false);
            expect(result).toBe(true);
            expect(fuelPurchaseSuccess.value).toBe(true);
            expect(isSubmittingFuelPurchase.value).toBe(false);
        });

        it('stores the raw response.data as the error on a 422', async () => {
            const responseData = { message: 'Invalid.', errors: { liters: ['مطلوب.'] } };
            fuelService.storePurchase.mockRejectedValue({ response: { status: 422, data: responseData } });

            const { submitFuelPurchase, fuelPurchaseError, fuelPurchaseSuccess } = useMaintenance();
            const result = await submitFuelPurchase({ liters: '' });

            expect(result).toBe(false);
            expect(fuelPurchaseSuccess.value).toBe(false);
            expect(fuelPurchaseError.value).toEqual(responseData);
        });

        it('falls back to the shared save_failed_message on a network error', async () => {
            fuelService.storePurchase.mockRejectedValue({ message: 'Network Error' });

            const { submitFuelPurchase, fuelPurchaseError } = useMaintenance();
            await submitFuelPurchase({});

            expect(fuelPurchaseError.value).toEqual({ message: 'تعذّر حفظ البيانات، حاول مرة أخرى.' });
        });
    });

    describe('submitFuelReading', () => {
        it('builds a FormData against the selected generator and returns true on success', async () => {
            fuelService.storeReading.mockResolvedValue({ data: {} });

            const { selectedGeneratorId, submitFuelReading, fuelReadingSuccess } = useMaintenance();
            selectedGeneratorId.value = 4;
            const result = await submitFuelReading({ reading: 75 });

            const [generatorIdArg, formDataArg] = fuelService.storeReading.mock.calls[0];
            expect(generatorIdArg).toBe(4);
            expect(formDataArg.get('reading')).toBe('75');
            expect(result).toBe(true);
            expect(fuelReadingSuccess.value).toBe(true);
        });

        it('stores the raw response.data as the error on a 422', async () => {
            const responseData = { message: 'Invalid.' };
            fuelService.storeReading.mockRejectedValue({ response: { status: 422, data: responseData } });

            const { submitFuelReading, fuelReadingError, fuelReadingSuccess } = useMaintenance();
            const result = await submitFuelReading({});

            expect(result).toBe(false);
            expect(fuelReadingSuccess.value).toBe(false);
            expect(fuelReadingError.value).toEqual(responseData);
        });

        it('falls back to the shared save_failed_message on a network error', async () => {
            fuelService.storeReading.mockRejectedValue({ message: 'Network Error' });

            const { submitFuelReading, fuelReadingError } = useMaintenance();
            await submitFuelReading({});

            expect(fuelReadingError.value).toEqual({ message: 'تعذّر حفظ البيانات، حاول مرة أخرى.' });
        });
    });
});
