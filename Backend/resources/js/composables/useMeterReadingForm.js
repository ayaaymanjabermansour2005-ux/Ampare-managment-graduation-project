import meterReadingService from "@/services/meterReadingService";

let readingIdempotencyKey = crypto.randomUUID();

export function useMeterReadingForm() {
  /**
   * @param {object} payload
   * @param {File|null} [attachmentFile] - صورة اختيارية للعداد، تُرفع كطلب
   *   منفصل بعد نجاح إنشاء القراءة (لا شيء يُرسل ضمن طلب الإنشاء نفسه —
   *   StoreMeterReadingRequest لا يعرف أي حقل صورة).
   */
  async function submitReading(payload, attachmentFile = null) {
    const response = await meterReadingService.createReading(
      payload,
      readingIdempotencyKey,
    );
    const isQueued = !!response.data.queued;

    let attachmentError = null;
    if (!isQueued && attachmentFile) {
      const formData = new FormData();
      formData.append("file", attachmentFile);
      try {
        await meterReadingService.storeAttachment(response.data.data.id, formData);
      } catch (err) {
        attachmentError = err;
      }
    }

    if (!isQueued) {
      readingIdempotencyKey = crypto.randomUUID();
    }

    return { ...response.data, isQueued, attachmentError };
  }

  return {
    submitReading,
  };
}
