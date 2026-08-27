import { ref } from "vue";
import { useI18n } from "vue-i18n";
import adminDashboardService from "@/services/adminDashboardService";

export function useAdminDashboard() {
  const { t } = useI18n();
  const stats = ref(null);
  const isLoading = ref(true);
  const error = ref(null);

  async function fetchStats() {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await adminDashboardService.stats();
      stats.value = data.data;
    } catch (err) {
      error.value =
        err.response?.data?.message ?? t("dashboard.stats_load_error");
    } finally {
      isLoading.value = false;
    }
  }

  return { stats, isLoading, error, fetchStats };
}
