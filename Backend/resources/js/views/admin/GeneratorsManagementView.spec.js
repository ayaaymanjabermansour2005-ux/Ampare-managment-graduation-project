import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import GeneratorsManagementView from './GeneratorsManagementView.vue';

// vReveal (used as `v-reveal`) is a local <script setup> import — see
// SettingsView.spec.js for why this must be a real IntersectionObserver
// stub rather than a `global.directives` override.
vi.stubGlobal('IntersectionObserver', class {
    observe() {}
    unobserve() {}
    disconnect() {}
});

vi.mock('@/services/generatorService', () => ({
    default: {
        list: vi.fn(),
        cities: vi.fn(),
        stats: vi.fn(),
        verify: vi.fn(),
        reject: vi.fn(),
        exportUrl: vi.fn(() => '/export'),
        create: vi.fn(),
        update: vi.fn(),
        destroy: vi.fn(),
    },
}));
vi.mock('@/services/userService', () => ({
    default: { list: vi.fn() },
}));

const confirmMock = vi.fn();
vi.mock('@/composables/useConfirm', () => ({
    useConfirm: () => ({ confirm: confirmMock }),
}));

vi.mock('vue-router', () => ({
    useRoute: () => ({ query: {} }),
    RouterLink: { template: '<a><slot /></a>' },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: { all: '', view: '', edit: '', delete: '', close: '', system_running: '' },
            status: { active: '', maintenance: '', inactive: '', pending_verification: 'بانتظار الاعتماد', rejected: '' },
            dashboard: { capacity_col: '', kw_label: '', subscribers_col: '', cancel: 'إلغاء' },
            subscribers_page: { time_now: '', time_mins_ago: '', time_hours_ago: '', time_days_ago: '', subscriber_updated_message: '' },
            users_page: { saved_toast_title: '', created_toast_title: '' },
            owners_page: { generator_created_msg: '' },
            owner_applications_page: { reject_action: 'رفض المولد' },
            generators_management_page: {
                all_owners: '', all_areas: '', generators_offline: '', total_generators: '', total_capacity_suffix: '',
                needs_follow_up: '', inactive_rejected: '', high_priority: '', avg_fuel_level: '', among_tracked_units: '',
                total_monthly_revenue: '', n_subscribers_suffix: '', low_fuel_level: '', medium_fuel_level: '',
                approve_generator_title: '', approve_generator_message: '', approve_action: 'موافقة على المولد',
                approved_toast_title: '', generator_approved_message: '', approval_failed_title: 'فشلت الموافقة',
                try_again: 'تعذر تنفيذ العملية، حاول مرة أخرى',
                reject_generator_title: '', reject_notice_desc: '', rejection_reason_field_label: '',
                rejection_reason_placeholder: '', rejecting_ellipsis: '', confirm_rejection_button: 'تأكيد الرفض',
                rejected_toast_title: '', generator_rejected_message: '', rejection_failed: 'تعذر رفض المولد',
            },
        },
    },
});

async function mountComponent() {
    const generatorService = (await import('@/services/generatorService')).default;
    const userService = (await import('@/services/userService')).default;
    generatorService.list.mockResolvedValue({
        data: { data: { data: [{ id: 1, name: 'مولد الاختبار', status: 'pending_verification', fuel_percentage: null }], meta: { current_page: 1, last_page: 1, total: 1, per_page: 12 } } },
    });
    generatorService.cities.mockResolvedValue({ data: { data: [] } });
    generatorService.stats.mockResolvedValue({
        data: {
            data: {
                total: 1, total_capacity_kw: 10, active: 0, maintenance: 0, inactive: 0, rejected: 0,
                pending_verification: 1, avg_fuel_percentage: null, total_monthly_revenue_ils: 0, total_subscribers: 0,
            },
        },
    });
    userService.list.mockResolvedValue({ data: { data: [] } });

    const pinia = createPinia();
    setActivePinia(pinia);

    const wrapper = mount(GeneratorsManagementView, {
        global: {
            plugins: [i18n, pinia],
            stubs: {
                AdminGeneratorsMap: true,
                AppDropdownSelect: true,
                AdminGeneratorFormModal: true,
                GeneratorViewModal: true,
                Doughnut: true,
                AppIcon: true,
                Teleport: true,
            },
        },
    });
    await flushPromises();
    return wrapper;
}

describe('GeneratorsManagementView', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('handleVerify', () => {
        it('does nothing when the confirmation dialog is dismissed', async () => {
            confirmMock.mockResolvedValue(false);
            const generatorService = (await import('@/services/generatorService')).default;

            const wrapper = await mountComponent();
            await wrapper.find('[aria-label="موافقة على المولد"]').trigger('click');
            await flushPromises();

            expect(generatorService.verify).not.toHaveBeenCalled();
        });

        it('shows a danger toast with the normalized error message on failure', async () => {
            confirmMock.mockResolvedValue(true);
            const generatorService = (await import('@/services/generatorService')).default;
            generatorService.verify.mockRejectedValue({
                response: { status: 500, data: { message: 'تعذرت الموافقة فعليًا.' } },
            });

            const wrapper = await mountComponent();
            const { useToastStore } = await import('@/stores/toast');
            await wrapper.find('[aria-label="موافقة على المولد"]').trigger('click');
            await flushPromises();

            const toasts = useToastStore().toasts;
            expect(toasts).toHaveLength(1);
            expect(toasts[0]).toMatchObject({ type: 'danger', title: 'فشلت الموافقة', message: 'تعذرت الموافقة فعليًا.' });
        });

        it('falls back to the translated generic message on a network error', async () => {
            confirmMock.mockResolvedValue(true);
            const generatorService = (await import('@/services/generatorService')).default;
            generatorService.verify.mockRejectedValue({ message: 'Network Error' });

            const wrapper = await mountComponent();
            const { useToastStore } = await import('@/stores/toast');
            await wrapper.find('[aria-label="موافقة على المولد"]').trigger('click');
            await flushPromises();

            const toasts = useToastStore().toasts;
            expect(toasts[0].message).toBe('تعذر تنفيذ العملية، حاول مرة أخرى');
        });
    });

    describe('handleReject', () => {
        it('prioritizes the field-level "reason" error over the generic message', async () => {
            const generatorService = (await import('@/services/generatorService')).default;
            generatorService.reject.mockRejectedValue({
                response: { status: 422, data: { message: 'بيانات غير صالحة', errors: { reason: ['السبب مطلوب.'] } } },
            });

            const wrapper = await mountComponent();
            await wrapper.find('[aria-label="رفض المولد"]').trigger('click');
            await wrapper.find('textarea').setValue('سبب الرفض');
            const confirmBtn = wrapper.findAll('button').find((b) => b.text() === 'تأكيد الرفض');
            await confirmBtn.trigger('click');
            await flushPromises();

            expect(wrapper.text()).toContain('السبب مطلوب.');
        });

        it('falls back to the generic rejection-failed message when there is no reason field error', async () => {
            const generatorService = (await import('@/services/generatorService')).default;
            generatorService.reject.mockRejectedValue({
                response: { status: 500, data: { message: 'خطأ في الخادم.' } },
            });

            const wrapper = await mountComponent();
            await wrapper.find('[aria-label="رفض المولد"]').trigger('click');
            await wrapper.find('textarea').setValue('سبب الرفض');
            const confirmBtn = wrapper.findAll('button').find((b) => b.text() === 'تأكيد الرفض');
            await confirmBtn.trigger('click');
            await flushPromises();

            expect(wrapper.text()).toContain('خطأ في الخادم.');
        });
    });
});
