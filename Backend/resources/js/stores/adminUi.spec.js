import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';

const THEME_KEY = 'ampere-admin-theme';
const COLLAPSE_KEY = 'ampere-admin-sidebar-collapsed';

const { useAdminUiStore } = await import('./adminUi');

describe('useAdminUiStore', () => {
    beforeEach(() => {
        localStorage.clear();
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    it('defaults to light theme, expanded sidebar and closed mobile menu when localStorage is empty', () => {
        const store = useAdminUiStore();

        expect(store.isDark).toBe(false);
        expect(store.isCollapsed).toBe(false);
        expect(store.isMobileOpen).toBe(false);
    });

    it('reads isDark=true from a previously stored "dark" theme', () => {
        localStorage.setItem(THEME_KEY, 'dark');
        const store = useAdminUiStore();

        expect(store.isDark).toBe(true);
    });

    it('reads isCollapsed=true only from the exact stored value "1"', () => {
        localStorage.setItem(COLLAPSE_KEY, '1');
        const store = useAdminUiStore();

        expect(store.isCollapsed).toBe(true);
    });

    it('treats any other stored collapse value (e.g. "0") as not collapsed', () => {
        localStorage.setItem(COLLAPSE_KEY, '0');
        const store = useAdminUiStore();

        expect(store.isCollapsed).toBe(false);
    });

    it('toggleTheme() flips isDark and persists the new value to localStorage', () => {
        const store = useAdminUiStore();

        store.toggleTheme();
        expect(store.isDark).toBe(true);
        expect(localStorage.getItem(THEME_KEY)).toBe('dark');

        store.toggleTheme();
        expect(store.isDark).toBe(false);
        expect(localStorage.getItem(THEME_KEY)).toBe('light');
    });

    it('toggleCollapse() flips isCollapsed and persists "1"/"0" to localStorage', () => {
        const store = useAdminUiStore();

        store.toggleCollapse();
        expect(store.isCollapsed).toBe(true);
        expect(localStorage.getItem(COLLAPSE_KEY)).toBe('1');

        store.toggleCollapse();
        expect(store.isCollapsed).toBe(false);
        expect(localStorage.getItem(COLLAPSE_KEY)).toBe('0');
    });

    it('openMobile()/closeMobile() set isMobileOpen independently of the other flags', () => {
        const store = useAdminUiStore();

        store.openMobile();
        expect(store.isMobileOpen).toBe(true);
        expect(store.isDark).toBe(false);
        expect(store.isCollapsed).toBe(false);

        store.closeMobile();
        expect(store.isMobileOpen).toBe(false);
    });

    it('toggleTheme() still flips in-memory state even if localStorage.setItem throws', () => {
        const store = useAdminUiStore();
        const setItemSpy = vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
            throw new Error('QuotaExceededError');
        });

        expect(() => store.toggleTheme()).not.toThrow();
        expect(store.isDark).toBe(true);

        setItemSpy.mockRestore();
    });
});
