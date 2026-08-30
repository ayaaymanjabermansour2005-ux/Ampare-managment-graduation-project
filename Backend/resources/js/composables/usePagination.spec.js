import { describe, it, expect, vi } from 'vitest';
import { usePagination } from './usePagination';

describe('usePagination', () => {
    it('starts on page 1 with a default per_page of 15', () => {
        const fetchFn = vi.fn().mockResolvedValue({ data: [] });
        const { currentPage, perPage } = usePagination(fetchFn);

        expect(currentPage.value).toBe(1);
        expect(perPage.value).toBe(15);
        expect(fetchFn).not.toHaveBeenCalled();
    });

    it('goToPage calls fetchFn with the requested page and current per_page, and updates currentPage', async () => {
        const fetchFn = vi.fn().mockResolvedValue({ data: [{ id: 1 }, { id: 2 }] });
        const { currentPage, goToPage } = usePagination(fetchFn);

        await goToPage(3);

        expect(currentPage.value).toBe(3);
        expect(fetchFn).toHaveBeenCalledTimes(1);
        expect(fetchFn).toHaveBeenCalledWith({ page: 3, per_page: 15 });
    });

    it('handles an empty result set from fetchFn without throwing (empty page is a valid state)', async () => {
        const fetchFn = vi.fn().mockResolvedValue({ data: [], meta: { total: 0 } });
        const { currentPage, goToPage } = usePagination(fetchFn);

        await expect(goToPage(1)).resolves.toBeUndefined();
        expect(currentPage.value).toBe(1);
    });

    it('supports navigating to a high/last page number correctly', async () => {
        const fetchFn = vi.fn().mockResolvedValue({ data: [{ id: 99 }], meta: { current_page: 12, last_page: 12 } });
        const { currentPage, goToPage } = usePagination(fetchFn);

        await goToPage(12);

        expect(currentPage.value).toBe(12);
        expect(fetchFn).toHaveBeenCalledWith({ page: 12, per_page: 15 });
    });

    it('propagates an error from fetchFn (does not swallow it) so the caller can handle it', async () => {
        const failure = new Error('Request failed');
        const fetchFn = vi.fn().mockRejectedValue(failure);
        const { currentPage, goToPage } = usePagination(fetchFn);

        await expect(goToPage(2)).rejects.toThrow('Request failed');
        // currentPage is still updated optimistically before the fetch settles —
        // this documents the composable's actual (not swallow-on-error) behavior.
        expect(currentPage.value).toBe(2);
    });

    it('uses the current perPage value (not a fixed default) on subsequent calls', async () => {
        const fetchFn = vi.fn().mockResolvedValue({ data: [] });
        const { perPage, goToPage } = usePagination(fetchFn);

        perPage.value = 50;
        await goToPage(2);

        expect(fetchFn).toHaveBeenCalledWith({ page: 2, per_page: 50 });
    });
});
