import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/faultService', () => ({
    default: {
        list: vi.fn(),
        verify: vi.fn(),
        decideRepair: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            faults_page: {
                load_error: 'تعذر تحميل الأعطال',
            },
            owner_faults: {
                verify_error: 'تعذر التحقق من العطل',
                decide_repair_error: 'تعذر اتخاذ قرار الإصلاح',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useOwnerFaults } = await import('./useOwnerFaults');
const faultService = (await import('@/services/faultService')).default;

describe('useOwnerFaults', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('has the expected default reactive state (isLoading starts true)', () => {
        const {
            faults, pagination, isLoading, error, search, statusFilter,
            isVerifying, verifyError, isDecidingRepair, decideRepairError,
        } = useOwnerFaults();

        expect(faults.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(true);
        expect(error.value).toBeNull();
        expect(search.value).toBe('');
        expect(statusFilter.value).toBe('');
        expect(isVerifying.value).toBe(false);
        expect(verifyError.value).toBeNull();
        expect(isDecidingRepair.value).toBe(false);
        expect(decideRepairError.value).toBeNull();
    });

    describe('fetchFaults', () => {
        it('populates faults/pagination from a paginated response', async () => {
            faultService.list.mockResolvedValue({
                data: {
                    data: {
                        data: [{ id: 1, status: 'pending' }],
                        meta: { current_page: 3, last_page: 6, total: 30, per_page: 5 },
                    },
                },
            });

            const { fetchFaults, faults, pagination, isLoading } = useOwnerFaults();
            await fetchFaults(3);

            expect(faultService.list).toHaveBeenCalledWith({ page: 3, search: undefined, status: undefined });
            expect(faults.value).toEqual([{ id: 1, status: 'pending' }]);
            expect(pagination.value).toEqual({ current_page: 3, last_page: 6, total: 30, per_page: 5 });
            expect(isLoading.value).toBe(false);
        });

        it('passes search and statusFilter through when set', async () => {
            faultService.list.mockResolvedValue({ data: { data: [] } });

            const { fetchFaults, search, statusFilter } = useOwnerFaults();
            search.value = 'noise';
            statusFilter.value = 'verified';
            await fetchFaults(1);

            expect(faultService.list).toHaveBeenCalledWith({ page: 1, search: 'noise', status: 'verified' });
        });

        it('handles an empty result and derives pagination defaults', async () => {
            faultService.list.mockResolvedValue({ data: { data: [] } });

            const { fetchFaults, faults, pagination } = useOwnerFaults();
            await fetchFaults();

            expect(faults.value).toEqual([]);
            expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        });

        it('sets a translated error message on a network error', async () => {
            faultService.list.mockRejectedValue({ message: 'Network Error' });

            const { fetchFaults, error, isLoading } = useOwnerFaults();
            await fetchFaults();

            expect(error.value).toBe('تعذر تحميل الأعطال');
            expect(isLoading.value).toBe(false);
        });

        it('surfaces the backend message on a response error', async () => {
            faultService.list.mockRejectedValue({ response: { status: 500, data: { message: 'خطأ في الخادم' } } });

            const { fetchFaults, error } = useOwnerFaults();
            await fetchFaults();

            expect(error.value).toBe('خطأ في الخادم');
        });
    });

    describe('onSearchInput / onFilterChange', () => {
        it('onSearchInput debounces to a single fetchFaults call', async () => {
            vi.useFakeTimers();
            faultService.list.mockResolvedValue({ data: { data: [] } });

            const { onSearchInput } = useOwnerFaults();
            onSearchInput();
            onSearchInput();

            expect(faultService.list).not.toHaveBeenCalled();
            await vi.advanceTimersByTimeAsync(300);

            expect(faultService.list).toHaveBeenCalledTimes(1);
        });

        it('onFilterChange fetches page 1 immediately', async () => {
            faultService.list.mockResolvedValue({ data: { data: [] } });

            const { onFilterChange } = useOwnerFaults();
            onFilterChange();
            await Promise.resolve();
            await Promise.resolve();

            expect(faultService.list).toHaveBeenCalledWith({ page: 1, search: undefined, status: undefined });
        });
    });

    describe('verifyFault', () => {
        it('replaces the matching fault in the list and returns the updated fault', async () => {
            faultService.list.mockResolvedValue({
                data: { data: { data: [{ id: 1, is_valid: null }] } },
            });
            faultService.verify.mockResolvedValue({ data: { data: { id: 1, is_valid: true } } });

            const { fetchFaults, verifyFault, faults, isVerifying, verifyError } = useOwnerFaults();
            await fetchFaults();
            const result = await verifyFault(1, true);

            expect(faultService.verify).toHaveBeenCalledWith(1, { is_valid: true });
            expect(result).toEqual({ id: 1, is_valid: true });
            expect(faults.value[0]).toEqual({ id: 1, is_valid: true });
            expect(isVerifying.value).toBe(false);
            expect(verifyError.value).toBeNull();
        });

        it('does not throw when the fault id is not present in the current list', async () => {
            faultService.verify.mockResolvedValue({ data: { data: { id: 999, is_valid: false } } });

            const { verifyFault, faults } = useOwnerFaults();
            const result = await verifyFault(999, false);

            expect(result).toEqual({ id: 999, is_valid: false });
            expect(faults.value).toEqual([]);
        });

        it('sets a translated error and returns null on failure', async () => {
            faultService.verify.mockRejectedValue({ message: 'Network Error' });

            const { verifyFault, verifyError, isVerifying } = useOwnerFaults();
            const result = await verifyFault(1, true);

            expect(result).toBeNull();
            expect(verifyError.value).toBe('تعذر التحقق من العطل');
            expect(isVerifying.value).toBe(false);
        });
    });

    describe('decideFaultRepair', () => {
        it('replaces the matching fault in the list and returns the updated fault', async () => {
            faultService.list.mockResolvedValue({
                data: { data: { data: [{ id: 2, repair_decision: null }] } },
            });
            faultService.decideRepair.mockResolvedValue({ data: { data: { id: 2, repair_decision: 'approved' } } });

            const { fetchFaults, decideFaultRepair, faults } = useOwnerFaults();
            await fetchFaults();
            const result = await decideFaultRepair(2, { decision: 'approved' });

            expect(faultService.decideRepair).toHaveBeenCalledWith(2, { decision: 'approved' });
            expect(result).toEqual({ id: 2, repair_decision: 'approved' });
            expect(faults.value[0]).toEqual({ id: 2, repair_decision: 'approved' });
        });

        it('sets a translated error and returns null on failure', async () => {
            faultService.decideRepair.mockRejectedValue({ message: 'Network Error' });

            const { decideFaultRepair, decideRepairError, isDecidingRepair } = useOwnerFaults();
            const result = await decideFaultRepair(2, { decision: 'approved' });

            expect(result).toBeNull();
            expect(decideRepairError.value).toBe('تعذر اتخاذ قرار الإصلاح');
            expect(isDecidingRepair.value).toBe(false);
        });
    });
});
