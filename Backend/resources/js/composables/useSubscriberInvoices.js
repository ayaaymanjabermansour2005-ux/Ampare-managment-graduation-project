import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import invoiceService from "@/services/invoiceService";

export function useSubscriberInvoices() {
  const { t } = useI18n();
  const invoices = ref([]);
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  });
  const isLoading = ref(false);
  const error = ref(null);

  const totalDue = computed(() =>
    invoices.value
      .filter((i) =>
        ["pending", "partially_paid", "overdue"].includes(i.status),
      )
      .reduce((sum, i) => sum + i.remaining_balance_ils, 0),
  );

  async function fetchInvoices(page = 1) {
    isLoading.value = true;
    error.value = null;

    try {
      const { data } = await invoiceService.list({ page });
      const payload = data.data;
      const meta = payload.meta ?? payload;

      invoices.value = payload.data ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? invoices.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = err.response?.data?.message ?? t("my_invoices_page.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  const isLoadingDetail = ref(false);
  const detailError = ref(null);

  async function fetchInvoiceDetail(id) {
    isLoadingDetail.value = true;
    detailError.value = null;
    try {
      const { data } = await invoiceService.getInvoice(id);
      return data.data;
    } catch (err) {
      detailError.value = err.response?.data?.message ?? t("my_invoices_page.load_error");
      return null;
    } finally {
      isLoadingDetail.value = false;
    }
  }

  return {
    invoices,
    pagination,
    isLoading,
    error,
    totalDue,
    fetchInvoices,
    isLoadingDetail,
    detailError,
    fetchInvoiceDetail,
  };
}
