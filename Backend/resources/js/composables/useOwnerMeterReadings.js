import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import meterReadingService from "@/services/meterReadingService";
import generatorService from "@/services/generatorService";
import subscriptionService from "@/services/subscriptionService";

export function useOwnerMeterReadings() {
  const { t } = useI18n();
  const readings = ref([]);
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  });
  const isLoading = ref(false);
  const error = ref(null);

  const search = ref("");
  const statusFilter = ref("");

  async function fetchReadings(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await meterReadingService.list({
        page,
        search: search.value || undefined,
        status: statusFilter.value || undefined,
      });
      const payload = data.data;
      const meta = payload.meta ?? payload;
      readings.value = payload.data ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? readings.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("owner_meter_readings.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  let searchDebounceHandle = null;
  function onSearchInput() {
    clearTimeout(searchDebounceHandle);
    searchDebounceHandle = setTimeout(() => fetchReadings(1), 300);
  }
  function onFilterChange() {
    fetchReadings(1);
  }

  const generators = ref([]);
  async function loadGenerators() {
    const { data } = await generatorService.list({ per_page: 100 });
    const payload = data.data;
    generators.value = payload.data ?? payload;
  }

  const subscriptionsForGenerator = ref([]);
  const isLoadingSubscriptions = ref(false);
  async function loadSubscriptionsFor(generatorId) {
    isLoadingSubscriptions.value = true;
    try {
      const { data } = await subscriptionService.list({ per_page: 100 });
      const payload = data.data;
      const all = payload.data ?? payload;
      subscriptionsForGenerator.value = all.filter(
        (s) => s.generator?.id === generatorId && s.status === "active",
      );
    } finally {
      isLoadingSubscriptions.value = false;
    }
  }

  const isSubmitting = ref(false);
  const submitError = ref(null);
  const attachmentError = ref(null);

  async function submitReading(payload, idempotencyKey, meterImageFile = null) {
    isSubmitting.value = true;
    submitError.value = null;
    attachmentError.value = null;
    try {
      // FIX: كانت الصورة تُرفَق ضمن نفس طلب الإنشاء كحقل meter_image عبر
      // FormData — لكن StoreMeterReadingRequest/MeterReadingService::create()
      // لا يعرفان هذا الحقل إطلاقًا، فكانت الصورة تُهمَل بصمت في كل مرة
      // بدون أي خطأ ظاهر. القراءة الآن تُنشأ كـ JSON عادي، والصورة (إن
      // وُجدت) تُرفَع بعدها كطلب منفصل حقيقي عبر endpoint المرفقات المخصص
      // (POST /meter-readings/{id}/attachments، حقل file).
      const { data } = await meterReadingService.createReading(
        payload,
        idempotencyKey,
      );
      const isQueued = !!data.queued;

      if (!isQueued && meterImageFile) {
        const formData = new FormData();
        formData.append("file", meterImageFile);
        try {
          await meterReadingService.storeAttachment(data.data.id, formData);
        } catch (attachErr) {
          // القراءة نفسها اتسجلت بنجاح — فشل رفع الصورة فقط ما لازم يُقرأ
          // كفشل بتسجيل القراءة، بس لازم يبان للمستخدم مش يختفي بصمت.
          attachmentError.value = normalizeApiError(
            attachErr,
            t("owner_meter_readings.attachment_upload_error"),
          ).message;
        }
      }

      if (!isQueued) await fetchReadings(1);
      return { success: true, isQueued, attachmentError: attachmentError.value };
    } catch (err) {
      submitError.value = normalizeApiError(err, t("owner_meter_readings.submit_error")).message;
      return { success: false };
    } finally {
      isSubmitting.value = false;
    }
  }

  const approvingId = ref(null);
  const approveError = ref(null);

  async function approveReading(id) {
    approvingId.value = id;
    approveError.value = null;
    try {
      const { data } = await meterReadingService.approve(id);
      const index = readings.value.findIndex((r) => r.id === id);
      if (index !== -1) readings.value[index] = data.data;
      return true;
    } catch (err) {
      approveError.value =
        normalizeApiError(err, t("owner_meter_readings.approve_error")).message;
      return false;
    } finally {
      approvingId.value = null;
    }
  }

  // ===================== رفض القراءة مع سبب =====================
  const rejectingId = ref(null);
  const rejectError = ref(null);

  async function rejectReading(id, reason) {
    rejectingId.value = id;
    rejectError.value = null;
    try {
      // يتطلب endpoint جديد بالباك إند، مثلًا:
      // POST /owner/meter-readings/{id}/reject  Body: { reason }
      const { data } = await meterReadingService.reject(id, reason);
      const index = readings.value.findIndex((r) => r.id === id);
      if (index !== -1) readings.value[index] = data.data;
      return true;
    } catch (err) {
      rejectError.value =
        normalizeApiError(err, t("owner_meter_readings.reject_error")).message;
      return false;
    } finally {
      rejectingId.value = null;
    }
  }

  // ===================== تنبيه القراءات المتأخرة =====================
  const overdueSubscribers = ref([]);
  const isLoadingOverdue = ref(false);
  const overdueError = ref(null);

  async function loadOverdueSubscribers() {
    isLoadingOverdue.value = true;
    overdueError.value = null;
    try {
      // يتطلب endpoint جديد بالباك إند، مثلًا:
      // GET /owner/meter-readings/overdue-subscribers
      // يرجع المشتركين النشطين اللي ما انسجلت لهم قراءة بالدورة الحالية.
      const { data } = await meterReadingService.overdueSubscribers();
      const payload = data.data;
      overdueSubscribers.value = payload.data ?? payload;
    } catch (err) {
      overdueError.value =
        normalizeApiError(err, t("owner_meter_readings.overdue_load_error")).message;
      overdueSubscribers.value = [];
    } finally {
      isLoadingOverdue.value = false;
    }
  }

  // ===================== سجل الاستهلاك الشهري (للرسم البياني) =====================
  const subscriberHistory = ref([]);
  const isLoadingHistory = ref(false);
  const historyError = ref(null);

  async function loadSubscriberHistory(subscriptionId) {
    isLoadingHistory.value = true;
    historyError.value = null;
    subscriberHistory.value = [];
    try {
      // يتطلب endpoint جديد بالباك إند، مثلًا:
      // GET /owner/meter-readings/subscriptions/{subscriptionId}/history
      // يرجع آخر 6-12 شهر: [{ month: '2026-01', consumed_kw: 120 }, ...]
      const { data } = await meterReadingService.history(subscriptionId);
      subscriberHistory.value = data.data ?? [];
    } catch (err) {
      historyError.value =
        normalizeApiError(err, t("owner_meter_readings.history_load_error")).message;
    } finally {
      isLoadingHistory.value = false;
    }
  }

  return {
    readings,
    pagination,
    isLoading,
    error,
    search,
    statusFilter,
    fetchReadings,
    onSearchInput,
    onFilterChange,
    generators,
    loadGenerators,
    subscriptionsForGenerator,
    isLoadingSubscriptions,
    loadSubscriptionsFor,
    isSubmitting,
    submitError,
    attachmentError,
    submitReading,
    approvingId,
    approveError,
    approveReading,
    rejectingId,
    rejectError,
    rejectReading,
    overdueSubscribers,
    isLoadingOverdue,
    overdueError,
    loadOverdueSubscribers,
    subscriberHistory,
    isLoadingHistory,
    historyError,
    loadSubscriberHistory,
  };
}