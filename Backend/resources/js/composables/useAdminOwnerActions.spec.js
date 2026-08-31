import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref } from 'vue';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/userService', () => ({
    default: {
        updateCommissionSettings: vi.fn(),
        sendPasswordResetLink: vi.fn(),
        ownersStats: vi.fn(),
    },
}));
vi.mock('@/services/planService', () => ({
    default: { list: vi.fn(), assign: vi.fn() },
}));
vi.mock('@/services/generatorService', () => ({
    default: { list: vi.fn(), transferOwnership: vi.fn() },
}));
vi.mock('@/services/activityLogService', () => ({
    default: { index: vi.fn() },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owners_page: {
                commission_save_error: 'تعذر حفظ إعدادات العمولة',
                plan_assign_error: 'تعذر تعيين الخطة',
                transfer_error: 'تعذر نقل الملكية',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminOwnerActions } = await import('./useAdminOwnerActions');
const userService = (await import('@/services/userService')).default;
const generatorService = (await import('@/services/generatorService')).default;

function makeDeps() {
    return { owners: ref([{ id: 1, name: 'Owner' }]), loadAll: vi.fn() };
}

describe('useAdminOwnerActions — custom error-handling logic (migrated to normalizeApiError)', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    describe('saveCommissionSettings — two-field-priority chain (commission_rate, then commission_mode)', () => {
        it('prioritizes commission_rate over commission_mode when both are present', async () => {
            userService.updateCommissionSettings.mockRejectedValue({
                response: {
                    status: 422,
                    data: {
                        message: 'Invalid.',
                        errors: { commission_rate: ['النسبة يجب أن تكون بين 0 و100.'], commission_mode: ['غير صالح.'] },
                    },
                },
            });

            const { saveCommissionSettings, commissionError } = useAdminOwnerActions(makeDeps());
            const result = await saveCommissionSettings(1, {});

            expect(result).toBeNull();
            expect(commissionError.value).toBe('النسبة يجب أن تكون بين 0 و100.');
        });

        it('falls back to commission_mode when commission_rate has no error', async () => {
            userService.updateCommissionSettings.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { commission_mode: ['وضع غير مدعوم.'] } } },
            });

            const { saveCommissionSettings, commissionError } = useAdminOwnerActions(makeDeps());
            await saveCommissionSettings(1, {});

            expect(commissionError.value).toBe('وضع غير مدعوم.');
        });

        it('falls back to the generic message when neither field has an error', async () => {
            userService.updateCommissionSettings.mockRejectedValue({
                response: { status: 500, data: { message: 'خطأ في الخادم.' } },
            });

            const { saveCommissionSettings, commissionError } = useAdminOwnerActions(makeDeps());
            await saveCommissionSettings(1, {});

            expect(commissionError.value).toBe('خطأ في الخادم.');
        });
    });

    describe('sendPasswordResetLink — explicit-null-fallback case', () => {
        it('returns { success: false, message } using a null fallback (not the translated default)', async () => {
            userService.sendPasswordResetLink.mockRejectedValue({
                response: { status: 500, data: { message: 'فشل الإرسال الفعلي.' } },
            });

            const { sendPasswordResetLink } = useAdminOwnerActions(makeDeps());
            const result = await sendPasswordResetLink(1);

            expect(result).toEqual({ success: false, message: 'فشل الإرسال الفعلي.' });
        });

        it('returns message: null on a network error (no fallback text substituted)', async () => {
            userService.sendPasswordResetLink.mockRejectedValue({ message: 'Network Error' });

            const { sendPasswordResetLink } = useAdminOwnerActions(makeDeps());
            const result = await sendPasswordResetLink(1);

            expect(result).toEqual({ success: false, message: null });
        });

        it('returns { success: true, message: null } on success', async () => {
            userService.sendPasswordResetLink.mockResolvedValue({});

            const { sendPasswordResetLink } = useAdminOwnerActions(makeDeps());
            const result = await sendPasswordResetLink(1);

            expect(result).toEqual({ success: true, message: null });
        });
    });

    describe('submitTransferGenerator — owner_id field-priority', () => {
        it('prioritizes the field-level owner_id error over the generic message', async () => {
            generatorService.transferOwnership.mockRejectedValue({
                response: {
                    status: 422,
                    data: { message: 'بيانات غير صالحة', errors: { owner_id: ['هذا المولد مملوك أصلًا لهذا المستخدم.'] } },
                },
            });

            const deps = makeDeps();
            const { submitTransferGenerator, transferGenError, transferGenForm } = useAdminOwnerActions(deps);
            transferGenForm.generator_id = 9;
            transferGenForm.owner_id = 1;
            const result = await submitTransferGenerator();

            expect(result).toBe(false);
            expect(transferGenError.value).toBe('هذا المولد مملوك أصلًا لهذا المستخدم.');
            expect(deps.loadAll).not.toHaveBeenCalled();
        });

        it('falls back to the generic transfer error message when there is no owner_id field error', async () => {
            generatorService.transferOwnership.mockRejectedValue({
                response: { status: 500, data: { message: 'خطأ في الخادم.' } },
            });

            const { submitTransferGenerator, transferGenError } = useAdminOwnerActions(makeDeps());
            await submitTransferGenerator();

            expect(transferGenError.value).toBe('خطأ في الخادم.');
        });

        it('calls loadAll and returns true on success', async () => {
            generatorService.transferOwnership.mockResolvedValue({});
            const deps = makeDeps();

            const { submitTransferGenerator } = useAdminOwnerActions(deps);
            const result = await submitTransferGenerator();

            expect(result).toBe(true);
            expect(deps.loadAll).toHaveBeenCalledTimes(1);
        });
    });
});
