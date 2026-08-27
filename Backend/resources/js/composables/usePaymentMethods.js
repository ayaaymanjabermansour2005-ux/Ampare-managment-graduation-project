import { ref } from "vue";
import { useI18n } from "vue-i18n";
import paymentMethodService from "@/services/paymentMethodService";

export function usePaymentMethods() {
  const { t } = useI18n();

  const methods = ref([]);
  const isLoading = ref(false);
  const error = ref(null);

  async function fetchMethods(params = {}) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await paymentMethodService.list(params);
      const payload = data.data;
      methods.value = payload.data ?? payload;
    } catch (err) {
      error.value = err.response?.data?.message ?? t("owner_settings.financial.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  const isSaving = ref(false);
  const saveError = ref(null);

  async function createMethod(payload) {
    isSaving.value = true;
    saveError.value = null;
    try {
      await paymentMethodService.create(payload);
      return true;
    } catch (err) {
      saveError.value = err.response?.data ?? { message: t("owner_settings.financial.save_error") };
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  async function updateMethod(id, payload) {
    isSaving.value = true;
    saveError.value = null;
    try {
      await paymentMethodService.update(id, payload);
      return true;
    } catch (err) {
      saveError.value = err.response?.data ?? { message: t("owner_settings.financial.save_error") };
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  const isDeleting = ref(false);
  const deleteError = ref(null);
  const deletingId = ref(null);

  async function deleteMethod(id) {
    isDeleting.value = true;
    deletingId.value = id;
    deleteError.value = null;
    try {
      await paymentMethodService.destroy(id);
      return true;
    } catch (err) {
      deleteError.value = err.response?.data?.message ?? t("owner_settings.financial.delete_error");
      return false;
    } finally {
      isDeleting.value = false;
      deletingId.value = null;
    }
  }

  return {
    methods,
    isLoading,
    error,
    fetchMethods,
    isSaving,
    saveError,
    createMethod,
    updateMethod,
    isDeleting,
    deleteError,
    deletingId,
    deleteMethod,
  };
}
