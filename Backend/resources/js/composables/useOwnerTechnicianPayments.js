import { ref } from "vue";
import { useI18n } from "vue-i18n";
import technicianPaymentService from "@/services/technicianPaymentService";

export function useOwnerTechnicianPayments() {
  const { t } = useI18n();
  const payments = ref([]);
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  });
  const isLoading = ref(false);
  const error = ref(null);

  const technicianFilter = ref("");
  const statusFilter = ref("");

  async function fetchPayments(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await technicianPaymentService.list({
        page,
        technician_id: technicianFilter.value || undefined,
        status: statusFilter.value || undefined,
      });
      const payload = data.data;
      payments.value = payload.data ?? payload;
      pagination.value = {
        current_page: payload.current_page ?? 1,
        last_page: payload.last_page ?? 1,
        total: payload.total ?? payments.value.length,
        per_page: payload.per_page ?? 15,
      };
    } catch (err) {
      error.value = err.response?.data?.message ?? t("owner_technician_payments.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  function onFilterChange() {
    fetchPayments(1);
  }

  const isCreating = ref(false);
  const createError = ref(null);

  async function createPayment(payload) {
    isCreating.value = true;
    createError.value = null;
    try {
      await technicianPaymentService.create(payload);
      await fetchPayments(1);
      return true;
    } catch (err) {
      createError.value = err.response?.data ?? { message: t("owner_technician_payments.create_error") };
      return false;
    } finally {
      isCreating.value = false;
    }
  }

  return {
    payments,
    pagination,
    isLoading,
    error,
    technicianFilter,
    statusFilter,
    fetchPayments,
    onFilterChange,
    isCreating,
    createError,
    createPayment,
  };
}
