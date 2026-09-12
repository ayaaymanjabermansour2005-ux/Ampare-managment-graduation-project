import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import loginLogService from "@/services/loginLogService";

export function useLoginLogs() {
  const { t } = useI18n();
  const logs = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1, total: 0 });
  const isLoading = ref(true);
  const error = ref(null);

  async function fetchLogs(page = 1, filters = {}) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await loginLogService.list({ page, ...filters });
      const payload = data.data;
      logs.value = payload.data ?? payload;
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? logs.value.length,
      };
    } catch (err) {
      error.value =
        normalizeApiError(err, t("login_logs_page.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  return { logs, pagination, isLoading, error, fetchLogs };
}
