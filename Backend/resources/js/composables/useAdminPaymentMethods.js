import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import paymentMethodService from "@/services/paymentMethodService";

export function useAdminPaymentMethods() {
  const { t } = useI18n();
  const methods = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const isLoading = ref(true);
  const error = ref(null);

  const search = ref("");

  async function fetchMethods(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await paymentMethodService.list({
        page,
        search: search.value || undefined,
      });
      const payload = data.data;
      methods.value = payload.data ?? payload;
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? methods.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("payment_methods_page.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  let debounceHandle = null;
  function onSearchInput() {
    clearTimeout(debounceHandle);
    debounceHandle = setTimeout(() => fetchMethods(1), 300);
  }
  const deletingId = ref(null);
  const deleteError = ref(null);

  async function deleteMethod(id) {
    deletingId.value = id;
    deleteError.value = null;
    try {
      await paymentMethodService.destroy(id);
      await fetchMethods(pagination.value.current_page);
      return true;
    } catch (err) {
      deleteError.value = normalizeApiError(err, t("payment_methods_page.delete_error")).message;
      return false;
    } finally {
      deletingId.value = null;
    }
  }

  return {
    methods,
    pagination,
    isLoading,
    error,
    search,
    fetchMethods,
    onSearchInput,
    deletingId,
    deleteError,
    deleteMethod,
  };
}