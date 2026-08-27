<script setup>
import { computed, onMounted, reactive, ref, watch } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { useConfirm } from "@/composables/useConfirm";
import subscriptionService from "@/services/subscriptionService";
import generatorService from "@/services/generatorService";
import userService from "@/services/userService";
import { useToastStore } from "@/stores/toast";
import { vReveal } from "@/directives/reveal";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { ArrowRightLeft, Bell, Check, ChevronLeft, ChevronRight, CircleAlert, Eye, FileSpreadsheet, Gauge, GripVertical, LoaderCircle, MessageCircleMore, Printer, Receipt, RotateCw, Search, Send, Table2, UserPlus, Users, X, ZoomOut } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const { t } = useI18n();
const { confirm } = useConfirm();
const toast = useToastStore();
const router = useRouter();

const subscriptions = ref([]);
const isLoading = ref(false);
const loadError = ref("");
const search = ref("");
const status = ref("");
const pagination = ref({ current_page: 1, last_page: 1, total: 0 });
const selected = ref(null);
const changingId = ref(null);
let searchTimer;

/* ---------------- عرض جدول/شبكة ---------------- */
const viewMode = ref("table");

/* ---------------- الفرز عبر رؤوس الأعمدة (أيقونة تصاعدي/تنازلي) ---------------- */
const SORT_FIELD_MAP = {
  subscriber: "subscriber_name",
  capacity: "requested_capacity_kw",
  price: "agreed_price_per_kw",
};
const sortBy = ref("");

function toggleSort(key) {
  const [curKey, curDir] = sortBy.value.split("-");
  const newDir = curKey === key && curDir === "desc" ? "asc" : "desc";
  sortBy.value = `${key}-${newDir}`;
  load(1);
}

function sortIconClass(key) {
  const [curKey, curDir] = sortBy.value.split("-");
  if (curKey !== key) return "opacity-40";
  return curDir === "desc" ? "opacity-100 text-[#8A6D1F] rotate-180" : "opacity-100 text-[#8A6D1F]";
}

/* ---------------- مولدات المالك (لفلتر إضافي ونقل الاشتراك) ---------------- */
const generators = ref([]);
const generatorFilter = ref("");
const isLoadingGenerators = ref(false);

async function loadGenerators() {
  isLoadingGenerators.value = true;
  try {
    const { data } = await generatorService.list({ per_page: 100 });
    const payload = data.data;
    generators.value = payload.data ?? payload;
  } catch {
    generators.value = [];
  } finally {
    isLoadingGenerators.value = false;
  }
}

const filters = computed(() => [
  { value: "", label: t("owner_subscribers.all") },
  { value: "pending", label: t("owner_subscribers.status.pending") },
  { value: "active", label: t("owner_subscribers.status.active") },
  { value: "suspended", label: t("owner_subscribers.status.suspended") },
]);

const summary = computed(() => ({
  total: pagination.value.total,
  pending: subscriptions.value.filter((item) => item.status === "pending").length,
  active: subscriptions.value.filter((item) => item.status === "active").length,
  suspended: subscriptions.value.filter((item) => item.status === "suspended").length,
}));

// إجمالي الإيراد الشهري التقريبي للاشتراكات النشطة المعروضة بالصفحة الحالية
// (تقدير: سعر الكيلوواط المتفق عليه فقط، بدون قراءة استهلاك فعلية)
const activeMonthlyRevenueEstimate = computed(() =>
  subscriptions.value
    .filter((item) => item.status === "active")
    .reduce((sum, item) => sum + Number(item.agreed_price_per_kw ?? 0), 0),
);

function fmtMoney(n) {
  return Number(n ?? 0).toLocaleString() + " ₪";
}
function pctOf(count) {
  return summary.value.total > 0 ? `${Math.round((count / summary.value.total) * 100)}%` : "—";
}

/* ---------------- بطاقات KPI (بنفس بنية بيانات لوحة تحكم الأدمن) ---------------- */
const KPI_CARDS = computed(() => [
  { icon: "fa-users", label: t("owner_subscribers.total"), value: summary.value.total, sub: t("owner_subscribers.kpi_all_statuses", "كل الحالات"), c1: "#52733D", c2: "#3E582E" },
  { icon: "fa-clock", label: t("owner_subscribers.status.pending"), value: summary.value.pending, sub: pctOf(summary.value.pending), c1: "#8A6D1F", c2: "#6b5417" },
  { icon: "fa-circle-check", label: t("owner_subscribers.status.active"), value: summary.value.active, sub: pctOf(summary.value.active), c1: "#28A745", c2: "#1f7a37" },
  { icon: "fa-pause-circle", label: t("owner_subscribers.status.suspended"), value: summary.value.suspended, sub: pctOf(summary.value.suspended), c1: "#17A2B8", c2: "#0f6c7d" },
  { icon: "fa-sack-dollar", label: t("owner_subscribers.active_revenue_estimate", "إيراد تقديري (هذه الصفحة)"), value: fmtMoney(activeMonthlyRevenueEstimate.value), sub: t("owner_subscribers.kpi_active_only", "اشتراكات نشطة فقط"), c1: "#D4AF37", c2: "#8A6D1F" },
]);

function unwrapPagination(payload) {
  const meta = payload.meta ?? payload;
  pagination.value = {
    current_page: meta.current_page ?? 1,
    last_page: meta.last_page ?? 1,
    total: meta.total ?? 0,
  };
  return payload.data ?? payload;
}

async function load(page = 1) {
  isLoading.value = true;
  loadError.value = "";
  try {
    const [sortKey, sortDir] = sortBy.value ? sortBy.value.split("-") : [null, null];
    const { data } = await subscriptionService.list({
      page,
      per_page: 15,
      search: search.value || undefined,
      status: status.value || undefined,
      generator_id: generatorFilter.value || undefined,
      sort_by: sortKey ? SORT_FIELD_MAP[sortKey] : undefined,
      sort_dir: sortKey ? sortDir : undefined,
    });
    subscriptions.value = unwrapPagination(data.data);
    selectedIds.value = [];
  } catch (error) {
    loadError.value = error.response?.data?.message ?? t("owner_subscribers.load_error");
  } finally {
    isLoading.value = false;
  }
}

function scheduleSearch() {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => load(1), 350);
}

function setFilter(value) {
  status.value = value;
  load(1);
}

function setGeneratorFilter(value) {
  generatorFilter.value = value;
  load(1);
}

function statusClass(value) {
  return {
    pending: "chip-warning",
    active: "chip-success",
    suspended: "chip-info",
    cancelled: "chip-neutral",
    rejected: "chip-danger",
  }[value] ?? "chip-neutral";
}

function formatPrice(item) {
  if (item.agreed_price_per_kw == null) return "—";
  return `${item.agreed_price_per_kw} ${item.currency ?? "₪"}`;
}

function statusLabel(value) {
  return t(`owner_subscribers.status.${value}`);
}

function actionsFor(item) {
  if (item.status === "pending") return [
    { value: "active", icon: "fa-check", class: "action-btn--view !text-[#28A745]", label: t("owner_subscribers.action_approve") },
    { value: "rejected", icon: "fa-xmark", class: "action-btn--delete", label: t("owner_subscribers.action_reject") },
  ];
  if (item.status === "active") return [
    { value: "suspended", icon: "fa-pause", class: "action-btn--edit !text-[#8A6D1F]", label: t("owner_subscribers.action_suspend") },
    { value: "cancelled", icon: "fa-ban", class: "action-btn--delete", label: t("owner_subscribers.action_cancel") },
  ];
  if (item.status === "suspended") return [
    { value: "active", icon: "fa-play", class: "action-btn--view !text-[#28A745]", label: t("owner_subscribers.action_activate") },
    { value: "cancelled", icon: "fa-ban", class: "action-btn--delete", label: t("owner_subscribers.action_cancel") },
  ];
  return [];
}

async function changeStatus(item, nextStatus) {
  const action = actionsFor(item).find((entry) => entry.value === nextStatus);
  const ok = await confirm({
    title: t("owner_subscribers.confirm_title", { action: action.label, name: item.subscriber?.name }),
    message: t("owner_subscribers.confirm_message", { name: item.subscriber?.name }),
    confirmLabel: action.label,
    variant: nextStatus === "cancelled" || nextStatus === "rejected" ? "danger" : "primary",
  });
  if (!ok) return;

  changingId.value = item.id;
  try {
    const { data } = await subscriptionService.updateStatus(item.id, nextStatus);
    const updated = data.data;
    const index = subscriptions.value.findIndex((entry) => entry.id === item.id);
    if (index !== -1) subscriptions.value[index] = updated;
    if (selected.value?.id === item.id) selected.value = updated;
    toast.show({ type: "success", title: t("owner_subscribers.status_updated_toast_title"), message: t("owner_subscribers.status_updated_toast_message", { name: updated.subscriber?.name, status: statusLabel(updated.status) }) });
  } catch (error) {
    toast.show({ type: "danger", title: t("owner_subscribers.status_update_failed_title"), message: error.response?.data?.message ?? t("owner_subscribers.status_update_failed_message") });
  } finally {
    changingId.value = null;
  }
}

/* ---------------- نقل الاشتراك لمولد آخر ---------------- */
const transferModal = ref({ open: false, subscription: null, generatorId: "" });
const isTransferring = ref(false);
const transferError = ref("");

async function openTransferModal(item) {
  transferError.value = "";
  transferModal.value = { open: true, subscription: item, generatorId: "" };
  if (generators.value.length === 0) await loadGenerators();
}

function closeTransferModal() {
  if (isTransferring.value) return;
  transferModal.value = { open: false, subscription: null, generatorId: "" };
}

const transferTargetOptions = computed(() =>
  generators.value.filter((g) => g.id !== transferModal.value.subscription?.generator?.id),
);

// خيارات AppDropdownSelect لمولدات النقل [{ value, label }]
const transferGeneratorDropdownOptions = computed(() =>
  transferTargetOptions.value.map((g) => ({ value: g.id, label: g.name })),
);

async function confirmTransfer() {
  const sub = transferModal.value.subscription;
  if (!sub || !transferModal.value.generatorId) return;

  isTransferring.value = true;
  transferError.value = "";
  try {
    const { data } = await subscriptionService.transferByOwner(sub.id, transferModal.value.generatorId);
    const updated = data.data ?? { ...sub, generator: generators.value.find((g) => g.id === transferModal.value.generatorId) };
    const index = subscriptions.value.findIndex((entry) => entry.id === sub.id);
    if (index !== -1) subscriptions.value[index] = updated;
    if (selected.value?.id === sub.id) selected.value = updated;

    toast.show({
      type: "success",
      title: t("owner_subscribers.transfer_success_title", "تم النقل"),
      message: t("owner_subscribers.transfer_success_message", { name: sub.subscriber?.name ?? "" }),
    });
    transferModal.value = { open: false, subscription: null, generatorId: "" };
  } catch (error) {
    transferError.value = error.response?.data?.message ?? t("owner_subscribers.transfer_error", "تعذّر نقل الاشتراك.");
  } finally {
    isTransferring.value = false;
  }
}

/* ---------------- تحديد جماعي + تذكير دفع جماعي ---------------- */
const selectedIds = ref([]);
const isSendingReminder = ref(false);

const allOnPageSelected = computed(
  () => subscriptions.value.length > 0 && selectedIds.value.length === subscriptions.value.length,
);

function toggleSelectAll() {
  selectedIds.value = allOnPageSelected.value ? [] : subscriptions.value.map((s) => s.id);
}

function toggleSelectOne(id) {
  const idx = selectedIds.value.indexOf(id);
  if (idx === -1) selectedIds.value.push(id);
  else selectedIds.value.splice(idx, 1);
}

async function sendBulkReminder() {
  const targets = subscriptions.value.filter((s) => selectedIds.value.includes(s.id));
  const subscriberIds = [...new Set(targets.map((s) => s.subscriber?.id).filter(Boolean))];
  if (subscriberIds.length === 0) return;

  const confirmed = await confirm({
    title: t("owner_subscribers.bulk_reminder_title", "تذكير دفع جماعي"),
    message: t("owner_subscribers.bulk_reminder_message", { count: subscriberIds.length }),
    confirmLabel: t("owner_subscribers.bulk_reminder_confirm", "إرسال التذكير"),
  });
  if (!confirmed) return;

  isSendingReminder.value = true;
  try {
    await userService.sendOwnerBulkPaymentReminder(subscriberIds);
    toast.show({
      type: "success",
      title: t("owner_subscribers.bulk_reminder_sent_title", "تم الإرسال"),
      message: t("owner_subscribers.bulk_reminder_sent_message", "تم إرسال تذكير الدفع بنجاح."),
    });
    selectedIds.value = [];
  } catch (error) {
    toast.show({
      type: "danger",
      title: t("owner_subscribers.bulk_reminder_failed_title", "تعذّر الإرسال"),
      message: error.response?.data?.message ?? t("owner_subscribers.bulk_reminder_failed_message", "تعذّر إرسال التذكير، حاول مرة أخرى."),
    });
  } finally {
    isSendingReminder.value = false;
  }
}

/* ---------------- تصدير Excel (CSV) للصفحة المعروضة حاليًا ---------------- */
function exportCsv() {
  const headers = [
    t("owner_subscribers.subscriber_col"),
    t("owner_subscribers.generator_meter_col"),
    t("owner_subscribers.price_col"),
    t("owner_subscribers.status_col"),
  ];
  const rows = subscriptions.value.map((item) => [
    item.subscriber?.name ?? "",
    item.generator?.name ?? "",
    item.agreed_price_per_kw ?? "",
    statusLabel(item.status),
  ]);
  const csv = [headers, ...rows]
    .map((row) => row.map((cell) => `"${String(cell).replaceAll('"', '""')}"`).join(","))
    .join("\n");
  const blob = new Blob(["\uFEFF" + csv], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = url;
  link.download = "subscribers.csv";
  link.click();
  URL.revokeObjectURL(url);
}

function printPage() {
  window.print();
}

/* ---------------- روابط سريعة (مراسلة / فواتير / قراءات) ---------------- */
function messageSubscriber(item) {
  if (!item.subscriber?.id) return;
  router.push({ name: "owner.messages", query: { startUserId: item.subscriber.id } });
}

function goToInvoices(item) {
  router.push({ name: "owner.invoices", query: { search: item.subscriber?.name ?? "" } });
}

function goToReadings(item) {
  router.push({ name: "owner.meter-readings", query: { search: item.subscriber?.name ?? "" } });
}

function goToComplaints(item) {
  router.push({ name: "owner.complaints", query: { search: item.subscriber?.name ?? "" } });
}

/* ==================================================================
   إضافة اشتراك جديد لمشترك موجود على أحد مولدات المالك الحالي.
   ==================================================================
   يستخدم مسارات مخصَّصة لمالك المولد (لا الإدارية/الذاتية القديمة التي كانت
   ترجع 403 دائمًا لهذا الدور):
   - بحث المشترك: GET /owner/subscriber-lookup (userService.ownerSubscriberLookup)
   - عدادات المشترك: GET /owner/subscriber-lookup/{user}/meters (userService.ownerSubscriberMeters)
   - إنشاء الاشتراك: POST /owner/subscriptions (subscriptionService.createByOwner)
   ⚠️ إنشاء حساب مشترك جديد بالكامل من طرف المالك (وليس اختيار مشترك موجود)
   ميزة أكبر منفصلة تحتاج Action/Policy مخصَّصة بالـ Backend لم تُطلب ضمن هذه
   المهمة — لذلك اقتصرت هذه النافذة على ربط اشتراك بمشترك موجود بالفعل،
   بنفس نمط الفجوة الموثَّقة مسبقًا بنافذة "إضافة اشتراك" لصفحة الأدمن.
   ================================================================== */
const SCHEDULE_OPTIONS = ["day", "night", "24h", "custom"];
const BILLING_CYCLE_OPTIONS = ["daily", "weekly", "monthly"];

const addSubscriptionModal = ref(false);

const subscriberSearch = ref("");
const subscriberResults = ref([]);
const isSearchingSubscribers = ref(false);
const selectedSubscriber = ref(null);
let subscriberSearchTimer;

const subscriberMeters = ref([]);
const isLoadingSubscriberMeters = ref(false);

const subscriptionForm = reactive({
  generator_id: "",
  subscriber_meter_id: "",
  requested_capacity_kw: null,
  schedule: "",
  billing_cycle: "",
  service_start_time: "",
  service_end_time: "",
  contract_type: "",
  start_date: "",
  end_date: "",
});

const isCreatingSubscription = ref(false);
const createSubscriptionError = ref("");

// خيارات دروب-داون: المولد / نمط الجدولة / دورة الفوترة / عدادات المشترك المختار
// (لموحّد AppDropdownSelect)
const generatorDropdownOptions = computed(() =>
  generators.value.map((g) => ({ value: g.id, label: g.name })),
);
const scheduleDropdownOptions = computed(() =>
  SCHEDULE_OPTIONS.map((opt) => ({ value: opt, label: t(`owner_subscribers.schedule.${opt}`, opt) })),
);
const billingCycleDropdownOptions = computed(() =>
  BILLING_CYCLE_OPTIONS.map((opt) => ({ value: opt, label: t(`owner_subscribers.cycle.${opt}`, opt) })),
);
const subscriberMeterDropdownOptions = computed(() =>
  subscriberMeters.value
    .filter((m) => m.status === "active")
    .map((m) => ({
      value: m.id,
      label: m.property_label ? `${m.meter_number} — ${m.property_label}` : m.meter_number,
    })),
);

function resetAddSubscriptionModal() {
  subscriberSearch.value = "";
  subscriberResults.value = [];
  selectedSubscriber.value = null;
  subscriberMeters.value = [];
  subscriptionForm.generator_id = "";
  subscriptionForm.subscriber_meter_id = "";
  subscriptionForm.requested_capacity_kw = null;
  subscriptionForm.schedule = "";
  subscriptionForm.billing_cycle = "";
  subscriptionForm.service_start_time = "";
  subscriptionForm.service_end_time = "";
  subscriptionForm.contract_type = "";
  subscriptionForm.start_date = "";
  subscriptionForm.end_date = "";
  createSubscriptionError.value = "";
}

async function openAddSubscriptionModal() {
  resetAddSubscriptionModal();
  if (generators.value.length === 0) await loadGenerators();
  addSubscriptionModal.value = true;
}

function closeAddSubscriptionModal() {
  if (isCreatingSubscription.value) return;
  addSubscriptionModal.value = false;
}

function scheduleSubscriberSearch() {
  clearTimeout(subscriberSearchTimer);
  subscriberSearchTimer = setTimeout(searchSubscribers, 350);
}

async function searchSubscribers() {
  if (!subscriberSearch.value.trim()) {
    subscriberResults.value = [];
    return;
  }
  isSearchingSubscribers.value = true;
  try {
    const { data } = await userService.ownerSubscriberLookup(subscriberSearch.value.trim());
    subscriberResults.value = data.data ?? [];
  } catch {
    subscriberResults.value = [];
  } finally {
    isSearchingSubscribers.value = false;
  }
}

async function pickSubscriber(subscriber) {
  selectedSubscriber.value = subscriber;
  subscriberResults.value = [];
  subscriptionForm.subscriber_meter_id = "";

  isLoadingSubscriberMeters.value = true;
  try {
    const { data } = await userService.ownerSubscriberMeters(subscriber.id);
    subscriberMeters.value = data.data ?? [];
  } catch {
    subscriberMeters.value = [];
  } finally {
    isLoadingSubscriberMeters.value = false;
  }
}

function clearPickedSubscriber() {
  selectedSubscriber.value = null;
  subscriberSearch.value = "";
  subscriberMeters.value = [];
  subscriptionForm.subscriber_meter_id = "";
}

const canSubmitSubscription = computed(() => {
  const scheduleOk =
    subscriptionForm.schedule &&
    (subscriptionForm.schedule !== "custom" ||
      (subscriptionForm.service_start_time && subscriptionForm.service_end_time));

  return Boolean(
    selectedSubscriber.value &&
      subscriptionForm.generator_id &&
      subscriptionForm.subscriber_meter_id &&
      subscriptionForm.billing_cycle &&
      subscriptionForm.start_date &&
      scheduleOk,
  );
});

async function handleCreateSubscription() {
  if (!canSubmitSubscription.value || isCreatingSubscription.value) return;
  isCreatingSubscription.value = true;
  createSubscriptionError.value = "";
  try {
    const { data } = await subscriptionService.createByOwner({
      generator_id: subscriptionForm.generator_id,
      subscriber_meter_id: subscriptionForm.subscriber_meter_id,
      requested_capacity_kw: subscriptionForm.requested_capacity_kw || undefined,
      schedule: subscriptionForm.schedule,
      billing_cycle: subscriptionForm.billing_cycle,
      service_start_time: subscriptionForm.schedule === "custom" ? subscriptionForm.service_start_time : undefined,
      service_end_time: subscriptionForm.schedule === "custom" ? subscriptionForm.service_end_time : undefined,
      contract_type: subscriptionForm.contract_type || undefined,
      start_date: subscriptionForm.start_date,
      end_date: subscriptionForm.end_date || undefined,
    });

    const created = data.data ?? data;
    subscriptions.value.unshift(created);
    pagination.value.total += 1;

    toast.show({
      type: "success",
      title: t("owner_subscribers.subscription_created_toast_title", "تم إنشاء الاشتراك"),
      message: t("owner_subscribers.subscription_created_toast_message", "تم إنشاء الاشتراك بنجاح."),
    });
    addSubscriptionModal.value = false;
  } catch (error) {
    createSubscriptionError.value =
      error.response?.data?.message ?? t("owner_subscribers.subscription_create_failed", "تعذّر إنشاء الاشتراك.");
  } finally {
    isCreatingSubscription.value = false;
  }
}

watch(search, scheduleSearch);
onMounted(() => {
  load();
  loadGenerators();
});
</script>

<template>
  <div class="space-y-5">
    <!-- ===== HEADER ===== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-64 h-64 rounded-full bg-[#D4AF37]/15 dark:bg-[#D4AF37]/20 blur-[90px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-64 h-64 rounded-full bg-[#52733D]/15 dark:bg-[#8cc35a]/10 blur-[90px] pointer-events-none"></div>
      <div class="relative flex flex-col lg:flex-row lg:items-end justify-between gap-4">
        <div>
          <p class="text-[11px] font-bold tracking-wide text-[#8A6D1F] mb-1">{{ $t("owner_subscribers.eyebrow") }}</p>
          <h1 class="text-xl lg:text-2xl font-extrabold flex items-center gap-2.5"><span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white grid place-items-center"><Users aria-hidden="true" /></span>{{ $t("owner_subscribers.title") }}</h1>
          <p class="text-[12px] text-[#777a74] mt-2">{{ $t("owner_subscribers.subtitle") }}</p>
        </div>
        <div class="flex flex-wrap gap-2.5">
          <button type="button" @click="openAddSubscriptionModal" class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md flex items-center gap-2">
            <UserPlus aria-hidden="true" />{{ t("owner_subscribers.new_subscription_button", "إضافة مشترك") }}
          </button>
          <button type="button" @click="load(pagination.current_page)" :disabled="isLoading" class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2">
            <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isLoading" /><RotateCw aria-hidden="true" v-else />{{ $t("owner_subscribers.refresh") }}
          </button>
        </div>
      </div>
    </section>

    <!-- ===== KPI CARDS (بنفس هوية لوحة تحكم الأدمن: .kpi-card / .kpi-icon) ===== -->
    <section v-reveal>
      <div v-if="isLoading && !subscriptions.length" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3.5">
        <div v-for="i in 5" :key="i" class="h-24 rounded-2xl thumb-loading"></div>
      </div>
      <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-3.5">
        <div v-for="c in KPI_CARDS" :key="c.label" class="kpi-card glass-card hoverable" :style="{ '--kpi-color': c.c1, '--kpi-color2': c.c2 }">
          <div class="kpi-icon mb-2.5"><AppIcon :name="c.icon" /></div>
          <div class="text-lg font-extrabold" dir="ltr">{{ c.value }}</div>
          <div class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5">{{ c.label }}</div>
          <div class="text-[10px] text-[#9a9d97] mt-1.5">{{ c.sub }}</div>
        </div>
      </div>
    </section>

    <!-- ===== TOOLBAR ===== -->
    <section v-reveal class="glass-card p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[220px]">
          <div class="relative flex-1 min-w-[160px] max-w-xs">
            <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] text-[10px]" aria-hidden="true" />
            <input
              v-model="search" type="text"
              :placeholder="$t('owner_subscribers.search_placeholder')"
              class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F]"
            />
          </div>
          <AppDropdownSelect
            v-if="generators.length > 1"
            :model-value="generatorFilter"
            @update:model-value="setGeneratorFilter"
            :options="[{ value: '', label: t('owner_subscribers.all_generators', 'كل المولدات') }, ...generatorDropdownOptions]"
            width-class="w-40"
          />
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
            <button
              v-for="filter in filters" :key="filter.value" type="button"
              @click="setFilter(filter.value)"
              class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
              :class="status === filter.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
            >{{ filter.label }}</button>
          </div>
          <div class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1">
            <button :aria-label="$t('common.view_as_table')" type="button" @click="viewMode = 'table'" class="icon-btn !w-8 !h-8" :class="{ '!bg-white dark:!bg-white/10': viewMode === 'table' }"><Table2 class="text-[11px]" aria-hidden="true" /></button>
            <button :aria-label="$t('common.view_as_grid')" type="button" @click="viewMode = 'grid'" class="icon-btn !w-8 !h-8" :class="{ '!bg-white dark:!bg-white/10': viewMode === 'grid' }"><GripVertical class="text-[11px]" aria-hidden="true" /></button>
          </div>
          <button
            type="button"
            @click="exportCsv"
            :disabled="!subscriptions.length"
            class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5 disabled:opacity-40"
            :title="t('owner_subscribers.export_excel', 'تصدير Excel')"
          >
            <FileSpreadsheet class="text-[11px]" aria-hidden="true" />
          </button>
          <button
            type="button"
            @click="printPage"
            class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
            :title="t('owner_applications_page.print')"
          >
            <Printer class="text-[11px]" aria-hidden="true" />
          </button>
        </div>
      </div>

      <!-- ===== شريط إجراء جماعي ===== -->
      <div v-if="selectedIds.length" class="mt-3 pt-3 border-t border-[#eee8da] dark:border-white/10 flex items-center justify-between gap-3 flex-wrap">
        <span class="text-[11.5px] font-bold text-[#3a3a38] dark:text-[#eef0ec]">{{ selectedIds.length }} {{ t("owner_subscribers.selected_suffix", "محدَّد") }}</span>
        <button type="button" @click="sendBulkReminder" :disabled="isSendingReminder" class="btn-fill-brand !py-1.5 !px-3 !text-[11px]">
          <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSendingReminder" /><Bell aria-hidden="true" v-else />
          {{ isSendingReminder ? t("owner_subscribers.sending_ellipsis", "جارٍ الإرسال...") : t("owner_subscribers.bulk_reminder_button", "إرسال تذكير دفع") }}
        </button>
      </div>
    </section>

    <!-- ===== TABLE (عرض كامل مطابق لجدول إدارة المولدات) ===== -->
    <section v-reveal class="glass-card p-4 overflow-hidden">
      <div v-if="isLoading" class="space-y-2">
        <div v-for="i in 6" :key="i" class="h-12 rounded-lg thumb-loading"></div>
      </div>
      <div v-else-if="loadError" class="text-center py-8 text-[12px] text-[#D9534F]">{{ loadError }}</div>
      <div v-else-if="!subscriptions.length" class="text-center py-10">
        <ZoomOut class="text-2xl text-[#c9cdc2] mb-2" aria-hidden="true" />
        <p class="text-[12px] text-[#9a9d97]">{{ $t("owner_subscribers.no_matching_subscribers") }}</p>
      </div>

      <template v-else>
        <div v-if="viewMode === 'table'" class="overflow-x-auto -mx-1">
          <table class="data-table w-full text-[12px] min-w-[1020px]">
            <thead>
              <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
                <th class="py-2.5 px-3 rounded-s-lg w-8"><input type="checkbox" :checked="allOnPageSelected" @change="toggleSelectAll" class="accent-[#52733D]" /></th>
                <th class="py-2.5 px-3 cursor-pointer select-none" @click="toggleSort('subscriber')">
                  {{ $t("owner_subscribers.subscriber_col") }}
                  <AppIcon :name="sortIconClass('subscriber')" class="text-[9px] ms-1 transition-all" />
                </th>
                <th class="py-2.5 px-3">{{ $t("owner_subscribers.generator_meter_col") }}</th>
                <th class="py-2.5 px-3 cursor-pointer select-none" @click="toggleSort('capacity')">
                  {{ $t("owner_subscribers.capacity_col") }}
                  <AppIcon :name="sortIconClass('capacity')" class="text-[9px] ms-1 transition-all" />
                </th>
                <th class="py-2.5 px-3 cursor-pointer select-none" @click="toggleSort('price')">
                  {{ $t("owner_subscribers.price_col") }}
                  <AppIcon :name="sortIconClass('price')" class="text-[9px] ms-1 transition-all" />
                </th>
                <th class="py-2.5 px-3">{{ $t("owner_subscribers.status_col") }}</th>
                <th class="py-2.5 px-3 rounded-e-lg">{{ $t("owner_subscribers.actions_col") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in subscriptions" :key="item.id" class="border-b border-[#f0ece0] dark:border-white/5 last:border-0 text-center">
                <td class="py-2.5 px-3"><input type="checkbox" :checked="selectedIds.includes(item.id)" @change="toggleSelectOne(item.id)" class="accent-[#52733D]" /></td>
                <td class="py-2.5 px-3">
                  <button type="button" @click="selected = item" class="flex items-center gap-2.5 mx-auto">
                    <span class="w-8 h-8 rounded-full bg-[#e3eddf] dark:bg-[#52733D]/20 text-[#52733D] dark:text-[#8cc35a] grid place-items-center font-bold shrink-0">{{ item.subscriber?.name?.slice(0, 1) }}</span>
                    <span class="text-start">
                      <span class="font-bold truncate max-w-[9rem] block">{{ item.subscriber?.name }}</span>
                      <span class="text-[10px] text-[#9a9d97]">{{ item.subscriber?.neighborhood || '—' }}</span>
                    </span>
                  </button>
                </td>
                <td class="py-2.5 px-3">
                  <div class="text-center">
                    <div class="font-bold truncate max-w-[9rem] mx-auto">{{ item.generator?.name }}</div>
                    <div class="text-[10px] text-[#9a9d97]">{{ $t("owner_subscribers.meter_hash", { number: item.subscriber_meter?.meter_number }) }}</div>
                  </div>
                </td>
                <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">{{ item.requested_capacity_kw ? `${item.requested_capacity_kw} kW` : '—' }}</td>
                <td class="py-2.5 px-3 font-bold">{{ formatPrice(item) }}</td>
                <td class="py-2.5 px-3"><span class="status-chip" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span></td>
                <td class="py-2.5 px-3">
                  <div class="row-actions">
                    <button type="button" @click="selected = item" class="action-btn action-btn--view" :title="$t('owner_subscribers.view_details')"><Eye aria-hidden="true" /></button>
                    <button type="button" @click="messageSubscriber(item)" class="action-btn action-btn--edit !text-[#17A2B8]" :title="t('owner_subscribers.message_action', 'مراسلة')"><MessageCircleMore aria-hidden="true" /></button>
                    <button type="button" @click="goToInvoices(item)" class="action-btn action-btn--edit !text-[#8A6D1F]" :title="t('owner_subscribers.invoices_action', 'فواتير المشترك')"><Receipt aria-hidden="true" /></button>
                    <button type="button" @click="goToReadings(item)" class="action-btn action-btn--edit !text-[#8A6D1F]" :title="t('owner_subscribers.readings_action', 'قراءات المشترك')"><Gauge aria-hidden="true" /></button>
                    <template v-if="item.status === 'active'">
                      <span class="row-actions-divider"></span>
                      <button type="button" @click="openTransferModal(item)" class="action-btn action-btn--view !text-[#3E582E]" :title="t('owner_subscribers.transfer_action', 'نقل لمولد آخر')"><ArrowRightLeft aria-hidden="true" /></button>
                    </template>
                    <template v-if="actionsFor(item).length">
                      <span class="row-actions-divider"></span>
                      <button
                        v-for="action in actionsFor(item)" :key="action.value"
                        type="button" @click="changeStatus(item, action.value)" :disabled="changingId === item.id"
                        class="action-btn" :class="action.class" :title="action.label"
                      ><AppIcon :name="changingId === item.id ? 'fa-spinner fa-spin' : action.icon" /></button>
                    </template>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
          <div v-for="item in subscriptions" :key="item.id" class="glass-card p-3.5">
            <div class="flex items-start justify-between mb-2.5 gap-2">
              <label class="flex items-start gap-2.5 min-w-0 cursor-pointer">
                <input type="checkbox" :checked="selectedIds.includes(item.id)" @change="toggleSelectOne(item.id)" class="accent-[#52733D] mt-1 shrink-0" />
                <span class="min-w-0">
                  <span class="font-bold text-[12.5px] truncate block">{{ item.subscriber?.name }}</span>
                  <span class="text-[10px] text-[#9a9d97] truncate block">{{ item.generator?.name }} · {{ $t("owner_subscribers.meter_hash", { number: item.subscriber_meter?.meter_number }) }}</span>
                </span>
              </label>
              <span class="status-chip shrink-0" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-[11px] mb-2.5">
              <div><span class="text-[#9a9d97]">{{ $t("owner_subscribers.capacity_col") }}:</span> <b>{{ item.requested_capacity_kw ? `${item.requested_capacity_kw} kW` : '—' }}</b></div>
              <div><span class="text-[#9a9d97]">{{ $t("owner_subscribers.price_col") }}:</span> <b>{{ formatPrice(item) }}</b></div>
            </div>
            <div class="flex items-center justify-between gap-2">
              <span class="text-[10px] text-[#9a9d97] truncate">{{ item.subscriber?.neighborhood || '—' }}</span>
              <div class="row-actions">
                <button type="button" @click="selected = item" class="action-btn action-btn--view" :title="$t('owner_subscribers.view_details')"><Eye aria-hidden="true" /></button>
                <button type="button" @click="messageSubscriber(item)" class="action-btn action-btn--edit !text-[#17A2B8]" :title="t('owner_subscribers.message_action', 'مراسلة')"><MessageCircleMore aria-hidden="true" /></button>
                <button type="button" @click="goToInvoices(item)" class="action-btn action-btn--edit !text-[#8A6D1F]" :title="t('owner_subscribers.invoices_action', 'فواتير المشترك')"><Receipt aria-hidden="true" /></button>
                <button type="button" @click="goToReadings(item)" class="action-btn action-btn--edit !text-[#8A6D1F]" :title="t('owner_subscribers.readings_action', 'قراءات المشترك')"><Gauge aria-hidden="true" /></button>
                <template v-if="item.status === 'active'">
                  <span class="row-actions-divider"></span>
                  <button type="button" @click="openTransferModal(item)" class="action-btn action-btn--view !text-[#3E582E]" :title="t('owner_subscribers.transfer_action', 'نقل لمولد آخر')"><ArrowRightLeft aria-hidden="true" /></button>
                </template>
                <template v-if="actionsFor(item).length">
                  <span class="row-actions-divider"></span>
                  <button
                    v-for="action in actionsFor(item)" :key="action.value"
                    type="button" @click="changeStatus(item, action.value)" :disabled="changingId === item.id"
                    class="action-btn" :class="action.class" :title="action.label"
                  ><AppIcon :name="changingId === item.id ? 'fa-spinner fa-spin' : action.icon" /></button>
                </template>
              </div>
            </div>
          </div>
        </div>
      </template>

      <div v-if="pagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-2 mt-3 text-[11px] text-[#9a9d97]">
        <span>{{ $t("owner_subscribers.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}</span>
        <div class="flex items-center gap-1">
          <button :aria-label="$t('common.previous_page')" type="button" :disabled="pagination.current_page <= 1" @click="load(pagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronRight class="text-[10px]" aria-hidden="true" /></button>
          <button :aria-label="t('common.next_page')" type="button" :disabled="pagination.current_page >= pagination.last_page" @click="load(pagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronLeft class="text-[10px]" aria-hidden="true" /></button>
        </div>
      </div>
    </section>

    <!-- ===== نافذة تفاصيل المشترك ===== -->
    <Teleport to="body">
      <div v-if="selected" class="fixed inset-0 z-[100] bg-black/45 backdrop-blur-sm p-4 flex items-center justify-center" @click.self="selected = null">
        <section class="glass-card !bg-white dark:!bg-[#1c1e20] max-w-md w-full overflow-hidden max-h-[90vh] overflow-y-auto">
          <header class="p-5 bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white flex justify-between items-center sticky top-0">
            <div>
              <h2 class="font-extrabold">{{ selected.subscriber?.name }}</h2>
              <p class="text-[11px] text-white/75">{{ selected.generator?.name }}</p>
            </div>
            <button :aria-label="t('common.close')" type="button" @click="selected = null" class="w-8 h-8 rounded-lg bg-white/10"><X aria-hidden="true" /></button>
          </header>

          <div class="p-5 space-y-3 text-[12px]">
            <div class="flex justify-between gap-4"><span class="text-[#858983]">{{ $t("users_page.phone_label") }}</span><b dir="ltr">{{ selected.subscriber?.phone || '—' }}</b></div>
            <div class="flex justify-between gap-4"><span class="text-[#858983]">{{ $t("users_page.email_label") }}</span><b dir="ltr" class="truncate">{{ selected.subscriber?.email || '—' }}</b></div>
            <div class="flex justify-between gap-4"><span class="text-[#858983]">{{ $t("owner_subscribers.meter_label") }}</span><b>{{ selected.subscriber_meter?.meter_number }}</b></div>
            <div class="flex justify-between gap-4"><span class="text-[#858983]">{{ t("owner_subscribers.subscribed_since", "تاريخ الاشتراك") }}</span><b dir="ltr">{{ (selected.starts_at ?? selected.created_at)?.slice(0, 10) || '—' }}</b></div>
            <div class="flex justify-between gap-4"><span class="text-[#858983]">{{ $t("owner_subscribers.schedule_col") }}</span><b>{{ $t(`owner_subscribers.schedule.${selected.schedule}`) }}</b></div>
            <div class="flex justify-between gap-4"><span class="text-[#858983]">{{ $t("owner_subscribers.billing_cycle_col") }}</span><b>{{ $t(`owner_subscribers.cycle.${selected.billing_cycle}`) }}</b></div>
            <div class="flex justify-between gap-4"><span class="text-[#858983]">{{ $t("owner_subscribers.status_col") }}</span><span class="status-chip" :class="statusClass(selected.status)">{{ statusLabel(selected.status) }}</span></div>
          </div>

          <!-- روابط سريعة بدل تضمين سجلات كاملة (تُفتح مفلترة باسم المشترك) -->
          <div class="px-5 pb-5 grid grid-cols-2 gap-2">
            <button type="button" @click="goToInvoices(selected)" class="btn-outline-brand !text-[11px] !py-2 justify-center"><Receipt aria-hidden="true" />{{ t("owner_subscribers.view_invoices", "سجل الفواتير") }}</button>
            <button type="button" @click="goToReadings(selected)" class="btn-outline-brand !text-[11px] !py-2 justify-center"><Gauge aria-hidden="true" />{{ t("owner_subscribers.view_readings", "آخر القراءات") }}</button>
            <button type="button" @click="goToComplaints(selected)" class="btn-outline-brand !text-[11px] !py-2 justify-center"><MessageCircleMore aria-hidden="true" />{{ t("owner_subscribers.view_complaints", "الشكاوى المرتبطة") }}</button>
            <button type="button" @click="messageSubscriber(selected)" class="btn-outline-brand !text-[11px] !py-2 justify-center"><Send aria-hidden="true" />{{ t("owner_subscribers.message_action", "مراسلة") }}</button>
          </div>

          <div v-if="selected.status === 'active'" class="px-5 pb-5">
            <button type="button" @click="openTransferModal(selected)" class="btn-fill-brand w-full justify-center !text-[11.5px]">
              <ArrowRightLeft aria-hidden="true" />{{ t("owner_subscribers.transfer_action", "نقل لمولد آخر") }}
            </button>
          </div>
        </section>
      </div>
    </Teleport>

    <!-- ===== نافذة نقل الاشتراك ===== -->
    <Teleport to="body">
      <div v-if="transferModal.open" class="fixed inset-0 z-[110] bg-black/45 backdrop-blur-sm p-4 flex items-center justify-center" @click.self="closeTransferModal">
        <section class="glass-card !bg-white dark:!bg-[#1c1e20] max-w-sm w-full overflow-hidden">
          <header class="p-5 bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white flex justify-between items-center">
            <h2 class="font-extrabold text-[13.5px]">{{ t("owner_subscribers.transfer_modal_title", "نقل الاشتراك") }}</h2>
            <button :aria-label="t('common.close')" type="button" @click="closeTransferModal" class="w-8 h-8 rounded-lg bg-white/10"><X aria-hidden="true" /></button>
          </header>
          <div class="p-5 space-y-3.5">
            <p class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5]">
              {{ t("owner_subscribers.transfer_modal_message", { name: transferModal.subscription?.subscriber?.name ?? '', generator: transferModal.subscription?.generator?.name ?? '' }) }}
            </p>
            <div v-if="transferError" class="alert-box"><CircleAlert class="shrink-0" aria-hidden="true" />{{ transferError }}</div>
            <div>
              <label class="field-label">{{ t("owner_subscribers.transfer_target_label", "المولد الجديد") }}</label>
              <AppDropdownSelect
                v-model="transferModal.generatorId"
                :options="transferGeneratorDropdownOptions"
                :placeholder="isLoadingGenerators ? t('common.loading') : t('owner_subscribers.transfer_select_generator', 'اختر مولدًا')"
                :disabled="isLoadingGenerators"
                variant="field"
                width-class="w-full"
              />
            </div>
          </div>
          <div class="modal-footer-brand">
            <button type="button" @click="closeTransferModal" class="btn-outline-brand">{{ t("owner_technicians.cancel") }}</button>
            <button type="button" @click="confirmTransfer" :disabled="isTransferring || !transferModal.generatorId" class="btn-fill-brand">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isTransferring" /><ArrowRightLeft aria-hidden="true" v-else />
              {{ isTransferring ? t("owner_subscribers.transferring_ellipsis", "جارٍ النقل...") : t("owner_subscribers.transfer_confirm", "تأكيد النقل") }}
            </button>
          </div>
        </section>
      </div>
    </Teleport>
    <!-- ===== نافذة إضافة اشتراك جديد ===== -->
    <Teleport to="body">
      <div v-if="addSubscriptionModal" class="fixed inset-0 z-[110] bg-black/45 backdrop-blur-sm p-4 flex items-center justify-center" @click.self="closeAddSubscriptionModal">
        <section class="glass-card !bg-white dark:!bg-[#1c1e20] max-w-lg w-full overflow-hidden max-h-[90vh] flex flex-col">
          <header class="p-5 bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white flex justify-between items-center shrink-0">
            <div class="min-w-0">
              <h2 class="font-extrabold text-[14px] flex items-center gap-2"><UserPlus aria-hidden="true" />{{ t("owner_subscribers.add_subscription_modal_title", "إضافة مشترك جديد") }}</h2>
              <p class="text-[11px] text-white/75 mt-0.5">{{ t("owner_subscribers.add_subscription_modal_subtitle", "اربط مشتركًا موجودًا بأحد مولداتك — السعر يُحدَّد تلقائيًا حسب سعر المولد") }}</p>
            </div>
            <button :aria-label="t('common.close')" type="button" @click="closeAddSubscriptionModal" class="w-8 h-8 rounded-lg bg-white/10 shrink-0"><X aria-hidden="true" /></button>
          </header>

          <div class="p-5 space-y-4 overflow-y-auto">
            <div v-if="createSubscriptionError" class="alert-box"><CircleAlert class="shrink-0" aria-hidden="true" />{{ createSubscriptionError }}</div>

            <!-- ===== بحث عن مشترك موجود ===== -->
            <div class="space-y-2">
              <label class="field-label">{{ t("owner_subscribers.search_subscriber_label", "ابحث عن المشترك بالاسم أو البريد أو الهاتف") }}</label>
              <div v-if="!selectedSubscriber" class="relative">
                <input v-model="subscriberSearch" @input="scheduleSubscriberSearch" type="text" class="field-input" :placeholder="t('owner_subscribers.search_subscriber_placeholder', 'اكتب اسم المشترك...')" />
                <div v-if="isSearchingSubscribers" class="absolute top-1/2 -translate-y-1/2 end-3 text-[#9a9d97]"><LoaderCircle class="text-[11px] animate-spin" aria-hidden="true" /></div>
                <div v-if="subscriberResults.length" class="mt-1.5 border border-[#e7e2d6] dark:border-white/10 rounded-xl overflow-hidden max-h-40 overflow-y-auto">
                  <button
                    v-for="s in subscriberResults" :key="s.id" type="button" @click="pickSubscriber(s)"
                    class="w-full text-start px-3 py-2 text-[12px] hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 flex items-center justify-between gap-2 border-b last:border-0 border-[#f0ece0] dark:border-white/5"
                  >
                    <span class="font-bold truncate">{{ s.name }}</span>
                    <span class="text-[10.5px] text-[#9a9d97] shrink-0" dir="ltr">{{ s.phone || s.email }}</span>
                  </button>
                </div>
                <p v-else-if="subscriberSearch.trim() && !isSearchingSubscribers" class="text-[10.5px] text-[#9a9d97] mt-1.5">{{ t("owner_subscribers.no_subscriber_found", "لا يوجد مشترك مطابق بهذا الاسم/البريد/الهاتف.") }}</p>
              </div>
              <div v-else class="flex items-center justify-between gap-2 border border-[#52733D]/30 bg-[#52733D]/5 rounded-xl px-3 py-2.5">
                <div class="flex items-center gap-2.5 min-w-0">
                  <span class="w-8 h-8 rounded-full bg-[#e3eddf] dark:bg-[#52733D]/20 text-[#52733D] dark:text-[#8cc35a] grid place-items-center font-bold shrink-0">{{ selectedSubscriber.name?.slice(0, 1) }}</span>
                  <span class="min-w-0"><b class="block text-[12px] truncate">{{ selectedSubscriber.name }}</b><small class="text-[10.5px] text-[#858983]" dir="ltr">{{ selectedSubscriber.phone || selectedSubscriber.email }}</small></span>
                </div>
                <button :aria-label="t('common.close')" type="button" @click="clearPickedSubscriber" class="w-7 h-7 rounded-lg hover:bg-black/5 dark:hover:bg-white/10 shrink-0"><X class="text-[11px]" aria-hidden="true" /></button>
              </div>
            </div>

            <hr class="border-[#eee8da] dark:border-white/10" />

            <!-- ===== بيانات الاشتراك ===== -->
            <div class="grid sm:grid-cols-2 gap-3">
              <div>
                <label class="field-label">{{ t("owner_subscribers.transfer_target_label", "المولد") }}</label>
                <AppDropdownSelect
                  v-model="subscriptionForm.generator_id"
                  :options="generatorDropdownOptions"
                  :placeholder="t('owner_subscribers.transfer_select_generator', 'اختر مولدًا')"
                  variant="field"
                  width-class="w-full"
                />
              </div>
              <div>
                <label class="field-label">{{ t("owner_subscribers.meter_label", "العدّاد") }}</label>
                <AppDropdownSelect
                  v-model="subscriptionForm.subscriber_meter_id"
                  :options="subscriberMeterDropdownOptions"
                  :placeholder="!selectedSubscriber ? t('owner_subscribers.pick_subscriber_first', 'اختر مشتركًا أولًا') : (isLoadingSubscriberMeters ? t('common.loading') : t('owner_subscribers.pick_meter', 'اختر عدّادًا'))"
                  :disabled="!selectedSubscriber || isLoadingSubscriberMeters"
                  variant="field"
                  width-class="w-full"
                />
                <p v-if="selectedSubscriber && !isLoadingSubscriberMeters && !subscriberMeterDropdownOptions.length" class="text-[10.5px] text-[#D9534F] mt-1">{{ t("owner_subscribers.no_active_meters", "لا يوجد لهذا المشترك عدّاد فعّال.") }}</p>
              </div>
              <div>
                <label class="field-label">{{ t("owner_subscribers.capacity_col") }} (kW) <span class="text-[#9a9d97] font-normal">({{ t("common.optional", "اختياري") }})</span></label>
                <input v-model.number="subscriptionForm.requested_capacity_kw" type="number" min="0" step="0.1" class="field-input" />
              </div>
              <div>
                <label class="field-label">{{ t("owner_subscribers.schedule_col") }}</label>
                <AppDropdownSelect
                  v-model="subscriptionForm.schedule"
                  :options="scheduleDropdownOptions"
                  :placeholder="t('common.choose', 'اختر')"
                  variant="field"
                  width-class="w-full"
                />
              </div>
              <template v-if="subscriptionForm.schedule === 'custom'">
                <div>
                  <label class="field-label">{{ t("owner_subscribers.service_start_time", "وقت البدء") }}</label>
                  <input v-model="subscriptionForm.service_start_time" type="time" class="field-input" />
                </div>
                <div>
                  <label class="field-label">{{ t("owner_subscribers.service_end_time", "وقت الانتهاء") }}</label>
                  <input v-model="subscriptionForm.service_end_time" type="time" class="field-input" />
                </div>
              </template>
              <div>
                <label class="field-label">{{ t("owner_subscribers.billing_cycle_col") }}</label>
                <AppDropdownSelect
                  v-model="subscriptionForm.billing_cycle"
                  :options="billingCycleDropdownOptions"
                  :placeholder="t('common.choose', 'اختر')"
                  variant="field"
                  width-class="w-full"
                />
              </div>
              <div>
                <label class="field-label">{{ t("owner_subscribers.start_date", "تاريخ البدء") }}</label>
                <input v-model="subscriptionForm.start_date" type="date" class="field-input" />
              </div>
              <div>
                <label class="field-label">{{ t("owner_subscribers.end_date", "تاريخ الانتهاء") }} <span class="text-[#9a9d97] font-normal">({{ t("common.optional", "اختياري") }})</span></label>
                <input v-model="subscriptionForm.end_date" type="date" class="field-input" />
              </div>
              <div class="sm:col-span-2">
                <label class="field-label">{{ t("owner_subscribers.contract_type", "نوع العقد") }} <span class="text-[#9a9d97] font-normal">({{ t("common.optional", "اختياري") }})</span></label>
                <input v-model="subscriptionForm.contract_type" type="text" class="field-input" />
              </div>
            </div>
          </div>

          <div class="modal-footer-brand shrink-0">
            <button type="button" @click="closeAddSubscriptionModal" class="btn-outline-brand">{{ t("owner_technicians.cancel") }}</button>
            <button type="button" @click="handleCreateSubscription" :disabled="isCreatingSubscription || !canSubmitSubscription" class="btn-fill-brand">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isCreatingSubscription" /><Check aria-hidden="true" v-else />
              {{ isCreatingSubscription ? t("owner_subscribers.creating_ellipsis", "جارٍ الإنشاء...") : t("owner_subscribers.add_subscription_confirm", "إنشاء الاشتراك") }}
            </button>
          </div>
        </section>
      </div>
    </Teleport>
  </div>
</template>