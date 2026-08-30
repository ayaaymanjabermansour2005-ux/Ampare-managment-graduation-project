import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import platformCommissionService from "@/services/platformCommissionService";

export function useCommissionReports() {
  const { t } = useI18n();
  const commissions = ref([]);
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  });
  const isLoading = ref(false);
  const error = ref(null);

  const summary = ref({ earned: 0, paid: 0, pending: 0 });
  const isLoadingSummary = ref(false);
  const totalEarned = computed(() => summary.value.earned);

  async function fetchSummary() {
    isLoadingSummary.value = true;
    try {
      const { data } = await platformCommissionService.summary();
      summary.value = {
        earned: data.data.earned ?? 0,
        paid: data.data.paid ?? 0,
        pending: data.data.pending ?? 0,
      };
    } catch {
      // تبقى القيم الافتراضية (0) — البطاقة تظل صامتة عن الخطأ لأن الجدول
      // نفسه يعرض رسالة الخطأ الرئيسية أصلًا عبر `error`.
    } finally {
      isLoadingSummary.value = false;
    }
  }

  async function fetchCommissions(page = 1) {
    isLoading.value = true;
    error.value = null;

    try {
      const { data } = await platformCommissionService.list({ page });
      const payload = data.data;
      const meta = payload.meta ?? payload;

      commissions.value = payload.data ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? commissions.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value =
        normalizeApiError(err, t("owner_reports.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  const markingId = ref(null);
  const markError = ref(null);

  async function markAsPaid(id) {
    markingId.value = id;
    markError.value = null;
    try {
      const { data } = await platformCommissionService.updateStatus(id, "paid");
      const index = commissions.value.findIndex((c) => c.id === id);
      if (index !== -1) commissions.value[index] = data.data;
      fetchSummary();
      return true;
    } catch (err) {
      markError.value =
        normalizeApiError(err, t("owner_reports.mark_paid_error")).message;
      return false;
    } finally {
      markingId.value = null;
    }
  }

  return {
    commissions,
    pagination,
    isLoading,
    error,
    summary,
    isLoadingSummary,
    totalEarned,
    fetchSummary,
    fetchCommissions,
    markingId,
    markError,
    markAsPaid,
  };
}
