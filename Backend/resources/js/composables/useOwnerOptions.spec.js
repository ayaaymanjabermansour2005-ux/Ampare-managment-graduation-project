import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.mock('@/services/userService', () => ({
    default: {
        list: vi.fn(),
    },
}));

vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: (k) => k, locale: { value: 'ar' } }) };
});

const { useOwnerOptions } = await import('./useOwnerOptions');
const userService = (await import('@/services/userService')).default;

describe('useOwnerOptions', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('has the expected default reactive state', () => {
        const { owners, isLoadingOwners, ownerSelectOptions } = useOwnerOptions();

        expect(owners.value).toEqual([]);
        expect(isLoadingOwners.value).toBe(false);
        expect(ownerSelectOptions.value).toEqual([]);
    });

    describe('fetchOwners', () => {
        it('requests generator_owner users, sorts them by name, and clears isLoadingOwners', async () => {
            userService.list.mockResolvedValue({
                data: {
                    data: {
                        data: [
                            { id: 1, name: 'Zaid', email: 'zaid@example.com' },
                            { id: 2, name: 'Ahmad', email: 'ahmad@example.com' },
                        ],
                    },
                },
            });

            const { fetchOwners, owners, isLoadingOwners } = useOwnerOptions();
            const loadPromise = fetchOwners();
            expect(isLoadingOwners.value).toBe(true);
            await loadPromise;

            expect(userService.list).toHaveBeenCalledWith({ role: 'generator_owner', per_page: 200 });
            expect(owners.value.map((o) => o.name)).toEqual(['Ahmad', 'Zaid']);
            expect(isLoadingOwners.value).toBe(false);
        });

        it('falls back to data.data directly when there is no nested pagination wrapper', async () => {
            userService.list.mockResolvedValue({
                data: { data: [{ id: 1, name: 'Ahmad', email: 'ahmad@example.com' }] },
            });

            const { fetchOwners, owners } = useOwnerOptions();
            await fetchOwners();

            expect(owners.value).toEqual([{ id: 1, name: 'Ahmad', email: 'ahmad@example.com' }]);
        });

        it('handles an empty owners list without throwing', async () => {
            userService.list.mockResolvedValue({ data: { data: { data: [] } } });

            const { fetchOwners, owners, ownerSelectOptions } = useOwnerOptions();
            await fetchOwners();

            expect(owners.value).toEqual([]);
            expect(ownerSelectOptions.value).toEqual([]);
        });

        it('propagates a rejection but still clears isLoadingOwners (finally)', async () => {
            userService.list.mockRejectedValue(new Error('boom'));

            const { fetchOwners, isLoadingOwners } = useOwnerOptions();

            await expect(fetchOwners()).rejects.toThrow('boom');
            expect(isLoadingOwners.value).toBe(false);
        });
    });

    describe('ownerSelectOptions', () => {
        it('derives {value, label} pairs combining name and email', async () => {
            userService.list.mockResolvedValue({
                data: { data: { data: [{ id: 5, name: 'Sara', email: 'sara@example.com' }] } },
            });

            const { fetchOwners, ownerSelectOptions } = useOwnerOptions();
            await fetchOwners();

            expect(ownerSelectOptions.value).toEqual([{ value: 5, label: 'Sara — sara@example.com' }]);
        });
    });
});
