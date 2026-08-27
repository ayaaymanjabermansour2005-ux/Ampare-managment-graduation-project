import { ref, reactive } from "vue";
import { useI18n } from "vue-i18n";
import generatorService from "@/services/generatorService";
import generatorDiagnosticService from "@/services/generatorDiagnosticService";
import fuelService from "@/services/fuelService";

export function useMaintenance() {
  const { t } = useI18n();
  const generators = ref([]);
  const selectedGeneratorId = ref(null);
  const isLoadingGenerators = ref(true);

  async function loadGenerators() {
    isLoadingGenerators.value = true;
    try {
      const { data } = await generatorService.list({ per_page: 100 });
      const payload = data.data;
      generators.value = payload.data ?? payload;
      if (generators.value.length)
        selectedGeneratorId.value = generators.value[0].id;
    } finally {
      isLoadingGenerators.value = false;
    }
  }

  const isSubmittingDiagnostic = ref(false);
  const diagnosticError = ref(null);
  const diagnosticSuccess = ref(false);

  async function submitDiagnostic(payload) {
    isSubmittingDiagnostic.value = true;
    diagnosticError.value = null;
    diagnosticSuccess.value = false;
    try {
      await generatorDiagnosticService.create(
        selectedGeneratorId.value,
        payload,
      );
      diagnosticSuccess.value = true;
      return true;
    } catch (err) {
      diagnosticError.value = err.response?.data ?? {
        message: t("generator_diagnostics.save_failed_message"),
      };
      return false;
    } finally {
      isSubmittingDiagnostic.value = false;
    }
  }

  const isSubmittingFuelPurchase = ref(false);
  const fuelPurchaseError = ref(null);
  const fuelPurchaseSuccess = ref(false);

  async function submitFuelPurchase(payload) {
    isSubmittingFuelPurchase.value = true;
    fuelPurchaseError.value = null;
    fuelPurchaseSuccess.value = false;
    try {
      const formData = new FormData();
      Object.entries(payload).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== "")
          formData.append(key, value);
      });
      await fuelService.storePurchase(selectedGeneratorId.value, formData);
      fuelPurchaseSuccess.value = true;
      return true;
    } catch (err) {
      fuelPurchaseError.value = err.response?.data ?? {
        message: t("generator_diagnostics.save_failed_message"),
      };
      return false;
    } finally {
      isSubmittingFuelPurchase.value = false;
    }
  }

  const isSubmittingFuelReading = ref(false);
  const fuelReadingError = ref(null);
  const fuelReadingSuccess = ref(false);

  async function submitFuelReading(payload) {
    isSubmittingFuelReading.value = true;
    fuelReadingError.value = null;
    fuelReadingSuccess.value = false;
    try {
      const formData = new FormData();
      Object.entries(payload).forEach(([key, value]) => {
        if (value !== null && value !== undefined && value !== "")
          formData.append(key, value);
      });
      await fuelService.storeReading(selectedGeneratorId.value, formData);
      fuelReadingSuccess.value = true;
      return true;
    } catch (err) {
      fuelReadingError.value = err.response?.data ?? {
        message: t("generator_diagnostics.save_failed_message"),
      };
      return false;
    } finally {
      isSubmittingFuelReading.value = false;
    }
  }

  return {
    generators,
    selectedGeneratorId,
    isLoadingGenerators,
    loadGenerators,
    isSubmittingDiagnostic,
    diagnosticError,
    diagnosticSuccess,
    submitDiagnostic,
    isSubmittingFuelPurchase,
    fuelPurchaseError,
    fuelPurchaseSuccess,
    submitFuelPurchase,
    isSubmittingFuelReading,
    fuelReadingError,
    fuelReadingSuccess,
    submitFuelReading,
  };
}
