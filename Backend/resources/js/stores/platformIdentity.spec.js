import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

vi.mock('@/services/settingService', () => ({
    default: { publicIdentity: vi.fn() },
}));

const { usePlatformIdentityStore } = await import('./platformIdentity');
const settingService = (await import('@/services/settingService')).default;

describe('usePlatformIdentityStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('populates siteName/logoUrl/faviconUrl from the public endpoint', async () => {
        settingService.publicIdentity.mockResolvedValue({
            data: { data: { site_name: 'Ampare', logo_url: '/custom-logo.png', favicon_url: '/favicon.ico' } },
        });

        const store = usePlatformIdentityStore();
        await store.fetch();

        expect(store.siteName).toBe('Ampare');
        expect(store.logoUrl).toBe('/custom-logo.png');
        expect(store.faviconUrl).toBe('/favicon.ico');
        expect(store.isLoaded).toBe(true);
    });

    it('leaves fields null (for static fallbacks) on failure, without throwing', async () => {
        settingService.publicIdentity.mockRejectedValue(new Error('Network Error'));

        const store = usePlatformIdentityStore();
        await expect(store.fetch()).resolves.toBeUndefined();

        expect(store.siteName).toBeNull();
        expect(store.isLoaded).toBe(false);
    });

    it('only calls the API once across multiple fetch() calls (cached)', async () => {
        settingService.publicIdentity.mockResolvedValue({
            data: { data: { site_name: 'Ampare' } },
        });

        const store = usePlatformIdentityStore();
        await Promise.all([store.fetch(), store.fetch(), store.fetch()]);
        await store.fetch();

        expect(settingService.publicIdentity).toHaveBeenCalledTimes(1);
    });

    it('treats empty-string settings as unset (falls back to null, not "")', async () => {
        settingService.publicIdentity.mockResolvedValue({
            data: { data: { site_name: '', logo_url: '', favicon_url: '' } },
        });

        const store = usePlatformIdentityStore();
        await store.fetch();

        expect(store.siteName).toBeNull();
        expect(store.logoUrl).toBeNull();
        expect(store.faviconUrl).toBeNull();
    });
});
