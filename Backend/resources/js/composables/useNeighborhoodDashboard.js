import { ref } from "vue";
import { useI18n } from "vue-i18n";
import neighborhoodDashboardService from "@/services/neighborhoodDashboardService";

export function useNeighborhoodDashboard() {
  const { t } = useI18n();
  const neighborhoods = ref([]);
  const isLoading = ref(false);
  const error = ref(null);

  async function fetchSummary() {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await neighborhoodDashboardService.summary();
      neighborhoods.value = data.data;
    } catch (err) {
      error.value = err.response?.data?.message ?? t("neighborhood_dashboard_page.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  return { neighborhoods, isLoading, error, fetchSummary };
}
