import { ref } from "vue";
import { useI18n } from "vue-i18n";
import userService from "@/services/userService";
import { normalizeApiError } from "@/utils/normalizeApiError";

export function useAdminSubscribers() {
  const { t } = useI18n();
  const subscribers = ref([]);
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  });
  const isLoading = ref(false);
  const error = ref(null);
  const searchTerm = ref("");

  const subscriptionFilter = ref("");

  const stats = ref(null);
  const isLoadingStats = ref(true);

  async function fetchStats() {
    isLoadingStats.value = true;
    try {
      const { data } = await userService.subscribersStats();
      stats.value = data.data;
    } finally {
      isLoadingStats.value = false;
    }
  }

  async function fetchSubscribers(page = 1) {
    isLoading.value = true;
    error.value = null;

    try {
      const { data } = await userService.list({
        role: "subscriber",
        page,
        search: searchTerm.value || undefined,
        subscription_status: subscriptionFilter.value || undefined,
      });
      const payload = data.data;

      subscribers.value = payload.data ?? payload;
      // ملاحظة إصلاح حرج: نفس نمط الخلل المتكرر بالجلسة — الباك اند بيرجّع
      // شكل Resource Collection مُصفَّح {data:[...], links, meta:{...}}،
      // وكانت قيم الترقيم تُقرأ من المستوى الخاطئ مباشرة بدل meta، فتطلع
      // undefined بصمت وتختفي أزرار التنقّل بين الصفحات نهائيًا.
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? subscribers.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("subscribers_page.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  function onSubscriptionFilterChange() {
    fetchSubscribers(1);
  }

  const isSaving = ref(false);
  const saveError = ref(null);

  async function updateSubscriber(id, payload) {
    isSaving.value = true;
    saveError.value = null;
    try {
      const { data } = await userService.update(id, payload);
      const index = subscribers.value.findIndex((s) => s.id === id);
      if (index !== -1) subscribers.value[index] = data.data;
      return true;
    } catch (err) {
      const normalized = normalizeApiError(err, t("subscribers_page.update_error"));
      saveError.value = { message: normalized.message, errors: normalized.fieldErrors };
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  const deletingId = ref(null);
  const deleteError = ref(null);

  async function deleteSubscriber(id) {
    deletingId.value = id;
    deleteError.value = null;
    try {
      await userService.destroy(id);
      subscribers.value = subscribers.value.filter((s) => s.id !== id);
      return true;
    } catch (err) {
      // ملاحظة إصلاح: الرسالة المحدَّدة (عدد الاشتراكات الفعالة المانعة
      // للحذف) موجودة بحقل errors.user، لا بحقل message العام.
      const normalized = normalizeApiError(err, t("subscribers_page.delete_error"));
      deleteError.value = normalized.fieldError("user") ?? normalized.message;
      return false;
    } finally {
      deletingId.value = null;
    }
  }

  async function unlockSubscriber(id) {
    await userService.unlock(id);
    const index = subscribers.value.findIndex((s) => s.id === id);
    if (index !== -1) subscribers.value[index].is_locked = false;
  }

  /* ---------------- إضافة مشترك جديد ----------------
   * كانت createSubscriber/isCreating/createError مفترَضة بالواجهة (SubscribersView.vue)
   * لكن غير مُنفَّذة هنا فعليًا — الزر كان يعرض رسالة خطأ صريحة بدل ما ينكسر.
   * الـ Endpoint نفسه (POST /users/subscribers) جاهز ومختبَر أصلًا من صفحة
   * المستخدمين، فقط أضفناه هنا لجعل الميزة تعمل من هذه الصفحة أيضًا.
   */
  const isCreating = ref(false);
  const createError = ref(null);

  async function createSubscriber(payload) {
    isCreating.value = true;
    createError.value = null;
    try {
      const { data } = await userService.createSubscriber(payload);
      subscribers.value.unshift(data.data);
      pagination.value.total += 1;
      return true;
    } catch (err) {
      createError.value = err.response?.data ?? {
        message: t("subscribers_page.create_error"),
      };
      return false;
    } finally {
      isCreating.value = false;
    }
  }

  return {
    subscribers,
    pagination,
    isLoading,
    error,
    searchTerm,
    subscriptionFilter,
    fetchSubscribers,
    onSubscriptionFilterChange,
    isSaving,
    saveError,
    updateSubscriber,
    deletingId,
    deleteError,
    deleteSubscriber,
    unlockSubscriber,
    stats,
    isLoadingStats,
    fetchStats,
    isCreating,
    createError,
    createSubscriber,
  };
}