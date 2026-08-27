import meterReadingService from "@/services/meterReadingService";

let readingIdempotencyKey = crypto.randomUUID();

export function useMeterReadingForm() {
  async function submitReading(payload) {
    const response = await meterReadingService.createReading(
      payload,
      readingIdempotencyKey,
    );
    const isQueued = !!response.data.queued;

    if (!isQueued) {
      readingIdempotencyKey = crypto.randomUUID();
    }

    return { ...response.data, isQueued };
  }

  return {
    submitReading,
  };
}
