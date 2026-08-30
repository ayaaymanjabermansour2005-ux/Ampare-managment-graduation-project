import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import faultService from "@/services/faultService";

export function useOwnerFaults() {
  const { t } = useI18n();
  const faults = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const isLoading = ref(true);
  const error = ref(null);

  const search = ref("");
  const statusFilter = ref("");

  const isVerifying = ref(false);
  const verifyError = ref(null);

  const isDecidingRepair = ref(false);
  const decideRepairError = ref(null);

  async function fetchFaults(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await faultService.list({
        page,
        search: search.value || undefined,
        status: statusFilter.value || undefined,
      });
      const payload = data.data;
      faults.value = payload.data ?? payload;
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? faults.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("faults_page.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  let debounceHandle = null;
  function onSearchInput() {
    clearTimeout(debounceHandle);
    debounceHandle = setTimeout(() => fetchFaults(1), 300);
  }
  function onFilterChange() {
    fetchFaults(1);
  }

  async function verifyFault(id, isValid) {
    isVerifying.value = true;
    verifyError.value = null;
    try {
      const { data } = await faultService.verify(id, { is_valid: isValid });
      const index = faults.value.findIndex((f) => f.id === id);
      if (index !== -1) faults.value[index] = data.data;
      return data.data;
    } catch (err) {
      verifyError.value = normalizeApiError(err, t("owner_faults.verify_error")).message;
      return null;
    } finally {
      isVerifying.value = false;
    }
  }

  async function decideFaultRepair(id, payload) {
    isDecidingRepair.value = true;
    decideRepairError.value = null;
    try {
      const { data } = await faultService.decideRepair(id, payload);
      const index = faults.value.findIndex((f) => f.id === id);
      if (index !== -1) faults.value[index] = data.data;
      return data.data;
    } catch (err) {
      decideRepairError.value = normalizeApiError(err, t("owner_faults.decide_repair_error")).message;
      return null;
    } finally {
      isDecidingRepair.value = false;
    }
  }

  return {
    faults,
    pagination,
    isLoading,
    error,
    search,
    statusFilter,
    isVerifying,
    verifyError,
    isDecidingRepair,
    decideRepairError,
    fetchFaults,
    onSearchInput,
    onFilterChange,
    verifyFault,
    decideFaultRepair,
  };
}
