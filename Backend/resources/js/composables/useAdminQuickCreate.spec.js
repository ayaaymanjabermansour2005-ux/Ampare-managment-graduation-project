import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createPinia, setActivePinia } from 'pinia';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/userService', () => ({
    default: {
        createGeneratorOwner: vi.fn(),
        createSubscriber: vi.fn(),
        createTechnician: vi.fn(),
        list: vi.fn(),
    },
}));
vi.mock('@/services/adminOpsService', () => ({
    default: { runBackup: vi.fn() },
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
            dashboard: {
                owner_created: 'تم إنشاء المالك {name}', owner_create_error: 'تعذر إنشاء المالك',
                subscriber_created: 'تم إنشاء المشترك {name}', subscriber_create_error: 'تعذر إنشاء المشترك',
                technician_created: 'تم إنشاء الفني {name}', technician_create_error: 'تعذر إنشاء الفني',
                backup_confirm_title: '', backup_confirm_message: '', backup_confirm_start: '',
                backup_started: 'بدأت النسخة الاحتياطية', backup_start_error: 'تعذر بدء النسخة الاحتياطية',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminQuickCreate } = await import('./useAdminQuickCreate');
const { useToastStore } = await import('@/stores/toast');
const userService = (await import('@/services/userService')).default;
const adminOpsService = (await import('@/services/adminOpsService')).default;

describe('useAdminQuickCreate — {message, errors} reshapes (migrated to normalizeApiError)', () => {
    beforeEach(() => {
        setActivePinia(createPinia());
        vi.clearAllMocks();
    });

    describe('handleCreateOwner', () => {
        it('reshapes a 422 into { message, errors } for ownerError', async () => {
            userService.createGeneratorOwner.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { email: ['البريد مستخدم.'] } } },
            });

            const { handleCreateOwner, ownerError, isOwnerModalOpen } = useAdminQuickCreate();
            isOwnerModalOpen.value = true;
            await handleCreateOwner();

            expect(ownerError.value).toEqual({ message: 'Invalid.', errors: { email: ['البريد مستخدم.'] } });
            expect(isOwnerModalOpen.value).toBe(true); // stays open on failure
        });

        it('reshapes a network error into { message, errors: {} }', async () => {
            userService.createGeneratorOwner.mockRejectedValue({ message: 'Network Error' });

            const { handleCreateOwner, ownerError } = useAdminQuickCreate();
            await handleCreateOwner();

            expect(ownerError.value).toEqual({ message: 'تعذر إنشاء المالك', errors: {} });
        });

        it('closes the modal on success', async () => {
            userService.createGeneratorOwner.mockResolvedValue({});
            const { handleCreateOwner, isOwnerModalOpen, ownerForm } = useAdminQuickCreate();
            isOwnerModalOpen.value = true;
            ownerForm.value.name = 'Test Owner';

            await handleCreateOwner();

            expect(isOwnerModalOpen.value).toBe(false);
        });
    });

    describe('handleCreateSubscriber', () => {
        it('reshapes a 422 into { message, errors } for subscriberError', async () => {
            userService.createSubscriber.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { phone: ['رقم غير صالح.'] } } },
            });

            const { handleCreateSubscriber, subscriberError } = useAdminQuickCreate();
            await handleCreateSubscriber();

            expect(subscriberError.value).toEqual({ message: 'Invalid.', errors: { phone: ['رقم غير صالح.'] } });
        });

        it('calls the onSubscriberCreated callback on success', async () => {
            userService.createSubscriber.mockResolvedValue({});
            const onSubscriberCreated = vi.fn();
            const { handleCreateSubscriber } = useAdminQuickCreate({ onSubscriberCreated });

            await handleCreateSubscriber();

            expect(onSubscriberCreated).toHaveBeenCalledTimes(1);
        });
    });

    describe('handleCreateTechnician', () => {
        it('reshapes a 422 into { message, errors } for technicianError', async () => {
            userService.createTechnician.mockRejectedValue({
                response: { status: 422, data: { message: 'Invalid.', errors: { owner_id: ['المالك مطلوب.'] } } },
            });

            const { handleCreateTechnician, technicianError, technicianForm } = useAdminQuickCreate();
            technicianForm.value.owner_id = 1;
            technicianForm.value.name = 'Tech';
            await handleCreateTechnician();

            expect(technicianError.value).toEqual({ message: 'Invalid.', errors: { owner_id: ['المالك مطلوب.'] } });
        });

        it('does nothing when owner_id or name is missing (local guard, no API call)', async () => {
            const { handleCreateTechnician, technicianForm } = useAdminQuickCreate();
            technicianForm.value.owner_id = '';
            technicianForm.value.name = 'Tech';

            await handleCreateTechnician();

            expect(userService.createTechnician).not.toHaveBeenCalled();
        });
    });

    describe('handleRunBackup', () => {
        it('does nothing when the confirmation dialog is dismissed', async () => {
            confirmMock.mockResolvedValue(false);
            const { handleRunBackup } = useAdminQuickCreate();

            await handleRunBackup();

            expect(adminOpsService.runBackup).not.toHaveBeenCalled();
        });

        it('shows a danger toast with the normalized message on failure', async () => {
            confirmMock.mockResolvedValue(true);
            adminOpsService.runBackup.mockRejectedValue({
                response: { status: 500, data: { message: 'فشلت العملية.' } },
            });

            const { handleRunBackup, isRunningBackup } = useAdminQuickCreate();
            await handleRunBackup();

            expect(isRunningBackup.value).toBe(false);
            const toasts = useToastStore().toasts;
            expect(toasts).toHaveLength(1);
            expect(toasts[0]).toMatchObject({ type: 'danger', title: 'فشلت العملية.' });
        });
    });
});
