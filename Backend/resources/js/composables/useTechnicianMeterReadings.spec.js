import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.mock('@/services/generatorService', () => ({
    default: {
        list: vi.fn(),
    },
}));
vi.mock('@/services/subscriptionService', () => ({
    default: {
        list: vi.fn(),
    },
}));

// useMeterReadingForm has its own dedicated spec (useMeterReadingForm.spec.js)
// covering submitReading's full behavior (idempotency key rotation, attachment
// upload, error propagation). Here we only need to verify useTechnicianMeterReadings
// exposes it unmodified, so it's mocked as a collaborator.
const mockSubmitReading = vi.fn();
vi.mock('./useMeterReadingForm', () => ({
    useMeterReadingForm: () => ({ submitReading: mockSubmitReading }),
}));

const { useTechnicianMeterReadings } = await import('./useTechnicianMeterReadings');
const generatorService = (await import('@/services/generatorService')).default;
const subscriptionService = (await import('@/services/subscriptionService')).default;

describe('useTechnicianMeterReadings', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with no generators, isLoadingGenerators true, no selected generator, and empty subscriptions', () => {
        const { generators, selectedGeneratorId, isLoadingGenerators, subscriptions, isLoadingSubscriptions, submitReading } =
            useTechnicianMeterReadings();

        expect(generators.value).toEqual([]);
        expect(selectedGeneratorId.value).toBeNull();
        expect(isLoadingGenerators.value).toBe(true);
        expect(subscriptions.value).toEqual([]);
        expect(isLoadingSubscriptions.value).toBe(false);
        // pass-through from useMeterReadingForm, unmodified
        expect(submitReading).toBe(mockSubmitReading);
    });

    it('loadGenerators unwraps a paginated payload and auto-selects the first generator', async () => {
        generatorService.list.mockResolvedValue({
            data: { data: { data: [{ id: 5, name: 'Gen A' }, { id: 6, name: 'Gen B' }], meta: { total: 2 } } },
        });

        const { loadGenerators, generators, selectedGeneratorId, isLoadingGenerators } = useTechnicianMeterReadings();
        await loadGenerators();

        expect(generatorService.list).toHaveBeenCalledWith({ per_page: 100 });
        expect(generators.value).toEqual([{ id: 5, name: 'Gen A' }, { id: 6, name: 'Gen B' }]);
        expect(selectedGeneratorId.value).toBe(5);
        expect(isLoadingGenerators.value).toBe(false);
    });

    it('loadGenerators accepts a flat (non-paginated) array payload as well', async () => {
        generatorService.list.mockResolvedValue({ data: { data: [{ id: 9, name: 'Gen C' }] } });

        const { loadGenerators, generators, selectedGeneratorId } = useTechnicianMeterReadings();
        await loadGenerators();

        expect(generators.value).toEqual([{ id: 9, name: 'Gen C' }]);
        expect(selectedGeneratorId.value).toBe(9);
    });

    it('does not select a generator when the list comes back empty', async () => {
        generatorService.list.mockResolvedValue({ data: { data: [] } });

        const { loadGenerators, generators, selectedGeneratorId } = useTechnicianMeterReadings();
        await loadGenerators();

        expect(generators.value).toEqual([]);
        expect(selectedGeneratorId.value).toBeNull();
    });

    it('loadGenerators resets to an empty list and does not reject on failure', async () => {
        generatorService.list.mockRejectedValue(new Error('Network Error'));

        const { loadGenerators, generators, isLoadingGenerators } = useTechnicianMeterReadings();

        await expect(loadGenerators()).resolves.toBeUndefined();
        expect(generators.value).toEqual([]);
        expect(isLoadingGenerators.value).toBe(false);
    });

    it('loadSubscriptions is a no-op (empty list, no request) when no generator is selected', async () => {
        const { loadSubscriptions, subscriptions, isLoadingSubscriptions } = useTechnicianMeterReadings();
        await loadSubscriptions();

        expect(subscriptionService.list).not.toHaveBeenCalled();
        expect(subscriptions.value).toEqual([]);
        expect(isLoadingSubscriptions.value).toBe(false);
    });

    it('loadSubscriptions filters to active subscriptions on the selected generator only', async () => {
        generatorService.list.mockResolvedValue({ data: { data: [{ id: 5, name: 'Gen A' }] } });
        subscriptionService.list.mockResolvedValue({
            data: {
                data: {
                    data: [
                        { id: 1, status: 'active', generator: { id: 5 } },
                        { id: 2, status: 'inactive', generator: { id: 5 } },
                        { id: 3, status: 'active', generator: { id: 6 } },
                    ],
                },
            },
        });

        const { loadGenerators, loadSubscriptions, subscriptions, isLoadingSubscriptions } = useTechnicianMeterReadings();
        await loadGenerators(); // selects generator 5
        await loadSubscriptions();

        expect(subscriptionService.list).toHaveBeenCalledWith({ per_page: 100 });
        expect(subscriptions.value).toEqual([{ id: 1, status: 'active', generator: { id: 5 } }]);
        expect(isLoadingSubscriptions.value).toBe(false);
    });

    it('loadSubscriptions resets to an empty list and does not reject on failure', async () => {
        generatorService.list.mockResolvedValue({ data: { data: [{ id: 5 }] } });
        subscriptionService.list.mockRejectedValue(new Error('Network Error'));

        const { loadGenerators, loadSubscriptions, subscriptions, isLoadingSubscriptions } = useTechnicianMeterReadings();
        await loadGenerators();

        await expect(loadSubscriptions()).resolves.toBeUndefined();
        expect(subscriptions.value).toEqual([]);
        expect(isLoadingSubscriptions.value).toBe(false);
    });
});
