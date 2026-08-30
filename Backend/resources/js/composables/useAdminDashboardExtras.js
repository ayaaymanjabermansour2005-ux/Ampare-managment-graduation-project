import { ref } from "vue";
import { useI18n } from "vue-i18n";
import activityLogService from "@/services/activityLogService";
import generatorService from "@/services/generatorService";
import generatorScheduleService from "@/services/generatorScheduleService";
import { normalizeApiError } from "@/utils/normalizeApiError";

/**
 * Remaining admin-dashboard data-access extracted out of DashboardView.vue
 * (FE-01, second pass): the activity timeline feed, a single generator's
 * detail-modal timeline, the shared "generators for a <select>" list, the
 * maintenance-scheduling submit, and the generators export URL builder.
 * Kept separate from useAdminQuickCreate (owner/subscriber/technician/backup
 * quick-actions) since these are a distinct concern (dashboard timeline +
 * generator-schedule data), not another quick-create action.
 */
export function useAdminDashboardExtras() {
  const { t, locale } = useI18n();

  /* ---------------- الخط الزمني (سجل تدقيق حقيقي) ---------------- */
  const timeline = ref([]);
  const isLoadingTimeline = ref(true);

  async function fetchTimeline() {
    isLoadingTimeline.value = true;
    try {
      const { data } = await activityLogService.index({ per_page: 6 });
      timeline.value = data.data.data ?? data.data;
    } finally {
      isLoadingTimeline.value = false;
    }
  }

  /* ---------------- سجل الصيانة والأعطال لنافذة عرض مولد واحد ---------------- */
  const generatorTimeline = ref([]);
  const isLoadingGeneratorTimeline = ref(false);

  async function fetchGeneratorTimeline(id) {
    isLoadingGeneratorTimeline.value = true;
    generatorTimeline.value = [];
    try {
      const { data } = await generatorService.timeline(id);
      generatorTimeline.value = data.data ?? data;
    } catch {
      generatorTimeline.value = [];
    } finally {
      isLoadingGeneratorTimeline.value = false;
    }
  }

  /* ---------------- قائمة مولدات مشتركة لأكثر من نافذة ---------------- */
  const allGeneratorsForSelect = ref([]);

  async function ensureGeneratorsForSelectLoaded() {
    if (allGeneratorsForSelect.value.length > 0) return;
    const { data } = await generatorService.list({ per_page: 200 });
    allGeneratorsForSelect.value = data.data.data ?? data.data;
  }

  /* ---------------- جدولة صيانة (GeneratorSchedule) ---------------- */
  const isSavingMaintenance = ref(false);
  const maintenanceError = ref(null);

  async function scheduleMaintenance(generatorId, payload) {
    isSavingMaintenance.value = true;
    maintenanceError.value = null;
    try {
      await generatorScheduleService.create(generatorId, payload);
      return true;
    } catch (err) {
      maintenanceError.value = normalizeApiError(err, t("dashboard.maintenance_schedule_error")).message;
      return false;
    } finally {
      isSavingMaintenance.value = false;
    }
  }

  /* ---------------- رابط تصدير جدول المولدات (Excel) ---------------- */
  function generatorsExportUrl(params) {
    return generatorService.exportUrl(params);
  }

  return {
    timeline,
    isLoadingTimeline,
    fetchTimeline,

    generatorTimeline,
    isLoadingGeneratorTimeline,
    fetchGeneratorTimeline,

    allGeneratorsForSelect,
    ensureGeneratorsForSelectLoaded,

    isSavingMaintenance,
    maintenanceError,
    scheduleMaintenance,

    generatorsExportUrl,
  };
}
