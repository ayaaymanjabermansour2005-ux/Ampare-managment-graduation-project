import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/userService', () => ({
    default: { update: vi.fn(), updateAvatar: vi.fn() },
}));
vi.mock('@/services/authService', () => ({
    default: {
        changePassword: vi.fn(), sessions: vi.fn(), revokeSession: vi.fn(),
        logoutOtherDevices: vi.fn(), loginLog: vi.fn(), deleteAccount: vi.fn(),
    },
}));
vi.mock('@/stores/auth', () => ({
    useAuthStore: () => ({ user: { id: 1 }, logout: vi.fn() }),
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_settings: {
                profile: { save_error: 'تعذر حفظ الملف الشخصي', avatar_error: 'تعذر رفع الصورة' },
                security: {
                    password_error: 'تعذر تغيير كلمة المرور',
                    sessions_load_error: 'تعذر تحميل الجلسات',
                    wrong_password: 'كلمة المرور غير صحيحة',
                    login_log_load_error: 'تعذر تحميل السجل',
                },
                danger: { delete_error: 'تعذر حذف الحساب' },
            },
        },
    },
});

// useI18n() requires an active component/app context in Composition API mode;
// stub it directly since these tests exercise the composable's plain functions.
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return {
        ...actual,
        useI18n: () => ({ t: i18n.global.t }),
    };
});

const { useAccountSettings } = await import('./useAccountSettings');
const userService = (await import('@/services/userService')).default;
const authService = (await import('@/services/authService')).default;

describe('useAccountSettings — profileError/passwordError reshaping (migrated to normalizeApiError)', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('reshapes a 422 validation error into {message, errors} for profileError, preserving the field-error shape the template reads', async () => {
        userService.update.mockRejectedValue({
            response: {
                status: 422,
                data: { message: 'Invalid.', errors: { email: ['البريد الإلكتروني مستخدم من قبل.'] } },
            },
        });

        const { updateProfile, profileError } = useAccountSettings();
        const result = await updateProfile({ email: 'taken@example.com' });

        expect(result).toBe(false);
        expect(profileError.value.message).toBe('Invalid.');
        expect(profileError.value.errors.email[0]).toBe('البريد الإلكتروني مستخدم من قبل.');
    });

    it('falls back to the translated fallback message for profileError on a network error, with no crash on missing errors', async () => {
        userService.update.mockRejectedValue({ message: 'Network Error' });

        const { updateProfile, profileError } = useAccountSettings();
        await updateProfile({ name: 'New Name' });

        expect(profileError.value.message).toBe('تعذر حفظ الملف الشخصي');
        expect(profileError.value.errors?.email).toBeUndefined();
    });

    it('reshapes passwordError identically to profileError', async () => {
        authService.changePassword.mockRejectedValue({
            response: {
                status: 422,
                data: { message: 'Invalid.', errors: { current_password: ['كلمة المرور الحالية غير صحيحة.'] } },
            },
        });

        const { changePassword, passwordError } = useAccountSettings();
        await changePassword({ current_password: 'wrong' });

        expect(passwordError.value.message).toBe('Invalid.');
        expect(passwordError.value.errors.current_password[0]).toBe('كلمة المرور الحالية غير صحيحة.');
    });

    it('avatarError remains a plain string (mechanical swap, not reshaped)', async () => {
        userService.updateAvatar.mockRejectedValue({ message: 'Network Error' });

        const { uploadAvatar, avatarError } = useAccountSettings();
        await uploadAvatar(new Blob());

        expect(avatarError.value).toBe('تعذر رفع الصورة');
    });
});
