import { ref } from "vue";
import { useI18n } from "vue-i18n";
import complaintService from "@/services/complaintService";
import { normalizeApiError } from "@/utils/normalizeApiError";

export function useAdminComplaints() {
  const { t } = useI18n();
  const complaints = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const isLoading = ref(true);
  const error = ref(null);

  const search = ref("");
  const statusFilter = ref("");

  const isResolving = ref(false);
  const resolveError = ref(null);

  const deletingId = ref(null);
  const deleteError = ref(null);

  async function fetchComplaints(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await complaintService.list({
        page,
        search: search.value || undefined,
        status: statusFilter.value || undefined,
      });
      const payload = data.data;
      complaints.value = payload.data ?? payload;
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? complaints.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("complaints_page.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  let debounceHandle = null;
  function onSearchInput() {
    clearTimeout(debounceHandle);
    debounceHandle = setTimeout(() => fetchComplaints(1), 300);
  }
  function onFilterChange() {
    fetchComplaints(1);
  }

  async function resolveComplaint(id, payload) {
    isResolving.value = true;
    resolveError.value = null;
    try {
      const { data } = await complaintService.updateStatus(id, payload);
      const index = complaints.value.findIndex((c) => c.id === id);
      if (index !== -1) complaints.value[index] = data.data;
      return true;
    } catch (err) {
      resolveError.value = normalizeApiError(err, t("complaints_page.resolve_error")).message;
      return false;
    } finally {
      isResolving.value = false;
    }
  }

  async function deleteComplaint(id) {
    deletingId.value = id;
    deleteError.value = null;
    try {
      await complaintService.destroy(id);
      await fetchComplaints(pagination.value.current_page);
      return true;
    } catch (err) {
      deleteError.value = normalizeApiError(err, t("complaints_page.delete_error")).message;
      return false;
    } finally {
      deletingId.value = null;
    }
  }

  return {
    complaints,
    pagination,
    isLoading,
    error,
    search,
    statusFilter,
    isResolving,
    resolveError,
    deletingId,
    deleteError,
    fetchComplaints,
    onSearchInput,
    onFilterChange,
    resolveComplaint,
    deleteComplaint,
  };
}