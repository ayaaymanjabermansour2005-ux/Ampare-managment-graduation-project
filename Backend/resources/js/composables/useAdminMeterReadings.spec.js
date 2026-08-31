import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/meterReadingService', () => ({
    default: {
        list: vi.fn(),
        approve: vi.fn(),
        reject: vi.fn(),
        createReading: vi.fn(),
        update: vi.fn(),
        delete: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            meter_readings_page: {
                load_error: 'تعذر تحميل القراءات',
                approve_error: 'تعذر اعتماد القراءة',
                reject_error: 'تعذر رفض القراءة',
                create_error: 'تعذر إنشاء القراءة',
                update_error: 'تعذر تعديل القراءة',
                delete_error: 'تعذر حذف القراءة',
            },
        },
    },
});

vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useAdminMeterReadings } = await import('./useAdminMeterReadings');
const meterReadingService = (await import('@/services/meterReadingService')).default;

describe('useAdminMeterReadings — extractErrorMessage (rewritten to route through normalizeApiError)', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('approveReading joins every field-level validation error with " — " (preserving the original custom-join behavior)', async () => {
        meterReadingService.approve.mockRejectedValue({
            response: {
                status: 422,
                data: {
                    message: 'بيانات غير صالحة',
                    errors: {
                        current_reading: ['القراءة الحالية لا يمكن أن تكون أقل من القراءة السابقة.'],
                        reading_date: ['تم تسجيل قراءة لهذا الاشتراك بنفس هذا التاريخ مسبقًا.'],
                    },
                },
            },
        });

        const { approveReading, approveError } = useAdminMeterReadings();
        const result = await approveReading(5);

        expect(result).toBe(false);
        expect(approveError.value).toBe(
            'القراءة الحالية لا يمكن أن تكون أقل من القراءة السابقة. — تم تسجيل قراءة لهذا الاشتراك بنفس هذا التاريخ مسبقًا.'
        );
    });

    it('approveReading falls back to the generic message when there are no field errors', async () => {
        meterReadingService.approve.mockRejectedValue({
            response: { status: 500, data: { message: 'خطأ في الخادم.' } },
        });

        const { approveReading, approveError } = useAdminMeterReadings();
        await approveReading(5);

        expect(approveError.value).toBe('خطأ في الخادم.');
    });

    it('rejectReading joins field errors the same way as approveReading', async () => {
        meterReadingService.reject.mockRejectedValue({
            response: { status: 422, data: { message: 'Invalid.', errors: { reason: ['السبب مطلوب.'] } } },
        });

        const { rejectReading, rejectError } = useAdminMeterReadings();
        const result = await rejectReading(5, '');

        expect(result).toBe(false);
        expect(rejectError.value).toBe('السبب مطلوب.');
    });

    it('createReading joins field errors via the same helper', async () => {
        meterReadingService.createReading.mockRejectedValue({
            response: { status: 422, data: { message: 'Invalid.', errors: { subscription_id: ['الاشتراك مطلوب.'] } } },
        });

        const { createReading, saveError } = useAdminMeterReadings();
        const result = await createReading({});

        expect(result).toBe(false);
        expect(saveError.value).toBe('الاشتراك مطلوب.');
    });

    it('updateReading joins field errors via the same helper', async () => {
        meterReadingService.update.mockRejectedValue({
            response: { status: 422, data: { message: 'Invalid.', errors: { current_reading: ['غير صالح.'] } } },
        });

        const { updateReading, saveError } = useAdminMeterReadings();
        const result = await updateReading(5, {});

        expect(result).toBe(false);
        expect(saveError.value).toBe('غير صالح.');
    });

    it('deleteReading uses the plain normalizeApiError message (not the join helper)', async () => {
        meterReadingService.delete.mockRejectedValue({
            response: { status: 500, data: { message: 'تعذر الحذف.' } },
        });

        const { deleteReading, deleteError } = useAdminMeterReadings();
        const result = await deleteReading(5);

        expect(result).toBe(false);
        expect(deleteError.value).toBe('تعذر الحذف.');
    });

    it('fetchReadings sets a generic load error via normalizeApiError on failure', async () => {
        meterReadingService.list.mockRejectedValue({ message: 'Network Error' });

        const { fetchReadings, error } = useAdminMeterReadings();
        await fetchReadings();

        expect(error.value).toBe('تعذر تحميل القراءات');
    });
});
