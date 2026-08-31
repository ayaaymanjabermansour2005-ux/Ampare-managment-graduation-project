import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/technicianService', () => ({
    default: { list: vi.fn(), update: vi.fn(), destroy: vi.fn() },
}));
vi.mock('@/services/userService', () => ({
    default: { createTechnician: vi.fn(), unlock: vi.fn() },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            admin_technicians_page: {
                load_error: 'تعذر تحميل الفنيين',
                create_error: 'تعذر إنشاء الفني',
                update_error: 'تعذر تعديل الفني',
                delete_error: 'تعذر حذف الفني',
                unlock_error: 'تعذر إلغاء القفل',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminTechnicians } = await import('./useAdminTechnicians');
const technicianService = (await import('@/services/technicianService')).default;
const userService = (await import('@/services/userService')).default;

describe('useAdminTechnicians', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('fetchTechnicians sets the generic load error via normalizeApiError on failure', async () => {
        technicianService.list.mockRejectedValue({ message: 'Network Error' });

        const { fetchTechnicians, error } = useAdminTechnicians();
        await fetchTechnicians();

        expect(error.value).toBe('تعذر تحميل الفنيين');
    });

    describe('createTechnician — reshaped to { message, errors }', () => {
        it('reshapes a 422 response', async () => {
            userService.createTechnician.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { owner_id: ['المالك مطلوب.'] } } },
            });

            const { createTechnician, createError } = useAdminTechnicians();
            const result = await createTechnician({});

            expect(result).toBe(false);
            expect(createError.value).toEqual({ message: 'Invalid.', errors: { owner_id: ['المالك مطلوب.'] } });
        });

        it('reshapes a network error with an empty errors object', async () => {
            userService.createTechnician.mockRejectedValue({ message: 'Network Error' });

            const { createTechnician, createError } = useAdminTechnicians();
            await createTechnician({});

            expect(createError.value).toEqual({ message: 'تعذر إنشاء الفني', errors: {} });
        });
    });

    describe('updateTechnician — reshaped to { message, errors }', () => {
        it('reshapes a 422 response', async () => {
            technicianService.update.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { status: ['حالة غير صالحة.'] } } },
            });

            const { updateTechnician, saveError } = useAdminTechnicians();
            const result = await updateTechnician(1, { status: 'x' });

            expect(result).toBe(false);
            expect(saveError.value).toEqual({ message: 'Invalid.', errors: { status: ['حالة غير صالحة.'] } });
        });

        it('only forwards supported fields (status, notes) to the service call', async () => {
            technicianService.update.mockResolvedValue({ data: { data: { id: 1 } } });

            const { updateTechnician } = useAdminTechnicians();
            await updateTechnician(1, { status: 'active', notes: 'ok', unsupported_field: 'x' });

            expect(technicianService.update).toHaveBeenCalledWith(1, { status: 'active', notes: 'ok' });
        });
    });

    it('deleteTechnician sets the plain normalizeApiError message on failure', async () => {
        technicianService.destroy.mockRejectedValue({
            response: { status: 500, data: { message: 'تعذر الحذف فعليًا.' } },
        });

        const { deleteTechnician, deleteError } = useAdminTechnicians();
        const result = await deleteTechnician(1);

        expect(result).toBe(false);
        expect(deleteError.value).toBe('تعذر الحذف فعليًا.');
    });

    describe('unlockTechnician', () => {
        it('returns false without calling the API when the technician has no linked user_id', async () => {
            const { technicians, unlockTechnician } = useAdminTechnicians();
            technicians.value = [{ id: 1, user_id: null }];

            const result = await unlockTechnician(1);

            expect(result).toBe(false);
            expect(userService.unlock).not.toHaveBeenCalled();
        });

        it('sets deleteError via normalizeApiError on failure', async () => {
            userService.unlock.mockRejectedValue({ message: 'Network Error' });
            const { technicians, unlockTechnician, deleteError } = useAdminTechnicians();
            technicians.value = [{ id: 1, user_id: 42 }];

            const result = await unlockTechnician(1);

            expect(result).toBe(false);
            expect(deleteError.value).toBe('تعذر إلغاء القفل');
        });

        it('marks the technician unlocked in place on success', async () => {
            userService.unlock.mockResolvedValue({});
            const { technicians, unlockTechnician } = useAdminTechnicians();
            technicians.value = [{ id: 1, user_id: 42, is_locked: true }];

            const result = await unlockTechnician(1);

            expect(result).toBe(true);
            expect(technicians.value[0].is_locked).toBe(false);
        });
    });
});
