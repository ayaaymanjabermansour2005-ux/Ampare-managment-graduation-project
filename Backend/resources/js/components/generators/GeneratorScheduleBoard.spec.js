import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import GeneratorScheduleBoard from './GeneratorScheduleBoard.vue';

vi.mock('@/services/generatorScheduleService', () => ({
    default: { list: vi.fn(), create: vi.fn(), destroy: vi.fn() },
}));

const confirmMock = vi.fn();
vi.mock('@/composables/useConfirm', () => ({
    useConfirm: () => ({ confirm: confirmMock }),
}));

import generatorScheduleService from '@/services/generatorScheduleService';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            common: { delete: 'حذف' },
            owner_generators: {
                schedule_load_error: 'تعذّر تحميل جدول التشغيل.',
            },
            generator_schedule_board: {
                title: 'جدول التشغيل',
                publish_new: 'نشر جدول جديد',
                from_label: 'من',
                to_label: 'إلى',
                note_label: 'ملاحظة (اختياري)',
                note_placeholder: 'مثلاً: بسبب نقص الديزل',
                publishing: 'جاري النشر...',
                publish: 'نشر',
                active_now_label: 'شغّال الآن',
                empty_state: 'لا يوجد جدول تشغيل معلَن حاليًا.',
                delete_confirm_title: 'حذف جدول التشغيل',
                delete_confirm_message: 'هل أنت متأكد من حذف جدول التشغيل هذا؟',
            },
        },
    },
});

function mountBoard(props = {}) {
    return mount(GeneratorScheduleBoard, {
        props: { generatorId: 4, canManage: false, ...props },
        global: { plugins: [i18n] },
    });
}

describe('GeneratorScheduleBoard', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('fetches schedules for the given generatorId and shows a loading skeleton first', async () => {
        generatorScheduleService.list.mockReturnValue(new Promise(() => {}));
        const wrapper = mountBoard({ generatorId: 4 });
        // isLoading only flips to true inside the fetch call, triggered from onMounted — the DOM
        // reflects it after the next reactivity flush, not synchronously on mount.
        await flushPromises();

        expect(generatorScheduleService.list).toHaveBeenCalledWith(4);
        expect(wrapper.findAll('.animate-pulse')).toHaveLength(2);
    });

    it('shows the empty state once loaded with no schedules', async () => {
        generatorScheduleService.list.mockResolvedValue({ data: { data: [] } });
        const wrapper = mountBoard();
        await flushPromises();

        expect(wrapper.text()).toContain('لا يوجد جدول تشغيل معلَن حاليًا.');
    });

    it('shows the normalized error message when loading fails', async () => {
        generatorScheduleService.list.mockRejectedValue({ message: 'Network Error' });
        const wrapper = mountBoard();
        await flushPromises();

        expect(wrapper.text()).toContain('تعذّر تحميل جدول التشغيل.');
    });

    it('hides the "publish new" button and delete buttons when canManage is false', async () => {
        generatorScheduleService.list.mockResolvedValue({
            data: { data: [{ id: 1, starts_at: '2099-01-01T10:00:00', ends_at: '2099-01-01T14:00:00', note: '', is_active_now: true }] },
        });
        const wrapper = mountBoard({ canManage: false });
        await flushPromises();

        expect(wrapper.text()).not.toContain('نشر جدول جديد');
        expect(wrapper.find('button[aria-label="حذف"]').exists()).toBe(false);
    });

    it('renders the active-now entry distinctly from upcoming entries, with its note', async () => {
        generatorScheduleService.list.mockResolvedValue({
            data: {
                data: [
                    { id: 1, starts_at: '2020-01-01T08:00:00', ends_at: '2099-01-01T14:00:00', note: 'صيانة دورية', is_active_now: true },
                    { id: 2, starts_at: '2099-02-01T08:00:00', ends_at: '2099-02-01T14:00:00', note: 'جدول قادم', is_active_now: false },
                ],
            },
        });
        const wrapper = mountBoard();
        await flushPromises();

        expect(wrapper.text()).toContain('شغّال الآن');
        expect(wrapper.text()).toContain('صيانة دورية');
        expect(wrapper.text()).toContain('جدول قادم');
        expect(wrapper.text()).not.toContain('لا يوجد جدول تشغيل معلَن حاليًا.');
    });

    it('lets a manager publish a new schedule, then refetches and hides the form', async () => {
        generatorScheduleService.list.mockResolvedValue({ data: { data: [] } });
        generatorScheduleService.create.mockResolvedValue({});
        const wrapper = mountBoard({ canManage: true });
        await flushPromises();

        await wrapper.findAll('button').find((b) => b.text().includes('نشر جدول جديد')).trigger('click');
        expect(wrapper.find('form').exists()).toBe(true);

        await wrapper.find('input[type="datetime-local"]').setValue('2099-03-01T08:00');
        await wrapper.findAll('input[type="datetime-local"]')[1].setValue('2099-03-01T14:00');
        await wrapper.find('input[type="text"]').setValue('صيانة مجدولة');
        await wrapper.find('form').trigger('submit.prevent');
        await flushPromises();

        expect(generatorScheduleService.create).toHaveBeenCalledWith(4, {
            starts_at: '2099-03-01T08:00', ends_at: '2099-03-01T14:00', note: 'صيانة مجدولة',
        });
        expect(generatorScheduleService.list).toHaveBeenCalledTimes(2);
        expect(wrapper.find('form').exists()).toBe(false);
    });

    it('does nothing when delete is cancelled in the confirm dialog', async () => {
        generatorScheduleService.list.mockResolvedValue({
            data: { data: [{ id: 9, starts_at: '2099-01-01T10:00:00', ends_at: '2099-01-01T14:00:00', note: '', is_active_now: true }] },
        });
        confirmMock.mockResolvedValue(false);
        const wrapper = mountBoard({ canManage: true });
        await flushPromises();

        await wrapper.find('button[aria-label="حذف"]').trigger('click');
        await flushPromises();

        expect(generatorScheduleService.destroy).not.toHaveBeenCalled();
    });

    it('deletes a schedule after confirmation and removes it from the list', async () => {
        generatorScheduleService.list.mockResolvedValue({
            data: { data: [{ id: 9, starts_at: '2099-01-01T10:00:00', ends_at: '2099-01-01T14:00:00', note: 'قابل للحذف', is_active_now: true }] },
        });
        generatorScheduleService.destroy.mockResolvedValue({});
        confirmMock.mockResolvedValue(true);
        const wrapper = mountBoard({ canManage: true });
        await flushPromises();

        expect(wrapper.text()).toContain('قابل للحذف');
        await wrapper.find('button[aria-label="حذف"]').trigger('click');
        await flushPromises();

        expect(generatorScheduleService.destroy).toHaveBeenCalledWith(9);
        expect(wrapper.text()).not.toContain('قابل للحذف');
    });
});
