import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/faultService', () => ({
    default: {
        list: vi.fn(),
        overrideStatus: vi.fn(),
        destroy: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            faults_page: {
                load_error: 'تعذر تحميل الأعطال',
                override_error: 'تعذر تحديث حالة العطل',
                delete_error: 'تعذر حذف العطل',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminFaults } = await import('./useAdminFaults');
const faultService = (await import('@/services/faultService')).default;

function samplePage(items, meta = {}) {
    return {
        data: { data: { data: items, meta: { current_page: 1, last_page: 1, total: items.length, per_page: 15, ...meta } } },
    };
}

describe('useAdminFaults', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with default reactive state (isLoading true initially)', () => {
        const { faults, pagination, isLoading, error, search, statusFilter, isOverriding, deletingId } = useAdminFaults();

        expect(faults.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(true);
        expect(error.value).toBeNull();
        expect(search.value).toBe('');
        expect(statusFilter.value).toBe('');
        expect(isOverriding.value).toBe(false);
        expect(deletingId.value).toBeNull();
    });

    it('fetchFaults populates faults and pagination on success', async () => {
        faultService.list.mockResolvedValue(samplePage([{ id: 1 }], { total: 1 }));

        const { fetchFaults, faults, pagination, isLoading } = useAdminFaults();
        await fetchFaults(1);

        expect(faults.value).toEqual([{ id: 1 }]);
        expect(pagination.value.total).toBe(1);
        expect(isLoading.value).toBe(false);
    });

    it('fetchFaults handles an empty result list', async () => {
        faultService.list.mockResolvedValue(samplePage([], { total: 0 }));
        const { fetchFaults, faults } = useAdminFaults();

        await fetchFaults();

        expect(faults.value).toEqual([]);
    });

    it('fetchFaults sets a translated error message via normalizeApiError on network failure', async () => {
        faultService.list.mockRejectedValue({ message: 'Network Error' });
        const { fetchFaults, error } = useAdminFaults();

        await fetchFaults();

        expect(error.value).toBe('تعذر تحميل الأعطال');
    });

    it('onSearchInput debounces fetchFaults by 300ms', async () => {
        vi.useFakeTimers();
        faultService.list.mockResolvedValue(samplePage([]));
        const { onSearchInput } = useAdminFaults();

        onSearchInput();
        onSearchInput();
        expect(faultService.list).not.toHaveBeenCalled();

        await vi.advanceTimersByTimeAsync(300);
        expect(faultService.list).toHaveBeenCalledTimes(1);
        vi.useRealTimers();
    });

    it('onFilterChange refetches page 1', () => {
        faultService.list.mockResolvedValue(samplePage([]));
        const { onFilterChange } = useAdminFaults();

        onFilterChange();

        expect(faultService.list).toHaveBeenCalledWith({ page: 1, search: undefined, status: undefined });
    });

    it('overrideFaultStatus replaces the fault in the list on success', async () => {
        faultService.overrideStatus.mockResolvedValue({ data: { data: { id: 1, status: 'resolved' } } });
        const { overrideFaultStatus, faults } = useAdminFaults();
        faults.value = [{ id: 1, status: 'open' }];

        const result = await overrideFaultStatus(1, { status: 'resolved' });

        expect(result).toBe(true);
        expect(faults.value[0]).toEqual({ id: 1, status: 'resolved' });
    });

    it('overrideFaultStatus sets overrideError via normalizeApiError on failure', async () => {
        faultService.overrideStatus.mockRejectedValue({
            response: { status: 422, data: { message: 'بيانات غير صالحة', errors: {} } },
        });
        const { overrideFaultStatus, overrideError, isOverriding } = useAdminFaults();

        const result = await overrideFaultStatus(1, {});

        expect(result).toBe(false);
        expect(overrideError.value).toBe('بيانات غير صالحة');
        expect(isOverriding.value).toBe(false);
    });

    it('deleteFault removes it by refetching the current page on success', async () => {
        faultService.destroy.mockResolvedValue({});
        faultService.list.mockResolvedValue(samplePage([{ id: 2 }], { current_page: 1, total: 1 }));
        const { deleteFault, faults, deletingId } = useAdminFaults();

        const result = await deleteFault(1);

        expect(result).toBe(true);
        expect(faultService.destroy).toHaveBeenCalledWith(1);
        expect(faults.value).toEqual([{ id: 2 }]);
        expect(deletingId.value).toBeNull();
    });

    it('deleteFault sets deleteError via normalizeApiError on failure', async () => {
        faultService.destroy.mockRejectedValue({ message: 'Network Error' });
        const { deleteFault, deleteError } = useAdminFaults();

        const result = await deleteFault(1);

        expect(result).toBe(false);
        expect(deleteError.value).toBe('تعذر حذف العطل');
    });
});
