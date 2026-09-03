import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import AppSidebar from './AppSidebar.vue';
import { useAuthStore } from '@/stores/auth';
import { useAdminUiStore } from '@/stores/adminUi';
import { useSidebarBadgesStore } from '@/stores/sidebarBadges';

// sidebarBadges store's own fetchCounts() logic has its own spec — only its two
// backing services need mocking here so mounting doesn't hit real HTTP.
vi.mock('@/services/sidebarService', () => ({
    default: { badgeCounts: vi.fn() },
}));
vi.mock('@/services/conversationService', () => ({
    default: { unreadCount: vi.fn() },
}));

const pushMock = vi.fn();
vi.mock('vue-router', () => ({
    useRouter: () => ({ push: pushMock }),
    RouterLink: { props: ['to'], template: '<a @click="$emit(\'click\')"><slot /></a>' },
}));

// useMenu (role/permission filtering) has its own spec — AppSidebar's own job is
// grouping/rendering whatever visibleMenu it is handed, so drive that directly.
let visibleMenuItems = [];
vi.mock('@/composables/useMenu', () => ({
    useMenu: () => ({ visibleMenu: { value: visibleMenuItems } }),
}));

// Canvas lightning background is decorative and has its own spec; irrelevant to
// the sidebar's own logic and would otherwise need a 2D canvas context stub.
vi.mock('@/composables/useLightningCanvas', () => ({
    useLightningCanvas: vi.fn(),
}));

const confirmMock = vi.fn();
vi.mock('@/composables/useConfirm', () => ({
    useConfirm: () => ({ confirm: confirmMock }),
}));

import sidebarService from '@/services/sidebarService';
import conversationService from '@/services/conversationService';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: {
                main_nav: 'التنقل الرئيسي', brand: 'أمبير · لوحة التحكم', logout: 'تسجيل الخروج',
                logout_confirm: 'هل تريد تسجيل الخروج من لوحة التحكم؟', collapse_menu: 'طي القائمة', expand_menu: 'توسيع القائمة',
                role_admin: 'مدير النظام', role_owner: 'مالك مولد', role_subscriber: 'مشترك', role_technician: 'فني',
            },
            auth: { logo_alt: 'لوجو أمبير' },
            menu: { dashboard: 'لوحة التحكم', generators: 'المولدات' },
            menu_groups: { general: 'عام', operations: 'العمليات' },
        },
    },
});

const ONE_GROUP_MENU = [
    { label: 'راية', labelKey: 'menu.dashboard', icon: 'fa-house', route: 'admin.dashboard', group: 'عام', groupKey: 'menu_groups.general' },
    { label: 'مولدات', labelKey: 'menu.generators', icon: 'fa-plug', route: 'admin.generators', group: 'عام', groupKey: 'menu_groups.general' },
];

const TWO_GROUP_MENU = [
    { label: 'راية', labelKey: 'menu.dashboard', icon: 'fa-house', route: 'admin.dashboard', group: 'عام', groupKey: 'menu_groups.general' },
    { label: 'مولدات', labelKey: 'menu.generators', icon: 'fa-plug', route: 'admin.generators', group: 'العمليات', groupKey: 'menu_groups.operations' },
];

function mountSidebar(menuItems, { roles = ['admin'], userName = 'محمد علي', isCollapsed = false, isMobileOpen = false } = {}) {
    visibleMenuItems = menuItems;
    const pinia = createPinia();
    setActivePinia(pinia);

    const auth = useAuthStore();
    auth.roles = roles;
    auth.user = userName ? { name: userName } : null;

    const ui = useAdminUiStore();
    ui.isCollapsed = isCollapsed;
    ui.isMobileOpen = isMobileOpen;

    const badges = useSidebarBadgesStore();

    const wrapper = mount(AppSidebar, { global: { plugins: [i18n, pinia] } });
    return { wrapper, auth, ui, badges };
}

describe('AppSidebar', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        confirmMock.mockReset();
        pushMock.mockReset();
        sidebarService.badgeCounts.mockResolvedValue({ data: { data: {} } });
        conversationService.unreadCount.mockResolvedValue({ data: { data: { unread_count: 0 } } });
    });

    it('does not show group titles when every visible item shares one group', () => {
        const { wrapper } = mountSidebar(ONE_GROUP_MENU);

        expect(wrapper.find('.sidebar-group-title').exists()).toBe(false);
        expect(wrapper.text()).toContain('لوحة التحكم');
        expect(wrapper.text()).toContain('المولدات');
    });

    it('shows one group title per distinct group when items span more than one group', () => {
        const { wrapper } = mountSidebar(TWO_GROUP_MENU);

        const titles = wrapper.findAll('.sidebar-group-title').map((el) => el.text());
        expect(titles).toEqual(['عام', 'العمليات']);
    });

    it('falls back to the raw item.label when an item has no labelKey', () => {
        const { wrapper } = mountSidebar([
            { label: 'عنصر بدون مفتاح', icon: 'fa-house', route: 'admin.dashboard', group: 'عام' },
        ]);

        expect(wrapper.text()).toContain('عنصر بدون مفتاح');
    });

    it('shows a badge with the fetched count, capped at "99+"', async () => {
        sidebarService.badgeCounts.mockResolvedValue({ data: { data: { generators_pending_verification: 5 } } });
        const { wrapper } = mountSidebar([
            { label: 'مولدات', labelKey: 'menu.generators', icon: 'fa-plug', route: 'admin.generators', group: 'عام', groupKey: 'menu_groups.general', badgeKey: 'generators_pending_verification' },
        ]);
        await flushPromises();

        expect(wrapper.find('.sidebar-badge').text()).toBe('5');
    });

    it('shows "99+" instead of the raw count once it exceeds 99', async () => {
        sidebarService.badgeCounts.mockResolvedValue({ data: { data: { generators_pending_verification: 150 } } });
        const { wrapper } = mountSidebar([
            { label: 'مولدات', labelKey: 'menu.generators', icon: 'fa-plug', route: 'admin.generators', group: 'عام', groupKey: 'menu_groups.general', badgeKey: 'generators_pending_verification' },
        ]);
        await flushPromises();

        expect(wrapper.find('.sidebar-badge').text()).toBe('99+');
    });

    it('hides the badge span when the fetched count is zero', async () => {
        sidebarService.badgeCounts.mockResolvedValue({ data: { data: { generators_pending_verification: 0 } } });
        const { wrapper } = mountSidebar([
            { label: 'مولدات', labelKey: 'menu.generators', icon: 'fa-plug', route: 'admin.generators', group: 'عام', groupKey: 'menu_groups.general', badgeKey: 'generators_pending_verification' },
        ]);
        await flushPromises();

        expect(wrapper.find('.sidebar-badge').exists()).toBe(false);
    });

    it('never starts badge auto-refresh when no visible item declares a badgeKey', async () => {
        mountSidebar(ONE_GROUP_MENU);
        await flushPromises();

        expect(sidebarService.badgeCounts).not.toHaveBeenCalled();
    });

    it('derives user initials from the first letter of the first two words of the name', () => {
        const { wrapper } = mountSidebar(ONE_GROUP_MENU, { userName: 'محمد علي' });

        expect(wrapper.text()).toContain('مع');
    });

    it('derives user initials from the first two characters when the name is one word', () => {
        const { wrapper } = mountSidebar(ONE_GROUP_MENU, { userName: 'محمد' });

        expect(wrapper.text()).toContain('مح');
    });

    it('shows a placeholder initial and dash name when there is no user', () => {
        const { wrapper } = mountSidebar(ONE_GROUP_MENU, { userName: null });

        expect(wrapper.text()).toContain('؟');
        expect(wrapper.text()).toContain('—');
    });

    it('shows the translated role label for a known role and the raw role for an unknown one', () => {
        const known = mountSidebar(ONE_GROUP_MENU, { roles: ['generator_owner'] });
        expect(known.wrapper.text()).toContain('مالك مولد');

        const unknown = mountSidebar(ONE_GROUP_MENU, { roles: ['some_custom_role'] });
        expect(unknown.wrapper.text()).toContain('some_custom_role');
    });

    it('does nothing when the logout confirmation is dismissed', async () => {
        confirmMock.mockResolvedValue(false);
        const { wrapper, auth } = mountSidebar(ONE_GROUP_MENU);
        const logoutSpy = vi.spyOn(auth, 'logout').mockResolvedValue({ hadPendingSync: false });

        await wrapper.find('button[aria-label="تسجيل الخروج"]').trigger('click');
        await flushPromises();

        expect(logoutSpy).not.toHaveBeenCalled();
        expect(pushMock).not.toHaveBeenCalled();
    });

    it('logs out and redirects to login when the logout confirmation is accepted', async () => {
        confirmMock.mockResolvedValue(true);
        const { wrapper, auth } = mountSidebar(ONE_GROUP_MENU);
        const logoutSpy = vi.spyOn(auth, 'logout').mockResolvedValue({ hadPendingSync: false });

        await wrapper.find('button[aria-label="تسجيل الخروج"]').trigger('click');
        await flushPromises();

        expect(logoutSpy).toHaveBeenCalled();
        expect(pushMock).toHaveBeenCalledWith({ name: 'login' });
    });

    it('toggles ui.isCollapsed and reflects it on the aside element when the collapse button is clicked', async () => {
        const { wrapper, ui } = mountSidebar(ONE_GROUP_MENU, { isCollapsed: false });

        await wrapper.find('#adminSidebarToggle').trigger('click');

        expect(ui.isCollapsed).toBe(true);
        expect(wrapper.find('#adminSidebar').classes()).toContain('collapsed');
    });

    it('shows the mobile backdrop only when ui.isMobileOpen is true, and closes it on click', async () => {
        const closed = mountSidebar(ONE_GROUP_MENU, { isMobileOpen: false });
        expect(closed.wrapper.find('.fixed.inset-0.z-30').exists()).toBe(false);

        const open = mountSidebar(ONE_GROUP_MENU, { isMobileOpen: true });
        expect(open.wrapper.find('.fixed.inset-0.z-30').exists()).toBe(true);

        await open.wrapper.find('.fixed.inset-0.z-30').trigger('click');
        expect(open.ui.isMobileOpen).toBe(false);
    });

    it('closes the mobile menu on nav click only below the md breakpoint', async () => {
        const originalWidth = window.innerWidth;

        Object.defineProperty(window, 'innerWidth', { writable: true, configurable: true, value: 500 });
        const narrow = mountSidebar(ONE_GROUP_MENU, { isMobileOpen: true });
        await narrow.wrapper.find('a').trigger('click');
        expect(narrow.ui.isMobileOpen).toBe(false);

        Object.defineProperty(window, 'innerWidth', { writable: true, configurable: true, value: 1200 });
        const wide = mountSidebar(ONE_GROUP_MENU, { isMobileOpen: true });
        await wide.wrapper.find('a').trigger('click');
        expect(wide.ui.isMobileOpen).toBe(true);

        Object.defineProperty(window, 'innerWidth', { writable: true, configurable: true, value: originalWidth });
    });

    it('closes the mobile menu on a right-to-left swipe past the threshold, and not for a short one', async () => {
        const { wrapper, ui } = mountSidebar(ONE_GROUP_MENU, { isMobileOpen: true });
        const aside = wrapper.find('#adminSidebar');

        await aside.trigger('touchstart', { touches: [{ clientX: 200 }] });
        await aside.trigger('touchend', { changedTouches: [{ clientX: 230 }] }); // delta 30, below threshold
        expect(ui.isMobileOpen).toBe(true);

        await aside.trigger('touchstart', { touches: [{ clientX: 200 }] });
        await aside.trigger('touchend', { changedTouches: [{ clientX: 270 }] }); // delta 70, past threshold
        expect(ui.isMobileOpen).toBe(false);
    });
});
