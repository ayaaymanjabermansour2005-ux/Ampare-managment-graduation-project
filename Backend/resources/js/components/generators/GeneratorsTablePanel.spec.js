import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import GeneratorsTablePanel from './GeneratorsTablePanel.vue';

// v-reveal is a local <script setup> import — IntersectionObserver is unavailable in jsdom by default.
vi.stubGlobal('IntersectionObserver', class {
    observe() {}
    unobserve() {}
    disconnect() {}
});

vi.mock('@/services/generatorService', () => ({
    default: {
        list: vi.fn(), cities: vi.fn(), stats: vi.fn(), verify: vi.fn(), reject: vi.fn(),
        exportUrl: vi.fn(() => '/export'), create: vi.fn(), update: vi.fn(), destroy: vi.fn(),
    },
}));
vi.mock('@/services/userService', () => ({
    default: { list: vi.fn() },
}));

const confirmMock = vi.fn();
vi.mock('@/composables/useConfirm', () => ({
    useConfirm: () => ({ confirm: confirmMock }),
}));

let mockRouteQuery = {};
vi.mock('vue-router', () => ({
    useRoute: () => ({ query: mockRouteQuery }),
    RouterLink: { template: '<a><slot /></a>' },
}));

import generatorService from '@/services/generatorService';
import userService from '@/services/userService';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: {
                all: 'الكل', view: 'عرض', edit: 'تعديل', delete: 'حذف', close: 'إغلاق',
                previous_page: 'الصفحة السابقة', next_page: 'الصفحة التالية',
                view_as_table: 'عرض كجدول', view_as_grid: 'عرض كشبكة',
            },
            status: { active: 'يعمل', maintenance: 'صيانة', inactive: 'متوقف', pending_verification: 'بانتظار الاعتماد', rejected: 'مرفوض' },
            dashboard: {
                generator_col: 'المولد', city_col: 'المدينة', capacity_col: 'القدرة', kw_label: 'ك.و',
                fuel_label: 'الوقود', subscribers_col: 'المشتركون', status_col: 'الحالة', fuel_type_label: 'نوع الوقود',
                delete_generator_title: 'حذف المولد', delete_generator_subtitle: 'إجراء لا يمكن التراجع عنه',
                cancel: 'إلغاء', confirm_delete: 'نعم، احذف',
            },
            owner_dashboard: { add_generator: 'إضافة مولد جديد' },
            owner_applications_page: { reject_action: 'رفض', export_excel_title: 'تصدير Excel', print: 'طباعة' },
            subscribers_page: { actions_col: 'إجراءات', subscriber_updated_message: 'تم تحديث بيانات "{name}" بنجاح.' },
            users_page: { saved_toast_title: 'تم الحفظ', created_toast_title: 'تم الإنشاء', deleted_toast_title: 'تم الحذف' },
            owners_page: { generator_created_msg: 'تم إضافة مولد "{name}" بنجاح.' },
            generators_management_page: {
                all_owners: 'كل المالكين', all_areas: 'كل المناطق', search_placeholder: 'بحث عن مولد...',
                total_generators: 'إجمالي المولدات', inactive_rejected: 'متوقفة/مرفوضة', avg_fuel_level: 'متوسط نسبة الوقود',
                total_monthly_revenue: 'الإيراد الشهري الكلي', no_matching_generators: 'لا توجد مولدات مطابقة لبحثك',
                monthly_revenue_col: 'الإيراد الشهري', generator_deleted_message: 'تم حذف مولد "{name}" نهائيًا.',
                view_subscriptions_link: 'عرض اشتراكات هذا المولد ←',
                approve_generator_title: 'اعتماد المولد', approve_generator_message: 'سيتم اعتماد "{name}".',
                approve_action: 'اعتماد', approved_toast_title: 'تم الاعتماد', generator_approved_message: 'تم اعتماد "{name}" وإشعار المالك.',
                approval_failed_title: 'تعذّر الاعتماد', try_again: 'حاولي مرة أخرى.',
                reject_generator_title: 'رفض اعتماد المولد', reject_notice_desc: 'سيصل سبب الرفض لمالك المولد.',
                rejection_reason_field_label: 'سبب الرفض', rejection_reason_placeholder: 'مثال: صور غير واضحة',
                rejecting_ellipsis: 'جارٍ الرفض...', confirm_rejection_button: 'تأكيد الرفض',
                rejected_toast_title: 'تم الرفض', generator_rejected_message: 'تم رفض اعتماد "{name}" وإشعار المالك.',
                rejection_failed: 'تعذّر الرفض.',
                delete_generator_message: 'سيتم حذف "{name}".',
            },
        },
    },
});

function activeGenerator(overrides = {}) {
    return {
        id: 1, name: 'مولد النشط', code: 'GEN-01', status: 'active', capacity_kw: 30,
        fuel_percentage: 70, fuel_type: 'diesel', active_subscriptions_count: 4, monthly_revenue_ils: 500,
        location: { city: 'غزة' }, ...overrides,
    };
}
function pendingGenerator(overrides = {}) {
    return {
        id: 2, name: 'مولد بانتظار الاعتماد', code: 'GEN-02', status: 'pending_verification', capacity_kw: 20,
        fuel_percentage: null, fuel_type: 'gas', active_subscriptions_count: 0, monthly_revenue_ils: 0,
        location: { city: 'رفح' }, ...overrides,
    };
}

function listResponse(generators, meta = {}) {
    return { data: { data: { data: generators, meta: { current_page: 1, last_page: 1, total: generators.length, per_page: 12, ...meta } } } };
}
function statsResponse(overrides = {}) {
    return {
        data: {
            data: {
                total: 2, total_capacity_kw: 50, active: 1, maintenance: 0, inactive: 0, rejected: 0,
                pending_verification: 1, avg_fuel_percentage: 70, total_monthly_revenue_ils: 500, total_subscribers: 4,
                ...overrides,
            },
        },
    };
}

function setupDefaultMocks() {
    generatorService.list.mockResolvedValue(listResponse([activeGenerator(), pendingGenerator()]));
    generatorService.cities.mockResolvedValue({ data: { data: ['غزة', 'رفح'] } });
    generatorService.stats.mockResolvedValue(statsResponse());
    userService.list.mockResolvedValue({ data: { data: [] } });
}

// The panel's default sort is "fuel-asc" (lowest fuel first — see useGeneratorsTable's
// `sortBy` default), so with the plain fixtures above the pending generator (null fuel,
// treated as the lowest) renders before the active one. Tests that need a stable "first
// row is generator #1" order give it an explicit, lower fuel_percentage here.
function setupOrderedMocks() {
    generatorService.list.mockResolvedValue(listResponse([
        activeGenerator({ fuel_percentage: 10 }),
        pendingGenerator({ fuel_percentage: 90 }),
    ]));
    generatorService.cities.mockResolvedValue({ data: { data: ['غزة', 'رفح'] } });
    generatorService.stats.mockResolvedValue(statsResponse());
    userService.list.mockResolvedValue({ data: { data: [] } });
}

const STUBS = { AppDropdownSelect: true, AdminGeneratorFormModal: true, GeneratorViewModal: true, AppIcon: true, Teleport: true };

async function mountPanel(props = {}) {
    const pinia = createPinia();
    setActivePinia(pinia);
    const wrapper = mount(GeneratorsTablePanel, {
        props,
        global: { plugins: [i18n, pinia], stubs: STUBS },
    });
    await flushPromises();
    return wrapper;
}

describe('GeneratorsTablePanel', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        mockRouteQuery = {};
    });

    it('shows loading skeletons before the initial fetch resolves', () => {
        generatorService.list.mockReturnValue(new Promise(() => {}));
        generatorService.cities.mockReturnValue(new Promise(() => {}));
        generatorService.stats.mockReturnValue(new Promise(() => {}));
        userService.list.mockReturnValue(new Promise(() => {}));
        const pinia = createPinia();
        setActivePinia(pinia);
        const wrapper = mount(GeneratorsTablePanel, { global: { plugins: [i18n, pinia], stubs: STUBS } });

        expect(wrapper.find('.thumb-loading').exists()).toBe(true);
    });

    it('renders the KPI cards from the loaded stats', async () => {
        setupDefaultMocks();
        const wrapper = await mountPanel();

        expect(wrapper.text()).toContain('إجمالي المولدات');
        expect(wrapper.text()).toContain('متوسط نسبة الوقود');
        expect(wrapper.text()).toContain('الإيراد الشهري الكلي');
    });

    it('renders a row per generator with its city, capacity and status', async () => {
        setupDefaultMocks();
        const wrapper = await mountPanel();

        expect(wrapper.text()).toContain('مولد النشط');
        expect(wrapper.text()).toContain('غزة');
        expect(wrapper.text()).toContain('30');
        expect(wrapper.text()).toContain('يعمل');
        expect(wrapper.text()).toContain('مولد بانتظار الاعتماد');
        expect(wrapper.text()).toContain('بانتظار الاعتماد');
    });

    it('shows the empty state when there are no matching generators', async () => {
        generatorService.list.mockResolvedValue(listResponse([]));
        generatorService.cities.mockResolvedValue({ data: { data: [] } });
        generatorService.stats.mockResolvedValue(statsResponse());
        userService.list.mockResolvedValue({ data: { data: [] } });
        const wrapper = await mountPanel();

        expect(wrapper.text()).toContain('لا توجد مولدات مطابقة لبحثك');
    });

    it('shows the normalized error message when the list request fails', async () => {
        generatorService.list.mockRejectedValue({ message: 'Network Error' });
        generatorService.cities.mockResolvedValue({ data: { data: [] } });
        generatorService.stats.mockResolvedValue(statsResponse());
        userService.list.mockResolvedValue({ data: { data: [] } });
        const wrapper = await mountPanel();

        expect(wrapper.find('.thumb-loading').exists()).toBe(false);
        // owner_generators.load_error is untranslated here, but a concrete error message must still render
        expect(wrapper.find('.text-\\[\\#D9534F\\]').exists()).toBe(true);
    });

    it('switches from table view to grid view', async () => {
        setupDefaultMocks();
        const wrapper = await mountPanel();

        expect(wrapper.find('table').exists()).toBe(true);
        await wrapper.find('[aria-label="عرض كشبكة"]').trigger('click');

        expect(wrapper.find('table').exists()).toBe(false);
        expect(wrapper.text()).toContain('مولد النشط');
    });

    it('re-fetches with the selected status filter when a status pill is clicked', async () => {
        setupDefaultMocks();
        const wrapper = await mountPanel();
        expect(generatorService.list).toHaveBeenCalledTimes(1);

        await wrapper.findAll('button').find((b) => b.text() === 'يعمل').trigger('click');
        await flushPromises();

        expect(generatorService.list).toHaveBeenCalledTimes(2);
        expect(generatorService.list.mock.calls[1][0]).toMatchObject({ status: 'active' });
    });

    it('fetches owners on first use and opens the add modal', async () => {
        setupDefaultMocks();
        const wrapper = await mountPanel();
        expect(userService.list).toHaveBeenCalledTimes(1); // called once on mount already

        await wrapper.findAll('button').find((b) => b.text().includes('إضافة مولد جديد')).trigger('click');
        await flushPromises();

        const formModal = wrapper.findComponent({ name: 'AdminGeneratorFormModal' });
        expect(formModal.props('open')).toBe(true);
        expect(formModal.props('generator')).toBe(null);
    });

    it('opens the edit modal with the clicked generator', async () => {
        setupOrderedMocks();
        const wrapper = await mountPanel();

        await wrapper.find('[aria-label="تعديل"]').trigger('click');

        const formModal = wrapper.findComponent({ name: 'AdminGeneratorFormModal' });
        expect(formModal.props('open')).toBe(true);
        expect(formModal.props('generator').id).toBe(1);
    });

    it('opens the view modal with the clicked generator', async () => {
        setupOrderedMocks();
        const wrapper = await mountPanel();

        await wrapper.find('[aria-label="عرض"]').trigger('click');

        const viewModal = wrapper.findComponent({ name: 'GeneratorViewModal' });
        expect(viewModal.props('open')).toBe(true);
        expect(viewModal.props('generator').id).toBe(1);
    });

    it('opens the view modal automatically for the ?highlight= route query id', async () => {
        mockRouteQuery = { highlight: '2' };
        setupDefaultMocks();
        const wrapper = await mountPanel();

        const viewModal = wrapper.findComponent({ name: 'GeneratorViewModal' });
        expect(viewModal.props('open')).toBe(true);
        expect(viewModal.props('generator').id).toBe(2);
    });

    it('does nothing when the approve confirmation is dismissed', async () => {
        confirmMock.mockResolvedValue(false);
        setupDefaultMocks();
        const wrapper = await mountPanel();

        await wrapper.find('[aria-label="اعتماد"]').trigger('click');
        await flushPromises();

        expect(generatorService.verify).not.toHaveBeenCalled();
    });

    it('verifies the generator and shows a success toast after confirmation', async () => {
        confirmMock.mockResolvedValue(true);
        setupDefaultMocks();
        generatorService.verify.mockResolvedValue({});
        const wrapper = await mountPanel();

        await wrapper.find('[aria-label="اعتماد"]').trigger('click');
        await flushPromises();

        expect(generatorService.verify).toHaveBeenCalledWith(2);
        const { useToastStore } = await import('@/stores/toast');
        expect(useToastStore().toasts[0]).toMatchObject({ type: 'success', title: 'تم الاعتماد' });
    });

    it('shows a danger toast with the normalized message when verification fails', async () => {
        confirmMock.mockResolvedValue(true);
        setupDefaultMocks();
        generatorService.verify.mockRejectedValue({ response: { status: 500, data: { message: 'خطأ بالخادم.' } } });
        const wrapper = await mountPanel();

        await wrapper.find('[aria-label="اعتماد"]').trigger('click');
        await flushPromises();

        const { useToastStore } = await import('@/stores/toast');
        expect(useToastStore().toasts[0]).toMatchObject({ type: 'danger', title: 'تعذّر الاعتماد', message: 'خطأ بالخادم.' });
    });

    it('rejects a generator with the typed reason', async () => {
        setupDefaultMocks();
        generatorService.reject.mockResolvedValue({});
        const wrapper = await mountPanel();

        await wrapper.find('[aria-label="رفض"]').trigger('click');
        await wrapper.find('textarea').setValue('صور غير واضحة');
        await wrapper.findAll('button').find((b) => b.text() === 'تأكيد الرفض').trigger('click');
        await flushPromises();

        expect(generatorService.reject).toHaveBeenCalledWith(2, 'صور غير واضحة');
        expect(wrapper.text()).not.toContain('تأكيد الرفض');
    });

    it('shows the field-level rejection reason error over the generic failure message', async () => {
        setupDefaultMocks();
        generatorService.reject.mockRejectedValue({
            response: { status: 422, data: { message: 'بيانات غير صالحة', errors: { reason: ['السبب قصير جدًا.'] } } },
        });
        const wrapper = await mountPanel();

        await wrapper.find('[aria-label="رفض"]').trigger('click');
        await wrapper.find('textarea').setValue('x');
        await wrapper.findAll('button').find((b) => b.text() === 'تأكيد الرفض').trigger('click');
        await flushPromises();

        expect(wrapper.text()).toContain('السبب قصير جدًا.');
    });

    it('deletes a generator after confirming, and shows the subscriptions link when deletion is blocked', async () => {
        setupOrderedMocks();
        generatorService.destroy.mockRejectedValueOnce({
            response: { status: 422, data: { message: 'بيانات غير صالحة', errors: { generator: ['مرتبط باشتراكات فعالة.'] } } },
        });
        const wrapper = await mountPanel();

        await wrapper.find('[aria-label="حذف"]').trigger('click');
        await wrapper.findAll('button').find((b) => b.text() === 'نعم، احذف').trigger('click');
        await flushPromises();

        expect(generatorService.destroy).toHaveBeenCalledWith(1);
        expect(wrapper.text()).toContain('مرتبط باشتراكات فعالة.');
        expect(wrapper.text()).toContain('عرض اشتراكات هذا المولد ←');
    });

    it('submits the add-generator form and shows the created toast', async () => {
        setupDefaultMocks();
        generatorService.create.mockResolvedValue({});
        const wrapper = await mountPanel();

        await wrapper.findAll('button').find((b) => b.text().includes('إضافة مولد جديد')).trigger('click');
        await flushPromises();
        const formModal = wrapper.findComponent({ name: 'AdminGeneratorFormModal' });
        await formModal.vm.$emit('submit', { name: 'مولد مضاف حديثًا' });
        await flushPromises();

        expect(generatorService.create).toHaveBeenCalledWith({ name: 'مولد مضاف حديثًا' });
        const { useToastStore } = await import('@/stores/toast');
        expect(useToastStore().toasts[0]).toMatchObject({ type: 'success', title: 'تم الإنشاء', message: 'تم إضافة مولد "مولد مضاف حديثًا" بنجاح.' });
        expect(formModal.props('open')).toBe(false);
    });
});
