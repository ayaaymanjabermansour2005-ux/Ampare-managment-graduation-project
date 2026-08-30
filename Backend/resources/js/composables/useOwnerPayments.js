import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import paymentService from "@/services/paymentService";

export function useOwnerPayments() {
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

  const statusFilter = ref("pending");

  const pendingCount = computed(
    () => payments.value.filter((p) => p.status === "pending").length,
  );

  async function fetchPayments(page = 1) {
    isLoading.value = true;
    error.value = null;

    try {
      const { data } = await paymentService.list({
        page,
        status: statusFilter.value === "all" ? undefined : statusFilter.value,
      });
      const payload = data.data;
      const meta = payload.meta ?? payload;

      payments.value = payload.data ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? payments.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("owner_payments.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  function onFilterChange() {
    fetchPayments(1);
  }

  const detailCache = ref({});
  const isLoadingDetail = ref(false);

  async function fetchPaymentDetail(id) {
    isLoadingDetail.value = true;
    try {
      const { data } = await paymentService.show(id);
      detailCache.value[id] = data.data;
      return data.data;
    } finally {
      isLoadingDetail.value = false;
    }
  }

  function replaceInList(updated) {
    const index = payments.value.findIndex((p) => p.id === updated.id);
    if (index !== -1)
      payments.value[index] = { ...payments.value[index], ...updated };
  }

  const isActing = ref(false);
  const actionError = ref(null);

  async function approvePayment(id) {
    isActing.value = true;
    actionError.value = null;
    try {
      const { data } = await paymentService.approve(id);
      replaceInList(data.data);
      return true;
    } catch (err) {
      actionError.value = normalizeApiError(err, t("owner_payments.approve_error")).message;
      return false;
    } finally {
      isActing.value = false;
    }
  }

  async function rejectPayment(id, reason) {
    isActing.value = true;
    actionError.value = null;
    try {
      const { data } = await paymentService.reject(id, { reason });
      replaceInList(data.data);
      return true;
    } catch (err) {
      actionError.value = normalizeApiError(err, t("owner_payments.reject_error")).message;
      return false;
    } finally {
      isActing.value = false;
    }
  }

  async function requestCorrection(id, note) {
    isActing.value = true;
    actionError.value = null;
    try {
      const { data } = await paymentService.needsCorrection(id, { note });
      replaceInList(data.data);
      return true;
    } catch (err) {
      actionError.value =
        normalizeApiError(err, t("owner_payments.correction_error")).message;
      return false;
    } finally {
      isActing.value = false;
    }
  }

  return {
    payments,
    pagination,
    isLoading,
    error,
    statusFilter,
    pendingCount,
    fetchPayments,
    onFilterChange,
    isLoadingDetail,
    fetchPaymentDetail,
    isActing,
    actionError,
    approvePayment,
    rejectPayment,
    requestCorrection,
  };
}
