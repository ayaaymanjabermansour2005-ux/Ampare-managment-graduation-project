import { ref } from "vue";
import { useI18n } from "vue-i18n";
import paymentService from "@/services/paymentService";

export function useSubscriberPayments() {
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

  async function fetchPayments(page = 1) {
    isLoading.value = true;
    error.value = null;

    try {
      const { data } = await paymentService.list({ page });
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
      error.value = err.response?.data?.message ?? t("my_invoices_page.payments_load_error");
    } finally {
      isLoading.value = false;
    }
  }

  const isResubmitting = ref(false);
  const resubmitError = ref(null);

  async function resubmitPayment(
    id,
    { amount, transaction_reference, note, attachments },
  ) {
    isResubmitting.value = true;
    resubmitError.value = null;

    try {
      const formData = new FormData();
      if (amount) formData.append("amount", amount);
      if (transaction_reference)
        formData.append("transaction_reference", transaction_reference);
      if (note) formData.append("note", note);
      (attachments ?? []).forEach((file) =>
        formData.append("attachments[]", file),
      );

      const { data } = await paymentService.resubmit(id, formData);
      const index = payments.value.findIndex((p) => p.id === id);
      if (index !== -1) payments.value[index] = data.data;
      return true;
    } catch (err) {
      resubmitError.value = err.response?.data ?? {
        message: t("my_invoices_page.resubmit_error"),
      };
      return false;
    } finally {
      isResubmitting.value = false;
    }
  }

  const isCancelling = ref(false);

  async function cancelPayment(id) {
    isCancelling.value = true;
    try {
      const { data } = await paymentService.cancel(id);
      const index = payments.value.findIndex((p) => p.id === id);
      if (index !== -1) payments.value[index] = data.data;
      return true;
    } catch {
      return false;
    } finally {
      isCancelling.value = false;
    }
  }

  return {
    payments,
    pagination,
    isLoading,
    error,
    fetchPayments,
    isResubmitting,
    resubmitError,
    resubmitPayment,
    isCancelling,
    cancelPayment,
  };
}
