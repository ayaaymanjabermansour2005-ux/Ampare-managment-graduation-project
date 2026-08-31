import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import SettingsView from './SettingsView.vue';

// vReveal (used as `v-reveal`) is a local <script setup> import, not a global
// directive, so it cannot be overridden via `global.directives` — it resolves
// its real implementation, which uses IntersectionObserver (unavailable in
// jsdom by default). Same fix as ContactSection.spec.js.
vi.stubGlobal('IntersectionObserver', class {
    observe() {}
    unobserve() {}
    disconnect() {}
});

vi.mock('@/services/settingService', () => ({
    default: { index: vi.fn(), update: vi.fn() },
}));
vi.mock('@/services/neighborhoodService', () => ({
    default: { list: vi.fn(), store: vi.fn(), update: vi.fn(), destroy: vi.fn() },
}));

const confirmMock = vi.fn();
vi.mock('@/composables/useConfirm', () => ({
    useConfirm: () => ({ confirm: confirmMock }),
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: { home: '', delete: 'حذف', edit: 'تعديل' },
            menu_groups: { general: 'عام' },
            users_page: { saving_ellipsis: '', save_action: 'حفظ التعديل', add_button: 'إضافة' },
            dashboard: { cancel: '' },
            settings_page: {
                title: '', subtitle: '',
                tab_pages: '', tab_keys: '', tab_registration: '', tab_maintenance: '', tab_neighborhoods: 'الأحياء',
                load_failed: 'تعذر تحميل الإعدادات',
                save_failed: 'تعذر حفظ الإعدادات',
                save_settings_button: 'حفظ',
                settings_saved_success: '',
                field_site_name: '', field_logo_url: '', field_favicon_url: '', field_email_logo_url: '',
                field_invoice_logo_url: '', field_default_language: '', field_default_currency: '',
                field_default_ampere_price: '', field_platform_fee_percentage: '', field_tax_percentage: '',
                field_fallback_usd_ils_rate: '',
                new_neighborhood_placeholder: '', new_neighborhood_en_placeholder: '',
                no_neighborhoods_yet: '', neighborhood_en_missing: '',
                add_neighborhood_failed: 'تعذر إضافة الحي',
                update_neighborhood_failed: 'تعذر تعديل الحي',
                delete_neighborhood_failed: 'تعذر حذف الحي',
                delete_neighborhood_title: '', delete_neighborhood_confirm_message: '', deleting_ellipsis: '',
            },
        },
    },
});

async function mountComponent() {
    const wrapper = mount(SettingsView, {
        global: {
            plugins: [i18n],
            stubs: { AppIcon: true },
        },
    });
    await flushPromises();
    return wrapper;
}

describe('SettingsView', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('fetchSettings sets the generic load error via normalizeApiError on failure', async () => {
        const settingService = (await import('@/services/settingService')).default;
        settingService.index.mockRejectedValue({ message: 'Network Error' });

        const wrapper = await mountComponent();

        expect(wrapper.text()).toContain('تعذر تحميل الإعدادات');
    });

    describe('handleSave (general tab)', () => {
        it('shows the generic save error via normalizeApiError on failure', async () => {
            const settingService = (await import('@/services/settingService')).default;
            settingService.index.mockResolvedValue({ data: { data: {} } });
            settingService.update.mockRejectedValue({
                response: { status: 500, data: { message: 'فشل الحفظ الفعلي.' } },
            });

            const wrapper = await mountComponent();
            await wrapper.find('form').trigger('submit.prevent');
            await flushPromises();

            expect(wrapper.text()).toContain('فشل الحفظ الفعلي.');
        });

        it('falls back to the translated generic message on a network error', async () => {
            const settingService = (await import('@/services/settingService')).default;
            settingService.index.mockResolvedValue({ data: { data: {} } });
            settingService.update.mockRejectedValue({ message: 'Network Error' });

            const wrapper = await mountComponent();
            await wrapper.find('form').trigger('submit.prevent');
            await flushPromises();

            expect(wrapper.text()).toContain('تعذر حفظ الإعدادات');
        });
    });

    describe('neighborhoods tab', () => {
        async function goToNeighborhoodsTab(wrapper) {
            const neighborhoodService = (await import('@/services/neighborhoodService')).default;
            neighborhoodService.list.mockResolvedValue({ data: { data: [{ id: 1, name: 'رام الله', name_en: 'Ramallah' }] } });
            const tabs = wrapper.findAll('button').filter((b) => b.text() === 'الأحياء');
            await tabs[0].trigger('click');
            await flushPromises();
        }

        it('addNeighborhood: prioritizes the field-level "name" error over the generic message', async () => {
            const settingService = (await import('@/services/settingService')).default;
            const neighborhoodService = (await import('@/services/neighborhoodService')).default;
            settingService.index.mockResolvedValue({ data: { data: {} } });
            neighborhoodService.store.mockRejectedValue({
                response: { status: 422, data: { message: 'بيانات غير صالحة', errors: { name: ['هذا الاسم مستخدم مسبقًا.'] } } },
            });

            const wrapper = await mountComponent();
            await goToNeighborhoodsTab(wrapper);
            await wrapper.find('input[placeholder=""]').setValue('رام الله'); // new_neighborhood_placeholder is ''
            const addBtn = wrapper.findAll('button').find((b) => b.text() === 'إضافة');
            await addBtn.trigger('click');
            await flushPromises();

            expect(wrapper.text()).toContain('هذا الاسم مستخدم مسبقًا.');
        });

        it('addNeighborhood: falls back to the generic message when there is no name field error', async () => {
            const settingService = (await import('@/services/settingService')).default;
            const neighborhoodService = (await import('@/services/neighborhoodService')).default;
            settingService.index.mockResolvedValue({ data: { data: {} } });
            neighborhoodService.store.mockRejectedValue({
                response: { status: 500, data: { message: 'خطأ في الخادم.' } },
            });

            const wrapper = await mountComponent();
            await goToNeighborhoodsTab(wrapper);
            const textInputs = wrapper.findAll('input[type="text"]');
            await textInputs[0].setValue('حي جديد');
            const addBtn = wrapper.findAll('button').find((b) => b.text() === 'إضافة');
            await addBtn.trigger('click');
            await flushPromises();

            expect(wrapper.text()).toContain('خطأ في الخادم.');
        });

        it('deleteNeighborhood: asks for confirmation first and does nothing if dismissed', async () => {
            const settingService = (await import('@/services/settingService')).default;
            const neighborhoodService = (await import('@/services/neighborhoodService')).default;
            settingService.index.mockResolvedValue({ data: { data: {} } });
            confirmMock.mockResolvedValue(false);

            const wrapper = await mountComponent();
            await goToNeighborhoodsTab(wrapper);
            const deleteBtn = wrapper.findAll('button').find((b) => b.text() === 'حذف');
            await deleteBtn.trigger('click');
            await flushPromises();

            expect(neighborhoodService.destroy).not.toHaveBeenCalled();
        });

        it('deleteNeighborhood: prioritizes the field-level "neighborhood" error over the generic message', async () => {
            const settingService = (await import('@/services/settingService')).default;
            const neighborhoodService = (await import('@/services/neighborhoodService')).default;
            settingService.index.mockResolvedValue({ data: { data: {} } });
            confirmMock.mockResolvedValue(true);
            neighborhoodService.destroy.mockRejectedValue({
                response: {
                    status: 422,
                    data: { message: 'بيانات غير صالحة', errors: { neighborhood: ['لا يمكن حذف حي مرتبط بمشتركين.'] } },
                },
            });

            const wrapper = await mountComponent();
            await goToNeighborhoodsTab(wrapper);
            const deleteBtn = wrapper.findAll('button').find((b) => b.text() === 'حذف');
            await deleteBtn.trigger('click');
            await flushPromises();

            expect(wrapper.text()).toContain('لا يمكن حذف حي مرتبط بمشتركين.');
        });

        it('saveEdit: prioritizes the field-level "name" error over the generic message', async () => {
            const settingService = (await import('@/services/settingService')).default;
            const neighborhoodService = (await import('@/services/neighborhoodService')).default;
            settingService.index.mockResolvedValue({ data: { data: {} } });
            neighborhoodService.update.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { name: ['الاسم مكرر.'] } } },
            });

            const wrapper = await mountComponent();
            await goToNeighborhoodsTab(wrapper);
            const editBtn = wrapper.findAll('button').find((b) => b.text() === 'تعديل');
            await editBtn.trigger('click');
            const saveEditBtn = wrapper.findAll('button').find((b) => b.text() === 'حفظ التعديل');
            await saveEditBtn.trigger('click');
            await flushPromises();

            expect(wrapper.text()).toContain('الاسم مكرر.');
        });

        it('saveEdit: falls back to the generic message when there is no name field error', async () => {
            const settingService = (await import('@/services/settingService')).default;
            const neighborhoodService = (await import('@/services/neighborhoodService')).default;
            settingService.index.mockResolvedValue({ data: { data: {} } });
            neighborhoodService.update.mockRejectedValue({
                response: { status: 500, data: { message: 'خطأ في الخادم أثناء التعديل.' } },
            });

            const wrapper = await mountComponent();
            await goToNeighborhoodsTab(wrapper);
            const editBtn = wrapper.findAll('button').find((b) => b.text() === 'تعديل');
            await editBtn.trigger('click');
            const saveEditBtn = wrapper.findAll('button').find((b) => b.text() === 'حفظ التعديل');
            await saveEditBtn.trigger('click');
            await flushPromises();

            expect(wrapper.text()).toContain('خطأ في الخادم أثناء التعديل.');
        });
    });
});
