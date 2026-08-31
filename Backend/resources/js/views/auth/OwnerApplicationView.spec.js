import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import OwnerApplicationView from './OwnerApplicationView.vue';

vi.mock('@/services/authService', () => ({
    default: { submitOwnerApplication: vi.fn() },
}));
vi.mock('@/services/neighborhoodService', () => ({
    default: { list: vi.fn().mockResolvedValue({ data: { data: [] } }) },
}));
vi.mock('vue-router', () => ({
    useRouter: () => ({ push: vi.fn() }),
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            auth: {
                toggle_password_visibility: 'toggle',
                password_mismatch: 'كلمتا السر غير متطابقتين',
                owner_application: {
                    generic_error: 'تعذر إرسال الطلب، حاول مرة أخرى',
                    intro: '', section_owner: '', full_name_label: '', email_label: '', phone_label: '', phone_optional: '',
                    phone_placeholder: '', section_generator: '', section_generator_hint: '', generator_name_label: '',
                    generator_name_placeholder: '', generator_neighborhood_label: '', optional: '', generator_neighborhood_placeholder: '',
                    generator_price_label: '', generator_city_label: '', generator_city_placeholder: '', generator_currency_label: '',
                    currency_ils: '', currency_usd: '', generator_capacity_label: '', generator_address_label: '', notes_label: '',
                    section_documents: '', section_documents_hint: '', documents_remove: '', password_label: 'كلمة السر',
                    password_confirmation_label: '', password_hint: '', submit_idle: 'إرسال الطلب', submit_loading: '',
                    success_title: '', success_message: '', back_to_login: '', back_to_role_picker: '', have_account: '', login_link: '',
                    doc_id_label: '', doc_id_hint: '', doc_business_license_label: '', doc_business_license_hint: '',
                    doc_generator_photo_label: '', doc_generator_photo_hint: '', doc_ownership_contract_label: '', doc_ownership_contract_hint: '',
                    doc_required: 'هذا المستند مطلوب', doc_invalid_type: 'نوع الملف غير مدعوم',
                },
                register: { neighborhood_loading: '' },
            },
        },
    },
});

const AuthDocumentFieldStub = {
    props: ['label', 'hint', 'icon', 'accept', 'required', 'file', 'error', 'removeLabel'],
    emits: ['select', 'remove'],
    template: `<div class="doc-field"><button type="button" class="doc-select-btn" @click="selectFile">select</button><p v-if="error">{{ error }}</p></div>`,
    methods: {
        selectFile() {
            // .jpg is valid for both ALLOWED_DOC_EXTENSIONS and ALLOWED_PHOTO_EXTENSIONS
            // (generator_photo rejects .pdf) — a single extension usable for all 4 fields.
            this.$emit('select', new File(['x'], 'doc.jpg', { type: 'image/jpeg' }));
        },
    },
};

async function mountComponent() {
    const wrapper = mount(OwnerApplicationView, {
        global: {
            plugins: [i18n],
            stubs: {
                RouterLink: { template: '<a><slot /></a>' },
                AuthSelect: true,
                AuthDocumentField: AuthDocumentFieldStub,
            },
        },
    });
    await flushPromises();
    return wrapper;
}

async function fillRequiredFieldsAndUploadDocuments(wrapper) {
    await wrapper.find('input[type="text"]').setValue('Owner Name'); // name
    await wrapper.find('input[type="email"]').setValue('owner@example.com');
    const numberInputs = wrapper.findAll('input[type="number"]');
    await numberInputs[0].setValue('1.5'); // generator_price_per_kw
    const textInputs = wrapper.findAll('input[type="text"]');
    await textInputs[1].setValue('Ramallah'); // generator_city
    await wrapper.find('input[type="password"]').setValue('SuperSecret123');
    const passwordInputs = wrapper.findAll('input[type="password"]');
    await passwordInputs[1].setValue('SuperSecret123');

    const selectButtons = wrapper.findAll('.doc-select-btn');
    for (const btn of selectButtons) {
        await btn.trigger('click');
    }
}

describe('OwnerApplicationView — handleSubmit error handling (migrated to normalizeApiError)', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('reshapes a 422 response into per-field errors (null-vs-{} distinction preserved: errors is an object, not the always-truthy {} default)', async () => {
        const authService = (await import('@/services/authService')).default;
        authService.submitOwnerApplication.mockRejectedValue({
            response: {
                status: 422,
                data: { message: 'بيانات غير صالحة', errors: { email: ['البريد مستخدم مسبقًا.'] } },
            },
        });

        const wrapper = await mountComponent();
        await fillRequiredFieldsAndUploadDocuments(wrapper);
        await wrapper.find('form').trigger('submit.prevent');
        await flushPromises();

        expect(wrapper.text()).toContain('البريد مستخدم مسبقًا.');
        // The general banner must NOT show when field errors exist — only one or the other.
        expect(wrapper.text()).not.toContain('بيانات غير صالحة');
    });

    it('falls back to the general error banner when there are no field errors (errors stays null, not {})', async () => {
        const authService = (await import('@/services/authService')).default;
        authService.submitOwnerApplication.mockRejectedValue({
            response: { status: 500, data: { message: 'خطأ في الخادم.' } },
        });

        const wrapper = await mountComponent();
        await fillRequiredFieldsAndUploadDocuments(wrapper);
        await wrapper.find('form').trigger('submit.prevent');
        await flushPromises();

        expect(wrapper.text()).toContain('خطأ في الخادم.');
    });

    it('falls back to the translated generic message on a network error', async () => {
        const authService = (await import('@/services/authService')).default;
        authService.submitOwnerApplication.mockRejectedValue({ message: 'Network Error' });

        const wrapper = await mountComponent();
        await fillRequiredFieldsAndUploadDocuments(wrapper);
        await wrapper.find('form').trigger('submit.prevent');
        await flushPromises();

        expect(wrapper.text()).toContain('تعذر إرسال الطلب، حاول مرة أخرى');
    });

    it('does not call the API at all when a required document is missing (local guard short-circuits before normalizeApiError)', async () => {
        const authService = (await import('@/services/authService')).default;

        const wrapper = await mountComponent();
        await wrapper.find('input[type="text"]').setValue('Owner Name');
        await wrapper.find('input[type="email"]').setValue('owner@example.com');
        await wrapper.find('input[type="password"]').setValue('SuperSecret123');
        const passwordInputs = wrapper.findAll('input[type="password"]');
        await passwordInputs[1].setValue('SuperSecret123');
        // Intentionally uploading zero documents.
        await wrapper.find('form').trigger('submit.prevent');
        await flushPromises();

        expect(authService.submitOwnerApplication).not.toHaveBeenCalled();
        expect(wrapper.text()).toContain('هذا المستند مطلوب');
    });
});
