import http from "@/services/http";
import {
  listQueuedRequestsForUser,
  removeQueuedRequest,
  updateAttempts,
} from "./offlineQueue";

const MAX_ATTEMPTS = 5;

function objectToFormData(obj) {
  const formData = new FormData();
  Object.entries(obj).forEach(([key, value]) => formData.append(key, value));
  return formData;
}

/**
 * @param {string|number} userId
 */
export async function flushQueue(userId) {
  if (userId === undefined || userId === null) {
    return { needsReauth: false };
  }

  const items = await listQueuedRequestsForUser(userId);
  let needsReauth = false;

  for (const item of items) {
    if (needsReauth) break;

    try {
      await http({
        method: item.method,
        url: item.url,
        headers: { "Idempotency-Key": item.idempotencyKey },
        data: item.isFormData ? objectToFormData(item.data) : item.data,
      });

      await removeQueuedRequest(item.idempotencyKey);
    } catch (error) {
      const status = error.response?.status;

      if (status === 401) {
        needsReauth = true;
        continue;
      }

      if (status && status >= 400 && status < 500 && status !== 429) {
        console.error(
          "Queued request permanently failed, dropping.",
          item,
          error,
        );
        await removeQueuedRequest(item.idempotencyKey);
        continue;
      }

      const attempts = (item.attempts || 0) + 1;
      if (attempts >= MAX_ATTEMPTS) {
        console.error("Queued request exceeded max attempts, dropping.", item);
        await removeQueuedRequest(item.idempotencyKey);
      } else {
        await updateAttempts(item.idempotencyKey, attempts);
      }
    }
  }

  return { needsReauth };
}
