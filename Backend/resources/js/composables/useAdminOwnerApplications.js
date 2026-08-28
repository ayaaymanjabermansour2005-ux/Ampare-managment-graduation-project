import { ref } from "vue";
import { useI18n } from "vue-i18n";
import ownerApplicationService from "@/services/ownerApplicationService";

export function useAdminOwnerApplications() {
  const { t } = useI18n();
  const applications = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const isLoading = ref(true);
  const error = ref(null);

  const statusFilter = ref("pending");
  const searchQuery = ref("");
  const sortBy = ref("created_desc");
  const dateFrom = ref("");
  const dateTo = ref("");
  const dateRangeError = ref(null);

  const reviewingId = ref(null);
  const reviewError = ref(null);

  const isBulkProcessing = ref(false);
  const bulkError = ref(null);

  const statusCounts = ref({ pending: 0, approved: 0, rejected: 0, all: 0 });

  let searchDebounceTimer = null;

  async function fetchApplications(page = 1) {
    if (dateFrom.value && dateTo.value && dateFrom.value > dateTo.value) {
      dateRangeError.value = t("owner_applications_page.date_range_error");
      return;
    }
    dateRangeError.value = null;

    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await ownerApplicationService.list({
        page,
        status: statusFilter.value || undefined,
        search: searchQuery.value || undefined,
        sort: sortBy.value || undefined,
        from_date: dateFrom.value || undefined,
        to_date: dateTo.value || undefined,
      });
      const payload = data.data;
      applications.value = payload.data ?? payload;
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? applications.value.length,
        per_page: meta.per_page ?? 15,
      };

      const counts = meta?.status_counts;
      if (counts) {
        statusCounts.value = {
          pending: counts.pending ?? 0,
          approved: counts.approved ?? 0,
          rejected: counts.rejected ?? 0,
          all: counts.all ?? (counts.pending ?? 0) + (counts.approved ?? 0) + (counts.rejected ?? 0),
        };
      }
    } catch (err) {
      const validationErrors = err.response?.status === 422 ? err.response?.data?.errors : null;
      if (validationErrors?.to_date || validationErrors?.from_date) {
        dateRangeError.value = (validationErrors.to_date ?? validationErrors.from_date)[0];
      }
      error.value = err.response?.data?.message ?? t("owner_applications_page.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  function onFilterChange() {
    fetchApplications(1);
  }

  function onSearchInput() {
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => {
      fetchApplications(1);
    }, 300);
  }

  function onSortChange() {
    fetchApplications(1);
  }

  function onDateFilterChange() {
    fetchApplications(1);
  }

  async function approveApplication(id) {
    reviewingId.value = id;
    reviewError.value = null;
    try {
      const { data } = await ownerApplicationService.approve(id);
      applications.value = applications.value.filter((a) => a.id !== id);
      return data.data;
    } catch (err) {
      reviewError.value = err.response?.data?.message ?? t("owner_applications_page.approve_error");
      return null;
    } finally {
      reviewingId.value = null;
    }
  }

  async function rejectApplication(id, reason) {
    reviewingId.value = id;
    reviewError.value = null;
    try {
      const { data } = await ownerApplicationService.reject(id, { reason });
      applications.value = applications.value.filter((a) => a.id !== id);
      return data.data;
    } catch (err) {
      reviewError.value = err.response?.data?.message ?? t("owner_applications_page.reject_error");
      return null;
    } finally {
      reviewingId.value = null;
    }
  }

  async function bulkApproveApplications(ids) {
    isBulkProcessing.value = true;
    bulkError.value = null;
    try {
      const { data } = await ownerApplicationService.bulkApprove(ids);
      await fetchApplications(1);
      return data.data;
    } catch (err) {
      bulkError.value = err.response?.data?.message ?? t("owner_applications_page.bulk_approve_error");
      return null;
    } finally {
      isBulkProcessing.value = false;
    }
  }

  async function bulkRejectApplications(ids, reason) {
    isBulkProcessing.value = true;
    bulkError.value = null;
    try {
      const { data } = await ownerApplicationService.bulkReject(ids, reason);
      await fetchApplications(1);
      return data.data;
    } catch (err) {
      bulkError.value = err.response?.data?.message ?? t("owner_applications_page.bulk_reject_error");
      return null;
    } finally {
      isBulkProcessing.value = false;
    }
  }

  async function updateInternalNote(id, note) {
    try {
      const { data } = await ownerApplicationService.updateInternalNote(id, { internal_note: note });
      const target = applications.value.find((a) => a.id === id);
      if (target) target.internal_note = data?.data?.internal_note ?? note;
      return true;
    } catch (err) {
      reviewError.value = err.response?.data?.message ?? t("owner_applications_page.note_save_error");
      return false;
    }
  }

  return {
    applications,
    pagination,
    isLoading,
    error,
    statusFilter,
    searchQuery,
    sortBy,
    dateFrom,
    dateTo,
    dateRangeError,
    reviewingId,
    reviewError,
    isBulkProcessing,
    bulkError,
    statusCounts,
    fetchApplications,
    onFilterChange,
    onSearchInput,
    onSortChange,
    onDateFilterChange,
    approveApplication,
    rejectApplication,
    bulkApproveApplications,
    bulkRejectApplications,
    updateInternalNote,
  };
}