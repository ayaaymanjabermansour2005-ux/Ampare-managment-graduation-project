import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import platformCommissionService from "@/services/platformCommissionService";

/**
 * "دفعات الملّاك" في لوحة الأدمن = عمولة المالك تجاه المنصة (platform_commissions)،
 * وهي مفهوم منفصل تمامًا عن "دفعات الفنيين" (Owner->Technician) — يُدار هنا
 * الاعتماد/التحويل فقط عبر PlatformCommissionController::updateStatus الموجود مسبقًا.
 */
export function useAdminOwnerCommissions() {
  const { t } = useI18n();

  const commissions = ref([]);
  const isLoading = ref(false);
  const error = ref(null);

  async function fetchCommissions(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await platformCommissionService.list({ page, per_page: 100 });
      const payload = data.data;
      commissions.value = payload.data ?? payload;
    } catch (err) {
      error.value = err.response?.data?.message ?? t("admin_payments_page.owner_payments_load_error");
    } finally {
      isLoading.value = false;
    }
  }

  const updatingId = ref(null);
  const updateError = ref(null);

  async function markPaid(commissionId) {
    updatingId.value = commissionId;
    updateError.value = null;
    try {
      const { data } = await platformCommissionService.updateStatus(commissionId, "paid");
      const index = commissions.value.findIndex((c) => c.id === commissionId);
      if (index !== -1) commissions.value[index] = data.data;
      return true;
    } catch (err) {
      updateError.value = err.response?.data?.message ?? t("admin_payments_page.owner_payments_update_error");
      return false;
    } finally {
      updatingId.value = null;
    }
  }

  return {
    commissions,
    isLoading,
    error,
    fetchCommissions,
    updatingId,
    updateError,
    markPaid,
  };
}
