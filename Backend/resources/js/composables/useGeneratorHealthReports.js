import { ref } from "vue";
import { useI18n } from "vue-i18n";
import generatorService from "@/services/generatorService";

export function useGeneratorHealthReports(generatorId) {
  const { t } = useI18n();
  const reports = ref([]);
  const isLoading = ref(false);
  const error = ref(null);

  async function fetchReports() {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await generatorService.healthReports(generatorId);
      reports.value = data.data;
    } catch (err) {
      error.value = err.response?.data?.message ?? t("owner_generators.health_reports_load_error");
    } finally {
      isLoading.value = false;
    }
  }

  return { reports, isLoading, error, fetchReports };
}
