import { ref } from "vue";
import { useI18n } from "vue-i18n";
import generatorService from "@/services/generatorService";
import { useGeneratorsTable } from "./useGeneratorsTable";
import { useToastStore } from "@/stores/toast";
import { normalizeApiError } from "@/utils/normalizeApiError";

export function useAdminGenerators() {
  const { t } = useI18n();
  const toast = useToastStore();
  const table = useGeneratorsTable({ perPage: 12 });

  const stats = ref(null);
  const isLoadingStats = ref(true);

  /* FIX (تدقيق شامل — الجولة الخامسة): ما كان في catch إطلاقًا هون. لو
   * تحديث الإحصائيات فشل بعد نجاح إنشاء/تعديل/حذف مولد فعليًا (استدعاء
   * fetchStats() أدناه بعد النجاح)، كانت الـ Promise الخارجية بترفض بدون
   * ما حد يلتقطها، فالمودال يضل مفتوح وكأن العملية فشلت رغم إنها نجحت
   * فعليًا — بدون أي رسالة توضح للأدمن شو صار.
   */
  async function fetchStats() {
    isLoadingStats.value = true;
    try {
      const { data } = await generatorService.stats();
      stats.value = data.data;
    } catch (err) {
      toast.show({ type: "danger", title: normalizeApiError(err, t("generators_management_page.stats_load_error")).message });
    } finally {
      isLoadingStats.value = false;
    }
  }

  async function createGenerator(payload) {
    const ok = await table.createGenerator(payload);
    if (ok) await fetchStats();
    return ok;
  }

  async function updateGenerator(id, payload) {
    const ok = await table.updateGenerator(id, payload);
    if (ok) await fetchStats();
    return ok;
  }

  async function deleteGenerator(id) {
    const ok = await table.deleteGenerator(id);
    if (ok) await fetchStats();
    return ok;
  }

  async function loadAll() {
    await Promise.all([table.fetchGenerators(1), fetchStats()]);
  }

  return {
    ...table,
    createGenerator,
    updateGenerator,
    deleteGenerator,
    stats,
    isLoadingStats,
    fetchStats,
    loadAll,
  };
}