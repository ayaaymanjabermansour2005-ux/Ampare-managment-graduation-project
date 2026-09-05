import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import subscriptionService from "@/services/subscriptionService";
import generatorService from "@/services/generatorService";
import userService from "@/services/userService";
import subscriberMeterService from "@/services/subscriberMeterService";

/**
 * Primary CRUD data-access for the admin Subscriptions view — list/filter/
 * search/pagination, the "add subscription" form's generator/subscriber
 * lookups and submit, and status transitions. Extracted out of
 * SubscriptionsView.vue (FE-01) so the view no longer calls
 * subscriptionService/generatorService/userService directly for its core
 * data; view-only concerns (derived stats, charts, the activity feed
 * widget, the "viewing subscription" detail panel) stay in the view, which
 * reacts to this composable's return values.
 */
export function useAdminSubscriptionsData() {
  const { t, locale } = useI18n();

  const subscriptions = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1, total: 0 });
  const isLoading = ref(false);
  const error = ref(null);
  const statusFilter = ref("all");
  const searchTerm = ref("");

  async function fetchSubscriptions(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const params = { page };
      if (statusFilter.value !== "all") params.status = statusFilter.value;
      if (searchTerm.value.trim()) params.search = searchTerm.value.trim();

      const { data } = await subscriptionService.list(params);
      const payload = data.data;
      subscriptions.value = payload.data ?? payload;
      const meta = payload.meta ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? subscriptions.value.length,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("subscriptions_page.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  function applyFilter(status) {
    statusFilter.value = status;
    fetchSubscriptions(1);
  }

  let searchTimeout = null;
  function handleSearchInput() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => fetchSubscriptions(1), 400);
  }

  /* ---------------- تصدير القائمة (Excel / PDF) ----------------
   * دفاعي: لو subscriptionService.exportExcelUrl/exportPdfUrl مش مضافين بعد
   * بالـ service الحقيقي، منرجع "#" بدل ما نخلي الصفحة كلها تطيح بخطأ Runtime.
   */
  function exportUrl(kind, params) {
    // ملاحظة إصلاح حرج: كان الكود القديم يفصل الدالة عن subscriptionService قبل
    // نداءها (`const fn = subscriptionService[...]; fn(params)`)، فتفقد ربط `this` —
    // exportExcelUrl الحقيقية بالـ service بتنادي `this.exportUrl(...)` داخليًا، فكانت
    // ترمي TypeError دايمًا وتلتقطها catch{} بصمت، فيرجع "#" حتى مع وجود endpoint حقيقي.
    const key = kind === "excel" ? "exportExcelUrl" : "exportPdfUrl";
    try {
      return typeof subscriptionService[key] === "function" ? subscriptionService[key](params) : "#";
    } catch {
      return "#";
    }
  }

  /* ---------------- نموذج إضافة اشتراك ---------------- */
  const generatorsForForm = ref([]);
  const isLoadingGeneratorsForForm = ref(false);

  async function fetchGeneratorsForForm() {
    isLoadingGeneratorsForForm.value = true;
    try {
      const { data } = await generatorService.list({ status: "active", per_page: 100 });
      generatorsForForm.value = data.data.data ?? data.data;
    } catch {
      generatorsForForm.value = [];
    } finally {
      isLoadingGeneratorsForForm.value = false;
    }
  }

  const subscriberSearchTerm = ref("");
  const subscriberSearchResults = ref([]);
  const isSearchingSubscribers = ref(false);
  let subscriberSearchTimeout = null;

  function handleSubscriberSearchInput() {
    clearTimeout(subscriberSearchTimeout);
    const term = subscriberSearchTerm.value.trim();
    if (term.length < 2) {
      subscriberSearchResults.value = [];
      return;
    }
    subscriberSearchTimeout = setTimeout(async () => {
      isSearchingSubscribers.value = true;
      try {
        const { data } = await userService.list({ role: "subscriber", search: term, per_page: 8 });
        subscriberSearchResults.value = data.data.data ?? data.data;
      } catch {
        subscriberSearchResults.value = [];
      } finally {
        isSearchingSubscribers.value = false;
      }
    }, 400);
  }

  /* ---------------- عدادات المشترك المختار (نموذج إضافة اشتراك) ----------------
   * عقد /owner/subscriptions الحقيقي (StoreSubscriptionRequest) بده subscriber_meter_id،
   * generator_id، schedule، billing_cycle و start_date — لا subscriber_id ولا price_per_kw
   * ولا starts_at/ends_at كما كان بالنموذج القديم (كان دايمًا يرجع 422). نفس النمط
   * المستخدَم فعليًا وبنجاح بصفحة owner/SubscribersView.vue (createByOwner)، بس هون
   * الأدمن بيحتاج يختار المشترك أولًا عبر owner/subscriber-lookup (مسموح له كمان).
   */
  const subscriberMeters = ref([]);
  const isLoadingSubscriberMeters = ref(false);

  async function fetchSubscriberMeters(userId) {
    isLoadingSubscriberMeters.value = true;
    try {
      const { data } = await userService.ownerSubscriberMeters(userId);
      subscriberMeters.value = data.data ?? [];
    } catch {
      subscriberMeters.value = [];
    } finally {
      isLoadingSubscriberMeters.value = false;
    }
    return subscriberMeters.value;
  }

  function resetSubscriberMeters() {
    subscriberMeters.value = [];
  }

  const isCreatingMeter = ref(false);
  const createMeterError = ref(null);

  /**
   * إنشاء عداد جديد نيابةً عن مشترك (لما يكون بدون أي عداد فعّال بعد).
   * subscriber-meters.store بيقبل user_id للأدمن (SubscriberMeterPolicy::create
   * + StoreSubscriberMeterRequest تم تعديلهم لدعم هذا المسار).
   */
  async function createMeterForSubscriber(userId, { meterNumber, propertyLabel }) {
    isCreatingMeter.value = true;
    createMeterError.value = null;
    try {
      const { data } = await subscriberMeterService.create({
        meter_number: meterNumber,
        property_label: propertyLabel || undefined,
        user_id: userId,
      });
      return data?.data ?? null;
    } catch (err) {
      createMeterError.value = normalizeApiError(err, t("subscriptions_page.meter_create_error")).message;
      return null;
    } finally {
      isCreatingMeter.value = false;
    }
  }

  const isSavingSubscription = ref(false);
  const saveSubscriptionError = ref(null);

  async function createSubscription(payload) {
    isSavingSubscription.value = true;
    saveSubscriptionError.value = null;
    try {
      const { data } = await subscriptionService.create(payload);
      await fetchSubscriptions(1);
      return data?.data ?? true;
    } catch (err) {
      saveSubscriptionError.value = normalizeApiError(err, t("subscriptions_page.create_error")).message;
      return false;
    } finally {
      isSavingSubscription.value = false;
    }
  }

  /* ---------------- تغيير الحالة (اعتماد/تعليق/إعادة تفعيل/إلغاء/رفض) ---------------- */
  const updatingStatusId = ref(null);
  const statusUpdateError = ref(null);

  async function updateSubscriptionStatus(sub, toStatus) {
    updatingStatusId.value = sub.id;
    statusUpdateError.value = null;
    try {
      const { data } = await subscriptionService.updateStatus(sub.id, toStatus);
      const updated = data?.data ?? { ...sub, status: toStatus };
      const index = subscriptions.value.findIndex((s) => s.id === sub.id);
      if (index !== -1) subscriptions.value[index] = updated;
      return updated;
    } catch (err) {
      statusUpdateError.value = normalizeApiError(err, t("subscriptions_page.status_update_error")).message;
      return null;
    } finally {
      updatingStatusId.value = null;
    }
  }

  /* ---------------- ملاحظات الاشتراك ---------------- */
  const updatingNotesId = ref(null);
  const notesUpdateError = ref(null);

  async function updateSubscriptionNotes(sub, notes) {
    updatingNotesId.value = sub.id;
    notesUpdateError.value = null;
    try {
      const { data } = await subscriptionService.updateNotes(sub.id, notes);
      const updated = data?.data ?? { ...sub, notes };
      const index = subscriptions.value.findIndex((s) => s.id === sub.id);
      if (index !== -1) subscriptions.value[index] = updated;
      return updated;
    } catch (err) {
      notesUpdateError.value = normalizeApiError(err, t("subscriptions_page.notes_update_error")).message;
      return null;
    } finally {
      updatingNotesId.value = null;
    }
  }

  function contractUrl(sub) {
    return subscriptionService.downloadContractPdfUrl(sub.id);
  }

  /* ---------------- نمو الاشتراكات الشهري ----------------
   * تحاول أولًا subscriptionService.monthlyStats() (لو موجودة بالباك اند)، ولو مش موجودة أو
   * فشلت، بترجع لحساب تقريبي من subscriptions.value (آخر 6 أشهر: عقود جديدة بحسب starts_at،
   * وعقود ملغاة بحسب ends_at لاشتراكات حالتها cancelled) - محدود بنطاق الصفحة الحالية فقط.
   */
  const MONTHS_AR = ["يناير", "فبراير", "مارس", "أبريل", "مايو", "يونيو", "يوليو", "أغسطس", "سبتمبر", "أكتوبر", "نوفمبر", "ديسمبر"];
  const MONTHS_EN = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
  const monthlyGrowth = ref({ labels: [], newCounts: [], cancelledCounts: [], isEstimate: true });

  function computeMonthlyGrowthFromPage() {
    const now = new Date();
    const months = [];
    for (let i = 5; i >= 0; i--) {
      const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
      months.push({ year: d.getFullYear(), month: d.getMonth(), newCount: 0, cancelledCount: 0 });
    }
    subscriptions.value.forEach((s) => {
      if (s.start_date) {
        const d = new Date(s.start_date);
        const bucket = months.find((m) => m.year === d.getFullYear() && m.month === d.getMonth());
        if (bucket) bucket.newCount++;
      }
      if (s.status === "cancelled" && s.end_date) {
        const d = new Date(s.end_date);
        const bucket = months.find((m) => m.year === d.getFullYear() && m.month === d.getMonth());
        if (bucket) bucket.cancelledCount++;
      }
    });
    monthlyGrowth.value = {
      labels: months.map((m) => (locale.value === "ar" ? MONTHS_AR[m.month] : MONTHS_EN[m.month])),
      newCounts: months.map((m) => m.newCount),
      cancelledCounts: months.map((m) => m.cancelledCount),
      isEstimate: true,
    };
  }

  async function fetchMonthlyGrowth() {
    try {
      if (typeof subscriptionService.monthlyStats === "function") {
        const { data } = await subscriptionService.monthlyStats();
        const payload = data?.data ?? data;
        if (payload?.labels?.length) {
          monthlyGrowth.value = { ...payload, isEstimate: false };
          return;
        }
      }
    } catch {
      /* تجاهل بصمت وارجع للحساب التقريبي المحلي */
    }
    computeMonthlyGrowthFromPage();
  }

  /* ---------------- آخر نشاطات الاشتراكات ----------------
   * تحاول أولًا subscriptionService.activityLog() (سجل حقيقي من الباك اند)، ولو مش موجودة، بيبقى
   * معتمد على سجل محلي بهاي الجلسة فقط (بينضاف له عنصر جديد كل ما الأدمن يعتمد/يرفض/يعلّق/ينقل/يضيف
   * اشتراك من نفس الصفحة) - وبيتصفّر لو الصفحة انعملها Refresh.
   */
  const activityLog = ref([]);

  function pushActivity(entry) {
    activityLog.value = [{ at: new Date().toISOString(), ...entry }, ...activityLog.value].slice(0, 20);
  }

  async function fetchActivityLog() {
    try {
      if (typeof subscriptionService.activityLog === "function") {
        const { data } = await subscriptionService.activityLog({ per_page: 8 });
        const list = data?.data?.data ?? data?.data ?? [];
        if (list.length) activityLog.value = list;
      }
    } catch {
      /* تجاهل بصمت - بنعتمد على السجل المحلي بهذه الجلسة */
    }
  }

  return {
    subscriptions,
    pagination,
    isLoading,
    error,
    statusFilter,
    searchTerm,
    fetchSubscriptions,
    applyFilter,
    handleSearchInput,
    exportUrl,

    generatorsForForm,
    isLoadingGeneratorsForForm,
    fetchGeneratorsForForm,

    subscriberSearchTerm,
    subscriberSearchResults,
    isSearchingSubscribers,
    handleSubscriberSearchInput,

    subscriberMeters,
    isLoadingSubscriberMeters,
    fetchSubscriberMeters,
    resetSubscriberMeters,
    isCreatingMeter,
    createMeterError,
    createMeterForSubscriber,

    isSavingSubscription,
    saveSubscriptionError,
    createSubscription,

    updatingStatusId,
    statusUpdateError,
    updateSubscriptionStatus,

    updatingNotesId,
    notesUpdateError,
    updateSubscriptionNotes,

    contractUrl,

    monthlyGrowth,
    fetchMonthlyGrowth,
    computeMonthlyGrowthFromPage,

    activityLog,
    pushActivity,
    fetchActivityLog,
  };
}
