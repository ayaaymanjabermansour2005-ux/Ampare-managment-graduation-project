import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import complaintService from "@/services/complaintService";

export function useOwnerComplaints() {
  const { t } = useI18n();
  const complaints = ref([]);
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

  const isSubmittingNew = ref(false);
  const newComplaintError = ref(null);

  const resolvingId = ref(null);
  const isResolving = ref(false);
  const resolveError = ref(null);

  const hasComplaints = computed(() => complaints.value.length > 0);

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
      const meta = payload.meta ?? payload;

      complaints.value = payload.data ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? complaints.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("owner_complaints.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  let searchDebounceHandle = null;
  function onSearchInput() {
    clearTimeout(searchDebounceHandle);
    searchDebounceHandle = setTimeout(() => fetchComplaints(1), 300);
  }

  function onFilterChange() {
    fetchComplaints(1);
  }

  async function submitComplaint({ subject, description }) {
    isSubmittingNew.value = true;
    newComplaintError.value = null;

    try {
      await complaintService.create({ subject, description });
      await fetchComplaints(1);
      return true;
    } catch (err) {
      newComplaintError.value =
        normalizeApiError(err, t("owner_complaints.create_error")).message;
      return false;
    } finally {
      isSubmittingNew.value = false;
    }
  }

  function startResolving(complaintId) {
    resolvingId.value = complaintId;
    resolveError.value = null;
  }

  function cancelResolving() {
    resolvingId.value = null;
    resolveError.value = null;
  }

  async function resolveComplaint(complaintId, { status, resolution_note }) {
    isResolving.value = true;
    resolveError.value = null;

    try {
      const { data } = await complaintService.updateStatus(complaintId, {
        status,
        resolution_note,
      });

      const index = complaints.value.findIndex((c) => c.id === complaintId);
      if (index !== -1) {
        complaints.value[index] = data.data;
      }

      resolvingId.value = null;
      return true;
    } catch (err) {
      resolveError.value =
        normalizeApiError(err, t("owner_complaints.resolve_error")).message;
      return false;
    } finally {
      isResolving.value = false;
    }
  }

  return {
    complaints,
    pagination,
    isLoading,
    error,
    search,
    statusFilter,
    hasComplaints,
    fetchComplaints,
    onSearchInput,
    onFilterChange,

    isSubmittingNew,
    newComplaintError,
    submitComplaint,

    resolvingId,
    isResolving,
    resolveError,
    startResolving,
    cancelResolving,
    resolveComplaint,
  };
}
