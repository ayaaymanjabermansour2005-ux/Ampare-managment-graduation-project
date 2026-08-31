import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/userService', () => ({
    default: {
        list: vi.fn(),
        update: vi.fn(),
        destroy: vi.fn(),
        unlock: vi.fn(),
        createSubscriber: vi.fn(),
        subscribersStats: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            subscribers_page: {
                load_error: 'تعذر تحميل المشتركين',
                update_error: 'تعذر حفظ التعديلات',
                delete_error: 'تعذر حذف المشترك',
                create_error: 'تعذر إنشاء المشترك',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminSubscribers } = await import('./useAdminSubscribers');
const userService = (await import('@/services/userService')).default;

describe('useAdminSubscribers — error-handling logic', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('updateSubscriber — reshaped to { message, errors } (migrated to normalizeApiError)', () => {
        it('reshapes a 422 response', async () => {
            userService.update.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { email: ['البريد مستخدم.'] } } },
            });

            const { updateSubscriber, saveError } = useAdminSubscribers();
            const result = await updateSubscriber(1, {});

            expect(result).toBe(false);
            expect(saveError.value).toEqual({ message: 'Invalid.', errors: { email: ['البريد مستخدم.'] } });
        });

        it('reshapes a network error with an empty errors object', async () => {
            userService.update.mockRejectedValue({ message: 'Network Error' });

            const { updateSubscriber, saveError } = useAdminSubscribers();
            await updateSubscriber(1, {});

            expect(saveError.value).toEqual({ message: 'تعذر حفظ التعديلات', errors: {} });
        });
    });

    describe('deleteSubscriber — field-level "user" error takes priority (migrated to normalizeApiError)', () => {
        it('prioritizes errors.user (active-subscriptions block) over the generic message', async () => {
            userService.destroy.mockRejectedValue({
                response: {
                    status: 422,
                    data: { message: 'بيانات غير صالحة', errors: { user: ['لا يمكن حذف مشترك لديه اشتراكات فعّالة.'] } },
                },
            });

            const { deleteSubscriber, deleteError } = useAdminSubscribers();
            const result = await deleteSubscriber(1);

            expect(result).toBe(false);
            expect(deleteError.value).toBe('لا يمكن حذف مشترك لديه اشتراكات فعّالة.');
        });

        it('falls back to the generic message when there is no user field error', async () => {
            userService.destroy.mockRejectedValue({
                response: { status: 500, data: { message: 'خطأ في الخادم.' } },
            });

            const { deleteSubscriber, deleteError } = useAdminSubscribers();
            await deleteSubscriber(1);

            expect(deleteError.value).toBe('خطأ في الخادم.');
        });
    });

    describe('createSubscriber — reshaped to { message, errors } (migrated to normalizeApiError in Phase 27)', () => {
        // Was previously a raw `err.response?.data` passthrough — missed by
        // FRONT-003's original grep (`response?.data?.message` / `.errors`)
        // because it captured the whole `.data` object rather than a chained
        // access. Migrated to match the established sibling pattern used by
        // updateSubscriber/deleteSubscriber in this same file.
        it('reshapes a 422 response into { message, errors }', async () => {
            userService.createSubscriber.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { email: ['البريد مستخدم.'] } } },
            });

            const { createSubscriber, createError } = useAdminSubscribers();
            const result = await createSubscriber({});

            expect(result).toBe(false);
            expect(createError.value).toEqual({ message: 'Invalid.', errors: { email: ['البريد مستخدم.'] } });
        });

        it('reshapes a network error with the translated fallback and an empty errors object', async () => {
            userService.createSubscriber.mockRejectedValue({ message: 'Network Error' });

            const { createSubscriber, createError } = useAdminSubscribers();
            await createSubscriber({});

            expect(createError.value).toEqual({ message: 'تعذر إنشاء المشترك', errors: {} });
        });

        it('unshifts the new subscriber and increments the pagination total on success', async () => {
            userService.createSubscriber.mockResolvedValue({ data: { data: { id: 99, name: 'New' } } });

            const { createSubscriber, subscribers, pagination } = useAdminSubscribers();
            const result = await createSubscriber({});

            expect(result).toBe(true);
            expect(subscribers.value[0]).toEqual({ id: 99, name: 'New' });
            expect(pagination.value.total).toBe(1);
        });
    });
});
