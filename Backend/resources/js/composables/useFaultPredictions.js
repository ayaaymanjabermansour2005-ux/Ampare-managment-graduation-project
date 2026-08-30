import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import faultPredictionService from "@/services/faultPredictionService";

export function useFaultPredictions() {
  const { t } = useI18n();
  const predictions = ref([]);
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  });
  const isLoading = ref(false);
  const error = ref(null);

  async function fetchPredictions(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await faultPredictionService.list({ page });
      const payload = data.data;
      predictions.value = payload.data ?? payload;
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? predictions.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value =
        normalizeApiError(err, t("fault_predictions.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  const decidingId = ref(null);
  const decisionError = ref(null);

  async function confirmPrediction(id) {
    decidingId.value = id;
    decisionError.value = null;
    try {
      await faultPredictionService.confirm(id);
      predictions.value = predictions.value.filter((p) => p.id !== id);
      return true;
    } catch (err) {
      decisionError.value =
        normalizeApiError(err, t("fault_predictions.confirm_error")).message;
      return false;
    } finally {
      decidingId.value = null;
    }
  }

  async function dismissPrediction(id) {
    decidingId.value = id;
    decisionError.value = null;
    try {
      await faultPredictionService.dismiss(id);
      predictions.value = predictions.value.filter((p) => p.id !== id);
      return true;
    } catch (err) {
      decisionError.value =
        normalizeApiError(err, t("fault_predictions.dismiss_error")).message;
      return false;
    } finally {
      decidingId.value = null;
    }
  }

  return {
    predictions,
    pagination,
    isLoading,
    error,
    fetchPredictions,
    decidingId,
    decisionError,
    confirmPrediction,
    dismissPrediction,
  };
}
