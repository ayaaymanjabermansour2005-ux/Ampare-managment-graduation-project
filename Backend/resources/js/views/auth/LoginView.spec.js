import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import LoginView from './LoginView.vue';

const loginMock = vi.fn();
vi.mock('@/stores/auth', () => ({
    useAuthStore: () => ({ login: loginMock }),
}));

vi.mock('@/services/authService', () => ({
    default: { resendVerificationEmail: vi.fn() },
}));

const routeQuery = {};
vi.mock('vue-router', () => ({
    useRouter: () => ({ push: vi.fn() }),
    useRoute: () => ({ query: routeQuery }),
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            auth: {
                toggle_password_visibility: 'toggle',
                login: {
                    generic_error: 'تعذر تسجيل الدخول، حاول مرة أخرى',
                    email_or_phone_label: 'البريد الإلكتروني أو الهاتف',
                    email_or_phone_placeholder: '', password_label: 'كلمة السر', password_placeholder: '',
                    forgot_password: '', remember_me: '', submit_idle: 'دخول', submit_loading: '', submit_success: '',
                    divider_or: '', no_account: '', create_account: '',
                    resend_prompt: 'يبدو أن بريدك الإلكتروني غير مفعّل بعد.',
                    resend_email_placeholder: '', resend_button: 'إعادة إرسال', resend_loading: '', resend_sent: 'تم إرسال بريد التفعيل.',
                    status_registered_success: '', status_verified_success: '', status_verified_expired: '', status_verified_invalid: '',
                },
            },
        },
    },
});

async function mountComponent() {
    return mount(LoginView, {
        global: {
            plugins: [i18n],
            stubs: { RouterLink: { template: '<a><slot /></a>' } },
        },
    });
}

async function fillAndSubmit(wrapper, { login = 'user@example.com', password = 'secret123' } = {}) {
    await wrapper.find('input[type="text"]').setValue(login);
    await wrapper.find('input[type="password"]').setValue(password);
    await wrapper.find('form').trigger('submit.prevent');
    await flushPromises();
}

describe('LoginView — handleSubmit error handling (migrated to normalizeApiError)', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('shows the field-level "login" error over the generic message on a 422 response', async () => {
        loginMock.mockRejectedValue({
            response: {
                status: 422,
                data: { message: 'بيانات غير صالحة', errors: { login: ['بيانات الدخول غير صحيحة.'] } },
            },
        });

        const wrapper = await mountComponent();
        await fillAndSubmit(wrapper);

        expect(wrapper.text()).toContain('بيانات الدخول غير صحيحة.');
        expect(wrapper.text()).not.toContain('بيانات غير صالحة');
    });

    it('falls back to the generic message when there is no field-level "login" error', async () => {
        loginMock.mockRejectedValue({
            response: { status: 401, data: { message: 'كلمة السر غير صحيحة.' } },
        });

        const wrapper = await mountComponent();
        await fillAndSubmit(wrapper);

        expect(wrapper.text()).toContain('كلمة السر غير صحيحة.');
    });

    it('falls back to the translated generic message on a network error', async () => {
        loginMock.mockRejectedValue({ message: 'Network Error' });

        const wrapper = await mountComponent();
        await fillAndSubmit(wrapper);

        expect(wrapper.text()).toContain('تعذر تسجيل الدخول، حاول مرة أخرى');
    });

    // Critical case (MSG/CODE-class bug avoided during FRONT-003 migration):
    // the backend sends `errors.code` as a plain string ("EMAIL_NOT_VERIFIED"),
    // not an array like normal field errors. Using normalizeApiError's
    // fieldError() here (which does fieldErrors?.[field]?.[0]) would silently
    // character-index the string and return "E" instead of comparing the
    // real value — so this must read normalized.fieldErrors.code raw.
    it('detects EMAIL_NOT_VERIFIED from the raw string field (not via fieldError(), which would character-index it)', async () => {
        loginMock.mockRejectedValue({
            response: {
                status: 403,
                data: {
                    message: 'يرجى تفعيل بريدك الإلكتروني أولاً.',
                    errors: { code: 'EMAIL_NOT_VERIFIED' },
                },
            },
        });

        const wrapper = await mountComponent();
        await fillAndSubmit(wrapper, { login: 'user@example.com' });

        expect(wrapper.text()).toContain('يبدو أن بريدك الإلكتروني غير مفعّل بعد.');
    });

    it('does NOT show the resend-verification banner for an unrelated 422 error', async () => {
        loginMock.mockRejectedValue({
            response: {
                status: 422,
                data: { message: 'Invalid.', errors: { login: ['غير صحيح.'] } },
            },
        });

        const wrapper = await mountComponent();
        await fillAndSubmit(wrapper);

        expect(wrapper.text()).not.toContain('يبدو أن بريدك الإلكتروني غير مفعّل بعد.');
    });

    it('resets isEmailNotVerified and submitError at the start of a new submit attempt', async () => {
        loginMock.mockRejectedValueOnce({
            response: { status: 403, data: { message: 'غير مفعّل.', errors: { code: 'EMAIL_NOT_VERIFIED' } } },
        });
        const wrapper = await mountComponent();
        await fillAndSubmit(wrapper);
        expect(wrapper.text()).toContain('يبدو أن بريدك الإلكتروني غير مفعّل بعد.');

        loginMock.mockRejectedValueOnce({ response: { status: 401, data: { message: 'كلمة سر خاطئة.' } } });
        await fillAndSubmit(wrapper);

        expect(wrapper.text()).not.toContain('يبدو أن بريدك الإلكتروني غير مفعّل بعد.');
        expect(wrapper.text()).toContain('كلمة سر خاطئة.');
    });
});
