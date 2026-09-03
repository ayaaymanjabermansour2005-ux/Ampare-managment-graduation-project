import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { mount } from '@vue/test-utils';
import { defineComponent, h } from 'vue';
import { useThemeSync } from './useThemeSync';
import { useAdminUiStore } from '@/stores/adminUi';

function makeWrapper(shellClass) {
    const ThemedComponent = defineComponent({
        setup() {
            const ui = useThemeSync(shellClass);
            return { ui };
        },
        render() {
            return h('div');
        },
    });
    return mount(ThemedComponent);
}

describe('useThemeSync', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        localStorage.clear();
        document.documentElement.className = '';
    });

    afterEach(() => {
        document.documentElement.className = '';
    });

    it('adds the given shellClass to <html> on mount', () => {
        const wrapper = makeWrapper('admin-shell');

        expect(document.documentElement.classList.contains('admin-shell')).toBe(true);
        wrapper.unmount();
    });

    it('does not add the dark class when the store starts in light mode', () => {
        const wrapper = makeWrapper('admin-shell');

        expect(document.documentElement.classList.contains('dark')).toBe(false);
        wrapper.unmount();
    });

    it('adds the dark class immediately when the persisted theme is already dark', () => {
        localStorage.setItem('ampere-admin-theme', 'dark');

        const wrapper = makeWrapper('admin-shell');

        expect(document.documentElement.classList.contains('dark')).toBe(true);
        wrapper.unmount();
    });

    it('toggles the dark class reactively when the store theme changes after mount', async () => {
        const wrapper = makeWrapper('admin-shell');
        const ui = useAdminUiStore();

        expect(document.documentElement.classList.contains('dark')).toBe(false);

        ui.toggleTheme();
        await wrapper.vm.$nextTick();
        expect(document.documentElement.classList.contains('dark')).toBe(true);

        ui.toggleTheme();
        await wrapper.vm.$nextTick();
        expect(document.documentElement.classList.contains('dark')).toBe(false);

        wrapper.unmount();
    });

    it('removes the dark class and the shellClass on unmount', async () => {
        const wrapper = makeWrapper('admin-shell');
        const ui = useAdminUiStore();
        ui.toggleTheme();
        await wrapper.vm.$nextTick();
        expect(document.documentElement.classList.contains('dark')).toBe(true);

        wrapper.unmount();

        expect(document.documentElement.classList.contains('dark')).toBe(false);
        expect(document.documentElement.classList.contains('admin-shell')).toBe(false);
    });

    it('works without a shellClass argument (no extra class added/removed, does not throw)', () => {
        const wrapper = makeWrapper(undefined);

        expect(document.documentElement.className.trim()).toBe('');
        expect(() => wrapper.unmount()).not.toThrow();
    });

    it('returns the adminUi store instance', () => {
        const wrapper = makeWrapper('admin-shell');

        expect(wrapper.vm.ui.isDark).toBe(false);
        expect(typeof wrapper.vm.ui.toggleTheme).toBe('function');
        wrapper.unmount();
    });
});
