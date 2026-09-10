<script setup>
import { normalizeApiError } from "@/utils/normalizeApiError";
import { reactive, ref, computed, onMounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { Doughnut, Pie } from "vue-chartjs";
import { useAdminSubscribers } from "@/composables/useAdminSubscribers";
import { useOwnerSubscriptionMeterTransfers } from "@/composables/useOwnerSubscriptionMeterTransfers";
import { useAdminSubscriptionsData } from "@/composables/useAdminSubscriptionsData";
import { useSubscriptionsAnalytics } from "@/composables/useSubscriptionsAnalytics";
import { useAdminPasswordTools } from "@/composables/useAdminPasswordTools";
import { useSubscriberExport } from "@/composables/useSubscriberExport";
import { useBulkPaymentReminder } from "@/composables/useBulkPaymentReminder";
import { useConfirm } from "@/composables/useConfirm";
import { usePermissions } from "@/composables/usePermissions";
import { vReveal } from "@/directives/reveal";
import { vCountUp } from "@/directives/countUp";
import activityLogService from "@/services/activityLogService";
import { useToastStore } from "@/stores/toast";
import TransferSubscriptionModal from "@/components/admin/TransferSubscriptionModal.vue";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { ArrowLeft, ArrowRight, ArrowRightLeft, Bell, CalendarDays, CalendarPlus, CalendarX, Check, ChevronDown, ChevronLeft, ChevronRight, Circle, CircleAlert, CircleCheck, CircleMinus, Clock, Copy, Eye, EyeOff, FileDown, FilePenLine, FilePlus, FileSpreadsheet, FileText, Funnel, GripVertical, HeartPulse, IdCard, Key, LoaderCircle, Lock, LockOpen, Mail, Pencil, Phone, PlugZap, Plus, Printer, Search, Shuffle, StickyNote, Table2, ToggleLeft, ToggleRight, Trash2, TriangleAlert, User, UserPlus, UserRound, Users, X, Zap, ZoomOut } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";
import StatCard from "@/components/dashboard/StatCard.vue";
import LightningCanvas from "@/components/ui/LightningCanvas.vue";


const { t, locale } = useI18n();
const { can } = usePermissions();
const route = useRoute();
const router = useRouter();
const { confirm } = useConfirm();
const toast = useToastStore();

/* ==========================================================================
 * ====================  بيانات تبويب: المشتركون (subscribers)  ============
 * ========================================================================== */
const {
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
  // ملاحظة: الأسماء التالية مفترَضة ولازم تكون موجودة بالـ composable (useAdminSubscribers)
  // عشان الميزات الجديدة تشتغل فعليًا من السيرفر. لو مش موجودة حاليًا، الأزرار
  // المرتبطة فيها رح تظهر تنبيه بالكونسول بدل ما تكسر الصفحة.
  createSubscriber,
  isCreating,
  createError,
} = useAdminSubscribers();

/* ---------------- FRONT-004a (slice 2): أدوات كلمة السر (رابط إعادة تعيين /
   تعيين مباشر) — منقولة لـ useAdminPasswordTools composable (God-component
   breakdown). ---------------- */
const {
  isSendingResetLink,
  handleSendResetLink,
  setPasswordModal,
  setPasswordForm,
  showSetPassword,
  isSettingPassword,
  setPasswordError,
  generateSetPassword,
  openSetPassword,
  copySetPassword,
  handleSetPassword,
} = useAdminPasswordTools();

/* ---------------- FRONT-004a (slice 4): تذكير جماعي بالدفع —
   منقولة لـ useBulkPaymentReminder composable (God-component breakdown). ---------------- */
const { isSendingBulkReminder, handleBulkReminder } = useBulkPaymentReminder({
  subscribers,
  subscriberStatus,
});

/* ==========================================================================
 * ==================  التابات (المشتركون / الاشتراكات / نقل العداد)  ======
 * ========================================================================== */
const activeTab = ref("subscribers");

/* ---------------- طلبات نقل عداد الاشتراك (تبويب "طلبات نقل العداد") ----------------
 * FIX: subscription-meter-transfers/{id}/approve|reject موثَّقة صراحة بالباك
 * اند بأنها تُراجَع من مالك المولد "أو الأدمن"، والـ composable
 * useOwnerSubscriptionMeterTransfers عام أصلاً (السيرفر يحدد النطاق حسب
 * الدور) — لكنه لم يكن مستخدَمًا سوى بواجهة المالك. نفس المنطق حرفيًا هون.
 * (watch(activeTab, ...) بالأسفل يتطلب أن يكون activeTab مُعرَّفًا هون قبله
 * مباشرة — استدعاء watch() يُنفَّذ فورًا، بعكس computed() اللي بيتأجل.)
 */
const {
  requests: transferRequests,
  pagination: transferPagination,
  isLoading: isLoadingTransfers,
  error: transferLoadError,
  statusFilter: transferStatusFilter,
  isApproving: isApprovingTransfer,
  isRejecting: isRejectingTransfer,
  rejectError: transferRejectError,
  fetchRequests: fetchTransferRequests,
  onFilterChange: onTransferFilterChange,
  approveRequest: approveTransferRequest,
  rejectRequest: rejectTransferRequestAction,
} = useOwnerSubscriptionMeterTransfers();

let transfersLoadedOnce = false;
watch(activeTab, (tab) => {
  if (tab === "transfers" && !transfersLoadedOnce) {
    transfersLoadedOnce = true;
    fetchTransferRequests(1);
  }
});

const TRANSFER_STATUS_META = {
  pending: { chip: "chip-warning", key: "owner_subscribers.transfer_status_pending" },
  approved: { chip: "chip-success", key: "owner_subscribers.transfer_status_approved" },
  rejected: { chip: "chip-danger", key: "owner_subscribers.transfer_status_rejected" },
};
function transferStatusLabel(s) {
  const m = TRANSFER_STATUS_META[s];
  return m ? t(m.key) : s;
}
const TRANSFER_STATUS_PILLS = computed(() => [
  { value: "", label: t("common.all") },
  ...Object.entries(TRANSFER_STATUS_META).map(([value, m]) => ({ value, label: t(m.key) })),
]);

async function handleApproveTransfer(reqItem) {
  const ok = await approveTransferRequest(reqItem.id);
  if (ok) {
    toast.show({ type: "success", title: t("owner_subscribers.transfer_success_title") });
  } else if (transferRejectError.value) {
    toast.show({ type: "danger", title: transferRejectError.value });
  }
}

const rejectTransferTarget = ref(null);
const transferRejectReason = ref("");
function openRejectTransfer(reqItem) {
  if (reqItem.status !== "pending") return;
  rejectTransferTarget.value = reqItem;
  transferRejectReason.value = "";
}
function closeRejectTransfer() {
  if (isRejectingTransfer.value) return;
  rejectTransferTarget.value = null;
}
async function handleRejectTransfer() {
  if (!transferRejectReason.value.trim()) return;
  const ok = await rejectTransferRequestAction(rejectTransferTarget.value.id, transferRejectReason.value.trim());
  if (ok) {
    rejectTransferTarget.value = null;
    transferRejectReason.value = "";
  } else if (transferRejectError.value) {
    toast.show({ type: "danger", title: transferRejectError.value });
  }
}

const TABS = computed(() => [
  { key: "subscribers", label: t("subscribers_page.breadcrumb"), icon: "fa-users" },
  { key: "subscriptions", label: t("subscriptions_page.title"), icon: "fa-file-contract", badge: countOnPage("pending") },
  {
    key: "transfers",
    label: t("owner_subscribers.tab_transfers"),
    icon: "fa-right-left",
    badge: transferStatusFilter.value === "pending" ? transferPagination.value.total : 0,
  },
]);

/* عنوان الهيدر الموحَّد (breadcrumb/eyebrow/h1) حسب التبويب النشط — عوّض عن
 * ثلاث نسخ مكررة من نفس الـ ternary (كانت أصلاً ثنائية ولا تدعم تبويب ثالث). */
const activeTabTitle = computed(() => {
  if (activeTab.value === "subscribers") return t("subscribers_page.breadcrumb");
  if (activeTab.value === "transfers") return t("owner_subscribers.tab_transfers");
  return t("subscriptions_page.title");
});

/* ==========================================================================
 * ====================  بيانات تبويب: الاشتراكات (subscriptions)  =========
 * ========================================================================== */
/* ملاحظة تبعية: هاد الملف بيستخدم Chart.js لرسم بطاقات "نمو الاشتراكات"، "توزيع الحالات"،
 * "حسب نوع الخطة" و"الاشتراكات المنتهية خلال الأسابيع القادمة" (نفس مكتبة نسخة الـ HTML الأصلية).
 * لو المكتبة مش مضافة للمشروع بعد: npm install chart.js */


/* ---------------- FE-01: بيانات CRUD الأساسية (قائمة/فلترة/بحث/صفحات، نموذج
   الإضافة، وتغيير الحالة) منقولة لـ useAdminSubscriptionsData composable —
   بدل نداء subscriptionService/generatorService/userService مباشرة من الـ
   view. الإحصائيات/الرسوم/سجل النشاطات (view-only) بتضل هون. ---------------- */
const {
  subscriptions,
  pagination: subscriptionPagination,
  isLoading: isLoadingSubscriptions,
  error: subscriptionsError,
  statusFilter,
  searchTerm: subscriptionSearchTerm,
  fetchSubscriptions,
  applyFilter,
  handleSearchInput: handleSubscriptionSearchInput,
  exportUrl,
  generatorsForForm,
  isLoadingGeneratorsForForm,
  fetchGeneratorsForForm,
  subscriberSearchTerm,
  subscriberSearchResults,
  isSearchingSubscribers,
  handleSubscriberSearchInput: searchSubscribers,
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
} = useAdminSubscriptionsData();

/* ---------------- FRONT-004a (slice 3): تصدير القائمة (CSV / Excel) —
   منقولة لـ useSubscriberExport composable (God-component breakdown). ---------------- */
const { exportExcelUrl, subscribersExportExcelUrl } = useSubscriberExport({
  statusFilter,
  subscriptionSearchTerm,
  exportUrl,
  subscriberSearchTerm: searchTerm,
  subscriberStatusFilter: subscriptionFilter,
});

const generatorFormOptions = computed(() =>
  generatorsForForm.value.map((g) => ({ value: g.id, label: `${g.name} — ${g.location?.city ?? "-"}` })),
);

function subscriptionAvatarStyle(idx) {
  const c = AVATAR_COLORS[idx % AVATAR_COLORS.length];
  return { background: `linear-gradient(135deg, ${c[0]}, ${c[1]})` };
}

/* ---------------- FRONT-004a (slice 1): تحليلات لوحة الاشتراكات + رسوم Chart.js —
   منقولة لـ useSubscriptionsAnalytics composable (God-component breakdown). ---------------- */
const {
  STATUS_META,
  subscriptionStatusLabel,
  statusChip,
  statusChipStyle,
  planMeta,
  planLabel,
  EXPIRING_SOON_DAYS,
  subscriptionDaysUntil,
  derivedStatusKey,
  derivedStatusLabel,
  derivedStatusCounts,
  statusDistribution,
  derivedFilter,
  applyDerivedFilter,
  planCounts,
  expiringBuckets,
  expiringBucketLabels,
  countOnPage,
  subsAlerts,
  subscriptionTimeAgo,
  topExpiring,
  expiringLabel,
  expiringColor,
  SUBSCRIPTION_STATUS_PILLS,
  SUBSCRIPTION_KPI_CARDS,
  systemStatusInfo,
  growthCanvas,
  statusCanvas,
  planCanvas,
  expiringCanvas,
  renderAllCharts,
} = useSubscriptionsAnalytics({ subscriptions, subscriptionPagination, monthlyGrowth, computeMonthlyGrowthFromPage });

/* ---------------- فرز عبر رؤوس الأعمدة (أيقونة تصاعدي/تنازلي - نفس أسلوب صفحتَي المولدات والمشتركين) ---------------- */
const subscriptionSortKey = ref("");
const subscriptionSortDir = ref("desc");
function subscriptionSortIconClass(key) {
  if (subscriptionSortKey.value !== key) return "opacity-40";
  return subscriptionSortDir.value === "desc" ? "opacity-100 text-[#8A6D1F] rotate-180" : "opacity-100 text-[#8A6D1F]";
}
function subscriptionToggleSort(key) {
  if (subscriptionSortKey.value === key) {
    subscriptionSortDir.value = subscriptionSortDir.value === "desc" ? "asc" : "desc";
  } else {
    subscriptionSortKey.value = key;
    subscriptionSortDir.value = "desc";
  }
}
const derivedFilteredSubscriptions = computed(() => {
  if (!derivedFilter.value) return subscriptions.value;
  return subscriptions.value.filter((s) => derivedStatusKey(s) === derivedFilter.value);
});
const sortedSubscriptions = computed(() => {
  if (!subscriptionSortKey.value) return derivedFilteredSubscriptions.value;
  const list = [...derivedFilteredSubscriptions.value];
  list.sort((a, b) => {
    let va, vb;
    if (subscriptionSortKey.value === "subscriber") {
      va = (a.subscriber?.name ?? "").toLowerCase();
      vb = (b.subscriber?.name ?? "").toLowerCase();
    } else if (subscriptionSortKey.value === "generator") {
      va = (a.generator?.name ?? "").toLowerCase();
      vb = (b.generator?.name ?? "").toLowerCase();
    } else if (subscriptionSortKey.value === "starts_at") {
      va = a.start_date ?? "";
      vb = b.start_date ?? "";
    } else if (subscriptionSortKey.value === "ends_at") {
      va = a.end_date ?? "";
      vb = b.end_date ?? "";
    } else if (subscriptionSortKey.value === "status") {
      va = a.status ?? "";
      vb = b.status ?? "";
    }
    if (va < vb) return subscriptionSortDir.value === "asc" ? -1 : 1;
    if (va > vb) return subscriptionSortDir.value === "asc" ? 1 : -1;
    return 0;
  });
  return list;
});

/* ---------------- Pagination بأزرار محدودة (بنفس نمط باقي الصفحات) ---------------- */
const subscriptionPaginationRange = computed(() => {
  const total = subscriptionPagination.value.last_page;
  const current = subscriptionPagination.value.current_page;
  const delta = 1;
  const range = [];
  const withDots = [];
  let last = null;

  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
      range.push(i);
    }
  }
  for (const i of range) {
    if (last !== null) {
      if (i - last === 2) withDots.push(last + 1);
      else if (i - last > 2) withDots.push("...");
    }
    withDots.push(i);
    last = i;
  }
  return withDots;
});

/* ==================================================================
 * إضافة اشتراك جديد يدويًا من الأدمن
 *
 * FIX (تدقيق شامل للوحة الأدمن): النموذج القديم كان يبعت subscriber_id/
 * price_per_kw/starts_at/ends_at/status — ولا حقل منهم موجود فعليًا بقواعد
 * StoreSubscriptionRequest (اللي بده subscriber_meter_id، generator_id،
 * schedule، billing_cycle و start_date كحقول مطلوبة) — يعني كان يرجع 422
 * دايمًا. أُعيد بناؤه بنفس النمط المستخدَم فعليًا وبنجاح بصفحة owner/
 * SubscribersView.vue (createByOwner)، مع فرق واحد: الأدمن مش عنده مولد
 * خاص فيه فلازم يختار مشترك موجود أولًا (عبر owner/subscriber-lookup —
 * مسموح له كمان الآن، UserPolicy::lookupForOwner) ثم يختار أحد عداداته أو
 * يسجّل له عدادًا جديدًا (subscriber-meters.store بيقبل الآن user_id
 * للأدمن — SubscriberMeterPolicy::create).
 *
 * "إنشاء مشترك جديد بالكامل" مش جزء من هذا النموذج — نفس القرار المعتمَد
 * بصفحة المالك (تعليق createByOwner أعلاه): المشترك الجديد يُنشأ أولًا من
 * تبويب "المشتركون" (زر "إضافة مشترك" — openAdd/createSubscriber بالأعلى)
 * ثم يُختار هون بالبحث، بدل تكرار منطق إنشاء الحساب (بما فيه كلمة السر)
 * بنافذتين مختلفتين.
 * ================================================================== */
const isAddFormOpen = ref(false);
const selectedSubscriber = ref(null);

const SUBSCRIPTION_SCHEDULE_OPTIONS = ["day", "night", "24h", "custom"];
const SUBSCRIPTION_BILLING_CYCLE_OPTIONS = ["daily", "weekly", "monthly"];

const meterMode = ref("pick"); // 'pick' | 'new'
const newMeterForm = reactive({ meter_number: "", property_label: "" });

function emptyAddForm() {
  return {
    generator_id: "",
    subscriber_meter_id: "",
    requested_capacity_kw: "",
    schedule: "",
    billing_cycle: "",
    service_start_time: "",
    service_end_time: "",
    contract_type: "",
    start_date: "",
    end_date: "",
  };
}
const subscriptionAddForm = ref(emptyAddForm());

const scheduleDropdownOptions = computed(() =>
  SUBSCRIPTION_SCHEDULE_OPTIONS.map((opt) => ({ value: opt, label: t(`owner_subscribers.schedule.${opt}`) })),
);
const billingCycleDropdownOptions = computed(() =>
  SUBSCRIPTION_BILLING_CYCLE_OPTIONS.map((opt) => ({ value: opt, label: t(`owner_subscribers.cycle.${opt}`) })),
);
const activeSubscriberMeterOptions = computed(() =>
  subscriberMeters.value
    .filter((m) => m.status === "active")
    .map((m) => ({
      value: m.id,
      label: m.property_label ? `${m.meter_number} — ${m.property_label}` : m.meter_number,
    })),
);

async function openAddModal() {
  subscriptionAddForm.value = emptyAddForm();
  subscriberSearchTerm.value = "";
  subscriberSearchResults.value = [];
  selectedSubscriber.value = null;
  resetSubscriberMeters();
  meterMode.value = "pick";
  newMeterForm.meter_number = "";
  newMeterForm.property_label = "";
  saveSubscriptionError.value = null;
  createMeterError.value = null;
  isAddFormOpen.value = true;
  if (generatorsForForm.value.length === 0) await fetchGeneratorsForForm();
}

function closeAddModal() {
  if (isSavingSubscription.value || isCreatingMeter.value) return;
  isAddFormOpen.value = false;
  saveSubscriptionError.value = null;
}

function handleSubscriberSearchInput() {
  // بخلاف الـ composable، الـ view هو اللي بيمسك selectedSubscriber (حالة
  // نموذج محلية) — لازم يُلغى الاختيار السابق كل ما المستخدم يكتب بحثًا جديدًا.
  selectedSubscriber.value = null;
  resetSubscriberMeters();
  searchSubscribers();
}

async function pickSubscriber(subscriber) {
  selectedSubscriber.value = subscriber;
  subscriberSearchResults.value = [];
  subscriberSearchTerm.value = "";
  subscriptionAddForm.value.subscriber_meter_id = "";
  newMeterForm.meter_number = "";
  newMeterForm.property_label = "";

  const meters = await fetchSubscriberMeters(subscriber.id);
  meterMode.value = meters.some((m) => m.status === "active") ? "pick" : "new";
}

function clearPickedSubscriber() {
  selectedSubscriber.value = null;
  resetSubscriberMeters();
  subscriptionAddForm.value.subscriber_meter_id = "";
}

const addFormValidationError = computed(() => {
  if (!selectedSubscriber.value) return t("subscriptions_page.validation_select_subscriber");
  if (!subscriptionAddForm.value.generator_id) return t("subscriptions_page.validation_select_generator");

  if (meterMode.value === "pick") {
    if (!subscriptionAddForm.value.subscriber_meter_id) return t("subscriptions_page.validation_select_meter");
  } else if (!newMeterForm.meter_number.trim()) {
    return t("subscriptions_page.validation_meter_number_required");
  }

  if (!subscriptionAddForm.value.schedule) return t("subscriptions_page.validation_select_schedule");
  if (
    subscriptionAddForm.value.schedule === "custom" &&
    (!subscriptionAddForm.value.service_start_time || !subscriptionAddForm.value.service_end_time)
  ) {
    return t("subscriptions_page.validation_custom_schedule_times_required");
  }
  if (!subscriptionAddForm.value.billing_cycle) return t("subscriptions_page.validation_select_billing_cycle");
  if (!subscriptionAddForm.value.start_date) return t("subscriptions_page.validation_start_date_required");
  if (
    subscriptionAddForm.value.end_date &&
    subscriptionAddForm.value.end_date < subscriptionAddForm.value.start_date
  ) {
    return t("subscriptions_page.validation_end_after_start");
  }
  return null;
});

async function handleAddSubscription() {
  const validationError = addFormValidationError.value;
  if (validationError) {
    saveSubscriptionError.value = validationError;
    return;
  }
  saveSubscriptionError.value = null;

  let meterId = subscriptionAddForm.value.subscriber_meter_id;
  if (meterMode.value === "new") {
    const meter = await createMeterForSubscriber(selectedSubscriber.value.id, {
      meterNumber: newMeterForm.meter_number.trim(),
      propertyLabel: newMeterForm.property_label.trim(),
    });
    if (!meter) {
      saveSubscriptionError.value = createMeterError.value;
      return;
    }
    meterId = meter.id;
  }

  const form = subscriptionAddForm.value;
  const payload = {
    generator_id: Number(form.generator_id),
    subscriber_meter_id: meterId,
    requested_capacity_kw: form.requested_capacity_kw ? Number(form.requested_capacity_kw) : undefined,
    schedule: form.schedule,
    billing_cycle: form.billing_cycle,
    service_start_time: form.schedule === "custom" ? form.service_start_time : undefined,
    service_end_time: form.schedule === "custom" ? form.service_end_time : undefined,
    contract_type: form.contract_type.trim() || undefined,
    start_date: form.start_date,
    end_date: form.end_date || undefined,
  };

  const subscriberName = selectedSubscriber.value?.name;
  const created = await createSubscription(payload);
  if (created) {
    pushActivity({
      icon: "fa-file-circle-plus",
      color: "#52733D",
      key: subscriberName ? "subscriptions_page.activity_contract_signed_named" : "subscriptions_page.activity_contract_signed",
      params: subscriberName ? { name: subscriberName } : undefined,
    });
    isAddFormOpen.value = false;
  }
}

/* ---------------- تغيير الحالة (اعتماد/تعليق/إعادة تفعيل/إلغاء/رفض) ---------------- */

/* الانتقالات المسموحة لكل حالة، مع لون وأيقونة الزر */
const STATUS_ACTIONS = {
  pending: [
    { to: "active", labelKey: "subscriptions_page.action_approve", icon: "fa-check", color: "#28A745", danger: false },
    { to: "rejected", labelKey: "subscriptions_page.action_reject", icon: "fa-xmark", color: "#D9534F", danger: true },
  ],
  active: [
    { to: "suspended", labelKey: "subscriptions_page.action_suspend", icon: "fa-circle-pause", color: "#17A2B8", danger: false },
    { to: "cancelled", labelKey: "subscriptions_page.action_cancel", icon: "fa-ban", color: "#D9534F", danger: true },
  ],
  suspended: [
    { to: "active", labelKey: "subscriptions_page.action_reactivate", icon: "fa-play", color: "#28A745", danger: false },
    { to: "cancelled", labelKey: "subscriptions_page.action_cancel", icon: "fa-ban", color: "#D9534F", danger: true },
  ],
  cancelled: [],
  rejected: [],
};
function actionsFor(sub) {
  return STATUS_ACTIONS[sub.status] ?? [];
}

async function handleStatusChange(sub, action) {
  const actionLabel = t(action.labelKey);
  if (action.danger) {
    const confirmed = await confirm({
      title: t("subscriptions_page.confirm_status_change_title", { action: actionLabel, name: sub.subscriber?.name }),
      message: t("subscriptions_page.confirm_status_change_message"),
      confirmLabel: actionLabel,
      variant: "danger",
    });
    if (!confirmed) return;
  }

  const updated = await updateSubscriptionStatus(sub, action.to);
  if (!updated) return;

  if (viewingSubscription.value?.id === sub.id) viewingSubscription.value = updated;
  pushActivity({
    icon: action.icon,
    color: action.color,
    key: "subscriptions_page.activity_status_changed",
    params: { actionKey: action.labelKey, name: sub.subscriber?.name ?? "-" },
  });
}

/* نص عنصر سجل النشاط: يترجم مفتاح i18n مع بارامتراته (والـ action الفرعي لو موجود)،
   أو يرجع للنص الخام لو العنصر جاي من الباك اند مباشرة */
function activityText(entry) {
  if (!entry.key) return entry.text ?? "";
  const params = { ...(entry.params ?? {}) };
  if (params.actionKey) {
    params.action = t(params.actionKey);
    delete params.actionKey;
  }
  return t(entry.key, params);
}

/* ---------------- نافذة عرض التفاصيل ---------------- */
const viewingSubscription = ref(null);
function openSubscriptionView(sub) {
  viewingSubscription.value = sub;
  isEditingNotes.value = false;
}

/* ---------------- ملاحظات الاشتراك (PATCH /subscriptions/{id}/notes) ---------------- */
const isEditingNotes = ref(false);
const notesDraft = ref("");

function startEditNotes() {
  notesDraft.value = viewingSubscription.value?.notes ?? "";
  notesUpdateError.value = null;
  isEditingNotes.value = true;
}

async function saveNotes() {
  if (!viewingSubscription.value) return;
  const updated = await updateSubscriptionNotes(viewingSubscription.value, notesDraft.value);
  if (!updated) return;
  viewingSubscription.value = updated;
  isEditingNotes.value = false;
}

/* ---------------- نقل الاشتراك لمولد آخر ---------------- */
const transferModal = ref({ open: false, subscription: null });
function openTransfer(subscription) {
  transferModal.value = { open: true, subscription };
}
function handleTransferred(updated) {
  const index = subscriptions.value.findIndex((s) => s.id === updated.id);
  if (index !== -1) subscriptions.value[index] = updated;
  if (viewingSubscription.value?.id === updated.id) viewingSubscription.value = updated;
  pushActivity({
    icon: "fa-right-left",
    color: "#8A6D1F",
    key: "subscriptions_page.activity_transferred",
    params: { name: updated.subscriber?.name ?? "-" },
  });
}


/* ---------------- تحميل بيانات تبويب الاشتراكات عند التركيب ----------------
 * رسم الرسوم البيانية الأربعة (نمو/حالة/خطة/انتهاء) وإعادة حسابها عند تغيّر
 * subscriptions/locale الآن من مسؤولية useSubscriptionsAnalytics composable
 * (FRONT-004a slice 1) — الـ view هون بس بيطلب تحميل البيانات ثم يطلب من
 * الـ composable يرسم أول مرة. */
onMounted(async () => {
  await fetchSubscriptions();
  await fetchMonthlyGrowth();
  await fetchActivityLog();
  renderAllCharts();
});

/* FIX (FRONT-004-chart-sizing): تبويب "الاشتراكات" بمحتواه (وبالتالي الـ 4
 * canvas الخاصة بالرسوم) معروض بـ v-if، يعني مش موجود بالـ DOM أصلًا لما
 * onMounted فوق ينفّذ (activeTab تبدأ "subscribers" دائمًا) — فكل محاولات
 * الرسم بتفشل بصمت (canvas.value لسا null) ولا في شي بيعيد الرسم لما
 * المستخدم يبدّل فعليًا لتبويب الاشتراكات. هاد الـ watch يضمن الرسم الفعلي
 * أول مرة توصل فيها الـ canvas عناصر حقيقية للـ DOM. */
watch(activeTab, (tab) => {
  if (tab === "subscriptions") renderAllCharts();
});

async function handleUnlock(subscriber) {
  // FIX: (item 13) نفس النمط المطبَّق سابقًا بـ UsersView.vue (FIX-015).
  try {
    await unlockSubscriber(subscriber.id);
    toast.show({
      type: "success",
      title: t("users_page.unlocked_toast_title"),
      message: t("users_page.unlocked_message", { name: subscriber.name }),
    });
  } catch (err) {
    toast.show({
      type: "danger",
      title: t("users_page.unlocked_toast_title"),
      message: normalizeApiError(err, t("common.unexpected_error_retry")).message,
    });
  }
}

/* ---------------- بحث ---------------- */
let searchTimeout = null;
function handleSearchInput() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => fetchSubscribers(1), 400);
}

/* ---------------- فلتر حسب حالة الاشتراك (من السيرفر عبر subscription_status) ---------------- */
const STATUS_PILLS = computed(() => [
  { value: "", label: t("common.all") },
  { value: "active", label: t("subscribers_page.filter_has_active_sub") },
  { value: "none", label: t("subscribers_page.no_subscription") },
  { value: "locked", label: t("subscribers_page.filter_locked") },
]);
function onFilterChange() {
  onSubscriptionFilterChange();
}

/* ---------------- توجيه التنبيهات لمكانها ذو الصلة (كانت عرضًا بصريًا بس) ---------------- */
function handleAlertClick(alert) {
  if (alert.type === "overdue_balance") {
    subscriptionFilter.value = "overdue";
    onFilterChange();
    document.getElementById("subscribers-table-section")?.scrollIntoView({ behavior: "smooth", block: "start" });
    return;
  }
  // pending_review و suspended_account حالتا حساب عامة (مو حالة اشتراك)،
  // وفلترة الحالة العامة موجودة أصلًا وشغّالة بصفحة "المستخدمين" — بدل ما
  // نبني فلتر مكرَّر هون، نوجّه لهناك مباشرة مع تفعيل الفلتر تلقائيًا.
  if (alert.type === "pending_review") {
    router.push({ name: "admin.users", query: { role: "subscriber", status: "pending_review" } });
    return;
  }
  if (alert.type === "suspended_account") {
    router.push({ name: "admin.users", query: { role: "subscriber", status: "suspended" } });
  }
}

/* ---------------- فلتر حسب الباقة (منزلي / تجاري / غيرها) ----------------
 * فلترة محلية على البيانات المحمّلة حاليًا (تشتغل فورًا بدون تعديل بالـ backend).
 * إذا صار عندك لاحقًا فلترة من السيرفر، فعّل onPackageFilterChange تستدعي
 * fetchSubscribers(1) بعد ما تمرري packageFilter للـ composable.
 */
/* ---------------- فلتر حسب فئة المستفيد (عادي / مستفيد من تبرّع) ----------------
 * ملاحظة إصلاح: كان الفلتر مبني على "الباقة" (تجاري/منزلي) — مفهوم وهمي
 * غير موجود بالباك اند. استبدلناه بفلتر على فئة المستفيد الحقيقية
 * (beneficiary_type) الموجودة فعليًا بالنظام.
 */
const packageFilter = ref("");
const PACKAGE_PILLS = computed(() => [
  { value: "", label: t("subscribers_page.all_categories") },
  { value: "normal", label: t("subscribers_page.category_normal") },
  { value: "special", label: t("subscribers_page.category_donation") },
]);
function subscriberPlanType(subscriber) {
  return subscriber.beneficiary_type ?? null;
}
function onPackageFilterChange() {
  // فلترة محلية على الصفحة الحالية — beneficiary_type محمَّل مسبقًا بكل استجابة.
}

/* ---------------- فرز عبر رؤوس الأعمدة (أيقونة تصاعدي/تنازلي - نفس أسلوب صفحة المولدات) ---------------- */
const sortKey = ref("");
const sortDir = ref("desc");
/** يبني كلاس CSS (شفافية + دوران) لأيقونة سهم الفرز الثابتة (ChevronDown)؛ يُستخدَم مع :class على <ChevronDown> وليس :name على <AppIcon>. */
function sortIconClass(key) {
  if (sortKey.value !== key) return "opacity-40";
  return sortDir.value === "desc" ? "opacity-100 text-[#8A6D1F] rotate-180" : "opacity-100 text-[#8A6D1F]";
}
function toggleSort(key) {
  if (sortKey.value === key) {
    sortDir.value = sortDir.value === "desc" ? "asc" : "desc";
  } else {
    sortKey.value = key;
    sortDir.value = "desc";
  }
}
const sortedSubscribers = computed(() => {
  if (!sortKey.value) return subscribers.value;
  const list = [...subscribers.value];
  list.sort((a, b) => {
    let va, vb;
    if (sortKey.value === "name") {
      va = (a.name ?? "").toLowerCase();
      vb = (b.name ?? "").toLowerCase();
    } else if (sortKey.value === "created_at") {
      va = new Date(a.created_at ?? 0).getTime();
      vb = new Date(b.created_at ?? 0).getTime();
    } else if (sortKey.value === "balance") {
      va = a.outstanding_balance_ils ?? 0;
      vb = b.outstanding_balance_ils ?? 0;
    }
    if (va < vb) return sortDir.value === "asc" ? -1 : 1;
    if (va > vb) return sortDir.value === "asc" ? 1 : -1;
    return 0;
  });
  return list;
});

/* فلترة الباقة تُطبَّق فوق نتيجة الفرز (فلترة محلية على الصفحة الحالية) */
const filteredSubscribers = computed(() => {
  if (!packageFilter.value) return sortedSubscribers.value;
  return sortedSubscribers.value.filter((s) => subscriberPlanType(s) === packageFilter.value);
});

/* ---------------- عرض جدول/شبكة (منقول من subscribers.html) ---------------- */
const viewMode = ref("table");

/* ---------------- تنسيق أحرف الأفاتار + ألوان متدرّجة (بنفس أسلوب باقي الصفحات) ---------------- */
function initialsOf(name) {
  const parts = (name ?? "").trim().split(/\s+/);
  return (parts[0]?.[0] ?? "") + (parts[1]?.[0] ?? "");
}
const AVATAR_COLORS = [
  ["#52733D", "#3E582E"],
  ["#8A6D1F", "#D4AF37"],
  ["#17A2B8", "#0f6c7d"],
  ["#D9534F", "#8A6D1F"],
];
function avatarColor(index) {
  return AVATAR_COLORS[index % AVATAR_COLORS.length];
}

/* ---------------- فئة المستفيد (عادي / مستفيد من تبرّع) ----------------
 * ملاحظة إصلاح: كان الكود يفترض حقل "plan_type" بقيم "commercial"/"residential"
 * — هاد المفهوم غير موجود إطلاقًا بمنطق النظام (لا يوجد تصنيف تجاري/سكني
 * للمشتركين بالباك اند). الحقل الحقيقي الموجود هو beneficiary_type
 * (عادي/مستفيد من تبرّع)، فاستبدلناه بدل اختراع بيانات وهمية.
 */
function planTypeLabel(subscriber) {
  return subscriber.beneficiary_type_label ?? "-";
}
function planTypeChipClass(subscriber) {
  return subscriber.beneficiary_type === "special" ? "chip-info" : "chip-neutral";
}

/* ---------------- الرصيد المستحق (حقيقي من الفواتير غير المسدَّدة) ----------------
 * ملاحظة إصلاح: كان الكود يفترض حقل "balance" غير موجود بالباك اند إطلاقًا.
 * الحقل الحقيقي هو outstanding_balance_ils — مجموع الفواتير غير المسدَّدة
 * فقط (pending/overdue/partially_paid)، محسوب ومحمَّل مسبقًا من الباك اند.
 */
function formatBalance(subscriber) {
  const balance = subscriber.outstanding_balance_ils;
  if (balance === null || balance === undefined) return "-";
  return balance > 0 ? `₪ ${balance.toLocaleString()}` : "₪ 0";
}
function balanceColor(subscriber) {
  const balance = subscriber.outstanding_balance_ils;
  return balance > 0 ? "#D9534F" : "inherit";
}

/* ---------------- حالة الدفع (3 حالات حقيقية من الباك اند: نشط / متأخر بالدفع / موقوف) ----------------
 * ملاحظة إصلاح: كان الكود يفترض حقل "status" بقيم "active"/"overdue"/"suspended"،
 * لكن الحقل status الفعلي بالـ UserResource هو حالة الحساب العامة
 * (active/inactive/suspended/pending_review) ومالوش علاقة بحالة الدفع —
 * "overdue" ما كانت لترجع أبدًا فعليًا. أضفنا حقل payment_status مشتق
 * فعليًا بالباك اند (من الفواتير المتأخرة الحقيقية) واستخدمناه بدل الافتراض.
 */
function subscriberStatus(subscriber) {
  if (subscriber.payment_status) return subscriber.payment_status;
  return subscriber.is_locked ? "suspended" : "active";
}
function statusLabel(subscriber) {
  const status = subscriberStatus(subscriber);
  if (status === "overdue") return t("subscribers_page.status_overdue");
  if (status === "suspended") return t("users_page.status_suspended");
  return t("users_page.status_active");
}
function statusChipClass(subscriber) {
  const status = subscriberStatus(subscriber);
  if (status === "overdue") return "chip-warning";
  if (status === "suspended") return "chip-danger";
  return "chip-success";
}

/* ---------------- KPI Cards ----------------
 * أصبحت الآن مبنية على إحصائيات حقيقية شاملة من الباك اند (userService.subscribersStats)
 * بدل أرقام "هذه الصفحة" التقريبية القديمة — نفس نمط أصحاب المولدات والداشبورد.
 */
const KPI_CARDS = computed(() => {
  if (!stats.value) return [];
  const s = stats.value;
  return [
    { icon: "fa-users", label: t("subscribers_page.total_subscribers"), raw: s.total, decimals: 0, tone: "primary" },
    { icon: "fa-circle-check", label: t("users_page.status_active"), raw: s.active, decimals: 0, tone: "success" },
    { icon: "fa-clock", label: t("subscribers_page.status_overdue"), raw: s.overdue_count, decimals: 0, tone: "warning" },
    { icon: "fa-ban", label: t("users_page.status_suspended"), raw: s.suspended_count, decimals: 0, tone: "danger" },
    { icon: "fa-user-plus", label: t("subscribers_page.new_this_month"), raw: s.new_this_month_count, decimals: 0, tone: "secondary" },
  ];
});

/* ---------------- الرسوم البيانية (منقولة من subscribers.html — نسخة صادقة) ----------------
 * ملاحظة هندسية مهمة: subscribers.html الأصلي فيه 4 رسوم بيانية:
 *   1) نمو المشتركين الشهري (سنة/ربع/شهر) — بيانات تاريخية Demo وهمية بالكامل.
 *   2) حالة الدفع — قابلة للاشتقاق من البيانات الحقيقية المحمّلة حاليًا.
 *   3) المشتركون حسب المنطقة — قابلة للاشتقاق من البيانات الحقيقية المحمّلة حاليًا.
 *   4) متوسط الاستهلاك الشهري (ك.و.س) — لا يوجد حقل استهلاك (kWh) على كائن
 *      subscriber حاليًا بالـ composable، فبيانات هذا الرسم وهمية بالكامل بالـ HTML.
 * نقلت فقط الرسمين (2) و(3) بصدق من بيانات الصفحة الحالية، وتجاهلت (1) و(4)
 * لأن نقلهم كان سيعني عرض أرقام وهمية — بما يخالف نفس المبدأ اللي معمول فيه
 * أصلاً ببطاقات الـ KPI بهذا الملف ("هذه الصفحة" بدل أرقام كاذبة).
 * لرسمين (1) و(4) بشكل حقيقي، لازم endpoints تاريخية (نمو شهري) وحقل استهلاك
 * فعلي (kWh) على المشترك/العداد بالـ backend. ---------------------------------------------------------------- */
/* ---------------- توزيع حالات المشتركين (الآن من إحصائيات حقيقية شاملة) ---------------- */
const paymentStatusChartData = computed(() => ({
  labels: [
    t("users_page.status_active"),
    t("subscribers_page.overdue_short"),
    t("users_page.status_suspended"),
  ],
  datasets: [{
    data: [stats.value?.active ?? 0, stats.value?.overdue_count ?? 0, stats.value?.suspended_count ?? 0],
    backgroundColor: ["#28A745", "#D4AF37", "#D9534F"],
    borderWidth: 0,
  }],
}));
const paymentDoughnutOptions = {
  responsive: true, maintainAspectRatio: false, cutout: "68%",
  plugins: { legend: { position: "bottom", labels: { boxWidth: 10, font: { size: 10.5 } } } },
};

const byAreaChartData = computed(() => {
  const counts = {};
  subscribers.value.forEach((s) => {
    const area = s.region || t("subscribers_page.unspecified_region");
    counts[area] = (counts[area] ?? 0) + 1;
  });
  const labels = Object.keys(counts);
  return {
    labels,
    datasets: [{
      data: Object.values(counts),
      backgroundColor: ["#52733D", "#8A6D1F", "#17A2B8", "#D4AF37", "#D9534F", "#9a9d97", "#3E582E", "#0f6c7d"],
      borderWidth: 0,
    }],
  };
});
const byAreaOptions = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { position: "bottom", labels: { boxWidth: 9, font: { size: 9.5 } } } },
};

/* ---------------- تنبيهات المشتركين (مشتقة من بيانات الصفحة الحالية — صادقة) ----------------
 * ملاحظة: subscribers.html الأصلي فيه "subAlertsList" لكن بدون مصدر بيانات حقيقي واضح
 * (Demo فقط). هون اشتققتها من نفس أرقام KPI الصفحة الحالية بدل اختراع بيانات جديدة. ---------------------------------------------------------------- */
/* ---------------- تنبيهات المشتركين (الآن من إحصائيات حقيقية شاملة، مو "هذه الصفحة" فقط) ---------------- */
const pageAlerts = computed(() => {
  const list = [];
  const overdueCount = stats.value?.overdue_count ?? 0;
  const suspendedCount = stats.value?.suspended_count ?? 0;
  if (overdueCount > 0) {
    list.push({
      type: "overdue", severity: "warning", icon: "fa-clock", color: "#D4AF37",
      title: t("subscribers_page.overdue_alert_title"),
      description: t("subscribers_page.overdue_alert_desc", { count: overdueCount }),
    });
  }
  if (suspendedCount > 0) {
    list.push({
      type: "suspended", severity: "critical", icon: "fa-ban", color: "#D9534F",
      title: t("subscribers_page.suspended_alert_title"),
      description: t("subscribers_page.suspended_alert_desc", { count: suspendedCount }),
    });
  }
  return list;
});
const ALERT_CHIPS = { critical: "chip-danger", warning: "chip-warning", info: "chip-info" };
function alertTag(severity) {
  const tags = {
    critical: t("owners_page.alert_tag_critical"),
    warning: t("owners_page.alert_tag_warning"),
    info: t("owners_page.alert_tag_info"),
  };
  return tags[severity] ?? t("owners_page.alert_tag_warning");
}

/* ---------------- الخط الزمني (Activity Log حقيقي — نفس نمط صفحة أصحاب المولدات) ---------------- */
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
function timeAgo(str) {
  if (!str) return "-";
  const diffMs = Date.now() - new Date(str.replace(" ", "T")).getTime();
  const mins = Math.floor(diffMs / 60000);
  if (mins < 1) return t("subscribers_page.time_now");
  if (mins < 60) return t("subscribers_page.time_mins_ago", { mins });
  const hours = Math.floor(mins / 60);
  if (hours < 24) return t("subscribers_page.time_hours_ago", { hours });
  return t("subscribers_page.time_days_ago", { days: Math.floor(hours / 24) });
}

/* ---------------- الاشتراكات الأقرب للانتهاء (الصفحة الحالية) ---------------- */
const soonestExpiring = computed(() =>
  [...subscribers.value]
    .filter((s) => s.active_subscription?.ends_at)
    .sort((a, b) => new Date(a.active_subscription.ends_at) - new Date(b.active_subscription.ends_at))
    .slice(0, 5),
);
function daysUntil(dateStr) {
  if (!dateStr) return null;
  const diffMs = new Date(dateStr).getTime() - Date.now();
  return Math.ceil(diffMs / 86400000);
}
function expiryColor(days) {
  if (days === null) return "#9a9d97";
  if (days <= 3) return "#D9534F";
  if (days <= 7) return "#FFC107";
  return "#6B6B6B";
}

/* ---------------- تنسيق تاريخ مختصر ---------------- */
function formatDate(dateStr) {
  return dateStr ? dateStr.slice(0, 10) : "-";
}

/* ---------------- طباعة (منقولة من subscribers.html: زر الهيرو + أيقونة شريط الأدوات) ---------------- */
function printPage() {
  window.print();
}

/* ---------------- Pagination بأزرار محدودة (مش زر لكل صفحة) ---------------- */
const paginationRange = computed(() => {
  const total = pagination.value.last_page;
  const current = pagination.value.current_page;
  const delta = 1;
  const range = [];
  const withDots = [];
  let last = null;

  for (let i = 1; i <= total; i++) {
    if (i === 1 || i === total || (i >= current - delta && i <= current + delta)) {
      range.push(i);
    }
  }
  for (const i of range) {
    if (last !== null) {
      if (i - last === 2) withDots.push(last + 1);
      else if (i - last > 2) withDots.push("...");
    }
    withDots.push(i);
    last = i;
  }
  return withDots;
});

/* ---------------- إضافة مشترك جديد ----------------
 * ملاحظة: بيعتمد على createSubscriber من الـ composable. لو مش مضافة هناك بعد،
 * رح تظهر رسالة خطأ بالنافذة بدل ما تنكسر الصفحة.
 */
const showAddModal = ref(false);
const addForm = reactive({ name: "", email: "", phone: "", password: "", password_confirmation: "" });
const addLocalError = ref("");
const showAddPassword = ref(false);

function generateAddPassword() {
  const upper = "ABCDEFGHJKLMNPQRSTUVWXYZ";
  const lower = "abcdefghijkmnpqrstuvwxyz";
  const digits = "23456789";
  const symbols = "!@#$%&*";
  const all = upper + lower + digits + symbols;
  let pwd = upper[Math.floor(Math.random() * upper.length)] + lower[Math.floor(Math.random() * lower.length)] + digits[Math.floor(Math.random() * digits.length)] + symbols[Math.floor(Math.random() * symbols.length)];
  for (let i = 0; i < 8; i++) pwd += all[Math.floor(Math.random() * all.length)];
  pwd = pwd.split("").sort(() => Math.random() - 0.5).join("");
  addForm.password = pwd;
  addForm.password_confirmation = pwd;
  showAddPassword.value = true;
}

function openAdd() {
  addForm.name = "";
  addForm.email = "";
  addForm.phone = "";
  addForm.password = "";
  addForm.password_confirmation = "";
  showAddPassword.value = false;
  addLocalError.value = "";
  if (createError) createError.value = null;
  showAddModal.value = true;
}
function closeAdd() {
  if (isCreating?.value) return;
  showAddModal.value = false;
}
async function handleCreate() {
  if (typeof createSubscriber !== "function") {
    addLocalError.value = t("subscribers_page.create_subscriber_missing_error");
    return;
  }
  const ok = await createSubscriber({ ...addForm });
  if (ok) showAddModal.value = false;
}

const editingSubscriber = ref(null);
const editForm = reactive({ name: "", email: "", phone: "" });

function openEdit(subscriber) {
  editingSubscriber.value = subscriber;
  editForm.name = subscriber.name;
  editForm.email = subscriber.email;
  editForm.phone = subscriber.phone ?? "";
  saveError.value = null;
}

function closeEdit() {
  if (isSaving.value) return;
  editingSubscriber.value = null;
  saveError.value = null;
}

async function handleSave() {
  const name = editingSubscriber.value.name;
  const ok = await updateSubscriber(editingSubscriber.value.id, {
    ...editForm,
  });
  if (ok) {
    editingSubscriber.value = null;
    toast.show({
      type: "success",
      title: t("users_page.saved_toast_title"),
      message: t("subscribers_page.subscriber_updated_message", { name }),
    });
  }
}

async function handleDelete(subscriber) {
  const confirmed = await confirm({
    title: t("subscribers_page.delete_subscriber_title", { name: subscriber.name }),
    message: t("subscribers_page.delete_subscriber_message"),
    confirmLabel: t("subscribers_page.delete_permanently"),
    variant: "danger",
  });
  if (confirmed) {
    const ok = await deleteSubscriber(subscriber.id);
    if (ok) {
      toast.show({
        type: "success",
        title: t("users_page.deleted_toast_title"),
        message: t("subscribers_page.subscriber_deleted_message", { name: subscriber.name }),
      });
    }
  }
}

/* ---------------- نافذة عرض التفاصيل (منفصلة عن التعديل) ---------------- */
const viewingSubscriber = ref(null);

function openView(subscriber) {
  viewingSubscriber.value = subscriber;
}

function switchToEditFromView() {
  const subscriber = viewingSubscriber.value;
  viewingSubscriber.value = null;
  openEdit(subscriber);
}

onMounted(() => {
  if (route.query.q) searchTerm.value = String(route.query.q);
  fetchSubscribers();
  fetchStats();
  fetchTimeline();
});

</script>

<template>
  <div class="space-y-6">
    <!-- ===== HERO ===== -->
    <section v-reveal class="glass-card relative overflow-hidden p-6 lg:p-8">
      <LightningCanvas />
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>

      <div class="relative flex flex-col gap-3">
        <nav class="flex items-center gap-1.5 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
          <span>{{ t("common.home") }}</span>
          <ChevronRight class="rtl:block ltr:hidden text-[9px]" aria-hidden="true" />
          <ChevronLeft class="ltr:block rtl:hidden text-[9px]" aria-hidden="true" />
          <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">
            {{ activeTabTitle }}
          </span>
        </nav>

        <div class="flex flex-wrap items-start justify-between gap-4">
          <div class="min-w-0">
            <span class="inline-flex items-center gap-2 text-[11px] font-bold text-[#52733D] dark:text-[#8cc35a] bg-[#EBF1E7] dark:bg-white/5 border border-[#D4AF37]/30 rounded-full px-3 py-1.5 w-fit">
              <Users aria-hidden="true" v-if="activeTab === 'subscribers'" /><ArrowRightLeft aria-hidden="true" v-else-if="activeTab === 'transfers'" /><FilePenLine aria-hidden="true" v-else />
              {{ activeTabTitle }}
            </span>

            <h1 class="text-2xl lg:text-[28px] font-extrabold mt-3">
              {{ activeTabTitle }}
            </h1>

            <p class="text-[13.5px] text-[#6B6B6B] dark:text-[#aeb1ab] leading-relaxed max-w-2xl mt-1.5">
              <template v-if="activeTab === 'subscribers'">
                {{ t("subscribers_page.subtitle_before") }}
                <b class="text-[#3E582E] dark:text-[#8cc35a]">{{ pagination.total ?? 0 }}</b>
                {{ t("subscribers_page.subtitle_after") }}
              </template>
              <template v-else-if="activeTab === 'transfers'">
                {{ t("owner_subscribers.transfer_requests_subtitle") }}
              </template>
              <template v-else>
                {{ t("subscriptions_page.subtitle") }}
                <span v-if="systemStatusInfo" class="inline-flex items-center gap-1.5 font-bold ms-1.5" :style="{ color: systemStatusInfo.color }">
                  <Circle class="text-[7px]" aria-hidden="true" /> {{ systemStatusInfo.label }}
                </span>
              </template>
            </p>
          </div>

          <!-- ===== تبديل التابات (المشتركون / الاشتراكات) ===== -->
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 shrink-0 print-hidden">
            <button
              v-for="tab in TABS"
              :key="tab.key"
              type="button"
              @click="activeTab = tab.key"
              class="relative flex items-center gap-1.5 px-4 py-2 rounded-full text-[12px] font-bold transition-colors"
              :class="
                activeTab === tab.key
                  ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-sm'
                  : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'
              "
            >
              <AppIcon :name="tab.icon" />
              {{ tab.label }}
              <span
                v-if="tab.badge"
                class="text-[10px] font-extrabold rounded-full px-1.5 min-w-[1.1rem] text-center"
                :class="activeTab === tab.key ? 'bg-white/25 text-white' : 'bg-[#D9534F] text-white'"
              >{{ tab.badge }}</span>
            </button>
          </div>
        </div>

        <div class="flex flex-wrap gap-2.5 mt-1">
          <template v-if="activeTab === 'subscribers'">
            <button v-if="can('users.create')" type="button" @click="openAdd" class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md flex items-center gap-2">
              <UserPlus aria-hidden="true" /> {{ t("subscribers_page.new_subscriber_button") }}
            </button>
            <a :href="subscribersExportExcelUrl" target="_blank" rel="noopener" class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2">
              <FileDown aria-hidden="true" /> {{ t("subscribers_page.export_list_button") }}
            </a>
            <button type="button" @click="handleBulkReminder" :disabled="isSendingBulkReminder" class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2 disabled:opacity-60">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSendingBulkReminder" /><Bell aria-hidden="true" v-else /> {{ t("subscribers_page.bulk_reminder_button") }}
            </button>
            <button type="button" @click="printPage" class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2">
              <Printer aria-hidden="true" /> {{ t("owner_applications_page.print") }}
            </button>
          </template>
          <template v-else-if="activeTab === 'subscriptions'">
            <a :href="exportExcelUrl" target="_blank" rel="noopener" class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2">
              <FileSpreadsheet aria-hidden="true" /> {{ t("owner_applications_page.export_excel_title") }}
            </a>
            <button type="button" @click="openAddModal" class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md flex items-center gap-2">
              <Plus aria-hidden="true" /> {{ t("subscriptions_page.add_subscription_button") }}
            </button>
          </template>
        </div>
      </div>
    </section>

    <!-- ==========================================================
         ==================  تبويب: المشتركون  =======================
         ========================================================== -->
    <template v-if="activeTab === 'subscribers'">
    <!-- ===== ALERTS (كانت محسوبة بالباك اند بدون أي عرض بالواجهة) ===== -->
    <section v-if="stats?.alerts?.length" v-reveal class="glass-card p-4">
      <h3 class="text-[13px] font-bold mb-3 flex items-center gap-2">
        <TriangleAlert class="text-[#D9534F]" aria-hidden="true" />
        {{ $t("subscribers_page.alerts_needing_attention") }}
        <span class="text-[10px] font-bold text-white bg-[#D9534F] rounded-full px-2 py-0.5">{{ stats.alerts.length }}</span>
      </h3>
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
        <button
          v-for="(a, i) in stats.alerts" :key="i" type="button"
          @click="handleAlertClick(a)"
          class="flex items-start gap-2.5 p-3 rounded-xl text-start transition-transform hover:-translate-y-0.5"
          :class="a.severity === 'critical' ? 'bg-[#D9534F]/8 border border-[#D9534F]/20' : 'bg-[#D4AF37]/8 border border-[#D4AF37]/20'"
        >
          <CircleAlert class="mt-0.5 shrink-0 text-[#D9534F]" aria-hidden="true" v-if="a.severity === 'critical'" /><Clock class="mt-0.5 shrink-0 text-[#8A6D1F]" aria-hidden="true" v-else />
          <div class="min-w-0 flex-1">
            <p class="text-[11.5px] font-bold">{{ a.title }}</p>
            <p class="text-[10.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ a.description }}</p>
          </div>
          <ChevronLeft class="text-[9px] text-[#c9cdc2] dark:text-[#565952] shrink-0 self-center" aria-hidden="true" v-if="locale === 'ar'" /><ChevronRight class="text-[9px] text-[#c9cdc2] dark:text-[#565952] shrink-0 self-center" aria-hidden="true" v-else />
        </button>
      </div>
    </section>

    <!-- ===== KPI CARDS ===== -->
    <section v-reveal>
      <div v-if="isLoadingStats" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
        <div v-for="i in 5" :key="i" class="h-24 rounded-2xl thumb-loading"></div>
      </div>
      <div v-else class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
        <StatCard
          v-for="c in KPI_CARDS" :key="c.label"
          :label="c.label" :value="c.raw" :icon="c.icon" :tone="c.tone" :decimals="c.decimals ?? 0"
        />
      </div>
    </section>

    <!-- ===== CHARTS (نسخة صادقة من subscribers.html — راجع الملاحظة الهندسية بالسكربت) ===== -->
    <section class="grid md:grid-cols-2 gap-4">
      <div v-reveal class="glass-card p-4">
        <h3 class="text-[13.5px] font-bold mb-3">{{ $t("subscribers_page.payment_status_chart_title") }}</h3>
        <div v-if="isLoading" class="h-40 thumb-loading rounded-lg"></div>
        <div v-else class="h-40"><Doughnut :data="paymentStatusChartData" :options="paymentDoughnutOptions" /></div>
      </div>
      <div v-reveal class="glass-card p-4">
        <h3 class="text-[13.5px] font-bold mb-3">{{ $t("subscribers_page.by_area_chart_title") }}</h3>
        <div v-if="isLoading" class="h-40 thumb-loading rounded-lg"></div>
        <div v-else class="h-40"><Pie :data="byAreaChartData" :options="byAreaOptions" /></div>
      </div>
    </section>

    <!-- ===== TOOLBAR ===== -->
    <section v-reveal class="glass-card p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="relative w-full sm:flex-1 sm:max-w-md">
          <Search class="absolute top-1/2 -translate-y-1/2 start-3.5 text-[#9a9d97] dark:text-[#8f938a] text-[11px]" aria-hidden="true" />
          <input
            v-model="searchTerm"
            type="text"
            @input="handleSearchInput"
            :placeholder="$t('subscribers_page.search_placeholder')"
            class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2.5 ps-9 pe-3 text-[12.5px] outline-none focus:border-[#8A6D1F]"
          />
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
            <button
              v-for="pill in STATUS_PILLS"
              :key="pill.value"
              type="button"
              @click="subscriptionFilter = pill.value; onFilterChange()"
              class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
              :class="subscriptionFilter === pill.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
            >
              {{ pill.label }}
            </button>
          </div>
          <div class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1">
            <button type="button" @click="viewMode = 'table'" class="icon-btn !w-8 !h-8" :class="{ '!bg-white dark:!bg-white/10': viewMode === 'table' }" :title="$t('subscribers_page.table_view')" :aria-label="$t('subscribers_page.table_view')">
              <Table2 class="text-[11px]" aria-hidden="true" />
            </button>
            <button type="button" @click="viewMode = 'grid'" class="icon-btn !w-8 !h-8" :class="{ '!bg-white dark:!bg-white/10': viewMode === 'grid' }" :title="$t('subscribers_page.grid_view')" :aria-label="$t('subscribers_page.grid_view')">
              <GripVertical class="text-[11px]" aria-hidden="true" />
            </button>
          </div>
          <div class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
          <button type="button" @click="handleExportCsv" class="icon-btn !w-8 !h-8" :title="$t('owner_applications_page.export_action')" :aria-label="$t('owner_applications_page.export_action')">
            <FileSpreadsheet class="text-[11px]" aria-hidden="true" />
          </button>
          <button type="button" @click="printPage" class="icon-btn !w-8 !h-8" :title="$t('owner_applications_page.print')" :aria-label="$t('owner_applications_page.print')">
            <Printer class="text-[11px]" aria-hidden="true" />
          </button>
        </div>
      </div>
      <div class="flex items-center gap-1 mt-3 pt-3 border-t border-[#eee8da] dark:border-white/10">
        <span class="text-[10.5px] font-bold text-[#9a9d97] dark:text-[#8f938a] px-2">{{ $t("subscribers_page.category_label") }}</span>
        <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
          <button
            v-for="pill in PACKAGE_PILLS"
            :key="pill.value"
            type="button"
            @click="packageFilter = pill.value; onPackageFilterChange()"
            class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
            :class="packageFilter === pill.value ? 'bg-gradient-to-l from-[#8A6D1F] to-[#6b551b] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
          >
            {{ pill.label }}
          </button>
        </div>
      </div>
    </section>

    <!-- ===== TABLE / GRID ===== -->
    <section id="subscribers-table-section" v-reveal class="glass-card p-4 overflow-hidden">
      <h3 class="text-[13.5px] font-bold mb-3">{{ $t("subscribers_page.subscribers_list_title") }}</h3>

      <div v-if="deleteError" class="mb-3 text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2">{{ deleteError }}</div>

      <div v-if="isLoading" class="space-y-2">
        <div v-for="i in 6" :key="i" class="h-14 rounded-lg thumb-loading"></div>
      </div>
      <div v-else-if="error" class="text-center py-8 text-[12px] text-[#D9534F]">{{ error }}</div>
      <div v-else-if="filteredSubscribers.length === 0" class="text-center py-10">
        <ZoomOut class="text-2xl text-[#c9cdc2] dark:text-[#565952] mb-2" aria-hidden="true" />
        <p class="text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("subscribers_page.no_matching_results") }}</p>
      </div>

      <template v-else>
      <div v-if="viewMode === 'table'" class="overflow-x-auto -mx-1">
        <table class="data-table w-full text-[12px] min-w-[1150px]">
          <thead>
            <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
              <th class="py-2.5 px-3 rounded-s-lg">
                <span class="inline-flex items-center gap-1 cursor-pointer select-none" @click="toggleSort('name')">
                  {{ $t("subscribers_page.subscriber_col") }}
                  <ChevronDown :class="['size-[9px] shrink-0 transition-all', sortIconClass('name')]" aria-hidden="true" />
                </span>
              </th>
              <th class="py-2.5 px-3">{{ $t("subscribers_page.phone_number_col") }}</th>
              <th class="py-2.5 px-3">{{ $t("subscribers_page.region_col") }}</th>
              <th class="py-2.5 px-3">{{ $t("subscribers_page.linked_generator_col") }}</th>
              <th class="py-2.5 px-3">{{ $t("subscribers_page.beneficiary_category_col") }}</th>
              <th class="py-2.5 px-3">
                <span class="inline-flex items-center gap-1 cursor-pointer select-none" @click="toggleSort('created_at')">
                  {{ $t("subscribers_page.joined_col") }}
                  <ChevronDown :class="['size-[9px] shrink-0 transition-all', sortIconClass('created_at')]" aria-hidden="true" />
                </span>
              </th>
              <th class="py-2.5 px-3">
                <span class="inline-flex items-center gap-1 cursor-pointer select-none" @click="toggleSort('balance')">
                  {{ $t("subscribers_page.balance_col") }}
                  <ChevronDown :class="['size-[9px] shrink-0 transition-all', sortIconClass('balance')]" aria-hidden="true" />
                </span>
              </th>
              <th class="py-2.5 px-3">{{ $t("dashboard.status") }}</th>
              <th class="py-2.5 px-3 rounded-e-lg">{{ $t("subscribers_page.actions_col") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(subscriber, idx) in filteredSubscribers"
              :key="subscriber.id"
              class="border-b border-[#f0ece0] dark:border-white/5 last:border-0 text-center"
              :class="{ 'opacity-50 pointer-events-none': deletingId === subscriber.id }"
            >
              <td class="py-2.5 px-3">
                <div class="flex items-center justify-center gap-2.5">
                  <div
                    class="w-8 h-8 rounded-full flex items-center justify-center text-white text-[11px] font-bold shrink-0"
                    :style="{ background: `linear-gradient(135deg, ${avatarColor(idx)[0]}, ${avatarColor(idx)[1]})` }"
                  >
                    {{ initialsOf(subscriber.name) }}
                  </div>
                  <div class="min-w-0 text-start">
                    <div class="font-bold truncate max-w-[8rem]">{{ subscriber.name }}</div>
                    <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] truncate max-w-[8rem]">{{ subscriber.email }}</div>
                  </div>
                </div>
              </td>
              <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]" dir="ltr">{{ subscriber.phone ?? "-" }}</td>
              <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">{{ subscriber.region ?? "-" }}</td>
              <td class="py-2.5 px-3">
                <template v-if="subscriber.active_subscription">
                  <div class="flex items-center justify-center gap-1.5">
                    <CircleCheck class="text-[#28A745] text-[11px]" aria-hidden="true" />
                    <span class="font-bold">
                      {{ subscriber.active_subscription.generator_name ?? subscriber.active_subscription.plan_name }}
                    </span>
                  </div>
                </template>
                <span v-else class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("subscribers_page.no_subscription") }}</span>
              </td>
              <td class="py-2.5 px-3">
                <span class="status-chip" :class="planTypeChipClass(subscriber)">
                  {{ planTypeLabel(subscriber) }}
                </span>
              </td>
              <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">{{ formatDate(subscriber.created_at) }}</td>
              <td class="py-2.5 px-3 font-bold" :style="{ color: balanceColor(subscriber) }">
                {{ formatBalance(subscriber) }}
              </td>
              <td class="py-2.5 px-3">
                <span class="status-chip" :class="statusChipClass(subscriber)">
                  {{ statusLabel(subscriber) }}
                </span>
              </td>
              <td class="py-2.5 px-3">
                <div class="row-actions">
                  <button type="button" @click="openView(subscriber)" class="action-btn action-btn--view" :title="$t('common.view')" :aria-label="$t('common.view')">
                    <Eye aria-hidden="true" />
                  </button>
                  <button v-if="subscriber.is_locked && can('users.unlock')" type="button" @click="handleUnlock(subscriber)" class="action-btn action-btn--unlock" :title="$t('users_page.unlock_action')" :aria-label="$t('users_page.unlock_action')">
                    <LockOpen aria-hidden="true" />
                  </button>
                  <button v-if="can('users.update')" type="button" @click="openEdit(subscriber)" class="action-btn action-btn--edit" :title="$t('common.edit')" :aria-label="$t('common.edit')">
                    <Pencil aria-hidden="true" />
                  </button>
                  <span class="row-actions-divider"></span>
                  <button
                    v-if="can('users.delete')"
                    type="button"
                    @click="handleDelete(subscriber)"
                    :disabled="deletingId === subscriber.id"
                    class="action-btn action-btn--delete"
                    :title="$t('common.delete')"
                    :aria-label="$t('common.delete')"
                  >
                    <LoaderCircle class="animate-spin" aria-hidden="true" v-if="deletingId === subscriber.id" /><Trash2 aria-hidden="true" v-else />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-else class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
        <div v-for="(subscriber, idx) in filteredSubscribers" :key="subscriber.id" class="glass-card p-3.5">
          <div class="flex items-start justify-between mb-2.5">
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="w-9 h-9 rounded-full flex items-center justify-center text-white text-[11px] font-bold shrink-0" :style="{ background: `linear-gradient(135deg, ${avatarColor(idx)[0]}, ${avatarColor(idx)[1]})` }">
                {{ initialsOf(subscriber.name) }}
              </div>
              <div class="min-w-0">
                <div class="font-bold text-[12.5px] truncate">{{ subscriber.name }}</div>
                <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] text-start" dir="ltr">{{ subscriber.phone ?? "-" }}</div>
              </div>
            </div>
            <span class="status-chip shrink-0" :class="statusChipClass(subscriber)">{{ statusLabel(subscriber) }}</span>
          </div>
          <div class="flex items-center gap-2 mb-2.5 flex-wrap">
            <span class="status-chip" :class="planTypeChipClass(subscriber)">{{ planTypeLabel(subscriber) }}</span>
            <span class="text-[11px] font-bold" :style="{ color: balanceColor(subscriber) }">{{ formatBalance(subscriber) }}</span>
          </div>
          <div class="grid grid-cols-2 gap-2 text-[11px] mb-3">
            <div><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("subscribers_page.region_col") }}:</span> <b>{{ subscriber.region ?? "-" }}</b></div>
            <div><span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("subscribers_page.joined_short_col") }}:</span> <b>{{ formatDate(subscriber.created_at) }}</b></div>
          </div>
          <div class="flex items-center justify-end gap-1">
            <button :aria-label="$t('common.view')" type="button" @click="openView(subscriber)" class="w-7 h-7 rounded-lg flex items-center justify-center text-[#17A2B8] hover:bg-[#17A2B8]/10"><Eye class="text-[11px]" aria-hidden="true" /></button>
            <button :aria-label="$t('common.unlock')" v-if="subscriber.is_locked && can('users.unlock')" type="button" @click="handleUnlock(subscriber)" class="w-7 h-7 rounded-lg flex items-center justify-center text-success hover:bg-success/10"><LockOpen class="text-[11px]" aria-hidden="true" /></button>
            <button v-if="can('users.update')" :aria-label="$t('common.edit')" type="button" @click="openEdit(subscriber)" class="w-7 h-7 rounded-lg flex items-center justify-center text-secondary-700 hover:bg-secondary-700/10"><Pencil class="text-[11px]" aria-hidden="true" /></button>
            <button v-if="can('users.delete')" :aria-label="$t('common.delete')" type="button" @click="handleDelete(subscriber)" :disabled="deletingId === subscriber.id" class="w-7 h-7 rounded-lg flex items-center justify-center text-danger hover:bg-danger/10 disabled:opacity-40">
              <LoaderCircle class="animate-spin text-[11px]" aria-hidden="true" v-if="deletingId === subscriber.id" /><Trash2 class="text-[11px]" aria-hidden="true" v-else />
            </button>
          </div>
        </div>
      </div>
      </template>

      <!-- Pagination بأزرار محدودة -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-3.5">
        <span class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
          {{ $t("subscribers_page.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}
        </span>
        <div class="flex items-center gap-1">
          <button :aria-label="$t('common.previous_page')"
            type="button"
            :disabled="pagination.current_page <= 1"
            @click="fetchSubscribers(pagination.current_page - 1)"
            class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
          >
            <ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>

          <template v-for="(page, i) in paginationRange" :key="i">
            <span v-if="page === '...'" class="w-7 h-7 flex items-center justify-center text-[11px] text-[#9a9d97] dark:text-[#8f938a]">…</span>
            <button
              v-else
              type="button"
              @click="fetchSubscribers(page)"
              class="w-7 h-7 rounded-lg text-[11px] font-bold transition-colors"
              :class="
                page === pagination.current_page
                  ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white'
                  : 'hover:bg-[#EBF1E7] dark:hover:bg-white/5 text-[#6B6B6B] dark:text-[#a8aaa5]'
              "
            >
              {{ page }}
            </button>
          </template>

          <button
            :aria-label="$t('common.next_page')"
            type="button"
            :disabled="pagination.current_page >= pagination.last_page"
            @click="fetchSubscribers(pagination.current_page + 1)"
            class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
          >
            <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===== الاشتراكات الأقرب للانتهاء (الصفحة الحالية) ===== -->
    <section v-if="soonestExpiring.length" v-reveal class="glass-card p-4">
      <h3 class="text-[13.5px] font-bold mb-3">
        {{ $t("subscribers_page.soonest_expiring_title") }}
      </h3>
      <div class="space-y-1">
        <button
          v-for="s in soonestExpiring"
          :key="s.id"
          type="button"
          @click="openView(s)"
          class="flex items-center gap-2.5 w-full text-start hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 rounded-lg p-2 transition-colors"
        >
          <span class="text-[12px] font-bold truncate flex-1">{{ s.name }}</span>
          <span class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] truncate max-w-[8rem]">
            {{ s.active_subscription.generator_name ?? s.active_subscription.plan_name }}
          </span>
          <span class="text-[11.5px] font-extrabold shrink-0" :style="{ color: expiryColor(daysUntil(s.active_subscription.ends_at)) }">
            {{ formatDate(s.active_subscription.ends_at) }}
          </span>
        </button>
      </div>
    </section>

    <!-- ===== ALERTS + TIMELINE (منقول من subscribers.html — بيانات صادقة، راجع الملاحظات بالسكربت) ===== -->
    <section class="grid md:grid-cols-2 gap-4">
      <div v-reveal class="glass-card p-4">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
          <h3 class="text-[13.5px] font-bold">{{ $t("subscribers_page.subscriber_alerts_title") }}</h3>
          <span v-if="pageAlerts.length" class="text-[10px] font-bold text-white bg-[#D9534F] rounded-full px-2 py-0.5">{{ pageAlerts.length }}</span>
        </div>
        <div v-if="isLoading" class="space-y-2">
          <div v-for="i in 2" :key="i" class="h-14 rounded-lg thumb-loading"></div>
        </div>
        <div v-else-if="!pageAlerts.length" class="text-center py-8 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("subscribers_page.no_alerts") }}</div>
        <div v-else class="space-y-2.5">
          <div v-for="(a, i) in pageAlerts" :key="i" class="flex items-start gap-2.5 p-2.5 rounded-lg hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 transition-colors">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shrink-0" :style="{ background: a.color }">
              <AppIcon :name="a.icon" class="text-[11px]" />
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-2">
                <span class="text-[12px] font-bold truncate">{{ a.title }}</span>
                <span class="status-chip shrink-0" :class="ALERT_CHIPS[a.severity]">{{ alertTag(a.severity) }}</span>
              </div>
              <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5">{{ a.description }}</p>
            </div>
          </div>
        </div>
      </div>

      <div v-reveal class="glass-card p-4">
        <h3 class="text-[13.5px] font-bold mb-3">{{ $t("users_page.recent_activity") }}</h3>
        <div v-if="isLoadingTimeline" class="space-y-2">
          <div v-for="i in 3" :key="i" class="h-10 rounded-lg thumb-loading"></div>
        </div>
        <div v-else-if="timeline.length === 0" class="text-center py-8 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("users_page.no_activity") }}</div>
        <div v-else class="mt-1">
          <div v-for="log in timeline" :key="log.id" class="timeline-item" style="--dot-color:#52733D">
            <p class="text-[12px] font-semibold">
              {{ log.causer?.name ?? $t("users_page.system_label") }}
              <span class="font-normal text-[#6B6B6B] dark:text-[#a8aaa5]">— {{ log.description }}</span>
            </p>
            <span class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ timeAgo(log.created_at) }}</span>
          </div>
        </div>
      </div>
    </section>

    <footer class="text-center text-[11px] text-[#9a9d97] dark:text-[#8f938a] py-4">
      {{ $t("subscribers_page.footer_copyright") }}
    </footer>

    <!-- ===================== نافذة إضافة مشترك جديد ===================== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div
        v-if="showAddModal"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        @click.self="closeAdd"
      >
        <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-lg max-h-[90vh] flex flex-col shadow-2xl overflow-hidden">
          <!-- Header -->
          <div class="relative shrink-0 px-5 py-4 bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white overflow-hidden">
            <div class="absolute inset-0 opacity-[0.08] pointer-events-none" style="background-image:radial-gradient(circle at 88% 15%, #fff 0%, transparent 40%)"></div>
            <div class="relative flex items-center justify-between gap-3">
              <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center text-[15px] shrink-0">
                  <UserPlus aria-hidden="true" />
                </span>
                <div class="min-w-0">
                  <h3 class="text-[15px] font-extrabold truncate">
                    {{ $t("subscribers_page.add_subscriber_modal_title") }}
                  </h3>
                  <p class="text-[11px] text-white/75 truncate">
                    {{ $t("subscribers_page.add_subscriber_modal_subtitle") }}
                  </p>
                </div>
              </div>
              <button :aria-label="$t('common.close')" type="button" @click="closeAdd" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center shrink-0 transition-colors"><X aria-hidden="true" /></button>
            </div>
          </div>

          <form @submit.prevent="handleCreate" class="p-5 space-y-3.5 overflow-y-auto">
            <div v-if="addLocalError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 border border-[#D9534F]/20 rounded-xl px-3.5 py-2.5 flex items-center gap-2">
              <CircleAlert class="shrink-0" aria-hidden="true" /> {{ addLocalError }}
            </div>
            <div v-if="createError?.message" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 border border-[#D9534F]/20 rounded-xl px-3.5 py-2.5 flex items-center gap-2">
              <CircleAlert class="shrink-0" aria-hidden="true" /> {{ createError.message }}
            </div>

            <div class="form-section">
              <div class="form-section-head"><span class="form-section-icon"><IdCard aria-hidden="true" /></span>{{ $t("subscribers_page.basic_info") }}</div>
              <div class="grid gap-3.5">
                <div>
                  <label class="field-label">{{ $t("dashboard.name") }}</label>
                  <input v-model="addForm.name" required type="text" autocomplete="off" :placeholder="$t('users_page.name_placeholder_example')" class="field-input" />
                </div>
                <div>
                  <label class="field-label">{{ $t("users_page.email_label") }}</label>
                  <input v-model="addForm.email" required type="email" dir="ltr" autocomplete="off" placeholder="example@email.com" class="field-input" />
                  <p v-if="createError?.errors?.email" class="text-[10.5px] text-[#D9534F] mt-1">{{ createError.errors.email[0] }}</p>
                </div>
                <div>
                  <label class="field-label">{{ $t("users_page.phone_label") }}</label>
                  <input v-model="addForm.phone" type="text" dir="ltr" placeholder="+970 5X XXX XXXX" class="field-input" />
                </div>
              </div>
            </div>

            <div class="form-section">
              <div class="form-section-head"><span class="form-section-icon"><Lock aria-hidden="true" /></span>{{ $t("dashboard.password") }}</div>
              <div class="grid gap-3.5">
                <div>
                  <label class="field-label">{{ $t("dashboard.password") }}</label>
                  <div class="relative">
                    <input v-model="addForm.password" required :type="showAddPassword ? 'text' : 'password'" autocomplete="new-password" class="field-input ps-8 font-mono" />
                    <button :aria-label="showAddPassword ? $t('common.hide_password') : $t('common.show_password')" type="button" @click="showAddPassword = !showAddPassword" class="absolute top-1/2 -translate-y-1/2 start-2 flex items-center text-[#9a9d97] dark:text-[#8f938a] hover:text-[#8A6D1F]">
                      <EyeOff class="text-[11px]" aria-hidden="true" v-if="showAddPassword" /><Eye class="text-[11px]" aria-hidden="true" v-else />
                    </button>
                  </div>
                  <p v-if="createError?.errors?.password" class="text-[10.5px] text-[#D9534F] mt-1">{{ createError.errors.password[0] }}</p>
                  <button type="button" @click="generateAddPassword" class="text-[10.5px] font-bold text-[#8A6D1F] mt-1.5"><Shuffle aria-hidden="true" /> {{ $t("users_page.generate_random_password") }}</button>
                </div>
                <div>
                  <label class="field-label">{{ $t("dashboard.confirm_password") }}</label>
                  <input v-model="addForm.password_confirmation" required :type="showAddPassword ? 'text' : 'password'" dir="ltr" autocomplete="new-password" class="field-input font-mono" />
                </div>
              </div>
            </div>
          </form>

          <div class="flex items-center justify-end gap-2.5 px-5 py-4 border-t border-[#eee8da] dark:border-white/10 shrink-0 bg-[#f4efe5]/40 dark:bg-white/[0.02]">
            <button type="button" @click="closeAdd" class="text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-white dark:hover:bg-white/5 transition-colors">{{ $t("dashboard.cancel") }}</button>
            <button type="submit" @click="handleCreate" :disabled="isCreating" class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-5 py-2.5 rounded-full shadow-md flex items-center gap-2 disabled:opacity-60">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isCreating" /><Check aria-hidden="true" v-else />
              {{ isCreating ? $t("subscribers_page.adding_ellipsis") : $t("subscribers_page.add_subscriber_button") }}
            </button>
          </div>
        </div>
      </div>
      </Transition>
    </Teleport>

    <!-- ===================== نافذة التعديل (بنفس أسلوب نافذة تعديل المولدات) ===================== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div
        v-if="editingSubscriber"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        @click.self="closeEdit"
      >
        <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-lg max-h-[90vh] flex flex-col shadow-2xl overflow-hidden">
          <!-- Header -->
          <div class="relative shrink-0 px-5 py-4 bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white overflow-hidden">
            <div class="absolute inset-0 opacity-[0.08] pointer-events-none" style="background-image:radial-gradient(circle at 88% 15%, #fff 0%, transparent 40%)"></div>
            <div class="relative flex items-center justify-between gap-3">
              <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center text-[15px] shrink-0">
                  <Pencil aria-hidden="true" />
                </span>
                <div class="min-w-0">
                  <h3 class="text-[15px] font-extrabold truncate">
                    {{ $t("subscribers_page.edit_subscriber_modal_title") }}
                  </h3>
                  <p class="text-[11px] text-white/75 truncate">{{ editingSubscriber.name }}</p>
                </div>
              </div>
              <button :aria-label="$t('common.close')" type="button" @click="closeEdit" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center shrink-0 transition-colors"><X aria-hidden="true" /></button>
            </div>
          </div>

          <form @submit.prevent="handleSave" class="p-5 space-y-3.5 overflow-y-auto">
            <div v-if="saveError?.message" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 border border-[#D9534F]/20 rounded-xl px-3.5 py-2.5 flex items-center gap-2">
              <CircleAlert class="shrink-0" aria-hidden="true" /> {{ saveError.message }}
            </div>

            <div class="form-section">
              <div class="form-section-head"><span class="form-section-icon"><IdCard aria-hidden="true" /></span>{{ $t("subscribers_page.basic_info") }}</div>
              <div class="grid gap-3.5">
                <div>
                  <label class="field-label">{{ $t("dashboard.name") }}</label>
                  <input v-model="editForm.name" required type="text" class="field-input" />
                </div>
                <div>
                  <label class="field-label">{{ $t("users_page.email_label") }}</label>
                  <input v-model="editForm.email" required type="email" class="field-input" />
                  <p v-if="saveError?.errors?.email" class="text-[10.5px] text-[#D9534F] mt-1">{{ saveError.errors.email[0] }}</p>
                </div>
                <div>
                  <label class="field-label">{{ $t("users_page.phone_label") }}</label>
                  <input v-model="editForm.phone" type="text" class="field-input" />
                </div>
              </div>
            </div>
          </form>

          <div class="flex items-center justify-end gap-2.5 px-5 py-4 border-t border-[#eee8da] dark:border-white/10 shrink-0 bg-[#f4efe5]/40 dark:bg-white/[0.02]">
            <button type="button" @click="closeEdit" class="text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-white dark:hover:bg-white/5 transition-colors">{{ $t("dashboard.cancel") }}</button>
            <button type="submit" @click="handleSave" :disabled="isSaving" class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-5 py-2.5 rounded-full shadow-md flex items-center gap-2 disabled:opacity-60">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" /><Check aria-hidden="true" v-else />
              {{ isSaving ? $t("users_page.saving_ellipsis") : $t("dashboard.save_changes") }}
            </button>
          </div>
        </div>
      </div>
      </Transition>
    </Teleport>

    <!-- ===================== نافذة عرض التفاصيل (بنفس أسلوب نافذة عرض المولدات) ===================== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div
        v-if="viewingSubscriber"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        @click.self="viewingSubscriber = null"
      >
        <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-2xl max-h-[88vh] flex flex-col shadow-2xl overflow-hidden">
          <!-- Header -->
          <div class="relative shrink-0 px-5 py-4 bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white overflow-hidden">
            <div class="absolute inset-0 opacity-[0.08] pointer-events-none" style="background-image:radial-gradient(circle at 88% 15%, #fff 0%, transparent 40%)"></div>
            <div class="relative flex items-center justify-between gap-3">
              <div class="flex items-center gap-3 min-w-0">
                <span
                  class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center text-[13px] font-bold shrink-0"
                >
                  {{ initialsOf(viewingSubscriber.name) }}
                </span>
                <div class="min-w-0">
                  <h3 class="text-[15px] font-extrabold truncate">{{ viewingSubscriber.name }}</h3>
                  <p class="text-[11px] text-white/75 truncate">{{ viewingSubscriber.email }}</p>
                </div>
              </div>
              <button :aria-label="$t('common.close')" type="button" @click="viewingSubscriber = null" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center shrink-0 transition-colors"><X aria-hidden="true" /></button>
            </div>
          </div>

          <div class="p-5 grid sm:grid-cols-2 gap-4 overflow-y-auto">
            <div class="space-y-4">
              <div class="glass-card p-4 flex items-center gap-4">
                <div
                  class="w-14 h-14 rounded-full flex items-center justify-center text-white text-base font-bold shrink-0"
                  :style="{ background: `linear-gradient(135deg, ${avatarColor(0)[0]}, ${avatarColor(0)[1]})` }"
                >
                  {{ initialsOf(viewingSubscriber.name) }}
                </div>
                <div class="min-w-0">
                  <div class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] mb-1.5">{{ $t("subscribers_page.account_status_label") }}</div>
                  <span class="status-chip" :class="statusChipClass(viewingSubscriber)">
                    {{ statusLabel(viewingSubscriber) }}
                  </span>
                </div>
              </div>

              <div class="glass-card p-4 space-y-1">
                <div class="info-row">
                  <span class="info-row-icon"><Mail aria-hidden="true" /></span>
                  <span class="info-row-label">{{ $t("users_page.email_label") }}</span>
                  <b class="info-row-value">{{ viewingSubscriber.email }}</b>
                </div>
                <div class="info-row">
                  <span class="info-row-icon"><Phone aria-hidden="true" /></span>
                  <span class="info-row-label">{{ $t("users_page.phone_label") }}</span>
                  <b class="info-row-value" dir="ltr">{{ viewingSubscriber.phone ?? "-" }}</b>
                </div>
                <div class="info-row">
                  <span class="info-row-icon"><CalendarPlus aria-hidden="true" /></span>
                  <span class="info-row-label">{{ $t("subscribers_page.joined_col") }}</span>
                  <b class="info-row-value">{{ formatDate(viewingSubscriber.created_at) }}</b>
                </div>
              </div>

              <div class="glass-card p-4 space-y-1">
                <!-- عرض للقراءة فقط، بتصميم متعمَّد: SubscriberPolicy::updateBeneficiaryType
                     تمنع الأدمن صراحة (isOwner() فقط) — تصنيف المستفيد قرار
                     خاص بمالك المولد تجاه مشتركيه، وليس إجراءً إداريًا عامًا. -->
                <div class="info-row">
                  <span class="info-row-icon"><IdCard aria-hidden="true" /></span>
                  <span class="info-row-label">{{ $t("subscribers_page.beneficiary_type_col") }}</span>
                  <span class="status-chip" :class="planTypeChipClass(viewingSubscriber)">{{ planTypeLabel(viewingSubscriber) }}</span>
                </div>
              </div>

              <div v-if="viewingSubscriber.family_members_count != null || viewingSubscriber.has_sick_family_member" class="glass-card p-4 space-y-1">
                <div v-if="viewingSubscriber.family_members_count != null" class="info-row">
                  <span class="info-row-icon"><Users aria-hidden="true" /></span>
                  <span class="info-row-label">{{ $t("subscribers_page.family_members_count_label") }}</span>
                  <b class="info-row-value">{{ viewingSubscriber.family_members_count }}</b>
                </div>
                <div v-if="viewingSubscriber.has_sick_family_member" class="info-row">
                  <span class="info-row-icon"><HeartPulse class="text-danger" aria-hidden="true" /></span>
                  <span class="info-row-label">{{ $t("subscribers_page.sick_family_member_label") }}</span>
                  <b class="info-row-value">{{ viewingSubscriber.sick_family_member_illness || $t("subscribers_page.sick_family_member_yes") }}</b>
                </div>
              </div>
            </div>

            <div class="space-y-4">
              <p class="text-[11px] font-bold text-[#9a9d97] dark:text-[#8f938a]">{{ $t("subscribers_page.subscription_history_title") }}</p>

              <div v-if="viewingSubscriber.subscription_history?.length" class="space-y-2 max-h-[19rem] overflow-y-auto pe-1">
                <div
                  v-for="sub in viewingSubscriber.subscription_history" :key="sub.id"
                  class="glass-card p-3"
                  :class="sub.status === 'active' ? '!border-[#28A745]/30' : ''"
                >
                  <div class="flex items-center justify-between gap-2 mb-1">
                    <span class="text-[12px] font-bold truncate">{{ sub.generator_name ?? "-" }}</span>
                    <span
                      class="status-chip shrink-0"
                      :class="{
                        'chip-success': sub.status === 'active',
                        'chip-danger': sub.status === 'rejected' || sub.status === 'cancelled',
                        'chip-warning': sub.status === 'pending' || sub.status === 'suspended',
                      }"
                    >{{ sub.status_label }}</span>
                  </div>
                  <p class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a]">
                    {{ formatDate(sub.start_date) }}
                    <ArrowRight class="text-[8px] mx-1 ltr:block rtl:hidden" aria-hidden="true" />
                    <ArrowLeft class="text-[8px] mx-1 rtl:block ltr:hidden" aria-hidden="true" />
                    {{ sub.end_date ? formatDate(sub.end_date) : $t("subscribers_page.ongoing_label") }}
                  </p>
                </div>
              </div>
              <div v-else class="glass-card p-4 flex items-center gap-2 text-[12px] text-[#9a9d97] dark:text-[#8f938a]">
                <CircleMinus aria-hidden="true" />
                {{ $t("subscribers_page.no_subscriptions_history") }}
              </div>
            </div>
          </div>

          <div class="flex items-center justify-between gap-2.5 px-5 py-4 border-t border-[#eee8da] dark:border-white/10 shrink-0 bg-[#f4efe5]/40 dark:bg-white/[0.02] flex-wrap">
            <div class="flex items-center gap-1.5">
              <button v-if="can('users.update')" type="button" :disabled="isSendingResetLink" @click="handleSendResetLink(viewingSubscriber)" class="w-9 h-9 rounded-full flex items-center justify-center text-secondary-700 border border-secondary-700/40 hover:bg-secondary-700/10 disabled:opacity-50" :title="$t('subscribers_page.send_reset_link_hint_short')" :aria-label="$t('subscribers_page.send_reset_link_hint_short')">
                <LoaderCircle class="text-[13px] animate-spin" aria-hidden="true" v-if="isSendingResetLink" /><Key class="text-[13px]" aria-hidden="true" v-else />
              </button>
              <button v-if="can('users.update')" type="button" @click="openSetPassword(viewingSubscriber)" class="w-9 h-9 rounded-full flex items-center justify-center text-danger border border-danger/40 hover:bg-danger/10" :title="$t('subscribers_page.set_password_hint_short')" :aria-label="$t('subscribers_page.set_password_hint_short')">
                <Lock class="text-[13px]" aria-hidden="true" />
              </button>
            </div>
            <div class="flex items-center gap-2.5">
              <button type="button" @click="viewingSubscriber = null" class="text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-white dark:hover:bg-white/5 transition-colors">
                {{ $t("common.close") }}
              </button>
              <button type="button" @click="switchToEditFromView" class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-5 py-2.5 rounded-full shadow-md flex items-center gap-2">
                <Pencil aria-hidden="true" />
                {{ $t("common.edit") }}
              </button>
            </div>
          </div>
        </div>
      </div>
      </Transition>
    </Teleport>

    <!-- ===================== نافذة تعيين كلمة سر مؤقتة ===================== -->
    <Teleport to="body">
      <div v-if="setPasswordModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="setPasswordModal = null">
        <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm p-5 shadow-2xl">
          <h3 class="text-[14px] font-extrabold mb-1.5 flex items-center gap-2">
            <Lock class="text-[#D9534F]" aria-hidden="true" />
            {{ $t("users_page.set_password_for", { name: setPasswordModal.name }) }}
          </h3>
          <p class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] mb-4">
            {{ $t("subscribers_page.set_password_desc_short") }}
          </p>

          <div v-if="setPasswordError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2 mb-3">{{ setPasswordError }}</div>

          <div class="space-y-3">
            <div>
              <label class="field-label">{{ $t("users_page.temp_password_label") }}</label>
              <div class="relative">
                <input
                  v-model="setPasswordForm.password"
                  :type="showSetPassword ? 'text' : 'password'"
                  class="field-input ps-16 font-mono"
                />
                <div class="absolute top-1/2 -translate-y-1/2 start-2 flex items-center gap-1">
                  <button :aria-label="showSetPassword ? $t('common.hide_password') : $t('common.show_password')" type="button" @click="showSetPassword = !showSetPassword" class="w-6 h-6 rounded-md flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-[#8A6D1F]">
                    <EyeOff class="text-[11px]" aria-hidden="true" v-if="showSetPassword" /><Eye class="text-[11px]" aria-hidden="true" v-else />
                  </button>
                  <button :aria-label="$t('common.copy_password')" type="button" @click="copySetPassword" class="w-6 h-6 rounded-md flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-[#8A6D1F]">
                    <Copy class="text-[11px]" aria-hidden="true" />
                  </button>
                </div>
              </div>
              <button type="button" @click="generateSetPassword" class="text-[10.5px] font-bold text-[#8A6D1F] mt-1.5"><Shuffle aria-hidden="true" /> {{ $t("users_page.generate_new_password") }}</button>
            </div>
          </div>

          <div class="flex gap-2.5 mt-5">
            <button type="button" @click="setPasswordModal = null" class="flex-1 text-[12.5px] font-bold py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5">{{ $t("dashboard.cancel") }}</button>
            <button type="button" @click="handleSetPassword" :disabled="isSettingPassword" class="flex-1 btn-fill relative bg-gradient-to-l from-[#D9534F] to-[#8A2E2A] text-white text-[12.5px] font-bold py-2.5 rounded-full shadow-md flex items-center justify-center gap-2 disabled:opacity-60">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSettingPassword" /><Check aria-hidden="true" v-else />
              {{ isSettingPassword ? $t("users_page.setting_ellipsis") : $t("users_page.set_action") }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    </template>

    <!-- ==========================================================
         ==================  تبويب: الاشتراكات  =======================
         ========================================================== -->
    <template v-else-if="activeTab === 'subscriptions'">
    <!-- ===== KPI CARDS ===== -->
    <section v-reveal>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
        <StatCard
          v-for="c in SUBSCRIPTION_KPI_CARDS" :key="c.label"
          :label="c.label" :value="c.value" :icon="c.icon" :tone="c.tone"
        />
      </div>
    </section>

    <!-- ===== CHARTS ===== -->
    <section v-reveal class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div class="glass-card p-4 md:col-span-2">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
          <h3 class="text-[13.5px] font-bold">{{ $t("subscriptions_page.monthly_growth_title") }}</h3>
          <span v-if="monthlyGrowth.isEstimate" class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">
            {{ $t("subscriptions_page.estimate_notice") }}
          </span>
        </div>
        <div class="h-64"><canvas ref="growthCanvas"></canvas></div>
      </div>

      <div class="glass-card p-4">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
          <h3 class="text-[13.5px] font-bold">{{ $t("subscriptions_page.status_breakdown_filter_title") }}</h3>
          <button
            v-if="derivedFilter"
            type="button"
            @click="applyDerivedFilter(derivedFilter)"
            class="text-[10px] font-bold text-[#D9534F] hover:underline shrink-0"
          >
            {{ $t("subscriptions_page.clear_filter") }}
          </button>
        </div>
        <div class="h-40"><canvas ref="statusCanvas"></canvas></div>
        <div class="grid grid-cols-4 gap-1.5 mt-3 text-center">
          <button
            v-for="s in statusDistribution"
            :key="s.key"
            type="button"
            @click="applyDerivedFilter(s.key)"
            class="rounded-lg py-1 transition-colors"
            :class="derivedFilter === s.key ? 'bg-[#f4efe5] dark:bg-white/10' : 'hover:bg-[#f4efe5]/60 dark:hover:bg-white/5'"
          >
            <div class="text-[13px] font-extrabold" :style="{ color: s.color }">{{ s.pct }}%</div>
            <div class="text-[9.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ s.label }}</div>
          </button>
        </div>
      </div>

      <div class="glass-card p-4">
        <h3 class="text-[13.5px] font-bold mb-3">{{ $t("subscriptions_page.by_plan_type_title") }}</h3>
        <div class="h-44"><canvas ref="planCanvas"></canvas></div>
      </div>

      <div class="glass-card p-4 md:col-span-2 lg:col-span-2">
        <h3 class="text-[13.5px] font-bold mb-3">{{ $t("subscriptions_page.expiring_weeks_title") }}</h3>
        <div class="h-44"><canvas ref="expiringCanvas"></canvas></div>
      </div>
    </section>

    <!-- ===== TOOLBAR ===== -->
    <section v-reveal class="glass-card p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="relative max-w-sm w-full sm:w-auto">
          <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
          <input
            v-model="subscriptionSearchTerm"
            type="text"
            @input="handleSubscriptionSearchInput"
            :placeholder="$t('subscriptions_page.search_placeholder')"
            class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F]"
          />
        </div>
        <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
          <button
            v-for="pill in SUBSCRIPTION_STATUS_PILLS"
            :key="pill.value"
            type="button"
            @click="applyFilter(pill.value)"
            class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
            :class="statusFilter === pill.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
          >
            {{ pill.label }}
          </button>
        </div>
      </div>
    </section>

    <!-- ===== TABLE ===== -->
    <section v-reveal class="glass-card p-4 overflow-hidden">
      <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
        <h3 class="text-[13.5px] font-bold">{{ $t("subscriptions_page.subscriptions_list_title") }}</h3>
        <button
          v-if="derivedFilter"
          type="button"
          @click="applyDerivedFilter(derivedFilter)"
          class="text-[10.5px] font-bold text-[#8A6D1F] bg-[#8A6D1F]/10 rounded-full px-3 py-1 flex items-center gap-1.5 hover:bg-[#8A6D1F]/20 transition-colors"
        >
          <Funnel class="text-[9px]" aria-hidden="true" />
          {{ derivedStatusLabel(derivedFilter) }}
          <X class="text-[9px]" aria-hidden="true" />
        </button>
      </div>

      <div v-if="statusUpdateError" class="mb-3 text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2">{{ statusUpdateError }}</div>

      <div v-if="isLoadingSubscriptions" class="space-y-2">
        <div v-for="i in 6" :key="i" class="h-16 rounded-lg thumb-loading"></div>
      </div>

      <div v-else-if="subscriptionsError" class="text-center py-8 text-[12px] text-[#D9534F]">{{ subscriptionsError }}</div>

      <div v-else-if="sortedSubscriptions.length === 0" class="text-center py-10">
        <ZoomOut class="text-2xl text-[#c9cdc2] dark:text-[#565952] mb-2" aria-hidden="true" />
        <p class="text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("subscriptions_page.no_matching_subscriptions") }}</p>
      </div>

      <div v-else class="overflow-x-auto -mx-1">
        <table class="data-table w-full text-[12px] min-w-[1180px]">
          <thead>
            <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
              <th class="py-2.5 px-3 rounded-s-lg">{{ $t("subscriptions_page.contract_no_col") }}</th>
              <th class="py-2.5 px-3 cursor-pointer select-none" @click="subscriptionToggleSort('subscriber')">
                {{ $t("subscribers_page.subscriber_col") }}
                <AppIcon :name="subscriptionSortIconClass('subscriber')" class="text-[9px] ms-1 transition-all" />
              </th>
              <th class="py-2.5 px-3 cursor-pointer select-none" @click="subscriptionToggleSort('generator')">
                {{ $t("dashboard.generator_col") }}
                <AppIcon :name="subscriptionSortIconClass('generator')" class="text-[9px] ms-1 transition-all" />
              </th>
              <th class="py-2.5 px-3">{{ $t("subscriptions_page.generator_owner_col") }}</th>
              <th class="py-2.5 px-3">{{ $t("subscriptions_page.plan_type_col") }}</th>
              <th class="py-2.5 px-3 cursor-pointer select-none" @click="subscriptionToggleSort('starts_at')">
                {{ $t("subscriptions_page.start_date_col") }}
                <AppIcon :name="subscriptionSortIconClass('starts_at')" class="text-[9px] ms-1 transition-all" />
              </th>
              <th class="py-2.5 px-3 cursor-pointer select-none" @click="subscriptionToggleSort('ends_at')">
                {{ $t("subscriptions_page.end_date_col") }}
                <AppIcon :name="subscriptionSortIconClass('ends_at')" class="text-[9px] ms-1 transition-all" />
              </th>
              <th class="py-2.5 px-3">{{ $t("subscriptions_page.auto_renewal_col") }}</th>
              <th class="py-2.5 px-3 cursor-pointer select-none" @click="subscriptionToggleSort('status')">
                {{ $t("dashboard.status_col") }}
                <AppIcon :name="subscriptionSortIconClass('status')" class="text-[9px] ms-1 transition-all" />
              </th>
              <th class="py-2.5 px-3 rounded-e-lg">{{ $t("subscribers_page.actions_col") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(sub, idx) in sortedSubscriptions"
              :key="sub.id"
              class="border-b border-[#f0ece0] dark:border-white/5 last:border-0 text-center"
              :class="{ 'opacity-50 pointer-events-none': updatingStatusId === sub.id }"
            >
              <td class="py-2.5 px-3 font-semibold text-[#6B6B6B] dark:text-[#a8aaa5]" dir="ltr">{{ sub.id }}</td>
              <td class="py-2.5 px-3">
                <div class="flex items-center gap-2.5 justify-center">
                  <div class="sub-avatar" :style="subscriptionAvatarStyle(idx)">{{ initialsOf(sub.subscriber?.name) }}</div>
                  <button type="button" @click="openSubscriptionView(sub)" class="font-bold hover:text-[#8A6D1F] transition-colors">
                    {{ sub.subscriber?.name ?? "-" }}
                  </button>
                </div>
              </td>
              <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">{{ sub.generator?.name ?? "-" }}</td>
              <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">{{ sub.generator?.owner_name ?? "-" }}</td>
              <td class="py-2.5 px-3">
                <span v-if="planMeta(sub.plan)" class="plan-chip" :class="planMeta(sub.plan).cls">
                  <AppIcon :name="planMeta(sub.plan).icon" />{{ planLabel(sub.plan) }}
                </span>
                <span v-else class="text-[#9a9d97] dark:text-[#8f938a]">-</span>
              </td>
              <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">{{ formatDate(sub.start_date) }}</td>
              <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">{{ formatDate(sub.end_date) }}</td>
              <td class="py-2.5 px-3">
                <span class="renew-chip" :class="sub.auto_renew ? 'renew-on' : 'renew-off'">
                  <ToggleRight aria-hidden="true" v-if="sub.auto_renew" /><ToggleLeft aria-hidden="true" v-else />
                  {{ sub.auto_renew ? $t("subscriptions_page.renew_on") : $t("subscriptions_page.renew_off") }}
                </span>
              </td>
              <td class="py-2.5 px-3">
                <span class="status-chip" :class="statusChip(sub.status)" :style="statusChipStyle(sub.status)">{{ subscriptionStatusLabel(sub.status) }}</span>
              </td>
              <td class="py-2.5 px-3">
                <div class="row-actions">
                  <button type="button" @click="openSubscriptionView(sub)" class="action-btn action-btn--view" :title="$t('common.view')" :aria-label="$t('common.view')">
                    <Eye aria-hidden="true" />
                  </button>

                  <button
                    v-for="action in actionsFor(sub)"
                    :key="action.to"
                    type="button"
                    @click="handleStatusChange(sub, action)"
                    :disabled="updatingStatusId === sub.id"
                    class="action-btn"
                    :style="{ color: action.color, '--hover-bg': action.color + '1A' }"
                    :title="t(action.labelKey)"
                    :aria-label="t(action.labelKey)"
                  >
                    <AppIcon :name="updatingStatusId === sub.id ? 'fa-spinner fa-spin' : action.icon" />
                  </button>

                  <span class="row-actions-divider"></span>

                  <button type="button" @click="openTransfer(sub)" class="action-btn action-btn--transfer" :title="$t('subscriptions_page.transfer_action_title')" :aria-label="$t('subscriptions_page.transfer_action_title')">
                    <ArrowRightLeft aria-hidden="true" />
                  </button>

                  <a
                    :href="contractUrl(sub)"
                    target="_blank"
                    rel="noopener"
                    class="action-btn action-btn--contract"
                    :title="$t('subscriptions_page.download_contract_title')"
                    :aria-label="$t('subscriptions_page.download_contract_title')"
                  >
                    <FileText aria-hidden="true" />
                  </a>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination بأزرار محدودة -->
      <div v-if="subscriptionPagination.last_page > 1" class="flex items-center justify-between mt-3.5">
        <span class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
          {{ $t("subscriptions_page.pagination_text", { current: subscriptionPagination.current_page, last: subscriptionPagination.last_page, total: subscriptionPagination.total }) }}
        </span>
        <div class="flex items-center gap-1">
          <button :aria-label="$t('common.previous_page')"
            type="button"
            :disabled="subscriptionPagination.current_page <= 1"
            @click="fetchSubscriptions(subscriptionPagination.current_page - 1)"
            class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
          >
            <ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>

          <template v-for="(page, i) in subscriptionPaginationRange" :key="i">
            <span v-if="page === '...'" class="w-7 h-7 flex items-center justify-center text-[11px] text-[#9a9d97] dark:text-[#8f938a]">…</span>
            <button
              v-else
              type="button"
              @click="fetchSubscriptions(page)"
              class="w-7 h-7 rounded-lg text-[11px] font-bold transition-colors"
              :class="
                page === subscriptionPagination.current_page
                  ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white'
                  : 'hover:bg-[#EBF1E7] dark:hover:bg-white/5 text-[#6B6B6B] dark:text-[#a8aaa5]'
              "
            >
              {{ page }}
            </button>
          </template>

          <button
            :aria-label="$t('common.next_page')"
            type="button"
            :disabled="subscriptionPagination.current_page >= subscriptionPagination.last_page"
            @click="fetchSubscriptions(subscriptionPagination.current_page + 1)"
            class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
          >
            <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===== ALERTS + TIMELINE + TOP EXPIRING ===== -->
    <section v-reveal class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div class="glass-card p-4">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
          <h3 class="text-[13.5px] font-bold">{{ $t("subscriptions_page.subscription_alerts_title") }}</h3>
          <span class="text-[10px] font-bold text-white bg-[#D9534F] rounded-full px-2 py-0.5">{{ subsAlerts.length }}</span>
        </div>
        <div v-if="subsAlerts.length" class="space-y-2.5">
          <div
            v-for="(a, i) in subsAlerts"
            :key="i"
            class="flex items-start gap-2.5 p-2.5 rounded-lg hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 transition-colors"
          >
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shrink-0" :style="{ background: a.color }">
              <AppIcon :name="a.icon" class="text-[11px]" />
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-2">
                <span class="text-[12px] font-bold truncate">{{ a.title }}</span>
                <span class="status-chip shrink-0" :class="a.chip">{{ a.tag }}</span>
              </div>
              <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5">{{ a.desc }}</p>
            </div>
          </div>
        </div>
        <p v-else class="text-[11.5px] text-[#9a9d97] dark:text-[#8f938a] text-center py-4">{{ $t("subscriptions_page.no_alerts_now") }}</p>
      </div>

      <div class="glass-card p-4">
        <h3 class="text-[13.5px] font-bold mb-3">{{ $t("subscriptions_page.latest_activity_title") }}</h3>
        <div v-if="activityLog.length" class="mt-1">
          <div v-for="(entry, i) in activityLog" :key="i" class="timeline-item" :style="{ '--dot-color': entry.color ?? '#52733D' }">
            <p class="text-[12px] font-semibold">{{ activityText(entry) }}</p>
            <span class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ subscriptionTimeAgo(entry.at) }}</span>
          </div>
        </div>
        <p v-else class="text-[11.5px] text-[#9a9d97] dark:text-[#8f938a] text-center py-4">
          {{ $t("subscriptions_page.no_activity_this_session") }}
        </p>
      </div>

      <div class="glass-card p-4">
        <h3 class="text-[13.5px] font-bold mb-3">{{ $t("subscriptions_page.expiring_soonest_title") }}</h3>
        <div v-if="topExpiring.length" class="space-y-2.5">
          <button
            v-for="sub in topExpiring"
            :key="sub.id"
            type="button"
            @click="openSubscriptionView(sub)"
            class="w-full flex items-center justify-between gap-2 text-start hover:bg-[#f4efe5]/50 dark:hover:bg-white/5 rounded-lg px-1.5 py-1 transition-colors"
          >
            <div class="min-w-0">
              <p class="text-[11.5px] font-bold truncate">{{ sub.subscriber?.name ?? "-" }}</p>
              <p class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]" dir="ltr">{{ sub.id }} · {{ sub.generator?.name ?? "-" }}</p>
            </div>
            <span class="text-[11px] font-extrabold shrink-0" :style="{ color: expiringColor(sub) }">{{ expiringLabel(sub) }}</span>
          </button>
        </div>
        <p v-else class="text-[11.5px] text-[#9a9d97] dark:text-[#8f938a] text-center py-4">
          {{ $t("subscriptions_page.no_subscriptions_nearing_expiry") }}
        </p>
      </div>
    </section>

    <!-- ===================== نافذة عرض التفاصيل (بنفس أسلوب نافذة عرض المولدات/المشتركين) ===================== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div
        v-if="viewingSubscription"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        @click.self="viewingSubscription = null"
      >
        <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-2xl max-h-[88vh] flex flex-col shadow-2xl overflow-hidden">
          <!-- Header -->
          <div class="relative shrink-0 px-5 py-4 bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white overflow-hidden">
            <div class="absolute inset-0 opacity-[0.08] pointer-events-none" style="background-image:radial-gradient(circle at 88% 15%, #fff 0%, transparent 40%)"></div>
            <div class="relative flex items-center justify-between gap-3">
              <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center text-[13px] font-bold shrink-0">
                  {{ initialsOf(viewingSubscription.subscriber?.name) }}
                </span>
                <div class="min-w-0">
                  <h3 class="text-[15px] font-extrabold truncate">{{ viewingSubscription.subscriber?.name ?? "-" }}</h3>
                  <p class="text-[11px] text-white/75 truncate">{{ viewingSubscription.generator?.name ?? "-" }}</p>
                </div>
              </div>
              <button :aria-label="$t('common.close')" type="button" @click="viewingSubscription = null" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center shrink-0 transition-colors"><X aria-hidden="true" /></button>
            </div>
          </div>

          <div class="p-5 grid sm:grid-cols-2 gap-4 overflow-y-auto">
            <div class="space-y-4">
              <div class="glass-card p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0" :style="{ background: (STATUS_META[viewingSubscription.status]?.color ?? '#9a9d97') + '1A' }">
                  <FilePenLine class="text-[16px]" aria-hidden="true" :style="{ color: STATUS_META[viewingSubscription.status]?.color ?? '#9a9d97' }" />
                </div>
                <div class="min-w-0">
                  <div class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] mb-1.5">{{ $t("subscriptions_page.subscription_status_label") }}</div>
                  <span class="status-chip" :class="statusChip(viewingSubscription.status)" :style="statusChipStyle(viewingSubscription.status)">{{ subscriptionStatusLabel(viewingSubscription.status) }}</span>
                </div>
              </div>

              <div class="glass-card p-4 space-y-1">
                <div class="info-row">
                  <span class="info-row-icon"><User aria-hidden="true" /></span>
                  <span class="info-row-label">{{ $t("subscribers_page.subscriber_col") }}</span>
                  <b class="info-row-value">{{ viewingSubscription.subscriber?.name ?? "-" }}</b>
                </div>
                <div class="info-row">
                  <span class="info-row-icon"><PlugZap aria-hidden="true" /></span>
                  <span class="info-row-label">{{ $t("dashboard.generator_col") }}</span>
                  <b class="info-row-value">{{ viewingSubscription.generator?.name ?? "-" }}</b>
                </div>
                <div class="info-row">
                  <span class="info-row-icon"><UserRound aria-hidden="true" /></span>
                  <span class="info-row-label">{{ $t("subscriptions_page.generator_owner_col") }}</span>
                  <b class="info-row-value">{{ viewingSubscription.generator?.owner_name ?? "-" }}</b>
                </div>
              </div>
            </div>

            <div class="space-y-4">
              <div class="grid grid-cols-3 gap-2.5">
                <div class="glass-card p-3 text-center">
                  <CalendarDays class="text-[#52733D] dark:text-[#8cc35a] text-[13px] mb-1" aria-hidden="true" />
                  <div class="text-sm font-extrabold">{{ formatDate(viewingSubscription.start_date) }}</div>
                  <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("subscriptions_page.starts_label") }}</div>
                </div>
                <div class="glass-card p-3 text-center">
                  <CalendarX class="text-[#D9534F] text-[13px] mb-1" aria-hidden="true" />
                  <div class="text-sm font-extrabold">{{ formatDate(viewingSubscription.end_date) }}</div>
                  <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("subscriptions_page.ends_label") }}</div>
                </div>
                <div class="glass-card p-3 text-center">
                  <CalendarPlus class="text-[#17A2B8] text-[13px] mb-1" aria-hidden="true" />
                  <div class="text-sm font-extrabold">{{ formatDate(viewingSubscription.created_at) }}</div>
                  <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("subscriptions_page.requested_label") }}</div>
                </div>
              </div>

              <div class="glass-card p-4">
                <div class="flex items-center justify-between gap-2 mb-2">
                  <h5 class="text-[12px] font-bold flex items-center gap-2"><StickyNote class="text-[#8A6D1F] text-[11px]" aria-hidden="true" /> {{ $t("subscriptions_page.notes_title") }}</h5>
                  <button v-if="!isEditingNotes" type="button" @click="startEditNotes" class="action-btn action-btn--view !w-6 !h-6" :title="$t('common.edit')"><Pencil class="text-[10px]" aria-hidden="true" /></button>
                </div>
                <p v-if="!isEditingNotes" class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5] whitespace-pre-line">
                  {{ viewingSubscription.notes || $t("subscriptions_page.notes_empty") }}
                </p>
                <div v-else class="space-y-2">
                  <textarea v-model="notesDraft" rows="3" maxlength="2000" class="field-input resize-none text-[12px]" :placeholder="$t('subscriptions_page.notes_placeholder')"></textarea>
                  <div class="flex items-center gap-2">
                    <button type="button" :disabled="updatingNotesId === viewingSubscription.id" class="btn-fill-brand !text-[11px] !py-2 !px-3 shrink-0" @click="saveNotes">
                      <LoaderCircle class="animate-spin" aria-hidden="true" v-if="updatingNotesId === viewingSubscription.id" /><Check aria-hidden="true" v-else />
                    </button>
                    <button type="button" class="btn-outline-brand !text-[11px] !py-2 !px-3 shrink-0" @click="isEditingNotes = false">
                      <X aria-hidden="true" />
                    </button>
                  </div>
                  <p v-if="notesUpdateError" class="text-[10.5px] text-[#D9534F]">{{ notesUpdateError }}</p>
                </div>
              </div>

              <div v-if="actionsFor(viewingSubscription).length" class="glass-card p-4">
                <h5 class="text-[12px] font-bold mb-2.5 flex items-center gap-2"><Zap class="text-[#8A6D1F] text-[11px]" aria-hidden="true" /> {{ $t("subscriptions_page.status_actions_title") }}</h5>
                <div class="flex gap-2 flex-wrap">
                  <button
                    v-for="action in actionsFor(viewingSubscription)"
                    :key="action.to"
                    type="button"
                    @click="handleStatusChange(viewingSubscription, action)"
                    :disabled="updatingStatusId === viewingSubscription.id"
                    class="flex-1 text-[12px] font-bold py-2 rounded-full border disabled:opacity-50 flex items-center justify-center gap-1.5"
                    :style="{ borderColor: action.color + '80', color: action.color }"
                  >
                    <AppIcon :name="updatingStatusId === viewingSubscription.id ? 'fa-spinner fa-spin' : action.icon" class="text-[11px]" />
                    {{ t(action.labelKey) }}
                  </button>
                </div>
              </div>

              <button type="button" @click="openTransfer(viewingSubscription)" class="glass-card p-4 w-full flex items-center gap-2.5 text-start hover:bg-[#f4efe5]/50 dark:hover:bg-white/5 transition-colors">
                <span class="w-9 h-9 rounded-lg bg-[#8A6D1F]/10 text-[#8A6D1F] flex items-center justify-center shrink-0"><ArrowRightLeft aria-hidden="true" /></span>
                <span class="text-[12px] font-bold">{{ $t("subscriptions_page.transfer_to_another_generator") }}</span>
              </button>
            </div>
          </div>

          <div class="flex items-center justify-end gap-2.5 px-5 py-4 border-t border-[#eee8da] dark:border-white/10 shrink-0 bg-[#f4efe5]/40 dark:bg-white/[0.02]">
            <button type="button" @click="viewingSubscription = null" class="text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-white dark:hover:bg-white/5 transition-colors">
              {{ $t("common.close") }}
            </button>
            <a
              :href="contractUrl(viewingSubscription)"
              target="_blank"
              rel="noopener"
              class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-5 py-2.5 rounded-full shadow-md flex items-center gap-2"
            >
              <FileText aria-hidden="true" />
              {{ $t("subscriptions_page.download_contract_button") }}
            </a>
          </div>
        </div>
      </div>
      </Transition>
    </Teleport>

    <TransferSubscriptionModal
      :open="transferModal.open"
      :subscription="transferModal.subscription"
      @close="transferModal.open = false"
      @transferred="handleTransferred"
    />

    <!-- ===================== نافذة إضافة اشتراك جديد ===================== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="isAddFormOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeAddModal">
        <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-2xl max-h-[90vh] flex flex-col shadow-2xl overflow-hidden">
          <!-- Header -->
          <div class="relative shrink-0 px-5 py-4 bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white overflow-hidden">
            <div class="absolute inset-0 opacity-[0.08] pointer-events-none" style="background-image:radial-gradient(circle at 88% 15%, #fff 0%, transparent 40%)"></div>
            <div class="relative flex items-center justify-between gap-3">
              <div class="flex items-center gap-3 min-w-0">
                <span class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center text-[15px] shrink-0">
                  <FilePlus aria-hidden="true" />
                </span>
                <div class="min-w-0">
                  <h3 class="text-[15px] font-extrabold truncate">{{ $t("subscriptions_page.add_new_subscription_title") }}</h3>
                  <p class="text-[11px] text-white/75 truncate">{{ $t("subscriptions_page.add_subscription_active_note") }}</p>
                </div>
              </div>
              <button :aria-label="$t('common.close')" type="button" @click="closeAddModal" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center shrink-0 transition-colors"><X aria-hidden="true" /></button>
            </div>
          </div>

          <form @submit.prevent="handleAddSubscription" class="p-5 space-y-3.5 overflow-y-auto">
            <div v-if="saveSubscriptionError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 border border-[#D9534F]/20 rounded-xl px-3.5 py-2.5 flex items-center gap-2">
              <CircleAlert class="shrink-0" aria-hidden="true" /> {{ saveSubscriptionError }}
            </div>

            <!-- Section: Subscriber -->
            <div class="form-section">
              <div class="form-section-head"><span class="form-section-icon"><User aria-hidden="true" /></span>{{ $t("subscribers_page.subscriber_col") }}</div>

              <div class="mt-3">
                <div v-if="!selectedSubscriber">
                  <div class="relative">
                    <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
                    <input
                      v-model="subscriberSearchTerm"
                      type="text"
                      @input="handleSubscriberSearchInput"
                      :placeholder="$t('subscriptions_page.search_by_name_email_phone_placeholder')"
                      class="field-input ps-8"
                    />
                  </div>
                  <div v-if="isSearchingSubscribers" class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] mt-2">{{ $t("common.searching") }}</div>
                  <div v-else-if="subscriberSearchTerm.trim().length >= 2 && subscriberSearchResults.length === 0" class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] mt-2">
                    {{ $t("subscriptions_page.no_results_create_subscriber_first") }}
                  </div>
                  <div v-else-if="subscriberSearchResults.length" class="mt-2 space-y-1.5 max-h-40 overflow-y-auto">
                    <button
                      v-for="s in subscriberSearchResults" :key="s.id" type="button"
                      @click="pickSubscriber(s)"
                      class="w-full text-start flex items-center gap-2.5 p-2 rounded-lg hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 transition-colors"
                    >
                      <span class="w-7 h-7 rounded-full bg-[#52733D]/10 text-[#52733D] dark:text-[#8cc35a] flex items-center justify-center text-[10px] font-bold shrink-0">{{ initialsOf(s.name) }}</span>
                      <span class="min-w-0">
                        <span class="block text-[11.5px] font-bold truncate">{{ s.name }}</span>
                        <span class="block text-[10px] text-[#9a9d97] dark:text-[#8f938a] truncate">{{ s.email }} · {{ s.phone }}</span>
                      </span>
                    </button>
                  </div>
                </div>
                <div v-else class="flex items-center gap-2.5 p-2.5 rounded-lg bg-[#EBF1E7] dark:bg-white/5">
                  <span class="w-8 h-8 rounded-full bg-[#52733D]/15 text-[#52733D] dark:text-[#8cc35a] flex items-center justify-center text-[11px] font-bold shrink-0">{{ initialsOf(selectedSubscriber.name) }}</span>
                  <span class="min-w-0 flex-1">
                    <span class="block text-[11.5px] font-bold truncate">{{ selectedSubscriber.name }}</span>
                    <span class="block text-[10px] text-[#9a9d97] dark:text-[#8f938a] truncate">{{ selectedSubscriber.email }} · {{ selectedSubscriber.phone }}</span>
                  </span>
                  <button type="button" @click="clearPickedSubscriber" class="text-[10.5px] font-bold text-[#D9534F] shrink-0">{{ $t("subscriptions_page.change_action") }}</button>
                </div>
              </div>
            </div>

            <!-- Section: Generator & Meter -->
            <div class="form-section" v-if="selectedSubscriber">
              <div class="form-section-head"><span class="form-section-icon" style="--ic1:#8A6D1F;--ic2:#D4AF37"><PlugZap aria-hidden="true" /></span>{{ $t("subscriptions_page.generator_pricing_section_title") }}</div>
              <div class="grid sm:grid-cols-2 gap-3.5">
                <div class="sm:col-span-2">
                  <label class="field-label">{{ $t("dashboard.generator_col") }}</label>
                  <AppDropdownSelect
                    v-model="subscriptionAddForm.generator_id"
                    :options="generatorFormOptions"
                    :disabled="isLoadingGeneratorsForForm"
                    :placeholder="isLoadingGeneratorsForForm ? $t('common.loading') : $t('subscriptions_page.select_generator_placeholder')"
                    variant="field"
                    width-class="w-full"
                    match-trigger-width
                  />
                </div>

                <div class="sm:col-span-2">
                  <label class="field-label">{{ $t("subscriptions_page.meter_col") }}</label>
                  <div v-if="isLoadingSubscriberMeters" class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("common.loading") }}</div>
                  <template v-else>
                    <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 w-fit mb-2">
                      <button
                        type="button" @click="meterMode = 'pick'" :disabled="!activeSubscriberMeterOptions.length"
                        class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors disabled:opacity-40"
                        :class="meterMode === 'pick' ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5]'"
                      >{{ $t("subscriptions_page.pick_existing_meter_tab") }}</button>
                      <button
                        type="button" @click="meterMode = 'new'"
                        class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
                        :class="meterMode === 'new' ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5]'"
                      >{{ $t("subscriptions_page.new_meter_tab") }}</button>
                    </div>

                    <template v-if="meterMode === 'pick'">
                      <AppDropdownSelect
                        v-model="subscriptionAddForm.subscriber_meter_id"
                        :options="activeSubscriberMeterOptions"
                        :placeholder="$t('subscriptions_page.select_meter_placeholder')"
                        variant="field" width-class="w-full" match-trigger-width
                      />
                      <p v-if="!activeSubscriberMeterOptions.length" class="text-[10.5px] text-[#D9534F] mt-1">{{ $t("subscriptions_page.no_active_meters_for_subscriber") }}</p>
                    </template>
                    <div v-else class="grid sm:grid-cols-2 gap-3">
                      <div>
                        <label class="field-label">{{ $t("subscriptions_page.meter_number_field_label") }}</label>
                        <input v-model="newMeterForm.meter_number" type="text" dir="ltr" class="field-input" />
                      </div>
                      <div>
                        <label class="field-label">{{ $t("subscriptions_page.property_label_field_label") }} <span class="text-[#9a9d97] font-normal">({{ $t("common.optional") }})</span></label>
                        <input v-model="newMeterForm.property_label" type="text" class="field-input" />
                      </div>
                    </div>
                    <p v-if="createMeterError" class="text-[10.5px] text-[#D9534F] mt-1.5">{{ createMeterError }}</p>
                  </template>
                </div>

                <div>
                  <label class="field-label">{{ $t("subscriptions_page.capacity_col") }} (kW) <span class="text-[#9a9d97] font-normal">({{ $t("common.optional") }})</span></label>
                  <input v-model="subscriptionAddForm.requested_capacity_kw" type="number" min="0" step="0.1" class="field-input" />
                </div>
              </div>
            </div>

            <!-- Section: Schedule & Billing -->
            <div class="form-section" v-if="selectedSubscriber">
              <div class="form-section-head"><span class="form-section-icon" style="--ic1:#17A2B8;--ic2:#0f6c7d"><CalendarDays aria-hidden="true" /></span>{{ $t("subscriptions_page.subscription_period_section_title") }}</div>
              <div class="grid sm:grid-cols-2 gap-3.5">
                <div>
                  <label class="field-label">{{ $t("subscriptions_page.schedule_col") }}</label>
                  <AppDropdownSelect
                    v-model="subscriptionAddForm.schedule"
                    :options="scheduleDropdownOptions"
                    :placeholder="$t('common.choose')"
                    variant="field" width-class="w-full" match-trigger-width
                  />
                </div>
                <div>
                  <label class="field-label">{{ $t("subscriptions_page.billing_cycle_col") }}</label>
                  <AppDropdownSelect
                    v-model="subscriptionAddForm.billing_cycle"
                    :options="billingCycleDropdownOptions"
                    :placeholder="$t('common.choose')"
                    variant="field" width-class="w-full" match-trigger-width
                  />
                </div>
                <template v-if="subscriptionAddForm.schedule === 'custom'">
                  <div>
                    <label class="field-label">{{ $t("owner_subscribers.service_start_time") }}</label>
                    <input v-model="subscriptionAddForm.service_start_time" type="time" class="field-input" />
                  </div>
                  <div>
                    <label class="field-label">{{ $t("owner_subscribers.service_end_time") }}</label>
                    <input v-model="subscriptionAddForm.service_end_time" type="time" class="field-input" />
                  </div>
                </template>
                <div>
                  <label class="field-label">{{ $t("subscriptions_page.start_date_field_label") }}</label>
                  <input v-model="subscriptionAddForm.start_date" type="date" class="field-input" />
                </div>
                <div>
                  <label class="field-label">{{ $t("subscriptions_page.end_date_col") }} <span class="text-[#9a9d97] font-normal">({{ $t("common.optional") }})</span></label>
                  <input v-model="subscriptionAddForm.end_date" type="date" class="field-input" />
                </div>
                <div class="sm:col-span-2">
                  <label class="field-label">{{ $t("subscriptions_page.contract_type_field_label") }} <span class="text-[#9a9d97] font-normal">({{ $t("common.optional") }})</span></label>
                  <input v-model="subscriptionAddForm.contract_type" type="text" class="field-input" />
                </div>
              </div>
            </div>
          </form>

          <div class="flex items-center justify-end gap-2.5 px-5 py-4 border-t border-[#eee8da] dark:border-white/10 shrink-0 bg-[#f4efe5]/40 dark:bg-white/[0.02]">
            <button type="button" @click="closeAddModal" class="text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-white dark:hover:bg-white/5 transition-colors">{{ $t("dashboard.cancel") }}</button>
            <button type="submit" @click="handleAddSubscription" :disabled="isSavingSubscription || isCreatingMeter" class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-5 py-2.5 rounded-full shadow-md flex items-center gap-2 disabled:opacity-60">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSavingSubscription || isCreatingMeter" /><Check aria-hidden="true" v-else />
              {{ $t("subscriptions_page.save_subscription_button") }}
            </button>
          </div>
        </div>
      </div>
      </Transition>
    </Teleport>

    </template>

    <!-- ==========================================================
         ============  تبويب: طلبات نقل العداد (مكرَّرة من واجهة المالك) ========
         ==========================================================
         FIX: subscription-meter-transfers/{id}/approve|reject موثَّقة صراحة
         بالباك اند (routes/api/v1.php) بأنها "تُراجَع من مالك المولد أو
         الأدمن"، لكن composables/useOwnerSubscriptionMeterTransfers.js (رغم
         اسمه) كان مستخدَمًا فقط بواجهة المالك — الأدمن ما كان عنده أي طريقة
         يوافق/يرفض بيها طلبات نقل العدادات رغم دعم الباك الكامل. نفس القالب
         والمنطق حرفيًا من owner/SubscribersView.vue. -->
    <template v-else-if="activeTab === 'transfers'">
      <section v-reveal class="glass-card p-5 lg:p-6">
        <h2 class="text-[15px] font-extrabold mb-1">{{ t("owner_subscribers.transfer_requests_title") }}</h2>
        <p class="text-[12px] text-[#777a74]">{{ t("owner_subscribers.transfer_requests_subtitle") }}</p>
      </section>

      <section v-reveal class="glass-card p-4">
        <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap w-fit">
          <button
            v-for="pill in TRANSFER_STATUS_PILLS" :key="pill.value" type="button"
            @click="transferStatusFilter = pill.value; onTransferFilterChange()"
            class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
            :class="transferStatusFilter === pill.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
          >{{ pill.label }}</button>
        </div>
      </section>

      <section v-reveal class="glass-card p-4 overflow-hidden">
        <div v-if="isLoadingTransfers" class="space-y-2">
          <div v-for="i in 4" :key="i" class="h-16 rounded-lg thumb-loading"></div>
        </div>
        <div v-else-if="transferLoadError" class="text-center py-8 text-[12px] text-[#D9534F]">{{ transferLoadError }}</div>
        <div v-else-if="!transferRequests.length" class="text-center py-10">
          <ZoomOut class="text-2xl text-[#c9cdc2] mb-2" aria-hidden="true" />
          <p class="text-[12px] text-[#9a9d97]">{{ t("owner_subscribers.transfer_no_matching_requests") }}</p>
        </div>
        <div v-else class="space-y-3">
          <div
            v-for="reqItem in transferRequests" :key="reqItem.id"
            class="rounded-xl border border-[#eee8da] dark:border-white/10 p-4"
            :class="{ 'opacity-50 pointer-events-none': isApprovingTransfer || isRejectingTransfer }"
          >
            <div class="flex items-start justify-between gap-3 flex-wrap">
              <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <h3 class="font-bold text-[13px]">{{ reqItem.subscriber_name ?? "—" }}</h3>
                  <span class="status-chip" :class="TRANSFER_STATUS_META[reqItem.status]?.chip">{{ transferStatusLabel(reqItem.status) }}</span>
                </div>
                <p class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] mt-0.5">{{ reqItem.generator_name ?? "—" }}</p>
              </div>
              <div class="flex items-center gap-3 text-[11.5px]">
                <span class="text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t("owner_subscribers.transfer_from_meter_label") }}: <b class="font-mono">{{ reqItem.from_meter?.meter_number ?? "—" }}</b></span>
                <ArrowRightLeft class="text-[10px] text-[#9a9d97]" aria-hidden="true" />
                <span class="text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t("owner_subscribers.transfer_to_meter_label") }}: <b class="font-mono">{{ reqItem.to_meter?.meter_number ?? "—" }}</b></span>
              </div>
            </div>

            <p v-if="reqItem.reason" class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/60 dark:bg-white/5 rounded-lg p-2.5 mt-3">
              <strong>{{ t("owner_subscribers.transfer_reason_label") }}:</strong> {{ reqItem.reason }}
            </p>
            <p v-if="reqItem.status === 'rejected' && reqItem.rejection_reason" class="text-[12px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg p-2.5 mt-2">
              <strong>{{ t("owner_subscribers.transfer_rejection_reason_label") }}:</strong> {{ reqItem.rejection_reason }}
            </p>

            <div v-if="reqItem.status === 'pending'" class="flex items-center gap-2 mt-3 pt-3 border-t border-[#eee8da] dark:border-white/10">
              <button
                type="button" @click="handleApproveTransfer(reqItem)" :disabled="isApprovingTransfer"
                class="flex-1 inline-flex items-center justify-center gap-2 py-2 rounded-lg text-[12px] font-semibold text-white bg-gradient-to-l from-[#3E582E] to-[#52733D] transition"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isApprovingTransfer" /><Check aria-hidden="true" v-else />
                {{ isApprovingTransfer ? t("owner_subscribers.transfer_approving_ellipsis") : t("owner_subscribers.transfer_approve_action") }}
              </button>
              <button
                type="button" @click="openRejectTransfer(reqItem)" :disabled="isRejectingTransfer"
                class="flex-1 py-2 rounded-lg text-[12px] font-semibold text-[#D9534F] border border-[#D9534F]/30 hover:bg-[#D9534F]/10 transition"
              >
                {{ t("owner_subscribers.transfer_reject_action") }}
              </button>
            </div>
          </div>
        </div>
      </section>

      <!-- ===================== نافذة رفض طلب النقل (بسبب مطلوب) ===================== -->
      <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
          <div v-if="rejectTransferTarget" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeRejectTransfer">
            <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden p-5">
              <h3 class="text-[14.5px] font-extrabold mb-1.5">{{ t("owner_subscribers.transfer_reject_modal_title") }}</h3>
              <p class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-3">{{ t("owner_subscribers.transfer_reject_modal_desc") }}</p>
              <textarea
                v-model="transferRejectReason"
                rows="4"
                required
                maxlength="500"
                :placeholder="t('owner_subscribers.transfer_reject_reason_placeholder')"
                class="w-full rounded-lg border border-[#e7e2d6] dark:border-white/10 bg-transparent px-3.5 py-2.5 text-[12.5px] resize-none outline-none focus:ring-2 focus:ring-[#D9534F]/20 focus:border-[#D9534F]"
              ></textarea>
              <p v-if="!transferRejectReason.trim()" class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] mt-1">{{ t("owner_subscribers.transfer_reject_reason_required_error") }}</p>
              <div class="flex items-center gap-2.5 mt-4">
                <button type="button" @click="closeRejectTransfer" class="flex-1 text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-white dark:hover:bg-white/5 transition-colors">
                  {{ t("owner_subscribers.transfer_reject_cancel_action") }}
                </button>
                <button
                  type="button" @click="handleRejectTransfer" :disabled="!transferRejectReason.trim() || isRejectingTransfer"
                  class="flex-1 text-[12.5px] font-bold px-4 py-2.5 rounded-full bg-[#D9534F] text-white shadow-md flex items-center justify-center gap-2 disabled:opacity-60"
                >
                  <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isRejectingTransfer" /><X aria-hidden="true" v-else />
                  {{ t("owner_subscribers.transfer_reject_confirm_action") }}
                </button>
              </div>
            </div>
          </div>
        </Transition>
      </Teleport>
    </template>
  </div>
</template>
