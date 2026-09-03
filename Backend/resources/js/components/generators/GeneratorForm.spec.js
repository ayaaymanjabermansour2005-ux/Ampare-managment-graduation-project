import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import GeneratorForm from './GeneratorForm.vue';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_generators: {
                close: 'إغلاق',
                schedule: { day: 'نهاري', night: 'ليلي', '24h': 'اربعة وعشرون ساعة', custom: 'مخصّص' },
                status: { active: 'يعمل الآن', maintenance: 'قيد الصيانة', inactive: 'متوقف' },
                form: {
                    add_title: 'إضافة مولد جديد',
                    edit_title: 'تعديل المولد',
                    name_label: 'اسم المولد',
                    name_placeholder: 'مثال: مولد حي الرمال الشمالي',
                    name_en_label: 'الاسم بالإنجليزي (اختياري)',
                    name_en_placeholder: 'e.g. North Al-Remal Generator',
                    name_en_hint: 'hint',
                    price_label: 'سعر الكيلوواط',
                    currency_label: 'العملة',
                    currency_ils: 'شيكل (ILS)',
                    currency_usd: 'دولار (USD)',
                    capacity_label: 'السعة الإجمالية',
                    capacity_placeholder: 'بدون حد أقصى',
                    lines_label: 'عدد الخطوط',
                    schedule_label: 'فترة التشغيل',
                    start_time_label: 'وقت البدء',
                    end_time_label: 'وقت الانتهاء',
                    status_label: 'الحالة',
                    map_pending_note: 'اختيار الموقع الدقيق على الخريطة قيد التطوير',
                    cancel: 'إلغاء',
                    save: 'حفظ التعديلات',
                    create: 'إضافة المولد',
                    saving: 'جارٍ الحفظ...',
                },
            },
        },
    },
});

function mountForm(props = {}) {
    return mount(GeneratorForm, {
        props: { open: false, generator: null, isSaving: false, serverError: null, ...props },
        global: { plugins: [i18n], stubs: { Teleport: true } },
    });
}

async function openWith(wrapper, props) {
    await wrapper.setProps({ open: true, ...props });
}

describe('GeneratorForm', () => {
    it('renders nothing while closed', () => {
        const wrapper = mountForm();
        expect(wrapper.find('.modal-panel-pop').exists()).toBe(false);
    });

    it('shows the "add" title and default field values when opened without a generator', async () => {
        const wrapper = mountForm();
        await openWith(wrapper, { generator: null });

        expect(wrapper.text()).toContain('إضافة مولد جديد');
        expect(wrapper.find('#nonexistent').exists()).toBe(false);
        expect(wrapper.find('input[type="text"]').element.value).toBe('');
        // status radios only show in edit mode
        expect(wrapper.findAll('input[type="radio"]')).toHaveLength(0);
    });

    it('pre-fills the form from the generator prop when opened in edit mode', async () => {
        const wrapper = mountForm();
        await openWith(wrapper, {
            generator: {
                name: 'مولد الاختبار', name_en: 'Test Gen', price_per_kw: '2.5', currency: 'USD',
                capacity_kw: 40, lines_count: 3, location: { id: 9 }, status: 'maintenance',
                operating_schedule: 'custom', operating_start_time: '08:00', operating_end_time: '20:00',
            },
        });

        expect(wrapper.text()).toContain('تعديل المولد');
        const nameInput = wrapper.findAll('input[type="text"]')[0];
        expect(nameInput.element.value).toBe('مولد الاختبار');
        // custom schedule -> start/end time fields visible and pre-filled
        const timeInputs = wrapper.findAll('input[type="time"]');
        expect(timeInputs).toHaveLength(2);
        expect(timeInputs[0].element.value).toBe('08:00');
        expect(timeInputs[1].element.value).toBe('20:00');
        // status radios shown in edit mode, maintenance selected
        const radios = wrapper.findAll('input[type="radio"]');
        expect(radios).toHaveLength(3);
        expect(radios.find((r) => r.element.value === 'maintenance').element.checked).toBe(true);
    });

    it('resets to blank defaults each time it is re-opened for "add" after an edit', async () => {
        const wrapper = mountForm();
        await openWith(wrapper, { generator: { name: 'مولد قديم' } });
        expect(wrapper.findAll('input[type="text"]')[0].element.value).toBe('مولد قديم');

        await wrapper.setProps({ open: false });
        await wrapper.setProps({ open: true, generator: null });

        expect(wrapper.findAll('input[type="text"]')[0].element.value).toBe('');
    });

    it('toggles the custom start/end time fields based on the schedule dropdown', async () => {
        const wrapper = mountForm();
        await openWith(wrapper, { generator: null });

        expect(wrapper.findAll('input[type="time"]')).toHaveLength(0);

        const dropdownTriggers = wrapper.findAll('button.field-input');
        // second field-input trigger is the schedule dropdown (first is currency)
        await dropdownTriggers[1].trigger('click');
        const customOption = wrapper.findAll('[role="option"]').find((o) => o.text() === 'مخصّص');
        await customOption.trigger('click');

        expect(wrapper.findAll('input[type="time"]')).toHaveLength(2);
    });

    it('shows field-level server validation errors next to the matching inputs', async () => {
        const wrapper = mountForm({
            serverError: { errors: { name: ['اسم المولد مطلوب.'], lines_count: ['عدد الخطوط غير صالح.'] } },
        });
        await openWith(wrapper, { generator: null });

        expect(wrapper.text()).toContain('اسم المولد مطلوب.');
        expect(wrapper.text()).toContain('عدد الخطوط غير صالح.');
    });

    it('shows the generic server error message when there are no field errors', async () => {
        const wrapper = mountForm({ serverError: { message: 'تعذّر حفظ بيانات المولد.' } });
        await openWith(wrapper, { generator: null });

        expect(wrapper.text()).toContain('تعذّر حفظ بيانات المولد.');
    });

    it('disables the submit button and shows the saving label while isSaving is true', async () => {
        const wrapper = mountForm({ isSaving: true });
        await openWith(wrapper, { generator: null });

        const submitBtn = wrapper.findAll('button').find((b) => b.text().includes('جارٍ الحفظ'));
        expect(submitBtn.attributes('disabled')).toBeDefined();
    });

    it('emits close from the header close button, the cancel button, and the backdrop', async () => {
        const wrapper = mountForm();
        await openWith(wrapper, { generator: null });

        await wrapper.find('.modal-head-brand__close').trigger('click');
        await wrapper.findAll('button').find((b) => b.text() === 'إلغاء').trigger('click');
        await wrapper.find('.fixed.inset-0').trigger('click');

        expect(wrapper.emitted('close')).toHaveLength(3);
    });

    it('emits submit with a payload stripped of empty capacity/location and non-custom schedule times', async () => {
        const wrapper = mountForm();
        await openWith(wrapper, { generator: null });

        await wrapper.findAll('input[type="text"]')[0].setValue('مولد جديد');
        await wrapper.find('input[type="number"][step="0.01"]').setValue('3.2');

        const submitBtn = wrapper.findAll('button').find((b) => b.text().includes('إضافة المولد'));
        await submitBtn.trigger('click');

        expect(wrapper.emitted('submit')).toHaveLength(1);
        const payload = wrapper.emitted('submit')[0][0];
        expect(payload.name).toBe('مولد جديد');
        // Vue 3 auto-applies the `.number` v-model modifier for a static type="number" input.
        expect(payload.price_per_kw).toBe(3.2);
        expect(payload.operating_schedule).toBe('day');
        expect(payload).not.toHaveProperty('capacity_kw');
        expect(payload).not.toHaveProperty('location_id');
        expect(payload).not.toHaveProperty('operating_start_time');
        expect(payload).not.toHaveProperty('operating_end_time');
    });

    it('includes operating_start_time/end_time in the payload when the schedule is custom', async () => {
        const wrapper = mountForm();
        await openWith(wrapper, { generator: null });

        await wrapper.findAll('button.field-input')[1].trigger('click');
        await wrapper.findAll('[role="option"]').find((o) => o.text() === 'مخصّص').trigger('click');
        const timeInputs = wrapper.findAll('input[type="time"]');
        await timeInputs[0].setValue('09:00');
        await timeInputs[1].setValue('17:00');

        await wrapper.findAll('button').find((b) => b.text().includes('إضافة المولد')).trigger('click');

        const payload = wrapper.emitted('submit')[0][0];
        expect(payload.operating_start_time).toBe('09:00');
        expect(payload.operating_end_time).toBe('17:00');
    });
});
