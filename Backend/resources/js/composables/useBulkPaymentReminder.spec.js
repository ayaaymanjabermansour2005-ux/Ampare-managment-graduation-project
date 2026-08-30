import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref } from 'vue';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/userService', () => ({
    default: { sendBulkPaymentReminder: vi.fn() },
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
            subscribers_page: {
                no_overdue_subscribers_title: 'لا يوجد متأخرون',
                no_overdue_subscribers_message: 'لا يوجد مشتركون متأخرون بالدفع حاليًا',
                ok_action: 'حسنًا',
                bulk_reminder_title: 'تذكير جماعي',
                bulk_reminder_message: 'سيتم إرسال تذكير إلى {count} مشترك',
                reminders_sent_message: 'تم إرسال التذكيرات',
                reminder_send_failed_message: 'تعذر إرسال التذكيرات',
            },
            users_page: { send_action: 'إرسال', sent_toast_title: 'تم الإرسال', send_failed_title: 'فشل الإرسال' },
        },
    },
});

vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useBulkPaymentReminder } = await import('./useBulkPaymentReminder');
const userService = (await import('@/services/userService')).default;

function makeInputs(subs) {
    return {
        subscribers: ref(subs),
        subscriberStatus: (s) => (s.is_locked ? 'suspended' : s.payment_status ?? 'active'),
    };
}

describe('useBulkPaymentReminder', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('shows a dedicated "no overdue subscribers" dialog and never calls the API when none are overdue', async () => {
        confirmMock.mockResolvedValue(true);
        const inputs = makeInputs([{ id: 1, payment_status: 'active' }, { id: 2, payment_status: 'active' }]);
        const { handleBulkReminder } = useBulkPaymentReminder(inputs);

        await handleBulkReminder();

        expect(confirmMock).toHaveBeenCalledTimes(1);
        expect(confirmMock).toHaveBeenCalledWith(expect.objectContaining({ title: 'لا يوجد متأخرون', hideCancel: true }));
        expect(userService.sendBulkPaymentReminder).not.toHaveBeenCalled();
    });

    it('collects only overdue subscriber ids and sends them after confirmation', async () => {
        confirmMock.mockResolvedValueOnce(true).mockResolvedValueOnce(true);
        userService.sendBulkPaymentReminder.mockResolvedValue({ data: {} });
        const inputs = makeInputs([
            { id: 1, payment_status: 'overdue' },
            { id: 2, payment_status: 'active' },
            { id: 3, payment_status: 'overdue' },
        ]);
        const { handleBulkReminder, isSendingBulkReminder } = useBulkPaymentReminder(inputs);

        await handleBulkReminder();

        expect(userService.sendBulkPaymentReminder).toHaveBeenCalledWith([1, 3]);
        expect(confirmMock).toHaveBeenNthCalledWith(1, expect.objectContaining({ title: 'تذكير جماعي' }));
        expect(confirmMock).toHaveBeenNthCalledWith(2, expect.objectContaining({ title: 'تم الإرسال' }));
        expect(isSendingBulkReminder.value).toBe(false);
    });

    it('does not send when the confirmation dialog is dismissed', async () => {
        confirmMock.mockResolvedValue(false);
        const inputs = makeInputs([{ id: 1, payment_status: 'overdue' }]);
        const { handleBulkReminder } = useBulkPaymentReminder(inputs);

        await handleBulkReminder();

        expect(userService.sendBulkPaymentReminder).not.toHaveBeenCalled();
    });

    it('shows a failure dialog with the server message when the request fails', async () => {
        confirmMock.mockResolvedValueOnce(true).mockResolvedValueOnce(true);
        userService.sendBulkPaymentReminder.mockRejectedValue({ response: { data: { message: 'Server error.' } } });
        const inputs = makeInputs([{ id: 1, payment_status: 'overdue' }]);
        const { handleBulkReminder, isSendingBulkReminder } = useBulkPaymentReminder(inputs);

        await handleBulkReminder();

        expect(confirmMock).toHaveBeenNthCalledWith(2, expect.objectContaining({ title: 'فشل الإرسال', message: 'Server error.' }));
        expect(isSendingBulkReminder.value).toBe(false);
    });
});
