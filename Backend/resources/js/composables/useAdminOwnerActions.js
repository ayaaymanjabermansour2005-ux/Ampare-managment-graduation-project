import { ref, reactive } from "vue";
import { useI18n } from "vue-i18n";
import userService from "@/services/userService";
import planService from "@/services/planService";
import generatorService from "@/services/generatorService";
import activityLogService from "@/services/activityLogService";
import { normalizeApiError } from "@/utils/normalizeApiError";

/**
 * Owner-management operations beyond the core list/create/update/delete CRUD
 * already covered by useAdminGeneratorOwners: commission settings, password
 * reset link, plan assignment, and generator ownership transfer. Extracted
 * out of GeneratorOwnersView.vue (FE-01) so these services aren't called
 * directly from the view.
 *
 * @param {{ owners: import('vue').Ref<Array>, loadAll: () => Promise<void> }} deps
 *   `owners` is the same ref returned by useAdminGeneratorOwners — mutated
 *   in place here so both composables stay in sync with the view's table.
 */
export function useAdminOwnerActions({ owners, loadAll }) {
  const { t } = useI18n();

  /* ---------------- إعدادات العمولة ---------------- */
  const isSavingCommission = ref(false);
  const commissionError = ref(null);

  async function saveCommissionSettings(ownerId, payload) {
    isSavingCommission.value = true;
    commissionError.value = null;
    try {
      const { data } = await userService.updateCommissionSettings(ownerId, payload);
      const index = owners.value.findIndex((o) => o.id === ownerId);
      if (index !== -1) owners.value[index] = data.data;
      return data.data;
    } catch (err) {
      const normalized = normalizeApiError(err, t("owners_page.commission_save_error"));
      commissionError.value = normalized.fieldError("commission_rate")
        ?? normalized.fieldError("commission_mode")
        ?? normalized.message;
      return null;
    } finally {
      isSavingCommission.value = false;
    }
  }

  /* ---------------- رابط إعادة تعيين كلمة السر ---------------- */
  const isSendingResetLink = ref(false);

  async function sendPasswordResetLink(ownerId) {
    isSendingResetLink.value = true;
    try {
      await userService.sendPasswordResetLink(ownerId);
      return { success: true, message: null };
    } catch (err) {
      return { success: false, message: normalizeApiError(err, null).message };
    } finally {
      isSendingResetLink.value = false;
    }
  }

  /* ---------------- تعيين الخطة ---------------- */
  const plans = ref([]);
  const isLoadingPlans = ref(false);
  const isAssigningPlan = ref(false);
  const planError = ref(null);

  async function fetchPlans() {
    if (plans.value.length > 0) return;
    isLoadingPlans.value = true;
    try {
      const { data } = await planService.list();
      plans.value = data.data;
    } finally {
      isLoadingPlans.value = false;
    }
  }

  async function assignPlan(ownerId, planId) {
    isAssigningPlan.value = true;
    planError.value = null;
    try {
      await planService.assign(ownerId, planId);
      const index = owners.value.findIndex((o) => o.id === ownerId);
      if (index !== -1) owners.value[index].plan = plans.value.find((p) => p.id === planId);
      return true;
    } catch (err) {
      planError.value = normalizeApiError(err, t("owners_page.plan_assign_error")).message;
      return false;
    } finally {
      isAssigningPlan.value = false;
    }
  }

  /* ---------------- نقل ملكية مولد ---------------- */
  const transferGenForm = reactive({ generator_id: "", owner_id: "" });
  const isTransferringGenerator = ref(false);
  const transferGenError = ref(null);
  const allGeneratorsForTransfer = ref([]);
  const isLoadingGeneratorsForTransfer = ref(false);

  async function fetchGeneratorsForTransfer() {
    isLoadingGeneratorsForTransfer.value = true;
    try {
      const { data } = await generatorService.list({ per_page: 200, sort: "name" });
      const payload = data.data;
      allGeneratorsForTransfer.value = payload.data ?? payload;
    } catch {
      allGeneratorsForTransfer.value = [];
    } finally {
      isLoadingGeneratorsForTransfer.value = false;
    }
  }

  async function submitTransferGenerator() {
    isTransferringGenerator.value = true;
    transferGenError.value = null;
    try {
      await generatorService.transferOwnership(transferGenForm.generator_id, transferGenForm.owner_id);
      await loadAll();
      return true;
    } catch (err) {
      // نفس النمط المتكرر — الرسالة المحدَّدة (مثلاً: "هذا المولد مملوك أصلًا
      // لهذا المستخدم") موجودة بـ errors.owner_id، لا بـ message العامة.
      const normalized = normalizeApiError(err, t("owners_page.transfer_error"));
      transferGenError.value = normalized.fieldError("owner_id") ?? normalized.message;
      return false;
    } finally {
      isTransferringGenerator.value = false;
    }
  }

  /* ---------------- مخطط الإيرادات الموزّعة (مستقل عن stats الرئيسية) ---------------- */
  const revenueDistData = ref({ labels: [], revenue: [], due: [] });
  const availableYears = ref([]);
  const isLoadingRevenue = ref(true);

  async function fetchRevenueDistribution(params) {
    isLoadingRevenue.value = true;
    try {
      const { data } = await userService.ownersStats(params);
      revenueDistData.value = data.data.revenue_distribution;
      if (data.data.revenue_distribution.available_years?.length) {
        availableYears.value = data.data.revenue_distribution.available_years;
      }
    } finally {
      isLoadingRevenue.value = false;
    }
  }

  /* ---------------- رابط تصدير قائمة أصحاب المولدات (Excel) ---------------- */
  function ownersExportUrl(params) {
    return userService.ownersExportUrl(params);
  }

  /* ---------------- الخط الزمني (Activity Log حقيقي) ---------------- */
  const timeline = ref([]);
  const isLoadingTimeline = ref(true);

  async function fetchTimeline() {
    isLoadingTimeline.value = true;
    try {
      const { data } = await activityLogService.index({ subject_type: "user", per_page: 6 });
      timeline.value = data.data.data ?? data.data;
    } finally {
      isLoadingTimeline.value = false;
    }
  }

  return {
    isSavingCommission,
    commissionError,
    saveCommissionSettings,

    isSendingResetLink,
    sendPasswordResetLink,

    plans,
    isLoadingPlans,
    isAssigningPlan,
    planError,
    fetchPlans,
    assignPlan,

    transferGenForm,
    isTransferringGenerator,
    transferGenError,
    allGeneratorsForTransfer,
    isLoadingGeneratorsForTransfer,
    fetchGeneratorsForTransfer,
    submitTransferGenerator,

    revenueDistData,
    availableYears,
    isLoadingRevenue,
    fetchRevenueDistribution,

    ownersExportUrl,

    timeline,
    isLoadingTimeline,
    fetchTimeline,
  };
}
