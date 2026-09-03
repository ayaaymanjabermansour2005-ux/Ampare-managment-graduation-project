import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import { Sun, Moon, Expand, Shrink } from '@lucide/vue';
import { useAuthStore } from '@/stores/auth';
import { useAdminUiStore } from '@/stores/adminUi';
import { useConnectivityStore } from '@/stores/connectivity';
import { setLocale } from '@/i18n';
import AppNavbar from './AppNavbar.vue';

vi.mock('@/services/generatorService', () => ({ default: { list: vi.fn() } }));
vi.mock('@/services/userService', () => ({ default: { list: vi.fn() } }));
vi.mock('@/services/invoiceService', () => ({ default: { list: vi.fn() } }));
import generatorService from '@/services/generatorService';
import userService from '@/services/userService';
import invoiceService from '@/services/invoiceService';

const pushMock = vi.fn();
vi.mock('vue-router', () => ({
    useRouter: () => ({ push: pushMock }),
    RouterLink: { template: '<a><slot /></a>' },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: {
                open_menu: 'فتح القائمة',
                offline_saving_locally: 'غير متصل — سيتم الحفظ محليًا',
                syncing_operations: 'جارٍ مزامنة {count} عملية',
                search_placeholder: 'ابحث عن مولد، صفحة...',
                clear_search: 'مسح البحث',
                searching: 'جارِ البحث...',
                no_results_for: 'لا توجد نتائج لـ "{query}"',
                view_all_in: 'عرض الكل بـ{label}',
                pages_group_label: 'صفحات',
                system_running: 'النظام يعمل بكفاءة',
                quick_add: 'إضافة سريعة',
                fullscreen: 'ملء الشاشة',
                toggle_theme: 'تبديل الوضع الليلي/النهاري',
                toggle_language: 'تبديل اللغة',
                invoice_hash: 'فاتورة #{id}',
            },
            menu: {
                dashboard: 'لوحة التحكم',
                generator_owners: 'أصحاب المولدات',
                subscribers: 'المشتركون',
                invoices: 'الفواتير',
                review_payment: 'مراجعة دفعة',
                technicians: 'الفنيون',
                articles: 'المقالات',
            },
        },
    },
});

// ConversationsBell and NotificationBell each pull in their own services/stores and
// each already have their own dedicated spec — stub them here so this file stays
// focused on AppNavbar's own search/quick-add/theme/fullscreen/clock wiring.
const stubs = {
    ConversationsBell: true,
    NotificationBell: true,
};

function mountComponent() {
    return mount(AppNavbar, {
        attachTo: document.body,
        global: { plugins: [i18n], stubs },
    });
}

describe('AppNavbar', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        localStorage.clear();
        vi.clearAllMocks();
        useAuthStore().roles = ['admin'];
        useAuthStore().permissions = ['generators.view', 'subscriptions.view', 'invoices.view', 'payments.view', 'technicians.view'];
        generatorService.list.mockResolvedValue({ data: { data: { data: [], meta: { total: 0 } } } });
        userService.list.mockResolvedValue({ data: { data: { data: [], meta: { total: 0 } } } });
        invoiceService.list.mockResolvedValue({ data: { data: { data: [], meta: { total: 0 } } } });
    });

    afterEach(() => {
        vi.restoreAllMocks();
        vi.useRealTimers();
        document.body.innerHTML = '';
    });

    it('shows the offline banner when the connectivity store reports offline', () => {
        useConnectivityStore().isOnline = false;
        const wrapper = mountComponent();

        expect(wrapper.text()).toContain('غير متصل — سيتم الحفظ محليًا');
        wrapper.unmount();
    });

    it('shows a syncing banner with the pending count when online with queued operations', () => {
        const connectivity = useConnectivityStore();
        connectivity.isOnline = true;
        connectivity.pendingCount = 3;
        const wrapper = mountComponent();

        expect(wrapper.text()).toContain('جارٍ مزامنة 3 عملية');
        wrapper.unmount();
    });

    it('opens the mobile sidebar via the admin UI store when the mobile menu button is clicked', async () => {
        const wrapper = mountComponent();
        const ui = useAdminUiStore();
        expect(ui.isMobileOpen).toBe(false);

        await wrapper.find('button.md\\:hidden').trigger('click');

        expect(ui.isMobileOpen).toBe(true);
        wrapper.unmount();
    });

    describe('search', () => {
        it('debounces input, queries generators/subscribers/invoices in parallel, and renders each result', async () => {
            vi.useFakeTimers();
            generatorService.list.mockResolvedValue({
                data: { data: { data: [{ id: 1, name: 'مولد الشمس', location: { city: 'غزة' } }], meta: { total: 1 } } },
            });
            userService.list.mockResolvedValue({
                data: { data: { data: [{ id: 2, name: 'مشترك تجريبي', email: 'a@a.com' }], meta: { total: 1 } } },
            });
            invoiceService.list.mockResolvedValue({
                data: { data: { data: [{ id: 77, subscriber: { name: 'فلان' } }], meta: { total: 5 } } },
            });

            const wrapper = mountComponent();
            await wrapper.find('input').setValue('12345');
            await vi.advanceTimersByTimeAsync(300);

            expect(generatorService.list).toHaveBeenCalledWith({ search: '12345', per_page: 4 });
            expect(userService.list).toHaveBeenCalledWith({ role: 'subscriber', search: '12345', per_page: 4 });
            expect(invoiceService.list).toHaveBeenCalledWith({ search: '12345', per_page: 4 });

            expect(wrapper.text()).toContain('مولد الشمس');
            expect(wrapper.text()).toContain('مشترك تجريبي');
            expect(wrapper.text()).toContain('فاتورة #77');
            // invoices: total(5) > results(1) -> a "view all" link is shown; the other
            // two entities have total === results.length, so they get no such link.
            expect(wrapper.text()).toContain('عرض الكل بـ');
            expect(wrapper.findAll('button.dropdown-row')).toHaveLength(3);

            wrapper.unmount();
        });

        it('navigates and closes the dropdown when a result row is clicked', async () => {
            vi.useFakeTimers();
            generatorService.list.mockResolvedValue({
                data: { data: { data: [{ id: 1, name: 'مولد الشمس', location: { city: 'غزة' } }], meta: { total: 1 } } },
            });

            const wrapper = mountComponent();
            await wrapper.find('input').setValue('12345');
            await vi.advanceTimersByTimeAsync(300);

            await wrapper.find('button.dropdown-row').trigger('click');

            expect(pushMock).toHaveBeenCalledWith({ name: 'admin.generator-owners', query: { q: 'مولد الشمس' } });
            expect(wrapper.findAll('button.dropdown-row')).toHaveLength(0);

            wrapper.unmount();
        });

        it('moves the active result with ArrowDown and selects it with Enter', async () => {
            vi.useFakeTimers();
            generatorService.list.mockResolvedValue({
                data: { data: { data: [{ id: 1, name: 'مولد الشمس', location: { city: 'غزة' } }], meta: { total: 1 } } },
            });
            userService.list.mockResolvedValue({
                data: { data: { data: [{ id: 2, name: 'مشترك تجريبي', email: 'a@a.com' }], meta: { total: 1 } } },
            });

            const wrapper = mountComponent();
            const input = wrapper.find('input');
            await input.setValue('12345');
            await vi.advanceTimersByTimeAsync(300);

            await input.trigger('keydown', { key: 'ArrowDown' });
            await input.trigger('keydown', { key: 'Enter' });

            // Results are ordered generators, then subscribers, then invoices; ArrowDown
            // from index 0 moves to the subscriber result.
            expect(pushMock).toHaveBeenCalledWith({ name: 'admin.subscribers', query: { q: 'مشترك تجريبي' } });

            wrapper.unmount();
        });

        it('clears the query and closes the dropdown when the clear button is clicked', async () => {
            vi.useFakeTimers();
            const wrapper = mountComponent();
            const input = wrapper.find('input');
            await input.setValue('12345');
            await vi.advanceTimersByTimeAsync(300);
            expect(input.element.value).toBe('12345');

            await wrapper.find('button[aria-label="مسح البحث"]').trigger('click');

            expect(input.element.value).toBe('');
            expect(wrapper.findAll('button.dropdown-row')).toHaveLength(0);

            wrapper.unmount();
        });

        it('shows the "no results" state when nothing matches', async () => {
            vi.useFakeTimers();
            const wrapper = mountComponent();
            await wrapper.find('input').setValue('12345');
            await vi.advanceTimersByTimeAsync(300);

            expect(wrapper.text()).toContain('لا توجد نتائج لـ "12345"');

            wrapper.unmount();
        });
    });

    describe('quick add', () => {
        it('lists only the permitted quick actions and toggles the panel open/closed', async () => {
            const wrapper = mountComponent();
            const quickAddButton = wrapper.find('button[title="إضافة سريعة"]');

            await quickAddButton.trigger('click');
            expect(wrapper.text()).toContain('أصحاب المولدات');
            expect(wrapper.text()).toContain('المشتركون');
            expect(wrapper.text()).toContain('الفواتير');
            expect(wrapper.text()).toContain('مراجعة دفعة');
            expect(wrapper.text()).toContain('الفنيون');
            expect(wrapper.text()).toContain('المقالات'); // no `permission` field -> always visible

            await quickAddButton.trigger('click');
            expect(wrapper.text()).not.toContain('مراجعة دفعة');

            wrapper.unmount();
        });

        it('hides quick actions the current user lacks permission for', async () => {
            useAuthStore().permissions = []; // only the permission-less "articles" action remains
            const wrapper = mountComponent();

            await wrapper.find('button[title="إضافة سريعة"]').trigger('click');

            expect(wrapper.text()).toContain('المقالات');
            expect(wrapper.text()).not.toContain('أصحاب المولدات');

            wrapper.unmount();
        });

        it('closes an open search dropdown when quick-add is opened', async () => {
            vi.useFakeTimers();
            const wrapper = mountComponent();
            await wrapper.find('input').setValue('12345');
            await vi.advanceTimersByTimeAsync(300);
            expect(wrapper.text()).toContain('لا توجد نتائج');

            await wrapper.find('button[title="إضافة سريعة"]').trigger('click');

            expect(wrapper.text()).not.toContain('لا توجد نتائج');

            wrapper.unmount();
        });
    });

    it('toggles the theme icon and the admin UI store\'s dark flag', async () => {
        const wrapper = mountComponent();
        expect(wrapper.findComponent(Moon).exists()).toBe(true);
        expect(wrapper.findComponent(Sun).exists()).toBe(false);

        await wrapper.find('button[title="تبديل الوضع الليلي/النهاري"]').trigger('click');

        expect(useAdminUiStore().isDark).toBe(true);
        expect(wrapper.findComponent(Sun).exists()).toBe(true);
        expect(wrapper.findComponent(Moon).exists()).toBe(false);

        wrapper.unmount();
    });

    it('updates the document direction/lang when the language toggle is clicked', async () => {
        setLocale('ar'); // deterministic starting point on the real i18n singleton
        const wrapper = mountComponent();

        await wrapper.find('button[title="تبديل اللغة"]').trigger('click');

        expect(document.documentElement.dir).toBe('ltr');
        expect(document.documentElement.lang).toBe('en');

        setLocale('ar'); // restore, so later tests in this file see the Arabic/RTL default
        wrapper.unmount();
    });

    it('flips the fullscreen icon when the browser reports a fullscreenchange', async () => {
        const wrapper = mountComponent();
        expect(wrapper.findComponent(Expand).exists()).toBe(true);

        // jsdom does not implement the Fullscreen API at all (the property does not
        // exist on `document`), so `vi.spyOn` has nothing to wrap — define it directly.
        Object.defineProperty(document, 'fullscreenElement', { configurable: true, get: () => document.body });
        document.dispatchEvent(new Event('fullscreenchange'));
        await flushPromises();

        expect(wrapper.findComponent(Shrink).exists()).toBe(true);
        expect(wrapper.findComponent(Expand).exists()).toBe(false);

        delete document.fullscreenElement;
        wrapper.unmount();
    });

    it('closes open panels on an outside click', async () => {
        const wrapper = mountComponent();
        await wrapper.find('button[title="إضافة سريعة"]').trigger('click');
        expect(wrapper.text()).toContain('أصحاب المولدات');

        document.body.click();
        await flushPromises();

        expect(wrapper.text()).not.toContain('أصحاب المولدات');
        wrapper.unmount();
    });

    it('focuses the search input on Ctrl+Shift+K', async () => {
        const wrapper = mountComponent();
        expect(document.activeElement).not.toBe(wrapper.find('input').element);

        document.dispatchEvent(new KeyboardEvent('keydown', { key: 'K', ctrlKey: true, shiftKey: true }));
        await flushPromises();

        expect(document.activeElement).toBe(wrapper.find('input').element);
        wrapper.unmount();
    });
});
