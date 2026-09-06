import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref } from 'vue';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import AdminSidebar from './AdminSidebar.vue';

const visibleMenu = ref([]);
vi.mock('@/composables/useMenu', () => ({
    useMenu: () => ({ visibleMenu }),
}));

const confirmMock = vi.fn();
vi.mock('@/composables/useConfirm', () => ({
    useConfirm: () => ({ confirm: confirmMock }),
}));

vi.mock('@/composables/useLightningCanvas', () => ({
    useLightningCanvas: vi.fn(),
}));

const pushMock = vi.fn();
vi.mock('vue-router', () => ({
    useRouter: () => ({ push: pushMock }),
    RouterLink: { props: ['to'], template: '<a :data-route="to && to.name"><slot /></a>' },
}));

const useAuthStoreMock = vi.fn();
vi.mock('@/stores/auth', () => ({
    useAuthStore: (...a) => useAuthStoreMock(...a),
}));

const badgesState = { counts: {}, startAutoRefresh: vi.fn(), stopAutoRefresh: vi.fn() };
vi.mock('@/stores/sidebarBadges', () => ({
    useSidebarBadgesStore: () => badgesState,
}));

import { useAdminUiStore } from '@/stores/adminUi';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: {
                brand: 'أمبير · لوحة التحكم',
                logout: 'تسجيل الخروج',
                logout_confirm: 'هل تريد تسجيل الخروج من لوحة التحكم؟',
                role_admin: 'مدير النظام',
                role_super_admin: 'مدير عام',
                collapse_menu: 'طي القائمة',
                expand_menu: 'توسيع القائمة',
            },
            menu_groups: { general: 'عام', operations: 'العمليات' },
            menu: { dashboard: 'لوحة التحكم', generators: 'المولدات', subscribers: 'المشتركون' },
        },
    },
});

function mountSidebar() {
    const pinia = createPinia();
    setActivePinia(pinia);
    const wrapper = mount(AdminSidebar, { global: { plugins: [i18n, pinia] } });
    return { wrapper, pinia };
}

describe('AdminSidebar', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        badgesState.counts = {};
        visibleMenu.value = [
            { route: 'admin.dashboard', labelKey: 'menu.dashboard', icon: 'fa-house', groupKey: 'menu_groups.general' },
            { route: 'admin.generators', labelKey: 'menu.generators', icon: 'fa-plug-circle-bolt', groupKey: 'menu_groups.operations', badgeKey: 'generators_pending_verification' },
            { route: 'admin.subscribers', labelKey: 'menu.subscribers', icon: 'fa-user-group', groupKey: 'menu_groups.operations' },
        ];
        useAuthStoreMock.mockReturnValue({
            user: { name: 'Ahmad Owner' },
            hasRole: (role) => role === 'admin',
            logout: vi.fn().mockResolvedValue({}),
        });
    });

    it('groups the visible menu items under their group titles, in order', () => {
        const { wrapper } = mountSidebar();

        const groupTitles = wrapper.findAll('.sidebar-group-title').map((el) => el.text());
        expect(groupTitles).toEqual(['عام', 'العمليات']);

        const links = wrapper.findAll('.sidebar-item');
        expect(links.map((l) => l.attributes('data-route'))).toEqual([
            'admin.dashboard',
            'admin.generators',
            'admin.subscribers',
        ]);
        expect(links[0].text()).toContain('لوحة التحكم');
    });

    it('shows a badge only for items with a non-zero badge count, formatted as "99+" above 99', async () => {
        badgesState.counts = { generators_pending_verification: 5 };
        const { wrapper } = mountSidebar();
        await flushPromises();

        const generatorsLink = wrapper.findAll('.sidebar-item').find((l) => l.attributes('data-route') === 'admin.generators');
        expect(generatorsLink.find('.sidebar-badge').text()).toBe('5');

        const dashboardLink = wrapper.findAll('.sidebar-item').find((l) => l.attributes('data-route') === 'admin.dashboard');
        expect(dashboardLink.find('.sidebar-badge').exists()).toBe(false);
    });

    it('caps the displayed badge count at "99+"', () => {
        badgesState.counts = { generators_pending_verification: 140 };
        const { wrapper } = mountSidebar();

        const generatorsLink = wrapper.findAll('.sidebar-item').find((l) => l.attributes('data-route') === 'admin.generators');
        expect(generatorsLink.find('.sidebar-badge').text()).toBe('99+');
    });

    it('starts badge auto-refresh on mount and stops it on unmount', () => {
        const { wrapper } = mountSidebar();
        expect(badgesState.startAutoRefresh).toHaveBeenCalledTimes(1);

        wrapper.unmount();
        expect(badgesState.stopAutoRefresh).toHaveBeenCalledTimes(1);
    });

    it('derives two-letter initials from a two-word user name', () => {
        useAuthStoreMock.mockReturnValue({ user: { name: 'Ahmad Owner' }, hasRole: () => true, logout: vi.fn() });
        const { wrapper } = mountSidebar();

        expect(wrapper.text()).toContain('AO');
    });

    it('falls back to the first two letters for a single-word name, and "?" when there is no user', () => {
        useAuthStoreMock.mockReturnValue({ user: { name: 'Cher' }, hasRole: () => true, logout: vi.fn() });
        const single = mountSidebar().wrapper;
        expect(single.text()).toContain('Ch');

        useAuthStoreMock.mockReturnValue({ user: null, hasRole: () => true, logout: vi.fn() });
        const noUser = mountSidebar().wrapper;
        expect(noUser.text()).toContain('?');
    });

    // FIX (تدقيق شامل — بند تنظيف): فرع "مدير عام" (super_admin) كان كودًا
    // ميتًا — هذا المكوّن محمي أصلًا بـ meta: { role: "admin" } بالراوتر، ولا
    // يوجد دور "super_admin" بالنظام إطلاقًا. roleLabel صارت ثابتة دائمًا.
    it('always shows the admin role label', () => {
        useAuthStoreMock.mockReturnValue({ user: { name: 'A B' }, hasRole: (r) => r === 'admin', logout: vi.fn() });
        const admin = mountSidebar().wrapper;
        expect(admin.text()).toContain('مدير النظام');
    });

    it('logging out: confirms first, and only calls store.logout() + redirects to login when confirmed', async () => {
        const logoutFn = vi.fn().mockResolvedValue({});
        useAuthStoreMock.mockReturnValue({ user: { name: 'A B' }, hasRole: () => true, logout: logoutFn });
        confirmMock.mockResolvedValue(false);
        const { wrapper } = mountSidebar();

        await wrapper.find('[aria-label="تسجيل الخروج"]').trigger('click');
        await flushPromises();

        expect(confirmMock).toHaveBeenCalledWith(expect.objectContaining({ title: 'تسجيل الخروج', variant: 'danger' }));
        expect(logoutFn).not.toHaveBeenCalled();
        expect(pushMock).not.toHaveBeenCalled();

        confirmMock.mockResolvedValue(true);
        await wrapper.find('[aria-label="تسجيل الخروج"]').trigger('click');
        await flushPromises();

        expect(logoutFn).toHaveBeenCalledTimes(1);
        expect(pushMock).toHaveBeenCalledWith({ name: 'login' });
    });

    it('toggles the collapsed state via the collapse/expand button', async () => {
        const { wrapper, pinia } = mountSidebar();
        const ui = useAdminUiStore(pinia);
        expect(ui.isCollapsed).toBe(false);

        await wrapper.find('#adminSidebarToggle').trigger('click');

        expect(ui.isCollapsed).toBe(true);
        expect(wrapper.find('#adminSidebarToggle').attributes('title')).toBe('توسيع القائمة');
    });

    it('clicking a nav link on a narrow (mobile) viewport closes the mobile sidebar', async () => {
        const originalWidth = window.innerWidth;
        Object.defineProperty(window, 'innerWidth', { value: 500, configurable: true });
        const { wrapper, pinia } = mountSidebar();
        const ui = useAdminUiStore(pinia);
        ui.openMobile();
        expect(ui.isMobileOpen).toBe(true);

        await wrapper.find('.sidebar-item').trigger('click');

        expect(ui.isMobileOpen).toBe(false);
        Object.defineProperty(window, 'innerWidth', { value: originalWidth, configurable: true });
    });

    it('clicking the mobile backdrop closes the mobile sidebar', async () => {
        const { wrapper, pinia } = mountSidebar();
        const ui = useAdminUiStore(pinia);
        ui.openMobile();
        await wrapper.vm.$nextTick();

        await wrapper.find('.fixed.inset-0.z-30').trigger('click');

        expect(ui.isMobileOpen).toBe(false);
    });

    it('a leftward touch swipe past the threshold closes the mobile sidebar', async () => {
        const { wrapper, pinia } = mountSidebar();
        const ui = useAdminUiStore(pinia);
        ui.openMobile();
        const aside = wrapper.find('#adminSidebar');

        await aside.trigger('touchstart', { touches: [{ clientX: 300 }] });
        await aside.trigger('touchend', { changedTouches: [{ clientX: 360 }] });
        expect(ui.isMobileOpen).toBe(false);

        ui.openMobile();
        await aside.trigger('touchstart', { touches: [{ clientX: 300 }] });
        await aside.trigger('touchend', { changedTouches: [{ clientX: 320 }] });
        expect(ui.isMobileOpen).toBe(true);
    });
});
