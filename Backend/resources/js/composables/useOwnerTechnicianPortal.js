import { ref } from "vue";
import { useI18n } from "vue-i18n";
import technicianTaskService from "@/services/technicianTaskService";
import meterReadingService from "@/services/meterReadingService";
import technicianPaymentService from "@/services/technicianPaymentService";

export function useOwnerTechnicianPortal() {
  const { t } = useI18n();

  const selectedTechnicianId = ref("");

  const tasks = ref([]);
  const readings = ref([]);
  const payments = ref([]);
  const isLoading = ref(false);
  const error = ref(null);

  async function loadTechnicianWork(technicianId) {
    selectedTechnicianId.value = technicianId;
    if (!technicianId) {
      tasks.value = [];
      readings.value = [];
      payments.value = [];
      return;
    }

    isLoading.value = true;
    error.value = null;
    try {
      const [tasksRes, readingsRes, paymentsRes] = await Promise.all([
        technicianTaskService.list({ technician_id: technicianId, per_page: 20 }),
        meterReadingService.list({ technician_id: technicianId, per_page: 20 }),
        technicianPaymentService.list({ technician_id: technicianId, per_page: 20 }),
      ]);

      tasks.value = tasksRes.data.data.data ?? tasksRes.data.data;
      readings.value = readingsRes.data.data.data ?? readingsRes.data.data;
      payments.value = paymentsRes.data.data.data ?? paymentsRes.data.data;
    } catch (err) {
      error.value = err.response?.data?.message ?? t("owner_technician_portal.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  return {
    selectedTechnicianId,
    tasks,
    readings,
    payments,
    isLoading,
    error,
    loadTechnicianWork,
  };
}
