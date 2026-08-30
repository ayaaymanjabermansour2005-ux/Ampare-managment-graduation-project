import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import technicianPaymentService from "@/services/technicianPaymentService";

export function useTechnicianPayments() {
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

  const pendingCount = computed(
    () => payments.value.filter((p) => p.status === "pending").length,
  );

  async function fetchPayments(page = 1) {
    isLoading.value = true;
    error.value = null;

    try {
      const { data } = await technicianPaymentService.list({ page });
      const payload = data.data;

      payments.value = payload.data ?? payload;
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? payments.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("technician_payments.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  function replaceInList(updated) {
    const index = payments.value.findIndex((p) => p.id === updated.id);
    if (index !== -1) payments.value[index] = updated;
  }

  const actingId = ref(null);
  const actionError = ref(null);

  async function approve(paymentId) {
    actingId.value = paymentId;
    actionError.value = null;

    try {
      const { data } = await technicianPaymentService.approve(paymentId);
      replaceInList(data.data);
      return true;
    } catch (err) {
      actionError.value = normalizeApiError(err, t("technician_payments.approve_error")).message;
      return false;
    } finally {
      actingId.value = null;
    }
  }

  async function reject(paymentId, reason) {
    actingId.value = paymentId;
    actionError.value = null;

    try {
      const { data } = await technicianPaymentService.reject(paymentId, reason);
      replaceInList(data.data);
      return true;
    } catch (err) {
      actionError.value = normalizeApiError(err, t("technician_payments.reject_error")).message;
      return false;
    } finally {
      actingId.value = null;
    }
  }

  return {
    payments,
    pagination,
    isLoading,
    error,
    pendingCount,
    fetchPayments,
    actingId,
    actionError,
    approve,
    reject,
  };
}
