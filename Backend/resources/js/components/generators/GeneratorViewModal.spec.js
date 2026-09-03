import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';
import GeneratorViewModal from './GeneratorViewModal.vue';

vi.mock('@/services/generatorService', () => ({
    default: {
        timeline: vi.fn(),
        attachments: vi.fn(),
        storeAttachment: vi.fn(),
        destroyAttachment: vi.fn(),
        linkedTechnicians: vi.fn(),
        availableTechnicians: vi.fn(),
        linkTechnician: vi.fn(),
        unlinkTechnician: vi.fn(),
    },
}));

const confirmMock = vi.fn();
vi.mock('@/composables/useConfirm', () => ({
    useConfirm: () => ({ confirm: confirmMock }),
}));

import generatorService from '@/services/generatorService';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: {
                close: 'إغلاق', delete: 'حذف', edit: 'تعديل', loading: 'جارِ التحميل...',
                delete_attachment: 'حذف المرفق', unlink_technician: 'فك ربط الفني',
            },
            status: { active: 'يعمل', maintenance: 'صيانة', inactive: 'متوقف', pending_verification: 'بانتظار الاعتماد', rejected: 'مرفوض' },
            subscribers_page: { time_now: '', time_mins_ago: '', time_hours_ago: '', time_days_ago: '' },
            users_page: { deleted_toast_title: 'تم الحذف' },
            dashboard: {
                revenue_label: 'الإيراد', subscriber_label: 'مشترك', kw_label: 'ك.و', fuel_label: 'الوقود',
                maintenance_fault_log: 'سجل الصيانة والأعطال', no_history_yet: 'لا يوجد سجل بعد.',
                code: 'الرمز', area: 'المنطقة', owner: 'المالك', fuel_type_value_label: 'نوع الوقود',
                commissioned_label: 'تاريخ التشغيل', last_maintenance_label: 'آخر صيانة', notes_label: 'ملاحظات',
                fuel_updated_by_hint: 'تُحدَّث هذه النسبة{lastUpdate}.', fuel_updated_by_hint_last_update: ' — آخر تحديث {time}',
            },
            generators_management_page: {
                generator_details_title: 'تفاصيل المولد',
                attachments_title: 'المرفقات', no_attachments_yet: 'لا يوجد مرفقات بعد.',
                short_description_optional: 'وصف مختصر (اختياري)', uploading_ellipsis: 'جارٍ الرفع...',
                upload_attachment_button: 'رفع المرفق',
                responsible_technicians_title: 'الفنيون المسؤولون عن هذا المولد',
                persistent_link_desc: 'ربط دائم', no_technician_linked: 'لا يوجد فني مرتبط بشكل دائم بعد.',
                select_technician_placeholder: 'اختاري فنيًا لربطه', link_action: 'ربط',
                doc_type_photo: 'صورة المولد', doc_type_license: 'رخصة تشغيل المولد', doc_type_invoice: 'فاتورة شراء المولد',
                uploaded_toast_title: 'تم الرفع', attachment_uploaded_message: 'تم رفع المرفق بنجاح.',
                delete_attachment_title: 'حذف المرفق', delete_attachment_message: 'هل تريدين حذف هذا المرفق نهائيًا؟',
                attachment_deleted_message: 'تم حذف المرفق.',
                unlink_technician_title: 'إلغاء ربط الفني', unlink_technician_message: 'هل تريدين إلغاء ربط هذا الفني بالمولد؟',
                unlink_action: 'إلغاء الربط', unlinked_toast_title: 'تم إلغاء الربط', technician_unlinked_message: 'تم إلغاء ربط الفني بالمولد.',
                linked_toast_title: 'تم الربط', technician_linked_message: 'تم ربط الفني بالمولد بنجاح.',
                linking_failed_title: 'تعذّر الربط', try_again: 'حاولي مرة أخرى.',
            },
        },
    },
});

const GENERATOR = {
    id: 12, name: 'مولد حي الرمال', monthly_revenue_ils: 1500, active_subscriptions_count: 8,
    capacity_kw: 50, fuel_percentage: 60, status: 'active', fuel_updated_at: null,
    code: 'GEN-12', location: { city: 'غزة' }, owner: { name: 'أحمد خالد' },
    fuel_type_label: 'ديزل', created_at: '2025-01-15T00:00:00', last_maintenance_at: null, notes: null,
};

function resolveDefaults() {
    generatorService.timeline.mockResolvedValue({ data: { data: [] } });
    generatorService.attachments.mockResolvedValue({ data: { data: [] } });
    generatorService.linkedTechnicians.mockResolvedValue({ data: { data: [] } });
    generatorService.availableTechnicians.mockResolvedValue({ data: { data: [] } });
}

function mountModal() {
    setActivePinia(createPinia());
    return mount(GeneratorViewModal, {
        props: { open: false, generator: null },
        global: { plugins: [i18n], stubs: { Teleport: true } },
    });
}

async function openWith(wrapper, generator) {
    await wrapper.setProps({ open: true, generator });
    await flushPromises();
}

describe('GeneratorViewModal', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('renders nothing while closed, and fetches nothing', () => {
        resolveDefaults();
        const wrapper = mountModal();

        expect(wrapper.find('.modal-panel-pop').exists()).toBe(false);
        expect(generatorService.timeline).not.toHaveBeenCalled();
    });

    it('fetches the timeline, attachments and technicians for the opened generator', async () => {
        resolveDefaults();
        const wrapper = mountModal();
        await openWith(wrapper, GENERATOR);

        expect(generatorService.timeline).toHaveBeenCalledWith(12);
        expect(generatorService.attachments).toHaveBeenCalledWith(12);
        expect(generatorService.linkedTechnicians).toHaveBeenCalledWith(12);
        expect(generatorService.availableTechnicians).toHaveBeenCalledWith(12);
    });

    it('shows the generator summary figures and status label', async () => {
        resolveDefaults();
        const wrapper = mountModal();
        await openWith(wrapper, GENERATOR);

        expect(wrapper.text()).toContain('مولد حي الرمال');
        expect(wrapper.text()).toContain('8');
        expect(wrapper.text()).toContain('50');
        expect(wrapper.text()).toContain('60%');
        expect(wrapper.text()).toContain('يعمل');
        expect(wrapper.text()).toContain('GEN-12');
        expect(wrapper.text()).toContain('غزة');
    });

    it('shows the owner name (admin view) when the viewer does not have the generator_owner role', async () => {
        resolveDefaults();
        const wrapper = mountModal();
        await openWith(wrapper, GENERATOR);

        expect(wrapper.text()).toContain('المالك');
        expect(wrapper.text()).toContain('أحمد خالد');
        // both the swapped label row and the always-admin commissioned row show up
        expect(wrapper.findAll('.info-row').filter((r) => r.text().includes('تاريخ التشغيل'))).toHaveLength(1);
    });

    it('replaces the owner row with the commissioned date (owner view) when the viewer has the generator_owner role', async () => {
        resolveDefaults();
        const wrapper = mountModal();
        const { useAuthStore } = await import('@/stores/auth');
        useAuthStore().roles = ['generator_owner'];
        await openWith(wrapper, GENERATOR);

        expect(wrapper.text()).not.toContain('أحمد خالد');
        // commissioned_label now appears exactly once (swapped in, second row hidden)
        expect(wrapper.findAll('.info-row').filter((r) => r.text().includes('تاريخ التشغيل'))).toHaveLength(1);
        expect(wrapper.text()).toContain('2025-01-15');
    });

    it('shows empty states for timeline, attachments and linked technicians', async () => {
        resolveDefaults();
        const wrapper = mountModal();
        await openWith(wrapper, GENERATOR);

        expect(wrapper.text()).toContain('لا يوجد سجل بعد.');
        expect(wrapper.text()).toContain('لا يوجد مرفقات بعد.');
        expect(wrapper.text()).toContain('لا يوجد فني مرتبط بشكل دائم بعد.');
    });

    it('renders attachments and linked technicians once loaded', async () => {
        generatorService.timeline.mockResolvedValue({ data: { data: [] } });
        generatorService.attachments.mockResolvedValue({
            data: { data: [{ id: 1, original_name: 'رخصة.pdf', document_type: 'generator_license', file_size: 204800 }] },
        });
        generatorService.linkedTechnicians.mockResolvedValue({ data: { data: [{ id: 5, name: 'محمد فني' }] } });
        generatorService.availableTechnicians.mockResolvedValue({ data: { data: [] } });
        const wrapper = mountModal();
        await openWith(wrapper, GENERATOR);

        expect(wrapper.text()).toContain('رخصة.pdf');
        expect(wrapper.text()).toContain('رخصة تشغيل المولد');
        expect(wrapper.text()).toContain('محمد فني');
    });

    it('deletes an attachment after confirmation and shows a success toast', async () => {
        generatorService.timeline.mockResolvedValue({ data: { data: [] } });
        generatorService.attachments.mockResolvedValue({
            data: { data: [{ id: 1, original_name: 'صورة.png', document_type: 'generator_photo', file_size: 1024 }] },
        });
        generatorService.linkedTechnicians.mockResolvedValue({ data: { data: [] } });
        generatorService.availableTechnicians.mockResolvedValue({ data: { data: [] } });
        generatorService.destroyAttachment.mockResolvedValue({});
        confirmMock.mockResolvedValue(true);
        const wrapper = mountModal();
        await openWith(wrapper, GENERATOR);

        await wrapper.find('[aria-label="حذف المرفق"]').trigger('click');
        await flushPromises();

        expect(generatorService.destroyAttachment).toHaveBeenCalledWith(1);
        const { useToastStore } = await import('@/stores/toast');
        expect(useToastStore().toasts[0]).toMatchObject({ type: 'success', title: 'تم الحذف' });
        expect(wrapper.text()).not.toContain('صورة.png');
    });

    it('does not delete the attachment when confirmation is dismissed', async () => {
        generatorService.timeline.mockResolvedValue({ data: { data: [] } });
        generatorService.attachments.mockResolvedValue({
            data: { data: [{ id: 1, original_name: 'صورة.png', document_type: 'generator_photo', file_size: 1024 }] },
        });
        generatorService.linkedTechnicians.mockResolvedValue({ data: { data: [] } });
        generatorService.availableTechnicians.mockResolvedValue({ data: { data: [] } });
        confirmMock.mockResolvedValue(false);
        const wrapper = mountModal();
        await openWith(wrapper, GENERATOR);

        await wrapper.find('[aria-label="حذف المرفق"]').trigger('click');
        await flushPromises();

        expect(generatorService.destroyAttachment).not.toHaveBeenCalled();
        expect(wrapper.text()).toContain('صورة.png');
    });

    it('unlinks a technician after confirmation and refetches the technician lists', async () => {
        generatorService.timeline.mockResolvedValue({ data: { data: [] } });
        generatorService.attachments.mockResolvedValue({ data: { data: [] } });
        generatorService.linkedTechnicians.mockResolvedValue({ data: { data: [{ id: 5, name: 'محمد فني' }] } });
        generatorService.availableTechnicians.mockResolvedValue({ data: { data: [] } });
        generatorService.unlinkTechnician.mockResolvedValue({});
        confirmMock.mockResolvedValue(true);
        const wrapper = mountModal();
        await openWith(wrapper, GENERATOR);

        await wrapper.find('[aria-label="فك ربط الفني"]').trigger('click');
        await flushPromises();

        expect(generatorService.unlinkTechnician).toHaveBeenCalledWith(5, 12);
        expect(generatorService.linkedTechnicians).toHaveBeenCalledTimes(2);
    });

    it('uploads a new attachment for the current generator', async () => {
        resolveDefaults();
        generatorService.storeAttachment.mockResolvedValue({ data: { data: { id: 99, original_name: 'new.png', document_type: 'generator_photo', file_size: 500 } } });
        const wrapper = mountModal();
        await openWith(wrapper, GENERATOR);

        const uploadBtn = wrapper.findAll('button').find((b) => b.text().includes('رفع المرفق'));
        expect(uploadBtn.attributes('disabled')).toBeDefined();

        const fileInput = wrapper.find('input[type="file"]');
        const file = new File(['data'], 'new.png', { type: 'image/png' });
        Object.defineProperty(fileInput.element, 'files', { value: [file] });
        await fileInput.trigger('change');

        expect(uploadBtn.attributes('disabled')).toBeUndefined();
        await uploadBtn.trigger('click');
        await flushPromises();

        expect(generatorService.storeAttachment).toHaveBeenCalledTimes(1);
        expect(generatorService.storeAttachment.mock.calls[0][0]).toBe(12);
        expect(generatorService.storeAttachment.mock.calls[0][1]).toBeInstanceOf(FormData);
    });

    it('emits close and edit from the footer buttons', async () => {
        resolveDefaults();
        const wrapper = mountModal();
        await openWith(wrapper, GENERATOR);

        await wrapper.find('.modal-head-brand__close').trigger('click');
        expect(wrapper.emitted('close')).toHaveLength(1);

        await wrapper.findAll('button').find((b) => b.text().includes('تعديل')).trigger('click');
        expect(wrapper.emitted('edit')).toEqual([[GENERATOR]]);
    });
});
