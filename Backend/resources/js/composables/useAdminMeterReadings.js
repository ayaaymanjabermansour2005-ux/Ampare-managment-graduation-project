import { ref } from "vue";
import { useI18n } from "vue-i18n";
import meterReadingService from "@/services/meterReadingService";
import { normalizeApiError } from "@/utils/normalizeApiError";

/* أخطاء الـ validation (422) بترجع رسالة عامة بالـ message وبترجع الرسالة
 * المحدّدة (زي "لا يمكن أن تكون القراءة الحالية أقل من القراءة السابقة")
 * جوا errors.<field>[0] — لازم نعرضها هي، مش الرسالة العامة، حتى يفهم
 * المستخدم سبب الرفض الفعلي بدل رسالة غامضة. */
function extractErrorMessage(err, fallback) {
  const normalized = normalizeApiError(err, fallback);
  const firstMessages = Object.values(normalized.fieldErrors).flat();
  if (firstMessages.length) return firstMessages.join(" — ");
  return normalized.message;
}

export function useAdminMeterReadings() {
  const { t } = useI18n();
  const readings = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const isLoading = ref(true);
  const error = ref(null);

  const search = ref("");
  const statusFilter = ref("");

  const approvingId = ref(null);
  const approveError = ref(null);

  const rejectingId = ref(null);
  const rejectError = ref(null);

  const isSaving = ref(false);
  const saveError = ref(null);
  const deletingId = ref(null);
  const deleteError = ref(null);

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
      readings.value = payload.data ?? payload;
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? readings.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("meter_readings_page.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  let debounceHandle = null;
  function onSearchInput() {
    clearTimeout(debounceHandle);
    debounceHandle = setTimeout(() => fetchReadings(1), 300);
  }
  function onFilterChange() {
    fetchReadings(1);
  }

  async function approveReading(id) {
    approvingId.value = id;
    approveError.value = null;
    try {
      const { data } = await meterReadingService.approve(id);
      const index = readings.value.findIndex((r) => r.id === id);
      if (index !== -1) readings.value[index] = data.data;
      return true;
    } catch (err) {
      approveError.value = extractErrorMessage(err, t("meter_readings_page.approve_error"));
      return false;
    } finally {
      approvingId.value = null;
    }
  }

  // FIX: القراءات كان ينفع اعتمادها بس، مش رفضها — رغم إنّ الباك اند
  // (meterReadingService.reject) وحالة "rejected" جاهزين أصلًا بالواجهة.
  async function rejectReading(id, reason) {
    rejectingId.value = id;
    rejectError.value = null;
    try {
      const { data } = await meterReadingService.reject(id, reason);
      const index = readings.value.findIndex((r) => r.id === id);
      if (index !== -1) readings.value[index] = data.data;
      return true;
    } catch (err) {
      rejectError.value = extractErrorMessage(err, t("meter_readings_page.reject_error"));
      return false;
    } finally {
      rejectingId.value = null;
    }
  }

  /* ---------------- رفع صورة إثبات مع القراءة الجديدة ----------------
   * FIX (تدقيق شامل للوحة الأدمن — بند 11): نفس النمط المطبَّق فعليًا بواجهة
   * المالك (useOwnerMeterReadings::submitReading) — الصورة (إن وُجدت) تُرفَع
   * كطلب منفصل بعد نجاح إنشاء القراءة عبر endpoint المرفقات المخصص، لأن
   * StoreMeterReadingRequest لا يعرف حقل صورة ضمن نفس طلب الإنشاء.
   */
  const attachmentError = ref(null);

  async function createReading(payload, meterImageFile = null) {
    isSaving.value = true;
    saveError.value = null;
    attachmentError.value = null;
    try {
      const { data } = await meterReadingService.createReading(payload);
      const created = data.data;

      if (meterImageFile) {
        const formData = new FormData();
        formData.append("file", meterImageFile);
        try {
          await meterReadingService.storeAttachment(created.id, formData);
        } catch (attachErr) {
          // القراءة نفسها اتسجلت بنجاح — فشل رفع الصورة فقط ما لازم يُقرأ
          // كفشل بتسجيل القراءة، بس لازم يبان للأدمن مش يختفي بصمت.
          attachmentError.value = normalizeApiError(
            attachErr,
            t("meter_readings_page.attachment_upload_error"),
          ).message;
        }
      }

      await fetchReadings(pagination.value.current_page);
      return true;
    } catch (err) {
      saveError.value = extractErrorMessage(err, t("meter_readings_page.create_error"));
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  /* ---------------- تنبيه القراءات المتأخرة ----------------
   * FIX (تدقيق شامل للوحة الأدمن — بند 10): GET /meter-readings/overdue-subscribers
   * يرجّع للأدمن كل الاشتراكات النشطة المتأخرة بالنظام (بدون تقييد بمولد
   * معيّن، بعكس المالك)، لكن الفرونت الإداري لا يعرضها إطلاقًا — نفس النمط
   * المطبَّق فعليًا بواجهة المالك.
   */
  const overdueSubscribers = ref([]);
  const isLoadingOverdue = ref(false);
  const overdueError = ref(null);

  async function loadOverdueSubscribers() {
    isLoadingOverdue.value = true;
    overdueError.value = null;
    try {
      const { data } = await meterReadingService.overdueSubscribers();
      const payload = data.data;
      overdueSubscribers.value = payload.data ?? payload;
    } catch (err) {
      overdueError.value = normalizeApiError(err, t("meter_readings_page.overdue_load_error")).message;
      overdueSubscribers.value = [];
    } finally {
      isLoadingOverdue.value = false;
    }
  }

  /* ---------------- سجل الاستهلاك الشهري (Drill-down) ----------------
   * FIX (تدقيق شامل للوحة الأدمن — بند 12): meterReadingService.history()
   * جاهز بالكامل لكنه Endpoint ميت بلوحة الأدمن — لا drill-down تاريخي
   * لاشتراك معيّن. نفس النمط المطبَّق فعليًا بواجهة المالك.
   */
  const subscriberHistory = ref([]);
  const isLoadingHistory = ref(false);
  const historyError = ref(null);

  async function loadSubscriberHistory(subscriptionId) {
    isLoadingHistory.value = true;
    historyError.value = null;
    subscriberHistory.value = [];
    try {
      const { data } = await meterReadingService.history(subscriptionId);
      subscriberHistory.value = data.data ?? [];
    } catch (err) {
      historyError.value = normalizeApiError(err, t("meter_readings_page.history_load_error")).message;
    } finally {
      isLoadingHistory.value = false;
    }
  }

  async function updateReading(id, payload) {
    isSaving.value = true;
    saveError.value = null;
    try {
      const { data } = await meterReadingService.update(id, payload);
      const index = readings.value.findIndex((r) => r.id === id);
      if (index !== -1) readings.value[index] = data.data;
      return true;
    } catch (err) {
      saveError.value = extractErrorMessage(err, t("meter_readings_page.update_error"));
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  async function deleteReading(id) {
    deletingId.value = id;
    deleteError.value = null;
    try {
      await meterReadingService.delete(id);
      await fetchReadings(pagination.value.current_page);
      return true;
    } catch (err) {
      deleteError.value = normalizeApiError(err, t("meter_readings_page.delete_error")).message;
      return false;
    } finally {
      deletingId.value = null;
    }
  }

  return {
    readings,
    pagination,
    isLoading,
    error,
    search,
    statusFilter,
    approvingId,
    approveError,
    rejectingId,
    rejectError,
    isSaving,
    saveError,
    deletingId,
    deleteError,
    attachmentError,
    fetchReadings,
    onSearchInput,
    onFilterChange,
    approveReading,
    rejectReading,
    createReading,
    updateReading,
    deleteReading,
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