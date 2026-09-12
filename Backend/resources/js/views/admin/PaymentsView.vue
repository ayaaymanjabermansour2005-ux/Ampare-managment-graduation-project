<script setup>
import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref, reactive, onMounted, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useOwnerPayments } from "@/composables/useOwnerPayments";
import { useAdminPaymentMethods } from "@/composables/useAdminPaymentMethods";
import { useAdminOwnerCommissions } from "@/composables/useAdminOwnerCommissions";
import { useAdminTechnicianPayments } from "@/composables/useAdminTechnicianPayments";
import { useConfirm } from "@/composables/useConfirm";
import { usePermissions } from "@/composables/usePermissions";
import { vReveal } from "@/directives/reveal";
import PaymentReviewPanel from "@/components/payments/PaymentReviewPanel.vue";
import userService from "@/services/userService";
import invoiceService from "@/services/invoiceService";
import paymentService from "@/services/paymentService";
import adminDashboardService from "@/services/adminDashboardService";
import { useToastStore } from "@/stores/toast";
import { ArrowLeft, ArrowRight, Check, ChevronLeft, ChevronRight, Circle, CirclePlus, CreditCard, Eye, FileDown, FileSpreadsheet, Info, LoaderCircle, Paperclip, Printer, Search, Trash2, UserCog, UserRound, X } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";
import StatCard from "@/components/dashboard/StatCard.vue";


const { t, locale } = useI18n();
const { confirm } = useConfirm();
const { can } = usePermissions();
const toast = useToastStore();

/* ==========================================================================
 * ============  التابات (دفعات المشتركين / دفعات الملّاك / دفعات الفنيين / طرق الدفع)  ====
 * ========================================================================== */
const TABS = computed(() => [
  { key: "payments", label: t("admin_payments_page.tab_subscriber_payments"), icon: "fa-wallet" },
  { key: "owner_payments", label: t("admin_payments_page.tab_owner_payments"), icon: "fa-user-tie" },
  { key: "technician_payments", label: t("admin_payments_page.tab_technician_payments"), icon: "fa-user-gear" },
  { key: "methods", label: t("admin_payments_page.tab_methods"), icon: "fa-credit-card" },
]);
const activeTab = ref("payments");

const TAB_META = computed(() => ({
  payments: { title: t("admin_payments_page.tab_subscriber_payments"), icon: "fa-wallet", gradient: "from-[#8A6D1F] to-[#D4AF37]" },
  owner_payments: { title: t("admin_payments_page.tab_owner_payments"), icon: "fa-user-tie", gradient: "from-[#3E582E] to-[#52733D]" },
  technician_payments: { title: t("admin_payments_page.tab_technician_payments"), icon: "fa-user-gear", gradient: "from-[#17A2B8] to-[#0f6c7d]" },
  methods: { title: t("payment_methods_page.title"), icon: "fa-credit-card", gradient: "from-[#17A2B8] to-[#0f6c7d]" },
}));

/* ==========================================================================
 * ===================  تبويب: دفعات الملّاك (عمولة المنصة)  =================
 * ========================================================================== */
const {
  commissions: ownerCommissions,
  isLoading: isOwnerCommissionsLoading,
  error: ownerCommissionsError,
  fetchCommissions: fetchOwnerCommissions,
  updatingId: updatingCommissionId,
  updateError: commissionUpdateError,
  markPaid: markCommissionPaid,
} = useAdminOwnerCommissions();

const OWNER_COMMISSION_STATUS_META = { pending: "chip-warning", earned: "chip-info", paid: "chip-success" };
function ownerCommissionStatusLabel(status) {
  return t(`admin_payments_page.owner_commission_status.${status}`, status);
}

const ownerCommissionStatusFilter = ref("");
const OWNER_COMMISSION_STATUS_PILLS = computed(() => [
  { value: "", label: t("common.all") },
  { value: "pending", label: ownerCommissionStatusLabel("pending") },
  { value: "earned", label: ownerCommissionStatusLabel("earned") },
  { value: "paid", label: ownerCommissionStatusLabel("paid") },
]);
const ownerCommissionOwnerSearch = ref("");
const filteredOwnerCommissions = computed(() => {
  const query = ownerCommissionOwnerSearch.value.trim().toLowerCase();
  return ownerCommissions.value.filter((c) => {
    const matchesStatus = !ownerCommissionStatusFilter.value || c.status === ownerCommissionStatusFilter.value;
    const matchesSearch = !query || (c.owner?.name ?? "").toLowerCase().includes(query);
    return matchesStatus && matchesSearch;
  });
});

async function handleMarkCommissionPaid(commission) {
  const confirmed = await confirm({
    title: t("admin_payments_page.mark_paid_confirm_title"),
    message: t("admin_payments_page.mark_paid_confirm_message", { name: commission.owner?.name ?? "" }),
    confirmLabel: t("admin_payments_page.mark_paid_action"),
  });
  if (!confirmed) return;

  const ok = await markCommissionPaid(commission.id);
  toast.show(
    ok
      ? { type: "success", title: t("admin_payments_page.mark_paid_success_title") }
      : { type: "danger", title: t("admin_payments_page.action_failed_title"), message: commissionUpdateError.value }
  );
}

/* ==========================================================================
 * ===============  تبويب: دفعات الفنيين (عرض/تدقيق فقط للأدمن)  ============
 * ========================================================================== */
const {
  payments: adminTechnicianPayments,
  pagination: adminTechnicianPaymentsPagination,
  isLoading: isAdminTechnicianPaymentsLoading,
  error: adminTechnicianPaymentsError,
  statusFilter: technicianPaymentStatusFilter,
  search: technicianPaymentSearch,
  fetchPayments: fetchAdminTechnicianPayments,
  onSearchInput: onTechnicianPaymentSearchInput,
} = useAdminTechnicianPayments();

const ADMIN_TECHNICIAN_PAYMENT_STATUS_META = { pending: "chip-warning", approved: "chip-success", rejected: "chip-danger" };
const TECHNICIAN_PAYMENT_STATUS_PILLS = computed(() => [
  { value: "", label: t("common.all") },
  { value: "pending", label: t("owner_technician_payments.status.pending") },
  { value: "approved", label: t("owner_technician_payments.status.approved") },
  { value: "rejected", label: t("owner_technician_payments.status.rejected") },
]);

/* ==========================================================================
 * ==============================  تبويب: الدفعات  ==========================
 * ========================================================================== */
const {
  payments,
  pagination,
  isLoading,
  error,
  pendingCount,
  fetchPayments,
  isLoadingDetail,
  fetchPaymentDetail,
  isActing,
  actionError,
  approvePayment,
  rejectPayment,
  requestCorrection,
} = useOwnerPayments();

const statusFilter = ref("pending");
const search = ref("");

const STATUS_META = {
  pending: { key: "admin_payments_page.status_pending", chip: "chip-warning", color: "#FFC107" },
  paid: { key: "admin_payments_page.status_paid", chip: "chip-success", color: "#28A745" },
  rejected: { key: "admin_payments_page.status_rejected", chip: "chip-danger", color: "#D9534F" },
  needs_correction: { key: "admin_payments_page.status_needs_correction", chip: "chip-info", color: "#17A2B8" },
};
function statusLabel(s) {
  const m = STATUS_META[s];
  return m ? t(m.key) : s;
}
function statusChip(s) {
  return STATUS_META[s]?.chip ?? null;
}
function statusChipStyle(s) {
  const m = STATUS_META[s];
  if (!m || m.chip) return {};
  return { color: m.color, background: m.color + "1A" };
}

const METHOD_LABELS_KEYS = { wallet: "admin_payments_page.method_wallet", bank: "admin_payments_page.method_bank", cash: "admin_payments_page.method_cash" };
function methodLabel(type) {
  const key = METHOD_LABELS_KEYS[type];
  return key ? t(key) : type;
}
const METHOD_ICONS = { wallet: "fa-wallet", bank: "fa-building-columns", cash: "fa-money-bill-wave" };

const STATUS_PILLS = computed(() => [
  { value: "pending", label: t("admin_payments_page.status_pending") },
  { value: "needs_correction", label: t("admin_payments_page.status_needs_correction") },
  { value: "paid", label: t("admin_payments_page.status_paid") },
  { value: "rejected", label: t("admin_payments_page.status_rejected") },
  { value: "all", label: t("admin_payments_page.status_all") },
]);

/* فلترة الحالة + البحث النصي (اسم المشترك/المولد) — تُطبَّق على الصفحة المحمّلة حاليًا،
 * نفس منهجية باقي صفحات الأدمن. */
const filteredPayments = computed(() => {
  let list = statusFilter.value === "all" ? payments.value : payments.value.filter((p) => p.status === statusFilter.value);
  const q = search.value.trim().toLowerCase();
  if (q) {
    list = list.filter(
      (p) =>
        (p.subscriber?.name ?? "").toLowerCase().includes(q) ||
        (p.generator?.name ?? "").toLowerCase().includes(q),
    );
  }
  return list;
});

const exportUrl = computed(() =>
  paymentService.exportUrl(
    statusFilter.value === "all" ? {} : { status: statusFilter.value },
  ),
);

/* ---------------- KPI Cards (نفس منهجية صفحة الفواتير: هذه الصفحة فقط) ---------------- */
const countOnPage = (status) => payments.value.filter((p) => p.status === status).length;
const KPI_CARDS = computed(() => [
  { icon: "fa-wallet", label: t("admin_payments_page.kpi_total"), value: pagination.value.total, tone: "secondary" },
  { icon: "fa-circle-check", label: t("admin_payments_page.kpi_accepted_page"), value: countOnPage("paid"), tone: "success" },
  { icon: "fa-hourglass-half", label: t("admin_payments_page.kpi_pending_page"), value: countOnPage("pending"), tone: "warning" },
  { icon: "fa-triangle-exclamation", label: t("admin_payments_page.kpi_needs_correction_page"), value: countOnPage("needs_correction"), tone: "info" },
]);

const systemStatusInfo = computed(() => {
  const pendingOnPage = countOnPage("pending");
  if (pendingOnPage === 0) {
    return { label: t("admin_payments_page.no_pending_review"), color: "#28A745" };
  }
  return { label: t("admin_payments_page.pending_review_this_page", { count: pendingOnPage }), color: "#FFC107" };
});

/* ---------------- Pagination بأزرار محدودة (مشتركة الشكل — تُبنى مرتين) ---------------- */
function buildPaginationRange(current, total) {
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
}

const paginationRange = computed(() => buildPaginationRange(pagination.value.current_page, pagination.value.last_page));

/* ---------------- الملخص المالي (بيانات حقيقية من الـAPI ) ---------------- */
const financialSummary = ref(null);
const isLoadingFinancialSummary = ref(true);
const financialSummaryError = ref(null);

async function fetchFinancialSummary() {
  isLoadingFinancialSummary.value = true;
  financialSummaryError.value = null;
  try {
    const { data } = await adminDashboardService.paymentsFinancialSummary();
    financialSummary.value = data.data;
  } catch (err) {
    financialSummaryError.value = normalizeApiError(err, t("admin_payments_page.financial_today")).message;
  } finally {
    isLoadingFinancialSummary.value = false;
  }
}

const FINANCIAL_SUMMARY = computed(() => {
  const s = financialSummary.value;
  if (!s) return [];
  return [
    { icon: "fa-money-bill-wave", label: t("admin_payments_page.financial_today"), value: Number(s.today_total_ils ?? 0), suffix: "₪", tone: "success" },
    { icon: "fa-calendar-days", label: t("admin_payments_page.financial_month_total"), value: Number(s.month_total_ils ?? 0), suffix: "₪", tone: "secondary" },
    { icon: "fa-receipt", label: t("admin_payments_page.financial_month_tx_count"), value: Number(s.month_transactions_count ?? 0), tone: "info" },
    { icon: "fa-hourglass-half", label: t("admin_payments_page.financial_pending_tx"), value: Number(s.pending_transactions_count ?? 0), tone: "warning" },
  ];
});

/* ---------------- حركة المدفوعات (آخر 7 أيام) وتوزيع طرق الدفع
   (بيانات حقيقية من الـAPI محسوبة على كامل جدول الدفعات — كانت سابقًا
   تُحسب من دفعات الصفحة/الفلتر المعروض بالجدول فقط، ما يجعلها تبدو
   ثابتة تقريبًا) ---------------- */
const METHOD_COLORS = { cash: "#28A745", bank: "#17A2B8", wallet: "#8A6D1F" };
const paymentsActivity = ref(null);
const isLoadingPaymentsActivity = ref(true);

async function fetchPaymentsActivity() {
  isLoadingPaymentsActivity.value = true;
  try {
    const { data } = await adminDashboardService.paymentsActivity();
    paymentsActivity.value = data.data;
  } catch {
    paymentsActivity.value = null;
  } finally {
    isLoadingPaymentsActivity.value = false;
  }
}

const methodDistribution = computed(() => {
  const counts = paymentsActivity.value?.method_counts ?? { cash: 0, bank: 0, wallet: 0 };
  const total = Object.values(counts).reduce((sum, n) => sum + n, 0);
  return Object.keys(METHOD_LABELS_KEYS).map((key) => ({
    key,
    label: methodLabel(key),
    icon: METHOD_ICONS[key],
    color: METHOD_COLORS[key],
    count: counts[key] ?? 0,
    pct: total ? Math.round(((counts[key] ?? 0) / total) * 100) : 0,
  }));
});
const methodDonutStyle = computed(() => {
  let acc = 0;
  const stops = methodDistribution.value
    .filter((m) => m.pct > 0)
    .map((m) => {
      const start = acc;
      acc += m.pct;
      return `${m.color} ${start}% ${acc}%`;
    });
  return { background: stops.length ? `conic-gradient(${stops.join(", ")})` : "#e7e2d6" };
});

const last7DaysActivity = computed(() => {
  const labels = paymentsActivity.value?.labels ?? [];
  const totals = paymentsActivity.value?.totals ?? [];
  const days = labels.map((label, i) => ({ key: i, label, total: totals[i] ?? 0 }));
  const max = Math.max(1, ...days.map((d) => d.total));
  return days.map((d) => ({ ...d, pct: Math.round((d.total / max) * 100) }));
});

/* ---------------- عرض الإيصال ---------------- */
function viewReceipt(payment) {
  if (payment.receipt_url) {
    window.open(payment.receipt_url, "_blank");
  } else {
    toast.show({ type: "info", title: t("owner_payments.no_attachments") });
  }
}

/* ---------------- طباعة (الصفحة المحمّلة حاليًا بعد الفلترة) — تحترم اللغة الحالية ---------------- */
function handlePrint() {
  const isAr = locale.value === "ar";
  const rowsHtml = filteredPayments.value
    .map(
      (p) => `<tr>
      <td>${p.subscriber?.name ?? "-"}</td><td>${p.generator?.name ?? "-"}</td>
      <td>${methodLabel(p.payment_method_type)}</td>
      <td>${p.amount} ${p.currency}</td><td>${statusLabel(p.status)}</td>
    </tr>`,
    )
    .join("");
  const printWin = window.open("", "_blank");
  if (!printWin) return;
  printWin.document.write(`<html dir="${isAr ? "rtl" : "ltr"}" lang="${isAr ? "ar" : "en"}"><head><meta charset="utf-8"><title>${t("admin_payments_page.list_title")}</title>
    <style>
      body{font-family:sans-serif;padding:24px;color:#222}
      h1{color:#3E582E}
      table{width:100%;border-collapse:collapse;margin-top:16px}
      th,td{border:1px solid #ccc;padding:8px;text-align:${isAr ? "right" : "left"};font-size:13px}
      th{background:#EBF1E7}
    </style></head><body>
    <h1>${t("admin_payments_page.list_title")}</h1>
    <p>${new Date().toLocaleString(isAr ? "ar-EG" : "en-GB")}</p>
    <table><thead><tr><th>${t("subscribers_page.subscriber_col")}</th><th>${t("dashboard.generator_col")}</th><th>${t("admin_payments_page.method_wallet")}/${t("admin_payments_page.method_bank")}/${t("admin_payments_page.method_cash")}</th><th>${t("subscriptions_page.price_per_kw_subscription_label")}</th><th>${t("dashboard.status_col")}</th></tr></thead>
    <tbody>${rowsHtml}</tbody></table></body></html>`);
  printWin.document.close();
  printWin.focus();
  setTimeout(() => printWin.print(), 400);
}

const showManualPaymentModal = ref(false);
const manualStep = ref(1); // 1: اختيار مشترك | 2: اختيار فاتورة + إدخال المبلغ

const subscriberSearch = ref("");
const subscriberResults = ref([]);
const isSearchingSubscribers = ref(false);
const selectedSubscriber = ref(null);
let subscriberSearchTimeout = null;

const subscriberInvoices = ref([]);
const isLoadingSubscriberInvoices = ref(false);
const selectedInvoice = ref(null);

const manualPaymentForm = ref({ amount: "", note: "", override_reason: "" });
const isSubmittingManual = ref(false);
const manualPaymentError = ref(null);
let manualPaymentIdempotencyKey = null;

function openManualPaymentModal() {
  manualStep.value = 1;
  subscriberSearch.value = "";
  subscriberResults.value = [];
  selectedSubscriber.value = null;
  subscriberInvoices.value = [];
  selectedInvoice.value = null;
  manualPaymentForm.value = { amount: "", note: "", override_reason: "" };
  manualPaymentError.value = null;
  manualPaymentIdempotencyKey = crypto.randomUUID();
  showManualPaymentModal.value = true;
}

function onSubscriberSearchInput() {
  clearTimeout(subscriberSearchTimeout);
  if (subscriberSearch.value.trim().length < 2) {
    subscriberResults.value = [];
    return;
  }
  subscriberSearchTimeout = setTimeout(async () => {
    isSearchingSubscribers.value = true;
    try {
      const { data } = await userService.list({ role: "subscriber", search: subscriberSearch.value, per_page: 10 });
      const payload = data.data;
      subscriberResults.value = payload.data ?? payload;
    } finally {
      isSearchingSubscribers.value = false;
    }
  }, 350);
}

async function selectSubscriberForPayment(subscriber) {
  selectedSubscriber.value = subscriber;
  subscriberResults.value = [];
  manualStep.value = 2;
  isLoadingSubscriberInvoices.value = true;
  try {
    const { data } = await invoiceService.list({
      subscriber_id: subscriber.id,
      status: "pending,overdue,partially_paid",
      per_page: 50,
    });
    const payload = data.data;
    subscriberInvoices.value = payload.data ?? payload;
  } finally {
    isLoadingSubscriberInvoices.value = false;
  }
}

function selectInvoiceForPayment(invoice) {
  selectedInvoice.value = invoice;
  manualPaymentForm.value.amount = String(invoice.remaining_balance_ils ?? 0);
  manualPaymentForm.value.override_reason = "";
}

const manualAmountExceedsBalance = computed(() => {
  if (!selectedInvoice.value) return false;
  const amount = Number(manualPaymentForm.value.amount) || 0;
  return amount > Number(selectedInvoice.value.remaining_balance_ils ?? 0);
});

async function submitManualPayment() {
  if (!selectedInvoice.value || !manualPaymentForm.value.amount) return;
  if (manualAmountExceedsBalance.value && !manualPaymentForm.value.override_reason.trim()) {
    manualPaymentError.value = t("admin_payments_page.manual_amount_exceeds_error");
    return;
  }

  isSubmittingManual.value = true;
  manualPaymentError.value = null;
  try {
    const formData = new FormData();
    formData.append("invoice_id", selectedInvoice.value.id);
    formData.append("amount", manualPaymentForm.value.amount);
    if (manualPaymentForm.value.note) formData.append("note", manualPaymentForm.value.note);
    if (manualPaymentForm.value.override_reason) {
      formData.append("override_reason", manualPaymentForm.value.override_reason);
    }

    await paymentService.createPayment(formData, manualPaymentIdempotencyKey);
    showManualPaymentModal.value = false;
    await fetchPayments(pagination.value.current_page);
  } catch (err) {
    manualPaymentError.value = normalizeApiError(err, t("admin_payments_page.manual_create_error")).message;
  } finally {
    isSubmittingManual.value = false;
  }
}

const isPanelOpen = ref(false);
const selectedPayment = ref(null);

async function openReview(payment) {
  isPanelOpen.value = true;
  selectedPayment.value = null;
  selectedPayment.value = await fetchPaymentDetail(payment.id);
}

async function handleApprove() {
  if (await approvePayment(selectedPayment.value.id)) isPanelOpen.value = false;
}
async function handleReject(reason) {
  if (await rejectPayment(selectedPayment.value.id, reason))
    isPanelOpen.value = false;
}
async function handleCorrection(note) {
  if (await requestCorrection(selectedPayment.value.id, note))
    isPanelOpen.value = false;
}

/* ==========================================================================
 * ============================  تبويب: طرق الدفع  ==========================
 * ========================================================================== */
const {
  methods,
  pagination: methodsPagination,
  isLoading: isMethodsLoading,
  error: methodsError,
  search: methodsSearch,
  deletingId: deletingMethodId,
  deleteError: methodsDeleteError,
  fetchMethods,
  onSearchInput: onMethodsSearchInput,
  deleteMethod,
} = useAdminPaymentMethods();

const TYPE_META = {
  bank: { icon: "fa-building-columns", key: "payment_methods_page.type_bank", c1: "#17A2B8", c2: "#0f6c7d" },
  wallet: { icon: "fa-wallet", key: "payment_methods_page.type_wallet", c1: "#8A6D1F", c2: "#D4AF37" },
  cash: { icon: "fa-money-bill-wave", key: "payment_methods_page.type_cash", c1: "#52733D", c2: "#3E582E" },
};
function typeMeta(type) {
  return TYPE_META[type] ?? { icon: "fa-wallet", key: null, fallback: type, c1: "#9a9d97", c2: "#6B6B6B" };
}
function typeLabel(type) {
  const m = typeMeta(type);
  return m.key ? t(m.key) : m.fallback;
}

/* ---------------- فلتر النوع ---------------- */
const TYPE_PILLS = computed(() => [
  { value: "", label: t("common.all") },
  ...Object.entries(TYPE_META).map(([value, m]) => ({ value, label: t(m.key) })),
]);
const typeFilter = ref("");

const filteredMethods = computed(() => {
  if (!typeFilter.value) return methods.value;
  return methods.value.filter((m) => m.type === typeFilter.value);
});

/* ---------------- KPI Cards (طرق الدفع) ----------------
 * "الإجمالي" دقيق دايمًا (من methodsPagination.total)، وباقي البطاقات محسوبة من
 * methods.value يعني الصفحة المحمّلة حاليًا بس (نفس منهجية صفحة العروض).
 */
const countMethodsByType = (type) => methods.value.filter((m) => m.type === type).length;
const METHOD_KPI_CARDS = computed(() => [
  { icon: "fa-credit-card", label: t("admin_payments_page.methods_kpi_total"), value: methodsPagination.value.total, tone: "secondary" },
  { icon: TYPE_META.bank.icon, label: typeLabel("bank") + " " + t("admin_payments_page.methods_active_page"), value: countMethodsByType("bank"), tone: "info" },
  { icon: TYPE_META.wallet.icon, label: typeLabel("wallet") + " " + t("admin_payments_page.methods_active_page"), value: countMethodsByType("wallet"), tone: "secondary" },
  { icon: TYPE_META.cash.icon, label: typeLabel("cash") + " " + t("admin_payments_page.methods_active_page"), value: countMethodsByType("cash"), tone: "primary" },
]);

async function handleDeleteMethod(m) {
  const confirmed = await confirm({
    title: t("payment_methods_page.delete_method_title"),
    message: t("payment_methods_page.delete_method_message", { name: m.owner?.name }),
    confirmLabel: t("common.delete"),
    variant: "danger",
  });
  if (!confirmed) return;
  const ok = await deleteMethod(m.id);
  if (!ok && methodsDeleteError.value) toast.show({ type: "danger", title: methodsDeleteError.value });
}

/* ---------------- نافذة عرض تفاصيل وسيلة الدفع ---------------- */
const viewingMethod = ref(null);
function openViewMethod(m) {
  viewingMethod.value = m;
}

/* ---------------- تصدير CSV (طرق الدفع) ----------------
 * على مستوى الصفحة المحمّلة حاليًا فقط، مو كل السجلات (نفس منهجية صفحة العروض).
 */
function handleExportMethodsCsv() {
  const escapeCsv = (val) => {
    const s = String(val ?? "");
    return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
  };
  const header = [
    t("owners_page.owner_details_title"),
    t("payment_methods_page.type_label"),
    t("payment_methods_page.bank_label"),
    t("payment_methods_page.account_name_label"),
    t("payment_methods_page.account_no_label"),
    t("dashboard.currency"),
    t("payment_methods_page.default_label"),
  ];
  const yesNo = (v) => (v ? t("common.all") : "-");
  const rows = methods.value.map((m) => [
    m.owner?.name ?? "",
    typeLabel(m.type),
    m.bank_name ?? "",
    m.account_name ?? "",
    m.account_number_masked ?? "",
    m.currency ?? "",
    m.is_default ? "✓" : "",
  ]);
  const csv = "\uFEFF" + [header, ...rows].map((r) => r.map(escapeCsv).join(",")).join("\n");
  const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `payment-methods-${new Date().toISOString().slice(0, 10)}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

onMounted(() => {
  fetchPayments();
  fetchFinancialSummary();
  fetchPaymentsActivity();
  fetchMethods(1);
  fetchOwnerCommissions();
  fetchAdminTechnicianPayments();
});
</script>

<template>
  <div class="space-y-6">
    <!-- ===== HEADER ===== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>
      <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] dark:text-[#8f938a] mb-2">
        <span>{{ $t("common.home") }}</span>
        <ChevronRight class="rtl:block ltr:hidden text-[9px]" aria-hidden="true" />
        <ChevronLeft class="ltr:block rtl:hidden text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ TAB_META[activeTab].title }}</span>
      </nav>
      <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span
              class="w-10 h-10 rounded-xl text-white flex items-center justify-center text-base bg-gradient-to-br"
              :class="TAB_META[activeTab].gradient"
            >
              <AppIcon :name="TAB_META[activeTab].icon" />
            </span>
            {{ TAB_META[activeTab].title }}
          </h1>

          <template v-if="activeTab === 'payments'">
            <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
              <span class="font-extrabold text-[#a3760a]">{{ pendingCount }}</span>
              {{ $t("admin_payments_page.pending_review_all") }}
            </p>
            <span
              v-if="systemStatusInfo"
              class="inline-flex items-center gap-2 text-[11px] font-bold mt-2.5"
              :style="{ color: systemStatusInfo.color }"
            >
              <Circle class="text-[7px]" aria-hidden="true" /> {{ systemStatusInfo.label }}
            </span>
          </template>
          <template v-else-if="activeTab === 'methods'">
            <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
              {{ $t("payment_methods_page.subtitle") }}
            </p>
          </template>
        </div>

        <!-- ===== تبديل التابات ===== -->
        <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1">
          <button
            v-for="tab in TABS"
            :key="tab.key"
            type="button"
            @click="activeTab = tab.key"
            class="flex items-center gap-1.5 px-4 py-2 rounded-full text-[12px] font-bold transition-colors"
            :class="
              activeTab === tab.key
                ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-sm'
                : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'
            "
          >
            <AppIcon :name="tab.icon" />
            {{ tab.label }}
          </button>
        </div>
      </div>
    </section>

    <!-- ==========================================================
         ==============  تبويب: الدفعات  ============================
         ========================================================== -->
    <template v-if="activeTab === 'payments'">
      <!-- ===== KPI CARDS ===== -->
      <section v-reveal>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
          <StatCard
            v-for="c in KPI_CARDS" :key="c.label"
            :label="c.label" :value="c.value" :icon="c.icon" :tone="c.tone"
          />
        </div>
      </section>

      <!-- ===== FINANCIAL SUMMARY ===== -->
      <section v-reveal>
        <div v-if="financialSummaryError" class="mb-3 text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2">
          {{ financialSummaryError }}
        </div>
        <div v-if="isLoadingFinancialSummary" class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
          <div v-for="i in 4" :key="i" class="h-20 rounded-xl thumb-loading"></div>
        </div>
        <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
          <StatCard
            v-for="c in FINANCIAL_SUMMARY" :key="c.label"
            :label="c.label" :value="c.value" :icon="c.icon" :tone="c.tone" :suffix="c.suffix ?? ''"
          />
        </div>
      </section>

      <!-- ===== CHARTS ===== -->
      <section v-reveal class="grid lg:grid-cols-2 gap-3.5">
        <!-- حركة المدفوعات آخر 7 أيام -->
        <div class="glass-card p-4">
          <h3 class="text-[13.5px] font-bold mb-4">{{ $t("admin_payments_page.activity_7days_title") }}</h3>
          <div class="flex items-end justify-between gap-2 h-36 px-1">
            <div v-for="d in last7DaysActivity" :key="d.key" class="flex-1 flex flex-col items-center gap-1.5 h-full justify-end">
              <span class="text-[9.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ d.total ? d.total.toLocaleString() : "" }}</span>
              <div class="w-full max-w-[26px] rounded-t-md bg-gradient-to-t from-[#3E582E] to-[#8cc35a] transition-all" :style="{ height: Math.max(d.pct, 3) + '%' }"></div>
              <span class="text-[10px] text-[#6B6B6B] dark:text-[#a8aaa5] font-bold">{{ d.label }}</span>
            </div>
          </div>
        </div>

        <!-- توزيع طرق الدفع -->
        <div class="glass-card p-4">
          <h3 class="text-[13.5px] font-bold mb-4">{{ $t("admin_payments_page.method_distribution_title") }}</h3>
          <div class="flex items-center gap-5">
            <div class="w-28 h-28 rounded-full shrink-0 relative" :style="methodDonutStyle">
              <div class="absolute inset-2.5 rounded-full bg-white dark:bg-[#1c1e20] flex items-center justify-center">
                <span class="text-[11px] font-bold text-[#9a9d97] dark:text-[#8f938a]">{{ payments.length }} {{ $t("admin_payments_page.payment_unit") }}</span>
              </div>
            </div>
            <div class="flex-1 space-y-2.5">
              <div v-for="m in methodDistribution" :key="m.key" class="flex items-center gap-2 text-[11.5px]">
                <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ background: m.color }"></span>
                <AppIcon :name="m.icon" class="text-[10px]" :style="{ color: m.color }" />
                <span class="flex-1 font-bold">{{ m.label }}</span>
                <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ m.pct }}%</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== TOOLBAR ===== -->
      <section v-reveal class="glass-card p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[220px]">
            <div class="relative flex-1 min-w-[260px] max-w-lg">
              <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
              <input
                v-model="search"
                type="text"
                :placeholder="$t('admin_payments_page.search_placeholder')"
                class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F]"
              />
            </div>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
              <button
                v-for="pill in STATUS_PILLS"
                :key="pill.value"
                type="button"
                @click="statusFilter = pill.value"
                class="px-3.5 py-1.5 rounded-full text-[11px] font-bold transition-colors shrink-0"
                :class="
                  statusFilter === pill.value
                    ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white'
                    : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'
                "
              >
                {{ pill.label }}
              </button>
            </div>
            <div class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
            <a
              :href="exportUrl"
              target="_blank"
              class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
              :title="$t('admin_payments_page.export_excel')"
              :aria-label="$t('admin_payments_page.export_excel')"
            >
              <FileDown class="text-[11px]" aria-hidden="true" />
            </a>
            <button
              type="button"
              @click="handlePrint"
              class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
              :title="$t('admin_payments_page.print')"
              :aria-label="$t('admin_payments_page.print')"
            >
              <Printer class="text-[11px]" aria-hidden="true" />
            </button>
            <div v-if="can('payments.create')" class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
            <button
              v-if="can('payments.create')"
              type="button"
              @click="openManualPaymentModal"
              class="flex items-center gap-1.5 px-4 py-2 rounded-full text-[11.5px] font-bold text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] shadow-md hover:brightness-110 transition"
            >
              <CirclePlus aria-hidden="true" />
              {{ $t("admin_payments_page.manual_payment_button") }}
            </button>
          </div>
        </div>
      </section>

      <!-- ===== LIST ===== -->
      <section v-reveal class="glass-card p-4 overflow-hidden">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
          <h3 class="text-[13.5px] font-bold">{{ $t("admin_payments_page.list_title") }}</h3>
          <span class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ filteredPayments.length }} {{ $t("admin_payments_page.payment_unit_suffix") }}</span>
        </div>

        <div v-if="error" class="mb-3 text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2">
          {{ error }}
        </div>

        <div v-if="isLoading" class="space-y-2">
          <div v-for="i in 5" :key="i" class="h-16 rounded-lg thumb-loading"></div>
        </div>

        <div v-else-if="filteredPayments.length === 0" class="text-center py-12">
          <div
            class="w-14 h-14 mx-auto mb-3 rounded-full bg-[#28A745]/10 flex items-center justify-center"
          >
            <Check class="text-xl text-[#28A745]" aria-hidden="true" />
          </div>
          <p class="text-[12.5px] font-bold">{{ $t("admin_payments_page.no_payments_status") }}</p>
        </div>

        <div v-else class="divide-y divide-[#f0ece0] dark:divide-white/5">
          <button
            v-for="payment in filteredPayments"
            :key="payment.id"
            type="button"
            @click="openReview(payment)"
            class="w-full flex items-center gap-3.5 py-3.5 px-1.5 text-start hover:bg-[#f4efe5]/50 dark:hover:bg-white/5 rounded-lg transition"
          >
            <div
              class="w-9 h-9 rounded-full bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] flex items-center justify-center shrink-0"
            >
              <AppIcon :name="METHOD_ICONS[payment.payment_method_type] ?? 'fa-wallet'" class="text-white text-[11px]" />
            </div>

            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2">
                <p class="text-[12.5px] font-bold truncate">
                  {{ payment.subscriber?.name ?? $t("admin_payments_page.unknown_subscriber") }}
                </p>
                <span
                  v-if="payment.attachments_count"
                  class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] shrink-0"
                >
                  <Paperclip aria-hidden="true" />
                  {{ payment.attachments_count }} {{ $t("admin_payments_page.attachments_count") }}
                </span>
              </div>
              <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5">
                {{ methodLabel(payment.payment_method_type) }}
                <span v-if="payment.generator?.name">
                  · {{ payment.generator.name }}</span
                >
              </p>
            </div>

            <div class="text-end shrink-0">
              <p class="text-[12.5px] font-extrabold text-[#8A6D1F] dark:text-[#D4AF37]">
                {{ payment.amount }} {{ payment.currency }}
              </p>
              <span
                class="status-chip mt-1 inline-block"
                :class="statusChip(payment.status)"
                :style="statusChipStyle(payment.status)"
              >
                {{ statusLabel(payment.status) }}
              </span>
            </div>

            <ChevronLeft class="rtl:block ltr:hidden text-[#c9cdc2] dark:text-[#565952] text-[10px] shrink-0" aria-hidden="true" />
            <ChevronRight class="ltr:block rtl:hidden text-[#c9cdc2] dark:text-[#565952] text-[10px] shrink-0" aria-hidden="true" />
          </button>
        </div>

        <!-- Pagination بأزرار محدودة -->
        <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-3.5">
          <span class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
            {{ $t("admin_payments_page.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}
          </span>
          <div class="flex items-center gap-1">
            <button :aria-label="$t('common.previous_page')"
              type="button"
              :disabled="pagination.current_page <= 1"
              @click="fetchPayments(pagination.current_page - 1)"
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
                @click="fetchPayments(page)"
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
              @click="fetchPayments(pagination.current_page + 1)"
              class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
            >
              <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
              <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
            </button>
          </div>
        </div>
      </section>
    </template>

    <!-- ==========================================================
         =============  تبويب: دفعات الملّاك (عمولة المنصة)  =========
         ========================================================== -->
    <template v-else-if="activeTab === 'owner_payments'">
      <section v-reveal class="glass-card p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="relative flex-1 min-w-[220px] max-w-lg">
            <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
            <input
              v-model="ownerCommissionOwnerSearch"
              type="text"
              :placeholder="$t('admin_payments_page.owner_commission_search_placeholder')"
              class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F]"
            />
          </div>
          <div class="flex flex-wrap items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1">
            <button
              v-for="pill in OWNER_COMMISSION_STATUS_PILLS" :key="pill.value" type="button"
              @click="ownerCommissionStatusFilter = pill.value"
              class="px-3.5 py-1.5 rounded-full text-[11px] font-bold transition-colors shrink-0"
              :class="ownerCommissionStatusFilter === pill.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
            >{{ pill.label }}</button>
          </div>
        </div>
      </section>

      <section v-reveal class="glass-card p-4 overflow-hidden">
        <div v-if="isOwnerCommissionsLoading" class="space-y-2">
          <div v-for="i in 6" :key="i" class="h-14 rounded-lg thumb-loading"></div>
        </div>
        <div v-else-if="ownerCommissionsError" class="text-center py-8 text-[12px] text-[#D9534F]">{{ ownerCommissionsError }}</div>
        <div v-else-if="filteredOwnerCommissions.length === 0" class="text-center py-10">
          <UserRound class="text-2xl text-[#c9cdc2] dark:text-[#565952] mb-2" aria-hidden="true" />
          <p class="text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("admin_payments_page.no_matching_owner_payments") }}</p>
        </div>
        <div v-else class="overflow-x-auto -mx-1">
          <table class="data-table w-full text-[12px] min-w-[720px]">
            <thead>
              <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
                <th class="py-2.5 px-3 rounded-s-lg">{{ $t("admin_payments_page.owner_col") }}</th>
                <th class="py-2.5 px-3">{{ $t("admin_payments_page.generator_col") }}</th>
                <th class="py-2.5 px-3">{{ $t("admin_payments_page.commission_rate_col") }}</th>
                <th class="py-2.5 px-3">{{ $t("admin_payments_page.commission_amount_col") }}</th>
                <th class="py-2.5 px-3">{{ $t("admin_payments_page.status_col") }}</th>
                <th class="py-2.5 px-3 rounded-e-lg">{{ $t("admin_payments_page.actions_col") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="commission in filteredOwnerCommissions" :key="commission.id"
                class="border-b border-[#f0ece0] dark:border-white/5 last:border-0 text-center"
                :class="{ 'opacity-50 pointer-events-none': updatingCommissionId === commission.id }"
              >
                <td class="py-2.5 px-3 font-bold">{{ commission.owner?.name ?? "-" }}</td>
                <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">{{ commission.generator_name ?? "-" }}</td>
                <td class="py-2.5 px-3" dir="ltr">{{ commission.commission_rate }}%</td>
                <td class="py-2.5 px-3 font-bold" dir="ltr">{{ commission.commission_amount }}</td>
                <td class="py-2.5 px-3"><span class="status-chip" :class="OWNER_COMMISSION_STATUS_META[commission.status] ?? 'chip-info'">{{ ownerCommissionStatusLabel(commission.status) }}</span></td>
                <td class="py-2.5 px-3">
                  <div v-if="commission.status === 'earned' && can('platform-commissions.updateStatus')" class="row-actions">
                    <button
                      type="button"
                      @click="handleMarkCommissionPaid(commission)"
                      :disabled="updatingCommissionId === commission.id"
                      class="action-btn action-btn--edit" :title="$t('admin_payments_page.mark_paid_action')" :aria-label="$t('admin_payments_page.mark_paid_action')"
                    >
                      <LoaderCircle class="animate-spin" aria-hidden="true" v-if="updatingCommissionId === commission.id" /><Check aria-hidden="true" v-else />
                    </button>
                  </div>
                  <span v-else class="text-[#9a9d97] dark:text-[#8f938a]">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
    </template>

    <!-- ==========================================================
         ======  تبويب: دفعات الفنيين (عرض ومراجعة فقط للأدمن)  ======
         ========================================================== -->
    <template v-else-if="activeTab === 'technician_payments'">
      <section v-reveal class="glass-card p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="relative flex-1 min-w-[220px] max-w-lg">
            <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
            <input
              v-model="technicianPaymentSearch"
              type="text"
              @input="onTechnicianPaymentSearchInput"
              :placeholder="$t('admin_payments_page.technician_payment_search_placeholder')"
              class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F]"
            />
          </div>
          <div class="flex flex-wrap items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1">
            <button
              v-for="pill in TECHNICIAN_PAYMENT_STATUS_PILLS" :key="pill.value" type="button"
              @click="technicianPaymentStatusFilter = pill.value; fetchAdminTechnicianPayments(1)"
              class="px-3.5 py-1.5 rounded-full text-[11px] font-bold transition-colors shrink-0"
              :class="technicianPaymentStatusFilter === pill.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
            >{{ pill.label }}</button>
          </div>
        </div>
      </section>

      <section v-reveal class="glass-card p-4">
        <div class="flex items-start gap-2.5 text-[11.5px] text-[#8A6D1F] dark:text-[#D4AF37] bg-[#D4AF37]/10 rounded-lg px-3.5 py-3">
          <Info class="mt-0.5" aria-hidden="true" />
          <span>{{ $t("admin_payments_page.technician_payments_readonly_notice") }}</span>
        </div>
      </section>

      <section v-reveal class="glass-card p-4 overflow-hidden">
        <div v-if="isAdminTechnicianPaymentsLoading" class="space-y-2">
          <div v-for="i in 6" :key="i" class="h-14 rounded-lg thumb-loading"></div>
        </div>
        <div v-else-if="adminTechnicianPaymentsError" class="text-center py-8 text-[12px] text-[#D9534F]">{{ adminTechnicianPaymentsError }}</div>
        <div v-else-if="adminTechnicianPayments.length === 0" class="text-center py-10">
          <UserCog class="text-2xl text-[#c9cdc2] dark:text-[#565952] mb-2" aria-hidden="true" />
          <p class="text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("admin_payments_page.no_matching_technician_payments") }}</p>
        </div>
        <div v-else class="overflow-x-auto -mx-1">
          <table class="data-table w-full text-[12px] min-w-[760px]">
            <thead>
              <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
                <th class="py-2.5 px-3 rounded-s-lg">{{ $t("admin_payments_page.technician_col") }}</th>
                <th class="py-2.5 px-3">{{ $t("admin_payments_page.owner_col") }}</th>
                <th class="py-2.5 px-3">{{ $t("owner_technician_payments.amount_col") }}</th>
                <th class="py-2.5 px-3">{{ $t("owner_technician_payments.note_col") }}</th>
                <th class="py-2.5 px-3 rounded-e-lg">{{ $t("owner_technician_payments.status_col") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in adminTechnicianPayments" :key="payment.id" class="border-b border-[#f0ece0] dark:border-white/5 last:border-0 text-center">
                <td class="py-2.5 px-3 font-bold">{{ payment.technician_name ?? "-" }}</td>
                <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">{{ payment.owner_name ?? "-" }}</td>
                <td class="py-2.5 px-3 font-bold" dir="ltr">{{ payment.amount }} {{ payment.currency }}</td>
                <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5] truncate max-w-[16rem]">
                  <span v-if="payment.status === 'rejected' && payment.rejection_reason" class="text-[#D9534F]">{{ payment.rejection_reason }}</span>
                  <span v-else>{{ payment.note || "-" }}</span>
                </td>
                <td class="py-2.5 px-3"><span class="status-chip" :class="ADMIN_TECHNICIAN_PAYMENT_STATUS_META[payment.status] ?? 'chip-info'">{{ $t(`owner_technician_payments.status.${payment.status}`, payment.status) }}</span></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="adminTechnicianPaymentsPagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-2 mt-3 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
          <span>{{ $t("owner_technician_payments.pagination_text", { current: adminTechnicianPaymentsPagination.current_page, last: adminTechnicianPaymentsPagination.last_page, total: adminTechnicianPaymentsPagination.total }) }}</span>
          <div class="flex items-center gap-1">
            <button :aria-label="$t('common.previous_page')" type="button" :disabled="adminTechnicianPaymentsPagination.current_page <= 1" @click="fetchAdminTechnicianPayments(adminTechnicianPaymentsPagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
              <ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
              <ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
            </button>
            <button :aria-label="$t('common.next_page')" type="button" :disabled="adminTechnicianPaymentsPagination.current_page >= adminTechnicianPaymentsPagination.last_page" @click="fetchAdminTechnicianPayments(adminTechnicianPaymentsPagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
              <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
              <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
            </button>
          </div>
        </div>
      </section>
    </template>

    <!-- ==========================================================
         ==============  تبويب: طرق الدفع  ==========================
         ========================================================== -->
    <template v-else-if="activeTab === 'methods'">
      <!-- ===== KPI CARDS ===== -->
      <section v-reveal>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
          <StatCard
            v-for="c in METHOD_KPI_CARDS" :key="c.label"
            :label="c.label" :value="c.value" :icon="c.icon" :tone="c.tone"
          />
        </div>
      </section>

      <!-- ===== TOOLBAR ===== -->
      <section v-reveal class="glass-card p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="relative">
            <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
            <input
              v-model="methodsSearch" type="text" @input="onMethodsSearchInput"
              :placeholder="$t('admin_payments_page.methods_search_placeholder')"
              class="bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F] w-56 md:w-72"
            />
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
              <button
                v-for="pill in TYPE_PILLS" :key="pill.value" type="button"
                @click="typeFilter = pill.value"
                class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
                :class="typeFilter === pill.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
              >{{ pill.label }}</button>
            </div>
            <div class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
            <button
              type="button"
              @click="handleExportMethodsCsv"
              class="btn-fill relative text-[12.5px] font-bold px-4 py-2 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2"
            >
              <FileSpreadsheet aria-hidden="true" />
              {{ $t("users_page.export_csv") }}
            </button>
          </div>
        </div>
      </section>

      <!-- ===== GRID ===== -->
      <section v-reveal>
        <div v-if="isMethodsLoading" class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3.5">
          <div v-for="i in 6" :key="i" class="h-36 rounded-2xl thumb-loading"></div>
        </div>
        <div v-else-if="methodsError" class="glass-card p-8 text-center text-[12px] text-[#D9534F]">{{ methodsError }}</div>
        <div v-else-if="filteredMethods.length === 0" class="glass-card p-10 text-center">
          <CreditCard class="text-2xl text-[#c9cdc2] dark:text-[#565952] mb-2" aria-hidden="true" />
          <p class="text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("payment_methods_page.no_matching_methods") }}</p>
        </div>
        <div v-else class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3.5">
          <div v-for="m in filteredMethods" :key="m.id" class="glass-card hoverable p-4">
            <div class="flex items-start justify-between mb-2.5">
              <span
                class="w-9 h-9 rounded-lg text-white flex items-center justify-center shrink-0"
                :style="{ background: `linear-gradient(135deg, ${typeMeta(m.type).c1}, ${typeMeta(m.type).c2})` }"
              >
                <AppIcon :name="typeMeta(m.type).icon" class="text-[12px]" />
              </span>
              <span v-if="m.is_default" class="status-chip chip-success">{{ $t("payment_methods_page.default_label") }}</span>
            </div>

            <button type="button" @click="openViewMethod(m)" class="text-start block w-full">
              <h3 class="font-bold text-[13px] mb-1 hover:text-[#8A6D1F] dark:hover:text-[#D4AF37] transition-colors truncate">{{ m.owner?.name ?? "—" }}</h3>
            </button>
            <p class="text-[11px] font-bold mb-3" :style="{ color: typeMeta(m.type).c1 }">{{ typeLabel(m.type) }}</p>

            <div class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] space-y-1 mb-3">
              <p v-if="m.bank_name" class="truncate">{{ m.bank_name }}</p>
              <p v-if="m.account_name" class="truncate">{{ m.account_name }}</p>
              <p v-if="m.account_number_masked" class="font-mono">{{ m.account_number_masked }}</p>
            </div>

            <div class="flex items-center justify-between text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] mb-3">
              <span>{{ m.currency ?? "—" }}</span>
            </div>

            <div class="flex items-center gap-2">
              <button
                type="button" @click="openViewMethod(m)"
                class="w-9 h-9 rounded-full flex items-center justify-center text-[#17A2B8] hover:bg-[#17A2B8]/10 shrink-0"
                :title="$t('common.view')"
                :aria-label="$t('common.view')"
              >
                <Eye aria-hidden="true" style="font-size:11px" />
              </button>
              <div class="flex-1"></div>
              <button
                v-if="can('payment-methods.delete')"
                type="button" @click="handleDeleteMethod(m)" :disabled="deletingMethodId === m.id"
                class="w-9 h-9 rounded-full flex items-center justify-center text-[#D9534F] hover:bg-[#D9534F]/10 disabled:opacity-40 shrink-0"
                :title="$t('common.delete')"
                :aria-label="$t('common.delete')"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="deletingMethodId === m.id" style="font-size:11px" /><Trash2 aria-hidden="true" v-else style="font-size:11px" />
              </button>
            </div>
          </div>
        </div>

        <div v-if="methodsPagination.last_page > 1" class="flex items-center justify-between mt-4 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
          <span>{{ $t("payment_methods_page.pagination_text", { current: methodsPagination.current_page, last: methodsPagination.last_page, total: methodsPagination.total }) }}</span>
          <div class="flex items-center gap-1">
            <button :aria-label="$t('common.previous_page')" type="button" :disabled="methodsPagination.current_page <= 1" @click="fetchMethods(methodsPagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
              <ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
              <ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
            </button>
            <button :aria-label="$t('common.next_page')" type="button" :disabled="methodsPagination.current_page >= methodsPagination.last_page" @click="fetchMethods(methodsPagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
              <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
              <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
            </button>
          </div>
        </div>
      </section>
    </template>

    <!-- ===================== لوحة مراجعة الدفعة ===================== -->
    <PaymentReviewPanel
      :open="isPanelOpen"
      :payment="selectedPayment"
      :is-loading-detail="isLoadingDetail"
      :is-acting="isActing"
      :action-error="actionError"
      @close="isPanelOpen = false"
      @approve="handleApprove"
      @reject="handleReject"
      @request-correction="handleCorrection"
    />

    <!-- ===================== تسجيل دفعة يدوية ===================== -->
    <Teleport to="body">
      <div v-if="showManualPaymentModal" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="showManualPaymentModal = false">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-md shadow-2xl overflow-hidden rounded-2xl max-h-[90vh] flex flex-col">
          <div class="modal-head-brand modal-head-brand--gold shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><CirclePlus aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ $t("admin_payments_page.manual_modal_title") }}</h3>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="showManualPaymentModal = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <div class="p-5 overflow-y-auto">
          <!-- خطوة 1: اختيار المشترك -->
          <div v-if="manualStep === 1" class="space-y-3">
            <label class="text-[11px] font-bold block mb-1.5">{{ $t("admin_payments_page.manual_step1_label") }}</label>
            <input
              v-model="subscriberSearch"
              type="text"
              @input="onSubscriberSearchInput"
              :placeholder="$t('admin_payments_page.manual_step1_placeholder')"
              class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]"
            />
            <div v-if="isSearchingSubscribers" class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] text-center py-3">{{ $t("admin_payments_page.manual_searching") }}</div>
            <div v-else-if="subscriberSearch.trim().length >= 2 && subscriberResults.length === 0" class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] text-center py-3">{{ $t("admin_payments_page.manual_no_results") }}</div>
            <div v-else class="space-y-1.5 max-h-64 overflow-y-auto">
              <button
                v-for="s in subscriberResults" :key="s.id" type="button"
                @click="selectSubscriberForPayment(s)"
                class="w-full text-start p-2.5 rounded-lg border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 transition-colors"
              >
                <p class="text-[12px] font-bold">{{ s.name }}</p>
                <p class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ s.email }}</p>
              </button>
            </div>
          </div>

          <!-- خطوة 2: اختيار الفاتورة وإدخال المبلغ -->
          <div v-else-if="manualStep === 2" class="space-y-3">
            <button type="button" @click="manualStep = 1" class="text-[11px] font-bold text-[#8A6D1F] dark:text-[#D4AF37] flex items-center gap-1.5 mb-1">
              <ArrowRight class="rtl:block ltr:hidden text-[9px]" aria-hidden="true" />
              <ArrowLeft class="ltr:block rtl:hidden text-[9px]" aria-hidden="true" />
              {{ $t("admin_payments_page.manual_change_subscriber") }}
            </button>
            <div class="text-[12.5px] font-bold p-2.5 rounded-lg bg-[#f4efe5]/60 dark:bg-white/5">{{ selectedSubscriber?.name }}</div>

            <div v-if="isLoadingSubscriberInvoices" class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] text-center py-4">{{ $t("admin_payments_page.manual_loading_invoices") }}</div>
            <div v-else-if="subscriberInvoices.length === 0" class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] text-center py-6">
              {{ $t("admin_payments_page.manual_no_invoices") }}
            </div>

            <template v-else>
              <label class="text-[11px] font-bold block mb-1.5">{{ $t("admin_payments_page.manual_select_invoice") }}</label>
              <div class="space-y-1.5 max-h-48 overflow-y-auto mb-2">
                <button
                  v-for="inv in subscriberInvoices" :key="inv.id" type="button"
                  @click="selectInvoiceForPayment(inv)"
                  class="w-full text-start p-2.5 rounded-lg border transition-colors"
                  :class="selectedInvoice?.id === inv.id ? 'border-[#8A6D1F] bg-[#f4efe5]/60 dark:bg-white/5' : 'border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/40 dark:hover:bg-white/5'"
                >
                  <div class="flex items-center justify-between">
                    <span class="text-[12px] font-bold">{{ $t("admin_payments_page.manual_invoice_hash", { id: inv.id }) }}</span>
                    <span class="text-[11px] font-bold text-[#D9534F]">{{ $t("admin_payments_page.manual_remaining", { amount: inv.remaining_balance_ils }) }}</span>
                  </div>
                </button>
              </div>

              <template v-if="selectedInvoice">
                <div v-if="manualPaymentError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2">{{ manualPaymentError }}</div>

                <div>
                  <label class="text-[11px] font-bold block mb-1.5">{{ $t("admin_payments_page.manual_amount_label") }}</label>
                  <input v-model="manualPaymentForm.amount" type="number" step="0.01" min="0.01" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]" />
                </div>

                <div v-if="manualAmountExceedsBalance">
                  <label class="text-[11px] font-bold block mb-1.5 text-[#D9534F]">{{ $t("admin_payments_page.manual_override_reason_label") }}</label>
                  <textarea v-model="manualPaymentForm.override_reason" rows="2" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#D9534F]/40 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#D9534F]"></textarea>
                </div>

                <div>
                  <label class="text-[11px] font-bold block mb-1.5">{{ $t("admin_payments_page.manual_note_label") }}</label>
                  <textarea v-model="manualPaymentForm.note" rows="2" class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F]"></textarea>
                </div>

                <div class="flex gap-2.5 pt-2">
                  <button type="button" @click="showManualPaymentModal = false" class="flex-1 text-[12.5px] font-bold py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5">{{ $t("admin_payments_page.manual_cancel") }}</button>
                  <button
                    type="button" @click="submitManualPayment"
                    :disabled="isSubmittingManual || !manualPaymentForm.amount || (manualAmountExceedsBalance && !manualPaymentForm.override_reason.trim())"
                    class="flex-1 btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold py-2.5 rounded-full shadow-md flex items-center justify-center gap-2 disabled:opacity-60"
                  >
                    <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSubmittingManual" /><Check aria-hidden="true" v-else />
                    {{ isSubmittingManual ? $t("admin_payments_page.manual_submitting") : $t("admin_payments_page.manual_submit") }}
                  </button>
                </div>
              </template>
            </template>
          </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ===================== نافذة عرض تفاصيل وسيلة الدفع ===================== -->
    <Teleport to="body">
      <div
        v-if="viewingMethod"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
        @click.self="viewingMethod = null"
      >
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--gold">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon">
                <AppIcon :name="typeMeta(viewingMethod.type).icon" />
              </span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title truncate">{{ viewingMethod.owner?.name ?? "—" }}</h3>
                <p v-if="viewingMethod.is_default" class="modal-head-brand__subtitle">{{ $t("payment_methods_page.default_label") }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="viewingMethod = null" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <div class="p-5">
            <div class="glass-card p-4 space-y-2.5 text-[12px]">
              <div class="flex justify-between gap-3">
                <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("payment_methods_page.type_label") }}</span>
                <b :style="{ color: typeMeta(viewingMethod.type).c1 }">{{ typeLabel(viewingMethod.type) }}</b>
              </div>
              <div v-if="viewingMethod.bank_name" class="flex justify-between gap-3">
                <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("payment_methods_page.bank_label") }}</span>
                <b class="truncate max-w-[10rem]">{{ viewingMethod.bank_name }}</b>
              </div>
              <div v-if="viewingMethod.account_name" class="flex justify-between gap-3">
                <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("payment_methods_page.account_name_label") }}</span>
                <b class="truncate max-w-[10rem]">{{ viewingMethod.account_name }}</b>
              </div>
              <div v-if="viewingMethod.account_number_masked" class="flex justify-between gap-3">
                <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("payment_methods_page.account_no_label") }}</span>
                <b class="font-mono">{{ viewingMethod.account_number_masked }}</b>
              </div>
              <div class="flex justify-between gap-3">
                <span class="text-[#9a9d97] dark:text-[#8f938a]">{{ $t("dashboard.currency") }}</span>
                <b>{{ viewingMethod.currency ?? "—" }}</b>
              </div>
            </div>
          </div>

          <div class="modal-footer-brand">
            <button type="button" @click="viewingMethod = null" class="btn-outline-brand">
              {{ $t("common.close") }}
            </button>
            <button
              v-if="can('payment-methods.delete')"
              type="button"
              @click="handleDeleteMethod(viewingMethod); viewingMethod = null"
              :disabled="deletingMethodId === viewingMethod.id"
              class="btn-fill-brand btn-fill-brand--danger"
            >
              <Trash2 aria-hidden="true" />
              {{ $t("common.delete") }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>