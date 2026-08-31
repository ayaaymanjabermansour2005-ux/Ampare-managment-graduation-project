import { describe, it, expect, vi, beforeEach } from 'vitest';
import { createI18n } from 'vue-i18n';

vi.mock('@/services/generatorService', () => ({
    default: {
        attachments: vi.fn(),
        storeAttachment: vi.fn(),
        destroyAttachment: vi.fn(),
    },
}));

const i18n = createI18n({
    legacy: false,
    locale: 'ar',
    messages: {
        ar: {
            owner_generators: {
                attachments_load_error: 'تعذر تحميل المرفقات',
                attachment_upload_error: 'تعذر رفع المرفق',
            },
        },
    },
});
vi.mock('vue-i18n', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useI18n: () => ({ t: i18n.global.t, locale: { value: 'ar' } }) };
});

const { useGeneratorAttachments } = await import('./useGeneratorAttachments');
const generatorService = (await import('@/services/generatorService')).default;

describe('useGeneratorAttachments — uploadAttachment: raw .errors if present, else synthetic single-field shape', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('uses the raw fieldErrors object when the server returns field-level validation errors', async () => {
        generatorService.storeAttachment.mockRejectedValue({
            response: {
                status: 422,
                data: { message: 'Invalid.', errors: { document_type: ['نوع المستند غير صالح.'] } },
            },
        });

        const { uploadAttachment, uploadError } = useGeneratorAttachments();
        const result = await uploadAttachment(1, { documentType: 'x', file: {} });

        expect(result).toBe(false);
        expect(uploadError.value).toEqual({ document_type: ['نوع المستند غير صالح.'] });
    });

    it('synthesizes a single "file" field error from the generic message when there are no field errors', async () => {
        generatorService.storeAttachment.mockRejectedValue({
            response: { status: 500, data: { message: 'حجم الملف كبير جدًا.' } },
        });

        const { uploadAttachment, uploadError } = useGeneratorAttachments();
        await uploadAttachment(1, { documentType: 'x', file: {} });

        expect(uploadError.value).toEqual({ file: ['حجم الملف كبير جدًا.'] });
    });

    it('synthesizes a "file" field error from the translated fallback on a network error', async () => {
        generatorService.storeAttachment.mockRejectedValue({ message: 'Network Error' });

        const { uploadAttachment, uploadError } = useGeneratorAttachments();
        await uploadAttachment(1, { documentType: 'x', file: {} });

        expect(uploadError.value).toEqual({ file: ['تعذر رفع المرفق'] });
    });

    it('unshifts the new attachment on success', async () => {
        generatorService.storeAttachment.mockResolvedValue({ data: { data: { id: 5 } } });

        const { uploadAttachment, attachments } = useGeneratorAttachments();
        const result = await uploadAttachment(1, { documentType: 'x', file: {} });

        expect(result).toBe(true);
        expect(attachments.value[0]).toEqual({ id: 5 });
    });

    it('fetchAttachments sets the generic load error via normalizeApiError on failure', async () => {
        generatorService.attachments.mockRejectedValue({ message: 'Network Error' });

        const { fetchAttachments, error } = useGeneratorAttachments();
        await fetchAttachments(1);

        expect(error.value).toBe('تعذر تحميل المرفقات');
    });

    it('deleteAttachment silently returns false on failure without touching the attachments list', async () => {
        generatorService.destroyAttachment.mockRejectedValue(new Error('boom'));

        const { attachments, deleteAttachment } = useGeneratorAttachments();
        attachments.value = [{ id: 7 }];
        const result = await deleteAttachment(7);

        expect(result).toBe(false);
        expect(attachments.value).toEqual([{ id: 7 }]);
    });
});
