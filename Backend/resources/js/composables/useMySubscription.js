import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import subscriptionService from "@/services/subscriptionService";

/**
 * useMySubscription — تختص حصرًا بعرض الاشتراك الحالي.
 * منطق "تصفّح المولدات + الاشتراك" بالكامل انتقل لـ useSubscriberBrowseGenerators.js
 * (كان مكرر هون بشكل ناقص — بدون subscriber_meter_id, contract_type، إلخ).
 * منطق "طلب نقل العداد" انتقل لـ useSubscriptionMeterTransfer.js (Workflow حقيقي
 * كامل: طلب ← مراجعة مالك المولد ← تنفيذ فعلي على subscriber_meter_id).
 */
export function useMySubscription() {
  const { t } = useI18n();
  const subscription = ref(null);
  const isLoadingInitial = ref(true);
  const initialError = ref(null);

  const hasActiveOrPendingSubscription = computed(
    () => subscription.value && ["pending", "active", "suspended"].includes(subscription.value.status),
  );

  async function loadInitial() {
    isLoadingInitial.value = true;
    initialError.value = null;
    try {
      const { data } = await subscriptionService.list({ per_page: 1 });
      const payload = data.data;
      subscription.value = (payload.data ?? payload)[0] ?? null;
    } catch (err) {
      initialError.value = normalizeApiError(err, t("my_subscription_page.load_error")).message;
    } finally {
      isLoadingInitial.value = false;
    }
  }

  const contractDownloadUrl = computed(() =>
    subscription.value ? subscriptionService.downloadContractPdfUrl(subscription.value.id) : null,
  );

  return {
    subscription,
    isLoadingInitial,
    initialError,
    hasActiveOrPendingSubscription,
    loadInitial,
    contractDownloadUrl,
  };
}