import { ref } from "vue";
import { useI18n } from "vue-i18n";
import meterReadingService from "@/services/meterReadingService";

/* أخطاء الـ validation (422) بترجع رسالة عامة بالـ message وبترجع الرسالة
 * المحدّدة (زي "لا يمكن أن تكون القراءة الحالية أقل من القراءة السابقة")
 * جوا errors.<field>[0] — لازم نعرضها هي، مش الرسالة العامة، حتى يفهم
 * المستخدم سبب الرفض الفعلي بدل رسالة غامضة. */
function extractErrorMessage(err, fallback) {
  const fieldErrors = err.response?.data?.errors;
  if (fieldErrors && typeof fieldErrors === "object") {
    const firstMessages = Object.values(fieldErrors).flat();
    if (firstMessages.length) return firstMessages.join(" — ");
  }
  return err.response?.data?.message ?? fallback;
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
      pagination.value = {
        current_page: payload.current_page ?? 1,
        last_page: payload.last_page ?? 1,
        total: payload.total ?? readings.value.length,
        per_page: payload.per_page ?? 15,
      };
    } catch (err) {
      error.value = err.response?.data?.message ?? t("meter_readings_page.load_error");
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

  async function createReading(payload) {
    isSaving.value = true;
    saveError.value = null;
    try {
      await meterReadingService.createReading(payload);
      await fetchReadings(pagination.value.current_page);
      return true;
    } catch (err) {
      saveError.value = extractErrorMessage(err, t("meter_readings_page.create_error"));
      return false;
    } finally {
      isSaving.value = false;
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
      deleteError.value = err.response?.data?.message ?? t("meter_readings_page.delete_error");
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
    isSaving,
    saveError,
    deletingId,
    deleteError,
    fetchReadings,
    onSearchInput,
    onFilterChange,
    approveReading,
    createReading,
    updateReading,
    deleteReading,
  };
}