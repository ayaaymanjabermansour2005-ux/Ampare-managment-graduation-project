import { describe, it, expect, vi, beforeEach } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import ContactSection from './ContactSection.vue';

// vReveal uses IntersectionObserver, unavailable in jsdom by default.
vi.stubGlobal('IntersectionObserver', class {
    observe() {}
    unobserve() {}
    disconnect() {}
});

vi.mock('@/services/publicContactService', () => ({
    default: { submit: vi.fn() },
}));

import publicContactService from '@/services/publicContactService';

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            landing: {
                contact: {
                    eyebrow: '', title: 'تواصل معنا', generic_error: 'تعذر إرسال رسالتك',
                    sent_confirmation: 'تم إرسال رسالتك بنجاح',
                    info: { phone_label: '', email_label: '', location_label: '', hours_label: '', location_value: '', hours_value: '' },
                    form: {
                        name_label: 'الاسم', phone_label: 'الهاتف', email_label: 'البريد الإلكتروني',
                        subject_label: 'الموضوع', message_label: 'الرسالة', submit: 'إرسال',
                        subject_general: 'عام', subject_owner: 'مالك', subject_technical: 'تقني', subject_partnership: 'شراكة',
                        phone_invalid_hint: '', email_invalid_hint: '',
                    },
                },
            },
        },
    },
});

async function mountComponent() {
    return mount(ContactSection, { global: { plugins: [i18n] } });
}

async function fillAndSubmit(wrapper) {
    await wrapper.find('#contact-name').setValue('Test User');
    await wrapper.find('#contact-phone').setValue('+970591234567');
    await wrapper.find('#contact-email').setValue('test@example.com');
    await wrapper.find('#contact-message').setValue('Test message body');
    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();
}

describe('ContactSection — handleSubmit error handling (migrated to normalizeApiError)', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('shows per-field validation errors on a 422 response', async () => {
        publicContactService.submit.mockRejectedValue({
            response: {
                status: 422,
                data: { message: 'Invalid.', errors: { phone: ['رقم الهاتف غير صالح.'] } },
            },
        });

        const wrapper = await mountComponent();
        await fillAndSubmit(wrapper);

        expect(wrapper.text()).toContain('رقم الهاتف غير صالح.');
    });

    it('shows the generic errorMessage on a non-422 error', async () => {
        publicContactService.submit.mockRejectedValue({
            response: { status: 500, data: { message: 'Internal error.' } },
        });

        const wrapper = await mountComponent();
        await fillAndSubmit(wrapper);

        expect(wrapper.text()).toContain('Internal error.');
    });

    it('falls back to the translated generic message on a network error', async () => {
        publicContactService.submit.mockRejectedValue({ message: 'Network Error' });

        const wrapper = await mountComponent();
        await fillAndSubmit(wrapper);

        expect(wrapper.text()).toContain('تعذر إرسال رسالتك');
    });

    it('shows the success confirmation and resets the form on success', async () => {
        publicContactService.submit.mockResolvedValue({ data: {} });

        const wrapper = await mountComponent();
        await fillAndSubmit(wrapper);

        expect(wrapper.text()).toContain('تم إرسال رسالتك بنجاح');
        expect(wrapper.find('#contact-name').element.value).toBe('');
    });
});
