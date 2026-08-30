import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/userService', () => ({
    default: {
        sendPasswordResetLink: vi.fn(),
        setPassword: vi.fn(),
    },
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
            users_page: {
                send_reset_link_title: 't', send_action: 'إرسال', sent_toast_title: 'تم الإرسال',
                copied_toast_title: 'تم النسخ', set_action: 'تعيين',
            },
            subscribers_page: {
                send_reset_link_message: 'إرسال رابط إلى {email}',
                reset_link_sent_message: 'تم الإرسال إلى {email}',
                send_failed_title: 'فشل الإرسال',
                password_copied_short: 'نُسخت',
                password_set_message: 'تم تعيين كلمة السر',
                could_not_set_password: 'تعذر تعيين كلمة السر',
            },
        },
    },
});

vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminPasswordTools } = await import('./useAdminPasswordTools');
const userService = (await import('@/services/userService')).default;

describe('useAdminPasswordTools', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    describe('handleSendResetLink', () => {
        it('does nothing when the confirmation dialog is dismissed', async () => {
            confirmMock.mockResolvedValue(false);
            const { handleSendResetLink, isSendingResetLink } = useAdminPasswordTools();

            await handleSendResetLink({ id: 1, email: 'a@example.com' });

            expect(userService.sendPasswordResetLink).not.toHaveBeenCalled();
            expect(isSendingResetLink.value).toBe(false);
        });

        it('sends the reset link and resets the loading flag on success', async () => {
            confirmMock.mockResolvedValue(true);
            userService.sendPasswordResetLink.mockResolvedValue({});
            const { handleSendResetLink, isSendingResetLink } = useAdminPasswordTools();

            await handleSendResetLink({ id: 7, email: 'a@example.com' });

            expect(userService.sendPasswordResetLink).toHaveBeenCalledWith(7);
            expect(isSendingResetLink.value).toBe(false);
        });

        it('resets the loading flag even when the request fails', async () => {
            confirmMock.mockResolvedValue(true);
            userService.sendPasswordResetLink.mockRejectedValue({
                response: { data: { message: 'Server error.' } },
            });
            const { handleSendResetLink, isSendingResetLink } = useAdminPasswordTools();

            await handleSendResetLink({ id: 7, email: 'a@example.com' });

            expect(isSendingResetLink.value).toBe(false);
        });
    });

    describe('generateSetPassword', () => {
        it('generates a 12-character password matching its confirmation field', () => {
            const { generateSetPassword, setPasswordForm } = useAdminPasswordTools();
            generateSetPassword();

            expect(setPasswordForm.password).toHaveLength(12);
            expect(setPasswordForm.password_confirmation).toBe(setPasswordForm.password);
        });

        it('generates a different password on each call (not a fixed string)', () => {
            const { generateSetPassword, setPasswordForm } = useAdminPasswordTools();
            generateSetPassword();
            const first = setPasswordForm.password;
            generateSetPassword();

            expect(setPasswordForm.password).not.toBe(first);
        });
    });

    describe('openSetPassword', () => {
        it('sets the modal target, clears any previous error, and generates a fresh password', () => {
            const { openSetPassword, setPasswordModal, setPasswordError, setPasswordForm, showSetPassword } = useAdminPasswordTools();
            setPasswordError.value = 'previous error';

            openSetPassword({ id: 3, name: 'Test' });

            expect(setPasswordModal.value).toEqual({ id: 3, name: 'Test' });
            expect(setPasswordError.value).toBeNull();
            expect(showSetPassword.value).toBe(true);
            expect(setPasswordForm.password).toHaveLength(12);
        });
    });

    describe('handleSetPassword', () => {
        it('submits the modal target id and password, then closes the modal on success', async () => {
            userService.setPassword.mockResolvedValue({});
            const { openSetPassword, handleSetPassword, setPasswordModal, setPasswordForm, isSettingPassword } = useAdminPasswordTools();
            openSetPassword({ id: 9 });

            const promise = handleSetPassword();
            expect(isSettingPassword.value).toBe(true);
            await promise;

            expect(userService.setPassword).toHaveBeenCalledWith(9, setPasswordForm.password, setPasswordForm.password_confirmation);
            expect(setPasswordModal.value).toBeNull();
            expect(isSettingPassword.value).toBe(false);
        });

        it('prioritizes the field-level password error over the generic message', async () => {
            userService.setPassword.mockRejectedValue({
                response: { data: { message: 'بيانات غير صالحة', errors: { password: ['كلمة السر ضعيفة جدًا.'] } } },
            });
            const { openSetPassword, handleSetPassword, setPasswordError, setPasswordModal } = useAdminPasswordTools();
            openSetPassword({ id: 9 });

            await handleSetPassword();

            expect(setPasswordError.value).toBe('كلمة السر ضعيفة جدًا.');
            // Modal stays open on failure so the admin can retry.
            expect(setPasswordModal.value).not.toBeNull();
        });

        it('falls back to the translated message when the server gives no specific error', async () => {
            userService.setPassword.mockRejectedValue({ message: 'Network Error' });
            const { openSetPassword, handleSetPassword, setPasswordError } = useAdminPasswordTools();
            openSetPassword({ id: 9 });

            await handleSetPassword();

            expect(setPasswordError.value).toBe('تعذر تعيين كلمة السر');
        });
    });
});
