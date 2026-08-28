import { ref } from "vue";
import { useI18n } from "vue-i18n";
import contactMessageService from "@/services/contactMessageService";

export function useAdminContactMessages() {
  const { t } = useI18n();
  const messages = ref([]);
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  });
  const isLoading = ref(false);
  const error = ref(null);
  const searchTerm = ref("");
  const statusFilter = ref("");

  async function fetchMessages(page = 1) {
    isLoading.value = true;
    error.value = null;

    try {
      const { data } = await contactMessageService.list({
        page,
        search: searchTerm.value || undefined,
        status: statusFilter.value || undefined,
      });
      const payload = data.data;

      messages.value = payload.data ?? payload;
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? messages.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = err.response?.data?.message ?? t("contact_messages_page.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  function onFilterChange() {
    fetchMessages(1);
  }

  const isUpdating = ref(false);
  const updateError = ref(null);

  async function updateStatus(id, status, adminNote) {
    isUpdating.value = true;
    updateError.value = null;
    try {
      const { data } = await contactMessageService.updateStatus(id, { status, admin_note: adminNote || null });
      const index = messages.value.findIndex((m) => m.id === id);
      if (index !== -1) messages.value[index] = data.data;
      return data.data;
    } catch (err) {
      updateError.value = err.response?.data?.message ?? t("contact_messages_page.update_error");
      return null;
    } finally {
      isUpdating.value = false;
    }
  }

  return {
    messages,
    pagination,
    isLoading,
    error,
    searchTerm,
    statusFilter,
    fetchMessages,
    onFilterChange,
    isUpdating,
    updateError,
    updateStatus,
  };
}