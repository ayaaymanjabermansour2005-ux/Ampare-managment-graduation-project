import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import subscriptionServiceRequestService from "@/services/subscriptionServiceRequestService";
import subscriptionService from "@/services/subscriptionService";

export function useServiceRequests() {
  const { t } = useI18n();
  const requests = ref([]);
  const isLoading = ref(true);
  const error = ref(null);

  async function fetchRequests() {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await subscriptionServiceRequestService.list({
        per_page: 30,
      });
      const payload = data.data;
      requests.value = payload.data ?? payload;
    } catch (err) {
      error.value = normalizeApiError(err, t("support_center_page.requests_load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  const mySubscriptionId = ref(null);

  async function loadMySubscription() {
    try {
      const { data } = await subscriptionService.list({ per_page: 1 });
      const payload = data.data;
      const sub = (payload.data ?? payload)[0];
      mySubscriptionId.value = sub?.id ?? null;
    } catch {
      // A failed lookup here must not reject — SupportCenterView.vue chains
      // .then(() => serviceRequests.fetchRequests()) after this call inside a
      // Promise.all with no .catch(), so an unhandled rejection here previously
      // skipped fetchRequests() entirely and left isLoading stuck at true forever.
    }
  }

  const isSubmitting = ref(false);
  const submitError = ref(null);

  async function submitRequest(payload) {
    isSubmitting.value = true;
    submitError.value = null;
    try {
      const { data } = await subscriptionServiceRequestService.create({
        ...payload,
        subscription_id: mySubscriptionId.value,
      });
      requests.value.unshift(data.data);
      return true;
    } catch (err) {
      submitError.value = err.response?.data ?? {
        message: t("support_center_page.request_submit_error"),
      };
      return false;
    } finally {
      isSubmitting.value = false;
    }
  }

  const cancellingId = ref(null);

  async function cancelRequest(id) {
    cancellingId.value = id;
    try {
      const { data } = await subscriptionServiceRequestService.cancel(id);
      const index = requests.value.findIndex((r) => r.id === id);
      if (index !== -1) requests.value[index] = data.data;
      return true;
    } catch {
      return false;
    } finally {
      cancellingId.value = null;
    }
  }

  return {
    requests,
    isLoading,
    error,
    fetchRequests,
    mySubscriptionId,
    loadMySubscription,
    isSubmitting,
    submitError,
    submitRequest,
    cancellingId,
    cancelRequest,
  };
}
