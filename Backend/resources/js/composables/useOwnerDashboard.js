import { ref } from "vue";
import { useI18n } from "vue-i18n";
import generatorService from "@/services/generatorService";
import paymentService from "@/services/paymentService";
import roleDashboardService from "@/services/roleDashboardService";

export function useOwnerDashboard() {
  const { t } = useI18n();
  const generators = ref([]);
  const pendingPayments = ref([]);
  const isLoading = ref(true);
  const error = ref(null);

  async function load() {
    isLoading.value = true;
    error.value = null;

    try {
      const [generatorsRes, paymentsRes] = await Promise.all([
        generatorService.list({ per_page: 5 }),
        paymentService.list({ status: "pending", per_page: 5 }),
      ]);

      generators.value =
        generatorsRes.data.data?.data ?? generatorsRes.data.data ?? [];
      pendingPayments.value =
        paymentsRes.data.data?.data ?? paymentsRes.data.data ?? [];
    } catch (err) {
      error.value =
        err.response?.data?.message ?? t("owner_dashboard.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  const extraStats = ref(null);
  const isLoadingExtraStats = ref(true);
  const extraStatsError = ref(null);

  async function loadExtraStats() {
    isLoadingExtraStats.value = true;
    extraStatsError.value = null;
    try {
      const { data } = await roleDashboardService.ownerStats();
      extraStats.value = data.data;
    } catch (err) {
      extraStatsError.value =
        err.response?.data?.message ?? t("owner_dashboard.load_extra_stats_error");
    } finally {
      isLoadingExtraStats.value = false;
    }
  }

  return {
    generators,
    pendingPayments,
    isLoading,
    error,
    load,
    extraStats,
    isLoadingExtraStats,
    extraStatsError,
    loadExtraStats,
  };
}
