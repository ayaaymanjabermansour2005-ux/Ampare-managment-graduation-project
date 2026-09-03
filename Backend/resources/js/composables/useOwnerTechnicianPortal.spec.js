import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/technicianTaskService', () => ({
    default: { list: vi.fn() },
}));
vi.mock('@/services/meterReadingService', () => ({
    default: { list: vi.fn() },
}));
vi.mock('@/services/technicianPaymentService', () => ({
    default: { list: vi.fn() },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_technician_portal: {
                load_error: 'تعذر تحميل بيانات الفني',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useOwnerTechnicianPortal } = await import('./useOwnerTechnicianPortal');
const technicianTaskService = (await import('@/services/technicianTaskService')).default;
const meterReadingService = (await import('@/services/meterReadingService')).default;
const technicianPaymentService = (await import('@/services/technicianPaymentService')).default;

describe('useOwnerTechnicianPortal', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts with no technician selected, empty lists, and no error', () => {
        const { selectedTechnicianId, tasks, readings, payments, isLoading, error } = useOwnerTechnicianPortal();

        expect(selectedTechnicianId.value).toBe('');
        expect(tasks.value).toEqual([]);
        expect(readings.value).toEqual([]);
        expect(payments.value).toEqual([]);
        expect(isLoading.value).toBe(false);
        expect(error.value).toBeNull();
    });

    it('loadTechnicianWork with a falsy id resets all three lists and does not call any service', async () => {
        const { loadTechnicianWork, selectedTechnicianId, tasks, readings, payments } = useOwnerTechnicianPortal();
        tasks.value = [{ id: 1 }];
        readings.value = [{ id: 1 }];
        payments.value = [{ id: 1 }];

        await loadTechnicianWork('');

        expect(selectedTechnicianId.value).toBe('');
        expect(tasks.value).toEqual([]);
        expect(readings.value).toEqual([]);
        expect(payments.value).toEqual([]);
        expect(technicianTaskService.list).not.toHaveBeenCalled();
        expect(meterReadingService.list).not.toHaveBeenCalled();
        expect(technicianPaymentService.list).not.toHaveBeenCalled();
    });

    it('loadTechnicianWork fetches tasks/readings/payments in parallel and unwraps the doubly-nested payload shape', async () => {
        technicianTaskService.list.mockResolvedValue({ data: { data: { data: [{ id: 1 }] } } });
        meterReadingService.list.mockResolvedValue({ data: { data: { data: [{ id: 2 }] } } });
        technicianPaymentService.list.mockResolvedValue({ data: { data: { data: [{ id: 3 }] } } });

        const { loadTechnicianWork, selectedTechnicianId, tasks, readings, payments, isLoading } = useOwnerTechnicianPortal();
        await loadTechnicianWork(42);

        expect(selectedTechnicianId.value).toBe(42);
        expect(technicianTaskService.list).toHaveBeenCalledWith({ technician_id: 42, per_page: 20 });
        expect(meterReadingService.list).toHaveBeenCalledWith({ technician_id: 42, per_page: 20 });
        expect(technicianPaymentService.list).toHaveBeenCalledWith({ technician_id: 42, per_page: 20 });
        expect(tasks.value).toEqual([{ id: 1 }]);
        expect(readings.value).toEqual([{ id: 2 }]);
        expect(payments.value).toEqual([{ id: 3 }]);
        expect(isLoading.value).toBe(false);
    });

    it('falls back to the single-nested payload shape (data.data is already the array) when there is no inner .data', async () => {
        technicianTaskService.list.mockResolvedValue({ data: { data: [{ id: 9 }] } });
        meterReadingService.list.mockResolvedValue({ data: { data: [] } });
        technicianPaymentService.list.mockResolvedValue({ data: { data: [] } });

        const { loadTechnicianWork, tasks } = useOwnerTechnicianPortal();
        await loadTechnicianWork(1);

        expect(tasks.value).toEqual([{ id: 9 }]);
    });

    it('reshapes a failure from any of the three parallel calls into the translated fallback message', async () => {
        technicianTaskService.list.mockResolvedValue({ data: { data: [] } });
        meterReadingService.list.mockRejectedValue({ message: 'Network Error' });
        technicianPaymentService.list.mockResolvedValue({ data: { data: [] } });

        const { loadTechnicianWork, error, isLoading } = useOwnerTechnicianPortal();
        await loadTechnicianWork(1);

        expect(error.value).toBe('تعذر تحميل بيانات الفني');
        expect(isLoading.value).toBe(false);
    });
});
