import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref, reactive, computed } from "vue";
import { useI18n } from "vue-i18n";
import subscriptionService from "@/services/subscriptionService";
import ownerRatingService from "@/services/ownerRatingService";

// نفس RateOwnerAction::ELIGIBLE_STATUSES بالباك اند — لازم تطابقها تمامًا
// (pending مستثناة عمدًا: الاشتراك لازم يكون بدأ فعليًا قبل تقييم صاحب المولد).
const RATING_ELIGIBLE_STATUSES = ["active", "suspended", "cancelled"];

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

  /* ---------------- تقييم صاحب المولد ----------------
   * POST /subscriptions/{id}/owner-rating كان مبنيًا بالكامل بالباك اند (Action،
   * Policy، Resource) بدون أي واجهة على الإطلاق — تقييم واحد فقط لكل اشتراك،
   * والباك اند نفسه يرفض المحاولة الثانية بخطأ واضح، فما في حاجة لعلم "already
   * rated" منفصل بالفرونت.
   */
  const canRateOwner = computed(
    () => subscription.value && RATING_ELIGIBLE_STATUSES.includes(subscription.value.status),
  );
  const ratingForm = reactive({ rating: 5, comment: "" });
  const isSubmittingRating = ref(false);
  const ratingSubmitted = ref(false);
  const ratingError = ref(null);

  async function submitOwnerRating() {
    if (!subscription.value) return false;
    isSubmittingRating.value = true;
    ratingError.value = null;
    try {
      await ownerRatingService.submit(subscription.value.id, {
        rating: ratingForm.rating,
        comment: ratingForm.comment || undefined,
      });
      ratingSubmitted.value = true;
      return true;
    } catch (err) {
      const normalized = normalizeApiError(err, t("my_subscription_page.rating_submit_error"));
      ratingError.value = normalized.fieldError("subscription") ?? normalized.fieldError("rating") ?? normalized.message;
      return false;
    } finally {
      isSubmittingRating.value = false;
    }
  }

  return {
    subscription,
    isLoadingInitial,
    initialError,
    hasActiveOrPendingSubscription,
    loadInitial,
    contractDownloadUrl,
    canRateOwner,
    ratingForm,
    isSubmittingRating,
    ratingSubmitted,
    ratingError,
    submitOwnerRating,
  };
}