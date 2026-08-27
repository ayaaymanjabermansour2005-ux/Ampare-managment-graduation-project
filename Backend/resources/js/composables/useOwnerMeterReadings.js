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
      error.value = err.response?.data?.message ?? t("owner_meter_readings.load_error");
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

  async function submitReading(payload, idempotencyKey, meterImageFile = null) {
    isSubmitting.value = true;
    submitError.value = null;
    try {
      // لو فيه صورة عداد مرفوعة، نبني FormData بدل JSON عادي.
      // meterReadingService.createReading لازم يدعم استقبال FormData
      // ويمرره لـ axios بدون تحويله لـ JSON (Content-Type: multipart/form-data).
      let body = payload;
      if (meterImageFile) {
        body = new FormData();
        Object.entries(payload).forEach(([key, value]) => {
          if (value !== undefined && value !== null) body.append(key, value);
        });
        body.append("meter_image", meterImageFile);
      }

      const { data } = await meterReadingService.createReading(
        body,
        idempotencyKey,
      );
      const isQueued = !!data.queued;
      if (!isQueued) await fetchReadings(1);
      return { success: true, isQueued };
    } catch (err) {
      submitError.value = err.response?.data?.message ?? t("owner_meter_readings.submit_error");
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
        err.response?.data?.message ?? t("owner_meter_readings.approve_error");
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
        err.response?.data?.message ?? t("owner_meter_readings.reject_error");
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
        err.response?.data?.message ?? t("owner_meter_readings.overdue_load_error");
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
        err.response?.data?.message ?? t("owner_meter_readings.history_load_error");
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