import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import subscriptionServiceRequestService from "@/services/subscriptionServiceRequestService";

/**
 * PATCH /subscription-service-requests/{id}/review كان جاهزًا بالكامل بالباك
 * اند (Controller/Action/Policy/FormRequest) وبصلاحية service-requests.review
 * المعطاة فعليًا لدور generator_owner، بدون أي واجهة تستخدمه إطلاقًا.
 */
export function useOwnerServiceRequests() {
  const { t } = useI18n();
  const requests = ref([]);
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  });
  const isLoading = ref(false);
  const error = ref(null);

  const hasRequests = computed(() => requests.value.length > 0);

  async function fetchRequests(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await subscriptionServiceRequestService.list({ page, per_page: 15 });
      const payload = data.data;
      const meta = payload.meta ?? payload;
      requests.value = payload.data ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? requests.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("owner_service_requests.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  const reviewingId = ref(null);
  const isReviewing = ref(false);
  const reviewError = ref(null);

  function startReviewing(requestId) {
    reviewingId.value = requestId;
    reviewError.value = null;
  }

  function cancelReviewing() {
    reviewingId.value = null;
    reviewError.value = null;
  }

  async function reviewRequest(requestId, { decision, review_note, fee_amount, fee_currency }) {
    isReviewing.value = true;
    reviewError.value = null;
    try {
      const { data } = await subscriptionServiceRequestService.review(requestId, {
        decision,
        review_note: review_note || undefined,
        fee_amount: fee_amount || undefined,
        fee_currency: fee_amount ? fee_currency : undefined,
      });

      const index = requests.value.findIndex((r) => r.id === requestId);
      if (index !== -1) requests.value[index] = data.data;

      reviewingId.value = null;
      return true;
    } catch (err) {
      reviewError.value = normalizeApiError(err, t("owner_service_requests.review_error")).message;
      return false;
    } finally {
      isReviewing.value = false;
    }
  }

  return {
    requests,
    pagination,
    isLoading,
    error,
    hasRequests,
    fetchRequests,

    reviewingId,
    isReviewing,
    reviewError,
    startReviewing,
    cancelReviewing,
    reviewRequest,
  };
}
