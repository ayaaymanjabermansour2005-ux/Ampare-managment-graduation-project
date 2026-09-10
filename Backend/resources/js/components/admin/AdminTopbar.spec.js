import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import AdminTopbar from './AdminTopbar.vue';

vi.mock('@/services/generatorService', () => ({ default: { list: vi.fn() } }));
vi.mock('@/services/userService', () => ({ default: { list: vi.fn() } }));
vi.mock('@/services/invoiceService', () => ({ default: { list: vi.fn() } }));
vi.mock('@/services/conversationService', () => ({ default: { list: vi.fn(), unreadCount: vi.fn() } }));
vi.mock('@/services/notificationService', () => ({
    default: { getAll: vi.fn(), unreadCount: vi.fn(), markAsRead: vi.fn(), markAllAsRead: vi.fn(), delete: vi.fn() },
}));

const { mockCan } = vi.hoisted(() => ({ mockCan: vi.fn(() => true) }));
vi.mock('@/composables/usePermissions', () => ({ usePermissions: () => ({ can: mockCan }) }));

const { mockSetLocale } = vi.hoisted(() => ({ mockSetLocale: vi.fn() }));
vi.mock('@/i18n', () => ({ setLocale: mockSetLocale }));

const { pushMock } = vi.hoisted(() => ({ pushMock: vi.fn() }));
vi.mock('vue-router', () => ({
    useRouter: () => ({ push: pushMock }),
    RouterLink: { props: ['to'], template: '<a :data-to="JSON.stringify(to)"><slot /></a>' },
}));

import generatorService from '@/services/generatorService';
import userService from '@/services/userService';
import invoiceService from '@/services/invoiceService';
import conversationService from '@/services/conversationService';
import notificationService from '@/services/notificationService';
import { useAdminUiStore } from '@/stores/adminUi';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: {
                open_menu: 'فتح القائمة',
                search_placeholder: 'ابحث عن مولد، صفحة...',
                clear_search: 'مسح البحث',
                searching: 'جارِ البحث...',
                no_results_for: 'لا توجد نتائج لـ "{query}"',
                view_all_in: 'عرض الكل بـ{label}',
                quick_add: 'إضافة سريعة',
                fullscreen: 'ملء الشاشة',
                conversations: 'المحادثات',
                view_all: 'عرض الكل',
                loading: 'جارِ التحميل...',
                no_conversations: 'لا توجد محادثات',
                notifications: 'الإشعارات',
                mark_all_read: 'تعليم الكل كمقروء',
                no_notifications: 'لا توجد إشعارات',
                dark_mode: 'الوضع الليلي',
                toggle_theme: 'تبديل الوضع الليلي/النهاري',
                toggle_language: 'تبديل اللغة',
                system_running: 'النظام يعمل بكفاءة',
                invoice_hash: 'فاتورة #{id}',
                pages_group_label: 'صفحات',
            },
            menu: {
                generator_owners: 'أصحاب المولدات',
                subscribers: 'المشتركون',
                invoices: 'الفواتير',
                technicians: 'الفنيون',
            },
            subscribers_page: {
                time_now: 'الآن',
                time_mins_ago: 'قبل {mins} دقيقة',
                time_hours_ago: 'قبل {hours} ساعة',
                time_days_ago: 'قبل {days} يوم',
            },
        },
    },
});

function threeLevelList(items, total) {
    // Matches generatorService/userService/invoiceService.list()'s real shape:
    // the raw axios response, whose body is itself wrapped once more by the
    // backend's generic envelope around a Laravel paginator.
    return { data: { data: { data: items, meta: { total: total ?? items.length } } } };
}

function mountTopbar() {
    const pinia = createPinia();
    setActivePinia(pinia);
    const wrapper = mount(AdminTopbar, { global: { plugins: [i18n, pinia] } });
    return { wrapper, pinia };
}

describe('AdminTopbar', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockCan.mockReturnValue(true);
        generatorService.list.mockResolvedValue(threeLevelList([]));
        userService.list.mockResolvedValue(threeLevelList([]));
        invoiceService.list.mockResolvedValue(threeLevelList([]));
        conversationService.list.mockResolvedValue({ data: { data: [] } });
        conversationService.unreadCount.mockResolvedValue({ data: { data: { unread_count: 0 } } });
        notificationService.getAll.mockResolvedValue({
            data: { data: [], meta: { current_page: 1, last_page: 1, total: 0, per_page: 15 } },
        });
        notificationService.unreadCount.mockResolvedValue({ data: { unread_count: 0 } });
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('fetches notifications on mount always, and conversations only when the admin can view them', async () => {
        const { wrapper } = mountTopbar();
        await flushPromises();

        expect(notificationService.getAll).toHaveBeenCalled();
        expect(conversationService.list).toHaveBeenCalled();
        expect(wrapper.find('[aria-label="المحادثات"]').exists()).toBe(true);
    });

    it('hides the conversations button and skips fetching conversations without permission', async () => {
        mockCan.mockReturnValue(false);
        const { wrapper } = mountTopbar();
        await flushPromises();

        expect(conversationService.list).not.toHaveBeenCalled();
        expect(conversationService.unreadCount).not.toHaveBeenCalled();
        expect(wrapper.find('[aria-label="المحادثات"]').exists()).toBe(false);
    });

    it('debounces the search input, fetches all three categories, and shows "view all" only where more results exist than shown', async () => {
        vi.useFakeTimers();
        generatorService.list.mockResolvedValue(
            threeLevelList([{ id: 1, name: 'مولد زيتون الرئيسي', location: { city: 'غزة' } }], 3),
        );
        userService.list.mockResolvedValue(threeLevelList([{ id: 10, name: 'أحمد زيتون', email: 'a@example.com' }], 1));
        invoiceService.list.mockResolvedValue(threeLevelList([{ id: 55, subscriber: { name: 'زيتون للفواتير' } }], 1));
        const { wrapper } = mountTopbar();
        await flushPromises();

        const input = wrapper.find('input[placeholder="ابحث عن مولد، صفحة..."]');
        await input.setValue('زيتون');
        expect(generatorService.list).not.toHaveBeenCalledWith(expect.objectContaining({ search: 'زيتون' }));

        await vi.advanceTimersByTimeAsync(300);
        await flushPromises();

        expect(generatorService.list).toHaveBeenCalledWith({ search: 'زيتون', per_page: 4 });
        expect(userService.list).toHaveBeenCalledWith({ role: 'subscriber', search: 'زيتون', per_page: 4 });
        expect(invoiceService.list).toHaveBeenCalledWith({ search: 'زيتون', per_page: 4 });

        expect(wrapper.text()).toContain('مولد زيتون الرئيسي');
        expect(wrapper.text()).toContain('أحمد زيتون');
        expect(wrapper.text()).toContain('فاتورة #55');

        const viewAllLinks = wrapper.findAll('a').filter((a) => a.text().includes('عرض الكل بـ'));
        expect(viewAllLinks).toHaveLength(1);
        expect(viewAllLinks[0].text()).toContain('أصحاب المولدات');
    });

    it('clicking a search result navigates via the router and closes the results dropdown', async () => {
        vi.useFakeTimers();
        generatorService.list.mockResolvedValue(
            threeLevelList([{ id: 1, name: 'مولد زيتون الرئيسي', location: { city: 'غزة' } }]),
        );
        const { wrapper } = mountTopbar();
        await flushPromises();
        await wrapper.find('input[placeholder="ابحث عن مولد، صفحة..."]').setValue('زيتون');
        await vi.advanceTimersByTimeAsync(300);
        await flushPromises();

        await wrapper.find('.dropdown-row').trigger('click');

        expect(pushMock).toHaveBeenCalledWith({ name: 'admin.generator-owners', query: { q: 'مولد زيتون الرئيسي' } });
        expect(wrapper.find('.dropdown-row').exists()).toBe(false);
    });

    it('shows a clear button once a query is typed, and clicking it resets the search state', async () => {
        const { wrapper } = mountTopbar();
        const input = wrapper.find('input[placeholder="ابحث عن مولد، صفحة..."]');
        expect(wrapper.find('[aria-label="مسح البحث"]').exists()).toBe(false);

        await input.setValue('زيتون');
        expect(wrapper.find('[aria-label="مسح البحث"]').exists()).toBe(true);

        await wrapper.find('[aria-label="مسح البحث"]').trigger('click');

        expect(input.element.value).toBe('');
        expect(wrapper.find('[aria-label="مسح البحث"]').exists()).toBe(false);
    });

    it('ArrowDown moves the active result so Enter navigates to the second (not first) match', async () => {
        vi.useFakeTimers();
        generatorService.list.mockResolvedValue(threeLevelList([{ id: 1, name: 'مولد أ', location: {} }]));
        userService.list.mockResolvedValue(threeLevelList([{ id: 2, name: 'مشترك ب' }]));
        const { wrapper } = mountTopbar();
        await flushPromises();
        const input = wrapper.find('input[placeholder="ابحث عن مولد، صفحة..."]');
        // 'س' doesn't appear in any of the static page labels, so this only matches the
        // two mocked generator/subscriber results below (the mocks ignore the query text
        // itself) — a query like 'ب' would incidentally also match the "أصحاب المولدات"
        // static page label and add a spurious third row.
        await input.setValue('س');
        await vi.advanceTimersByTimeAsync(300);
        await flushPromises();
        expect(wrapper.findAll('.dropdown-row')).toHaveLength(2);

        await input.trigger('keydown', { key: 'ArrowDown' });
        await input.trigger('keydown', { key: 'Enter' });

        expect(pushMock).toHaveBeenCalledWith({ name: 'admin.subscribers', query: { q: 'مشترك ب' } });
    });

    it('Escape closes the open results dropdown and clears the query', async () => {
        vi.useFakeTimers();
        generatorService.list.mockResolvedValue(threeLevelList([{ id: 1, name: 'مولد أ', location: {} }]));
        const { wrapper } = mountTopbar();
        await flushPromises();
        const input = wrapper.find('input[placeholder="ابحث عن مولد، صفحة..."]');
        await input.setValue('أ');
        await vi.advanceTimersByTimeAsync(300);
        await flushPromises();
        expect(wrapper.find('.dropdown-row').exists()).toBe(true);

        await input.trigger('keydown', { key: 'Escape' });

        expect(input.element.value).toBe('');
        expect(wrapper.find('.dropdown-row').exists()).toBe(false);
    });

    it('the quick-add panel only lists actions the admin has permission for', async () => {
        mockCan.mockImplementation((perm) => perm !== 'payments.view');
        const { wrapper } = mountTopbar();
        await flushPromises();

        await wrapper.find('[aria-label="إضافة سريعة"]').trigger('click');

        const routes = wrapper
            .findAll('a[data-to]')
            .map((a) => JSON.parse(a.attributes('data-to'))?.name)
            .filter((name) => name && name.startsWith('admin.'));
        expect(routes).not.toContain('admin.payments');
        expect(routes).toContain('admin.articles');
        expect(routes).toHaveLength(5);

        // FIX (تدقيق شامل للوحة الأدمن): كانت أزرار الإضافة السريعة تعرض
        // أيقونة بدون أي نص إطلاقًا (q.label غير موجود بـ adminQuickActions.js،
        // والقائمة فيها labelKey فقط بدون ترجمة بالـ template).
        const links = wrapper.findAll('a[data-to]').filter((a) => {
            const to = JSON.parse(a.attributes('data-to') ?? 'null');
            return to?.name?.startsWith('admin.');
        });
        links.forEach((link) => expect(link.text().trim()).not.toBe(''));
    });

    it('shows the fetched notifications with an unread dot, and marking all as read clears it', async () => {
        notificationService.getAll.mockResolvedValue({
            data: {
                data: [
                    {
                        id: 1,
                        type: 'FaultReportedNotification',
                        title: 'عطل جديد',
                        message: 'تم الإبلاغ عن عطل',
                        is_read: false,
                        created_at: '2024-01-01 08:00:00',
                    },
                ],
                meta: { current_page: 1, last_page: 1, total: 1, per_page: 15 },
            },
        });
        notificationService.unreadCount.mockResolvedValue({ data: { unread_count: 1 } });
        notificationService.markAllAsRead.mockResolvedValue({ data: {} });
        const { wrapper } = mountTopbar();
        await flushPromises();

        expect(wrapper.find('.notif-dot').exists()).toBe(true);

        await wrapper.find('[aria-label="الإشعارات"]').trigger('click');
        expect(wrapper.text()).toContain('عطل جديد');

        await wrapper.findAll('button').find((b) => b.text() === 'تعليم الكل كمقروء').trigger('click');
        await flushPromises();

        expect(notificationService.markAllAsRead).toHaveBeenCalledTimes(1);
        expect(wrapper.find('.notif-dot').exists()).toBe(false);
    });

    it('shows the fetched conversations and navigates to the selected one on click', async () => {
        conversationService.list.mockResolvedValue({
            data: {
                data: [
                    {
                        id: 5,
                        other_participant: { name: 'صاحب المولد' },
                        latest_message: { text: 'مرحبا', created_at: '2024-01-01 08:00:00' },
                        unread_count: 2,
                    },
                ],
            },
        });
        const { wrapper } = mountTopbar();
        await flushPromises();

        await wrapper.find('[aria-label="المحادثات"]').trigger('click');
        expect(wrapper.text()).toContain('صاحب المولد');
        expect(wrapper.text()).toContain('مرحبا');

        await wrapper.find('.dropdown-row.unread').trigger('click');

        expect(pushMock).toHaveBeenCalledWith({ name: 'admin.messages', query: { open: 5 } });
        expect(wrapper.find('.dropdown-row.unread').exists()).toBe(false);
    });

    it('toggles dark mode via the theme button', async () => {
        const { wrapper, pinia } = mountTopbar();
        const ui = useAdminUiStore(pinia);
        expect(ui.isDark).toBe(false);

        await wrapper.find('[aria-label="تبديل الوضع الليلي/النهاري"]').trigger('click');

        expect(ui.isDark).toBe(true);
    });

    it('shows "EN" while the locale is Arabic, and requests a switch to "en" when clicked', async () => {
        const { wrapper } = mountTopbar();
        expect(wrapper.text()).toContain('EN');

        await wrapper.find('[aria-label="تبديل اللغة"]').trigger('click');

        expect(mockSetLocale).toHaveBeenCalledWith('en');
    });

    it('requests fullscreen when not already in fullscreen mode', async () => {
        document.documentElement.requestFullscreen = vi.fn();
        const { wrapper } = mountTopbar();

        await wrapper.find('[aria-label="ملء الشاشة"]').trigger('click');

        expect(document.documentElement.requestFullscreen).toHaveBeenCalledTimes(1);
    });

    it('clicking outside any dropdown-scoped control closes all open panels', async () => {
        const { wrapper } = mountTopbar();
        await flushPromises();
        await wrapper.find('[aria-label="إضافة سريعة"]').trigger('click');
        expect(wrapper.find('.glass-card').exists()).toBe(true);

        document.body.click();
        await flushPromises();

        expect(wrapper.find('.glass-card').exists()).toBe(false);
    });
});
