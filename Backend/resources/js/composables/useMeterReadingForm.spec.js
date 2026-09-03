import { describe, it, expect, vi, beforeEach } from 'vitest';

vi.mock('@/services/meterReadingService', () => ({
    default: {
        createReading: vi.fn(),
        storeAttachment: vi.fn(),
    },
}));

const { useMeterReadingForm } = await import('./useMeterReadingForm');
const meterReadingService = (await import('@/services/meterReadingService')).default;

// NOTE: the idempotency key lives in module-level state (`let readingIdempotencyKey`
// in useMeterReadingForm.js), shared across every useMeterReadingForm() instance in
// this process — it is not per-composable-instance state. Tests below read the key
// used in each createReading call from the mock's call args rather than assuming a
// fixed value, since it evolves across the whole file.

describe('useMeterReadingForm', () => {
    beforeEach(() => {
        vi.clearAllMocks();
    });

    it('submits the reading with an idempotency key and returns the response merged with isQueued/attachmentError when there is no attachment', async () => {
        meterReadingService.createReading.mockResolvedValue({ data: { success: true, queued: false, data: { id: 1 } } });

        const { submitReading } = useMeterReadingForm();
        const payload = { subscription_id: 10, current_reading: 500 };
        const result = await submitReading(payload);

        expect(meterReadingService.createReading).toHaveBeenCalledTimes(1);
        const [calledPayload, calledKey] = meterReadingService.createReading.mock.calls[0];
        expect(calledPayload).toBe(payload);
        expect(typeof calledKey).toBe('string');
        expect(calledKey.length).toBeGreaterThan(0);

        expect(meterReadingService.storeAttachment).not.toHaveBeenCalled();
        expect(result).toEqual({ success: true, queued: false, data: { id: 1 }, isQueued: false, attachmentError: null });
    });

    it('rotates the idempotency key after a non-queued submission so the next call uses a different key', async () => {
        meterReadingService.createReading.mockResolvedValue({ data: { success: true, queued: false, data: { id: 1 } } });

        const { submitReading } = useMeterReadingForm();
        await submitReading({});
        const firstKey = meterReadingService.createReading.mock.calls[0][1];

        await submitReading({});
        const secondKey = meterReadingService.createReading.mock.calls[1][1];

        expect(secondKey).not.toBe(firstKey);
    });

    it('does NOT rotate the idempotency key after a queued (offline) submission, so a retry reuses the same key', async () => {
        meterReadingService.createReading.mockResolvedValue({ data: { queued: true } });

        const { submitReading } = useMeterReadingForm();
        await submitReading({});
        const firstKey = meterReadingService.createReading.mock.calls[0][1];

        await submitReading({});
        const secondKey = meterReadingService.createReading.mock.calls[1][1];

        expect(secondKey).toBe(firstKey);
    });

    it('uploads the attachment as a separate request after a non-queued success, and returns attachmentError null on success', async () => {
        meterReadingService.createReading.mockResolvedValue({ data: { success: true, queued: false, data: { id: 7 } } });
        meterReadingService.storeAttachment.mockResolvedValue({ data: {} });

        const { submitReading } = useMeterReadingForm();
        const file = new File(['x'], 'meter.jpg', { type: 'image/jpeg' });
        const result = await submitReading({ current_reading: 1 }, file);

        expect(meterReadingService.storeAttachment).toHaveBeenCalledTimes(1);
        const [id, formData] = meterReadingService.storeAttachment.mock.calls[0];
        expect(id).toBe(7);
        expect(formData).toBeInstanceOf(FormData);
        expect(formData.get('file')).toBe(file);
        expect(result.attachmentError).toBeNull();
        expect(result.isQueued).toBe(false);
    });

    it('captures (does not throw) an attachment upload failure and returns it as attachmentError, while the reading itself is still reported successful', async () => {
        meterReadingService.createReading.mockResolvedValue({ data: { success: true, queued: false, data: { id: 8 } } });
        const uploadError = new Error('upload failed');
        meterReadingService.storeAttachment.mockRejectedValue(uploadError);

        const { submitReading } = useMeterReadingForm();
        const file = new File(['x'], 'meter.jpg', { type: 'image/jpeg' });
        const result = await submitReading({}, file);

        expect(result.success).toBe(true);
        expect(result.attachmentError).toBe(uploadError);
    });

    it('does not attempt an attachment upload when the reading was queued offline, even if an attachment file was given', async () => {
        meterReadingService.createReading.mockResolvedValue({ data: { queued: true } });

        const { submitReading } = useMeterReadingForm();
        const file = new File(['x'], 'meter.jpg', { type: 'image/jpeg' });
        const result = await submitReading({}, file);

        expect(meterReadingService.storeAttachment).not.toHaveBeenCalled();
        expect(result.isQueued).toBe(true);
        expect(result.attachmentError).toBeNull();
    });

    // NOTE: submitReading has no try/catch around meterReadingService.createReading —
    // a rejection propagates straight out of the composable. This is intentional (every
    // real consumer — owner/technician MeterReadingsView.vue — wraps its own call to
    // submitReading in a try/catch and reshapes the error itself via normalizeApiError),
    // so this documents the actual, correct behavior rather than a bug.
    it('propagates a createReading rejection instead of swallowing it', async () => {
        meterReadingService.createReading.mockRejectedValue(new Error('Request failed'));

        const { submitReading } = useMeterReadingForm();

        await expect(submitReading({})).rejects.toThrow('Request failed');
        expect(meterReadingService.storeAttachment).not.toHaveBeenCalled();
    });
});
