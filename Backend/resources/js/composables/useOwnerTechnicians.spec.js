import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/technicianService', () => ({
    default: {
        list: vi.fn(),
        create: vi.fn(),
        createAccount: vi.fn(),
        update: vi.fn(),
        destroy: vi.fn(),
    },
}));
vi.mock('@/services/userService', () => ({
    default: {
        list: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_technicians: {
                load_error: 'تعذر تحميل الفنيين',
                create_error: 'تعذر إنشاء الفني',
                update_error: 'تعذر حفظ التعديلات',
                delete_error: 'تعذر حذف الفني',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useOwnerTechnicians } = await import('./useOwnerTechnicians');
const technicianService = (await import('@/services/technicianService')).default;
const userService = (await import('@/services/userService')).default;

describe('useOwnerTechnicians', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('starts loading (isLoading true by default) with an empty list, default pagination, and empty search', () => {
        const { technicians, pagination, isLoading, error, search, eligibleUsers, isLoadingUsers, isSaving, saveError, deletingId, deleteError } = useOwnerTechnicians();

        expect(technicians.value).toEqual([]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
        expect(isLoading.value).toBe(true);
        expect(error.value).toBeNull();
        expect(search.value).toBe('');
        expect(eligibleUsers.value).toEqual([]);
        expect(isLoadingUsers.value).toBe(false);
        expect(isSaving.value).toBe(false);
        expect(saveError.value).toBeNull();
        expect(deletingId.value).toBeNull();
        expect(deleteError.value).toBeNull();
    });

    it('fetchTechnicians populates the list/pagination and sends search: undefined when search is empty', async () => {
        technicianService.list.mockResolvedValue({
            data: {
                data: {
                    data: [{ id: 1, name: 'Ahmad' }],
                    meta: { current_page: 1, last_page: 2, total: 20, per_page: 15 },
                },
            },
        });

        const { fetchTechnicians, technicians, pagination, isLoading } = useOwnerTechnicians();
        await fetchTechnicians();

        expect(technicianService.list).toHaveBeenCalledWith({ page: 1, search: undefined });
        expect(technicians.value).toEqual([{ id: 1, name: 'Ahmad' }]);
        expect(pagination.value).toEqual({ current_page: 1, last_page: 2, total: 20, per_page: 15 });
        expect(isLoading.value).toBe(false);
    });

    it('reshapes a fetchTechnicians failure into the translated fallback message', async () => {
        technicianService.list.mockRejectedValue({ message: 'Network Error' });

        const { fetchTechnicians, error, isLoading } = useOwnerTechnicians();
        await fetchTechnicians();

        expect(error.value).toBe('تعذر تحميل الفنيين');
        expect(isLoading.value).toBe(false);
    });

    describe('onSearchInput debounce', () => {
        beforeEach(() => {
            vi.useFakeTimers();
        });
        afterEach(() => {
            vi.useRealTimers();
        });

        it('debounces fetchTechnicians by 300ms and cancels a pending call when triggered again', async () => {
            technicianService.list.mockResolvedValue({ data: { data: [] } });

            const { onSearchInput, search } = useOwnerTechnicians();
            search.value = 'ah';
            onSearchInput();
            await vi.advanceTimersByTimeAsync(200);
            search.value = 'ahm';
            onSearchInput(); // resets the debounce timer

            await vi.advanceTimersByTimeAsync(200);
            expect(technicianService.list).not.toHaveBeenCalled();

            await vi.advanceTimersByTimeAsync(100);
            expect(technicianService.list).toHaveBeenCalledTimes(1);
            expect(technicianService.list).toHaveBeenCalledWith({ page: 1, search: 'ahm' });
        });
    });

    it('loadEligibleUsers populates eligibleUsers with role=technician and resets isLoadingUsers', async () => {
        userService.list.mockResolvedValue({ data: { data: { data: [{ id: 3, name: 'Sami' }] } } });

        const { loadEligibleUsers, eligibleUsers, isLoadingUsers } = useOwnerTechnicians();
        await loadEligibleUsers('sam');

        expect(userService.list).toHaveBeenCalledWith({ role: 'technician', search: 'sam', per_page: 20 });
        expect(eligibleUsers.value).toEqual([{ id: 3, name: 'Sami' }]);
        expect(isLoadingUsers.value).toBe(false);
    });

    it('loadEligibleUsers swallows a rejection, clears eligibleUsers, and resets isLoadingUsers (called fire-and-forget from a debounced search box with no .catch())', async () => {
        userService.list.mockRejectedValue({ message: 'Network Error' });

        const { loadEligibleUsers, eligibleUsers, isLoadingUsers } = useOwnerTechnicians();

        await expect(loadEligibleUsers('x')).resolves.toBeUndefined();
        expect(eligibleUsers.value).toEqual([]);
        expect(isLoadingUsers.value).toBe(false);
    });

    it('createTechnician refetches page 1 and returns true on success', async () => {
        technicianService.create.mockResolvedValue({ data: {} });
        technicianService.list.mockResolvedValue({ data: { data: { data: [{ id: 1 }], meta: {} } } });

        const { createTechnician, technicians, isSaving, saveError } = useOwnerTechnicians();
        const result = await createTechnician({ name: 'New' });

        expect(result).toBe(true);
        expect(technicianService.create).toHaveBeenCalledWith({ name: 'New' });
        expect(technicians.value).toEqual([{ id: 1 }]);
        expect(isSaving.value).toBe(false);
        expect(saveError.value).toBeNull();
    });

    it('createTechnician on a 422 sets saveError to the raw response.data', async () => {
        const responseData = { message: 'Invalid.', errors: { name: ['مطلوب.'] } };
        technicianService.create.mockRejectedValue({ response: { status: 422, data: responseData } });

        const { createTechnician, saveError } = useOwnerTechnicians();
        const result = await createTechnician({});

        expect(result).toBe(false);
        // saveError.value is a ref, so Vue auto-wraps the assigned object in a reactive
        // proxy — compare structurally (toEqual), not by reference (toBe).
        expect(saveError.value).toEqual(responseData);
    });

    it('createTechnician on a network error falls back to a translated { message } object', async () => {
        technicianService.create.mockRejectedValue({ message: 'Network Error' });

        const { createTechnician, saveError } = useOwnerTechnicians();
        await createTechnician({});

        expect(saveError.value).toEqual({ message: 'تعذر إنشاء الفني' });
    });

    it('createTechnicianAccount refetches page 1 and returns true on success', async () => {
        technicianService.createAccount.mockResolvedValue({ data: {} });
        technicianService.list.mockResolvedValue({ data: { data: { data: [{ id: 2 }], meta: {} } } });

        const { createTechnicianAccount, technicians } = useOwnerTechnicians();
        const result = await createTechnicianAccount({ email: 'a@b.com' });

        expect(result).toBe(true);
        expect(technicianService.createAccount).toHaveBeenCalledWith({ email: 'a@b.com' });
        expect(technicians.value).toEqual([{ id: 2 }]);
    });

    it('createTechnicianAccount on failure falls back to the same translated create_error message object', async () => {
        technicianService.createAccount.mockRejectedValue({ message: 'Network Error' });

        const { createTechnicianAccount, saveError } = useOwnerTechnicians();
        await createTechnicianAccount({});

        expect(saveError.value).toEqual({ message: 'تعذر إنشاء الفني' });
    });

    it('updateTechnician replaces the matching technician in place and returns true on success', async () => {
        technicianService.list.mockResolvedValue({ data: { data: { data: [{ id: 1, name: 'Old' }], meta: {} } } });
        technicianService.update.mockResolvedValue({ data: { data: { id: 1, name: 'New' } } });

        const { fetchTechnicians, updateTechnician, technicians } = useOwnerTechnicians();
        await fetchTechnicians();

        const result = await updateTechnician(1, { name: 'New' });

        expect(result).toBe(true);
        expect(technicians.value[0]).toEqual({ id: 1, name: 'New' });
    });

    it('updateTechnician leaves the list untouched when the id is not found, and reshapes a failure into saveError', async () => {
        technicianService.update.mockRejectedValue({ message: 'Network Error' });

        const { updateTechnician, technicians, saveError } = useOwnerTechnicians();
        const result = await updateTechnician(999, {});

        expect(result).toBe(false);
        expect(technicians.value).toEqual([]);
        expect(saveError.value).toEqual({ message: 'تعذر حفظ التعديلات' });
    });

    it('deleteTechnician tracks deletingId while in flight, refetches the current page, and returns true on success', async () => {
        technicianService.destroy.mockResolvedValue({});
        technicianService.list.mockResolvedValue({ data: { data: { data: [], meta: { current_page: 3 } } } });

        const { deleteTechnician, deletingId, isLoading } = useOwnerTechnicians();
        const promise = deleteTechnician(7);
        expect(deletingId.value).toBe(7);

        const result = await promise;

        expect(result).toBe(true);
        expect(deletingId.value).toBeNull();
        expect(technicianService.list).toHaveBeenCalledWith({ page: 1, search: undefined });
    });

    it('deleteTechnician reshapes a failure (e.g. active-tasks conflict) via normalizeApiError into a string message', async () => {
        technicianService.destroy.mockRejectedValue({
            response: { status: 422, data: { message: 'لا يمكن حذف فني لديه مهام نشطة.' } },
        });

        const { deleteTechnician, deleteError, deletingId } = useOwnerTechnicians();
        const result = await deleteTechnician(7);

        expect(result).toBe(false);
        expect(deleteError.value).toBe('لا يمكن حذف فني لديه مهام نشطة.');
        expect(deletingId.value).toBeNull();
    });
});
