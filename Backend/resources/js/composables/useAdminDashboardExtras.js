import { ref } from "vue";
import { useI18n } from "vue-i18n";
import activityLogService from "@/services/activityLogService";
import generatorService from "@/services/generatorService";
import generatorScheduleService from "@/services/generatorScheduleService";
import { normalizeApiError } from "@/utils/normalizeApiError";

/**
 * يجمع باقي وظائف لوحة تحكم الأدمن غير المشمولة بـ useAdminDashboardFull/
 * useAdminDashboardCharts: الخط الزمني لسجل النشاط، سجل صيانة/أعطال مولد
 * واحد (لنافذة تفاصيله)، قائمة مولدات مشتركة لعناصر <select>، إرسال جدولة
 * صيانة جديدة، ورابط تصدير جدول المولدات.
 */
export function useAdminDashboardExtras() {
  const { t, locale } = useI18n();

  const timeline = ref([]);
  const isLoadingTimeline = ref(true);
  const timelineError = ref(null);

  /** يجلب آخر 6 عمليات من سجل النشاط العام لبطاقة "آخر النشاطات". */
  async function fetchTimeline() {
    isLoadingTimeline.value = true;
    timelineError.value = null;
    try {
      const { data } = await activityLogService.index({ per_page: 6 });
      timeline.value = data.data.data ?? data.data;
    } catch (err) {
      timelineError.value = normalizeApiError(err, t("dashboard.timeline_load_error")).message;
    } finally {
      isLoadingTimeline.value = false;
    }
  }

  const generatorTimeline = ref([]);
  const isLoadingGeneratorTimeline = ref(false);

  /** يجلب سجل الصيانة والأعطال الخاص بمولد واحد، لعرضه داخل نافذة تفاصيله. */
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

  const allGeneratorsForSelect = ref([]);

  /** يجلب قائمة كل المولدات لتعبئة عناصر <select> (جدولة صيانة، إضافة فني...)؛ لا يعيد الجلب إذا كانت محمَّلة أصلًا. */
  async function ensureGeneratorsForSelectLoaded() {
    if (allGeneratorsForSelect.value.length > 0) return;
    const { data } = await generatorService.list({ per_page: 200 });
    allGeneratorsForSelect.value = data.data.data ?? data.data;
  }

  const isSavingMaintenance = ref(false);
  const maintenanceError = ref(null);

  /** يرسل طلب جدولة صيانة جديدة لمولد محدَّد، ويرجع true/false حسب نجاح الحفظ. */
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

  /** يبني رابط تصدير جدول المولدات إلى Excel، حسب الفلاتر الممرَّرة. */
  function generatorsExportUrl(params) {
    return generatorService.exportUrl(params);
  }

  return {
    timeline,
    isLoadingTimeline,
    timelineError,
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
