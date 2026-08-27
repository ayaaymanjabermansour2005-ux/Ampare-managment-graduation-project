import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import invoiceService from "@/services/invoiceService";

export function useOwnerInvoices() {
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

  const search = ref("");
  const statusFilter = ref("");

  const overdueCount = computed(
    () => invoices.value.filter((i) => i.status === "overdue").length,
  );

  async function fetchInvoices(page = 1) {
    isLoading.value = true;
    error.value = null;

    try {
      const { data } = await invoiceService.list({
        page,
        search: search.value || undefined,
        status: statusFilter.value || undefined,
      });
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
      error.value =
        err.response?.data?.message ?? t("owner_invoices.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  let searchDebounceHandle = null;
  function onSearchInput() {
    clearTimeout(searchDebounceHandle);
    searchDebounceHandle = setTimeout(() => fetchInvoices(1), 300);
  }

  function onFilterChange() {
    fetchInvoices(1);
  }

  const cancellingId = ref(null);
  const cancelError = ref(null);

  async function cancelInvoice(id) {
    cancellingId.value = id;
    cancelError.value = null;

    try {
      const { data } = await invoiceService.cancel(id);
      const index = invoices.value.findIndex((i) => i.id === id);
      if (index !== -1) invoices.value[index] = data.data;
      return true;
    } catch (err) {
      cancelError.value =
        err.response?.data?.message ?? t("owner_invoices.cancel_error");
      return false;
    } finally {
      cancellingId.value = null;
    }
  }

  return {
    invoices,
    pagination,
    isLoading,
    error,
    search,
    statusFilter,
    overdueCount,
    fetchInvoices,
    onSearchInput,
    onFilterChange,
    cancellingId,
    cancelError,
    cancelInvoice,
  };
}
