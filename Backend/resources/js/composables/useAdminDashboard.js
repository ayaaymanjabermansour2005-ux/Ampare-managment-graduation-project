import { ref } from "vue";
import { useI18n } from "vue-i18n";
import userService from "@/services/userService";
import generatorService from "@/services/generatorService";
import paymentService from "@/services/paymentService";

export function useAdminDashboard() {
  const { t } = useI18n();
  const counts = ref({
    owners: null,
    subscribers: null,
    technicians: null,
    generators: null,
    pendingPayments: null,
  });
  const isLoading = ref(true);
  const error = ref(null);

  async function load() {
    isLoading.value = true;
    error.value = null;

    try {
      const [owners, subscribers, technicians, generators, payments] =
        await Promise.all([
          userService.list({ role: "generator_owner", per_page: 1 }),
          userService.list({ role: "subscriber", per_page: 1 }),
          userService.list({ role: "technician", per_page: 1 }),
          generatorService.list({ per_page: 1 }),
          paymentService.list({ per_page: 1 }),
        ]);

      counts.value = {
        owners: owners.data.data.total ?? 0,
        subscribers: subscribers.data.data.total ?? 0,
        technicians: technicians.data.data.total ?? 0,
        generators: generators.data.data.total ?? 0,
        totalPayments: payments.data.data.total ?? 0,
      };
    } catch (err) {
      error.value =
        err.response?.data?.message ?? t("dashboard.stats_load_error");
    } finally {
      isLoading.value = false;
    }
  }

  return { counts, isLoading, error, load };
}
