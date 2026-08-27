import { ref } from "vue";
import { useI18n } from "vue-i18n";
import loginLogService from "@/services/loginLogService";

export function useLoginLogs() {
  const { t } = useI18n();
  const logs = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1 });
  const isLoading = ref(true);
  const error = ref(null);

  async function fetchLogs(page = 1, filters = {}) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await loginLogService.list({ page, ...filters });
      const payload = data.data;
      logs.value = payload.data ?? payload;
      pagination.value = {
        current_page: payload.current_page ?? 1,
        last_page: payload.last_page ?? 1,
      };
    } catch (err) {
      error.value =
        err.response?.data?.message ?? t("login_logs_page.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  return { logs, pagination, isLoading, error, fetchLogs };
}
