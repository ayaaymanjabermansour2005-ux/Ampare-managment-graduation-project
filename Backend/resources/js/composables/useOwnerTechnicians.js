import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import technicianService from "@/services/technicianService";
import userService from "@/services/userService";

export function useOwnerTechnicians() {
  const { t } = useI18n();
  const technicians = ref([]);
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  });
  const isLoading = ref(true);
  const error = ref(null);

  const search = ref("");

  async function fetchTechnicians(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await technicianService.list({
        page,
        search: search.value || undefined,
      });
      const payload = data.data;
      const meta = payload.meta ?? payload;

      technicians.value = payload.data ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? technicians.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("owner_technicians.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  let searchDebounceHandle = null;
  function onSearchInput() {
    clearTimeout(searchDebounceHandle);
    searchDebounceHandle = setTimeout(() => fetchTechnicians(1), 300);
  }

  const eligibleUsers = ref([]);
  const isLoadingUsers = ref(false);

  async function loadEligibleUsers(search = "") {
    isLoadingUsers.value = true;
    try {
      const { data } = await userService.list({
        role: "technician",
        search,
        per_page: 20,
      });
      const payload = data.data;
      eligibleUsers.value = payload.data ?? payload;
    } catch {
      // Called fire-and-forget from a debounced search box (TechniciansView.vue)
      // with no .catch() of its own — an unhandled rejection here previously
      // left stale/incorrect results showing with no feedback. Clear the list
      // instead; the loading spinner already disabled itself via `finally`.
      eligibleUsers.value = [];
    } finally {
      isLoadingUsers.value = false;
    }
  }

  const isSaving = ref(false);
  const saveError = ref(null);

  async function createTechnician(payload) {
    isSaving.value = true;
    saveError.value = null;
    try {
      await technicianService.create(payload);
      await fetchTechnicians(1);
      return true;
    } catch (err) {
      saveError.value = err.response?.data ?? { message: t("owner_technicians.create_error") };
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  async function createTechnicianAccount(payload) {
    isSaving.value = true;
    saveError.value = null;
    try {
      await technicianService.createAccount(payload);
      await fetchTechnicians(1);
      return true;
    } catch (err) {
      saveError.value = err.response?.data ?? { message: t("owner_technicians.create_error") };
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  async function updateTechnician(id, payload) {
    isSaving.value = true;
    saveError.value = null;
    try {
      const { data } = await technicianService.update(id, payload);
      const index = technicians.value.findIndex((t) => t.id === id);
      if (index !== -1) technicians.value[index] = data.data;
      return true;
    } catch (err) {
      saveError.value = err.response?.data ?? {
        message: t("owner_technicians.update_error"),
      };
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  const deletingId = ref(null);
  const deleteError = ref(null);

  async function deleteTechnician(id) {
    deletingId.value = id;
    deleteError.value = null;
    try {
      await technicianService.destroy(id);
      await fetchTechnicians(pagination.value.current_page);
      return true;
    } catch (err) {
      deleteError.value = normalizeApiError(err, t("owner_technicians.delete_error")).message;
      return false;
    } finally {
      deletingId.value = null;
    }
  }

  return {
    technicians,
    pagination,
    isLoading,
    error,
    search,
    fetchTechnicians,
    onSearchInput,
    eligibleUsers,
    isLoadingUsers,
    loadEligibleUsers,
    isSaving,
    saveError,
    createTechnician,
    createTechnicianAccount,
    updateTechnician,
    deletingId,
    deleteError,
    deleteTechnician,
  };
}
