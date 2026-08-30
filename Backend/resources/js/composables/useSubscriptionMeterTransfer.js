import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import subscriptionMeterTransferService from "@/services/subscriptionMeterTransferService";
import subscriberMeterService from "@/services/subscriberMeterService";

/**
 * useSubscriptionMeterTransfer — Workflow المشترك الكامل لطلب نقل عداد
 * الاشتراك: تحميل عدّادات المشترك الفعّالة (لاختيار العداد الجديد)، تحميل
 * طلبات النقل الخاصة به (لعرض حالة آخر طلب: pending/approved/rejected)،
 * وإنشاء طلب جديد.
 */
export function useSubscriptionMeterTransfer() {
  const { t } = useI18n();

  const meters = ref([]);
  const isLoadingMeters = ref(false);
  const metersError = ref(null);

  const requests = ref([]);
  const isLoadingRequests = ref(false);
  const requestsError = ref(null);

  const isCreating = ref(false);
  const createError = ref(null);

  async function loadMeters() {
    isLoadingMeters.value = true;
    metersError.value = null;
    try {
      const { data } = await subscriberMeterService.list();
      const payload = data.data;
      meters.value = payload.data ?? payload ?? [];
    } catch (err) {
      metersError.value = normalizeApiError(err, t("my_subscription_page.load_error")).message;
      meters.value = [];
    } finally {
      isLoadingMeters.value = false;
    }
  }

  async function loadMyRequests() {
    isLoadingRequests.value = true;
    requestsError.value = null;
    try {
      const { data } = await subscriptionMeterTransferService.list();
      const payload = data.data;
      requests.value = payload.data ?? payload ?? [];
    } catch (err) {
      requestsError.value = normalizeApiError(err, t("my_subscription_page.load_error")).message;
      requests.value = [];
    } finally {
      isLoadingRequests.value = false;
    }
  }

  function latestRequestFor(subscriptionId) {
    return requests.value.find((r) => r.subscription_id === subscriptionId) ?? null;
  }

  // عدادات فعّالة أخرى غير العداد الحالي للاشتراك — الأهلية لعرض زر النقل
  function eligibleMetersFor(currentMeterId) {
    return meters.value.filter((m) => m.status === "active" && m.id !== currentMeterId);
  }

  async function createRequest(payload) {
    isCreating.value = true;
    createError.value = null;
    try {
      const { data } = await subscriptionMeterTransferService.create(payload);
      requests.value = requests.value.filter((r) => r.subscription_id !== payload.subscription_id);
      requests.value.unshift(data.data);
      return data.data;
    } catch (err) {
      createError.value = err.response?.data ?? { message: t("my_subscription_page.transfer_error") };
      return null;
    } finally {
      isCreating.value = false;
    }
  }

  return {
    meters,
    isLoadingMeters,
    metersError,
    loadMeters,
    eligibleMetersFor,

    requests,
    isLoadingRequests,
    requestsError,
    loadMyRequests,
    latestRequestFor,

    isCreating,
    createError,
    createRequest,
  };
}
