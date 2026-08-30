import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import technicianPaymentService from "@/services/technicianPaymentService";

/**
 * Admin's view of Technician Payments is STRICTLY read/audit-only — this
 * composable intentionally exposes no approve/reject actions at all. The
 * backend independently enforces the same rule (TechnicianPaymentPolicy
 * excludes Admin from approve/reject regardless of permission grants), so
 * this is defense in depth, not the only guard.
 */
export function useAdminTechnicianPayments() {
  const { t } = useI18n();

  const payments = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const isLoading = ref(false);
  const error = ref(null);
  const statusFilter = ref("");

  async function fetchPayments(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await technicianPaymentService.list({
        page,
        status: statusFilter.value || undefined,
      });
      const payload = data.data;
      payments.value = payload.data ?? payload;
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? payments.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("admin_payments_page.technician_payments_load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  return {
    payments,
    pagination,
    isLoading,
    error,
    statusFilter,
    fetchPayments,
  };
}
