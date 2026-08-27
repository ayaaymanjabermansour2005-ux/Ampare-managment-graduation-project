import { ref } from "vue";
import { useI18n } from "vue-i18n";
import userService from "@/services/userService";

export function useAdminGeneratorOwners() {
  const { t } = useI18n();
  const owners = ref([]);
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

  const stats = ref(null);
  const isLoadingStats = ref(true);

  async function fetchOwners(page = 1) {
    isLoading.value = true;
    error.value = null;

    try {
      const { data } = await userService.list({
        role: "generator_owner",
        page,
        search: searchTerm.value || undefined,
        status: statusFilter.value || undefined,
      });
      const payload = data.data;

      owners.value = payload.data ?? payload;
      // ملاحظة إصلاح حرج: نفس نمط الخلل المتكرر بالجلسة — الباك اند بيرجّع
      // شكل Resource Collection مُصفَّح {data:[...], links, meta:{...}}،
      // وكانت قيم الترقيم تُقرأ من المستوى الخاطئ مباشرة بدل meta، فتطلع
      // undefined بصمت وتختفي أزرار التنقّل بين الصفحات نهائيًا.
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? owners.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value =
        err.response?.data?.message ?? t("owners_page.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  async function fetchStats() {
    isLoadingStats.value = true;
    try {
      const { data } = await userService.ownersStats();
      stats.value = data.data;
    } finally {
      isLoadingStats.value = false;
    }
  }

  const isSaving = ref(false);
  const saveError = ref(null);

  async function updateOwner(id, payload) {
    isSaving.value = true;
    saveError.value = null;
    try {
      const { data } = await userService.update(id, payload);
      const index = owners.value.findIndex((o) => o.id === id);
      if (index !== -1) owners.value[index] = data.data;
      return true;
    } catch (err) {
      saveError.value = err.response?.data ?? {
        message: t("owners_page.update_error"),
      };
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  const deletingId = ref(null);
  const deleteError = ref(null);

  async function deleteOwner(id) {
    deletingId.value = id;
    deleteError.value = null;
    try {
      await userService.destroy(id);
      owners.value = owners.value.filter((o) => o.id !== id);
      await fetchStats();
      return true;
    } catch (err) {
      // ملاحظة إصلاح: نفس نمط الخلل المتكرر — الباك اند بيرجّع رسالة عامة
      // بحقل message ("بيانات غير صالحة") للأخطاء 422، والرسالة المفيدة
      // الفعلية (مثلاً: عدد الاشتراكات الفعالة المانعة للحذف) موجودة بحقل
      // errors.user تحديدًا.
      deleteError.value = err.response?.data?.errors?.user?.[0]
        ?? err.response?.data?.message
        ?? t("owners_page.delete_error");
      return false;
    } finally {
      deletingId.value = null;
    }
  }

  async function unlockOwner(id) {
    await userService.unlock(id);
    const index = owners.value.findIndex((o) => o.id === id);
    if (index !== -1) owners.value[index].is_locked = false;
    await fetchStats();
  }

  const isCreating = ref(false);
  const createError = ref(null);

  async function createOwner(payload) {
    isCreating.value = true;
    createError.value = null;
    try {
      const { data } = await userService.createGeneratorOwner(payload);
      owners.value.unshift(data.data);
      await fetchStats();
      return true;
    } catch (err) {
      createError.value = err.response?.data ?? {
        message: t("owners_page.create_error"),
      };
      return false;
    } finally {
      isCreating.value = false;
    }
  }

  async function loadAll() {
    await Promise.all([fetchOwners(1), fetchStats()]);
  }

  return {
    owners,
    pagination,
    isLoading,
    error,
    searchTerm,
    statusFilter,
    stats,
    isLoadingStats,
    fetchOwners,
    fetchStats,
    isSaving,
    saveError,
    updateOwner,
    deletingId,
    deleteError,
    deleteOwner,
    unlockOwner,
    isCreating,
    createError,
    createOwner,
    loadAll,
  };
}