import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/userService', () => ({
    default: {
        list: vi.fn(), ownersStats: vi.fn(), update: vi.fn(),
        destroy: vi.fn(), unlock: vi.fn(), createGeneratorOwner: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owners_page: {
                load_error: 'تعذر تحميل الملاك',
                update_error: 'تعذر حفظ التعديلات',
                delete_error: 'تعذر حذف المالك',
                create_error: 'تعذر إنشاء المالك',
            },
        },
    },
});

vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminGeneratorOwners } = await import('./useAdminGeneratorOwners');
const userService = (await import('@/services/userService')).default;

describe('useAdminGeneratorOwners — custom error-handling logic (migrated to normalizeApiError)', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        userService.ownersStats.mockResolvedValue({ data: { data: {} } });
        userService.destroy.mockResolvedValue({});
    });

    it('updateOwner reshapes a 422 into {message, errors} for saveError', async () => {
        userService.update.mockRejectedValue({
            response: { status: 422, data: { message: 'Invalid.', errors: { email: ['البريد مستخدم.'] } } },
        });

        const { updateOwner, saveError } = useAdminGeneratorOwners();
        const result = await updateOwner(1, { email: 'taken@example.com' });

        expect(result).toBe(false);
        expect(saveError.value.message).toBe('Invalid.');
        expect(saveError.value.errors.email[0]).toBe('البريد مستخدم.');
    });

    it('createOwner reshapes a 422 into {message, errors} for createError', async () => {
        userService.createGeneratorOwner.mockRejectedValue({
            response: { status: 422, data: { message: 'Invalid.', errors: { name: ['الاسم مطلوب.'] } } },
        });

        const { createOwner, createError } = useAdminGeneratorOwners();
        const result = await createOwner({});

        expect(result).toBe(false);
        expect(createError.value.message).toBe('Invalid.');
        expect(createError.value.errors.name[0]).toBe('الاسم مطلوب.');
    });

    it('deleteOwner prioritizes the field-level "user" error over the generic message (real business rule: active subscriptions block deletion)', async () => {
        userService.destroy.mockRejectedValue({
            response: {
                status: 422,
                data: {
                    message: 'بيانات غير صالحة',
                    errors: { user: ['لا يمكن حذف مالك لديه اشتراكات فعّالة.'] },
                },
            },
        });

        const { deleteOwner, deleteError } = useAdminGeneratorOwners();
        const result = await deleteOwner(1);

        expect(result).toBe(false);
        // Must show the specific field message, NOT the generic "بيانات غير صالحة"
        expect(deleteError.value).toBe('لا يمكن حذف مالك لديه اشتراكات فعّالة.');
    });

    it('deleteOwner falls back to the generic message when there is no field-level "user" error', async () => {
        userService.destroy.mockRejectedValue({
            response: { status: 500, data: { message: 'Server error.' } },
        });

        const { deleteOwner, deleteError } = useAdminGeneratorOwners();
        await deleteOwner(1);

        expect(deleteError.value).toBe('Server error.');
    });

    it('deleteOwner falls back to the translated fallback on a network error', async () => {
        userService.destroy.mockRejectedValue({ message: 'Network Error' });

        const { deleteOwner, deleteError } = useAdminGeneratorOwners();
        await deleteOwner(1);

        expect(deleteError.value).toBe('تعذر حذف المالك');
    });
});
