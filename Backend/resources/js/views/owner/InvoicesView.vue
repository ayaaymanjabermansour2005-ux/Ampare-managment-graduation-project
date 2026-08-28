<script setup>
import { ref, reactive, computed, watch, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import { useOwnerInvoices } from "@/composables/useOwnerInvoices";
import { useOwnerPayments } from "@/composables/useOwnerPayments";
import { useOwnerTechnicianPayments } from "@/composables/useOwnerTechnicianPayments";
import { useConfirm } from "@/composables/useConfirm";
import { useToastStore } from "@/stores/toast";
import { vReveal } from "@/directives/reveal";
import invoiceService from "@/services/invoiceService";
import paymentService from "@/services/paymentService";
import technicianService from "@/services/technicianService";
import paymentMethodService from "@/services/paymentMethodService";
import PaymentReviewPanel from "@/components/payments/PaymentReviewPanel.vue";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { Ban, Check, ChevronLeft, ChevronRight, CircleAlert, Eye, FileDown, FileSpreadsheet, GripVertical, Hourglass, Info, LoaderCircle, Paperclip, Plus, Printer, Receipt, Search, Table2, UserCog, X } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const { t } = useI18n();
const route = useRoute();
const { confirm } = useConfirm();
const toast = useToastStore();

/* ==========================================================================
 * =====================  التابات (فواتير / دفعات / دفعات الفنيين)  =========
 * ========================================================================== */
const TABS = [
  { key: "invoices", label: t("owner_invoices.title"), icon: "fa-file-invoice" },
  { key: "payments", label: t("owner_payments.title"), icon: "fa-wallet" },
  { key: "technician_payments", label: t("owner_technician_payments.title"), icon: "fa-user-gear" },
];
const activeTab = ref("invoices");

const TAB_META = {
  invoices: { title: t("owner_invoices.title"), eyebrow: t("owner_invoices.eyebrow"), icon: "fa-file-invoice" },
  payments: { title: t("owner_payments.title"), eyebrow: t("owner_payments.eyebrow"), icon: "fa-wallet" },
  technician_payments: { title: t("owner_technician_payments.title"), eyebrow: t("owner_technician_payments.eyebrow"), icon: "fa-user-gear" },
};

/* ---------------- عرض جدول/شبكة لكل تبويب (مطابق لصفحة إدارة المولدات) ---------------- */
const invoicesViewMode = ref("table");
const paymentsViewMode = ref("table");

/* ---------------- طباعة (مطابقة لصفحة إدارة المولدات) ---------------- */
function printPage() {
  window.print();
}

/* ==========================================================================
 * =============================  تبويب: الفواتير  ==========================
 * ========================================================================== */
const {
  invoices,
  pagination: invoicesPagination,
  isLoading: isInvoicesLoading,
  error: invoicesError,
  search: invoiceSearch,
  statusFilter: invoiceStatusFilter,
  overdueCount,
  fetchInvoices,
  onSearchInput: onInvoiceSearchInput,
  onFilterChange: onInvoiceFilterChange,
  cancellingId,
  cancelError,
  cancelInvoice,
} = useOwnerInvoices();

const INVOICE_STATUS_META = {
  pending: "chip-warning",
  partially_paid: "chip-info",
  paid: "chip-success",
  overdue: "chip-danger",
  cancelled: null,
};
function invoiceStatusLabel(status) {
  return t(`owner_invoices.status.${status}`, status);
}

const INVOICE_STATUS_PILLS = computed(() => [
  { v: "", l: t("common.all") },
  { v: "pending", l: invoiceStatusLabel("pending") },
  { v: "partially_paid", l: invoiceStatusLabel("partially_paid") },
  { v: "paid", l: invoiceStatusLabel("paid") },
  { v: "overdue", l: invoiceStatusLabel("overdue") },
  { v: "cancelled", l: invoiceStatusLabel("cancelled") },
]);

function selectInvoiceStatusFilter(value) {
  invoiceStatusFilter.value = value;
  onInvoiceFilterChange();
}

const totalOutstanding = computed(() =>
  invoices.value
    .filter((i) => ["pending", "partially_paid", "overdue"].includes(i.status))
    .reduce((sum, i) => sum + i.remaining_balance_ils, 0),
);

async function handleCancelInvoice(invoice) {
  const confirmed = await confirm({
    title: t("owner_invoices.cancel_title", { id: invoice.id }),
    message: t("owner_invoices.cancel_message"),
    confirmLabel: t("owner_invoices.cancel_confirm"),
    variant: "danger",
  });
  if (!confirmed) return;

  const ok = await cancelInvoice(invoice.id);
  if (ok) {
    toast.show({
      type: "success",
      title: t("owner_invoices.cancelled_toast_title"),
      message: t("owner_invoices.cancelled_toast_message", { id: invoice.id }),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_invoices.cancel_failed_title"),
      message: cancelError.value ?? t("owner_invoices.cancel_error"),
    });
  }
}

/* ==========================================================================
 * =============================  تبويب: الدفعات  ===========================
 * ========================================================================== */
const {
  payments,
  pagination: paymentsPagination,
  isLoading: isPaymentsLoading,
  error: paymentsError,
  search: paymentSearch,
  statusFilter: paymentStatusFilter,
  pendingCount,
  fetchPayments,
  onSearchInput: onPaymentSearchInput,
  onFilterChange: onPaymentFilterChange,
  isLoadingDetail,
  fetchPaymentDetail,
  isActing,
  actionError,
  approvePayment,
  rejectPayment,
  requestCorrection,
} = useOwnerPayments();

const PAYMENT_STATUS_META = {
  pending: "chip-warning",
  paid: "chip-success",
  rejected: "chip-danger",
  needs_correction: "chip-info",
};
function paymentStatusLabel(status) {
  return t(`owner_payments.status.${status}`, status);
}
function methodLabel(method) {
  return t(`owner_payments.method.${method}`, method);
}
function methodIcon(method) {
  return { wallet: "fa-wallet", bank: "fa-building-columns", cash: "fa-money-bill-wave" }[method] ?? "fa-wallet";
}

const PAYMENT_STATUS_PILLS = computed(() => [
  { v: "pending", l: paymentStatusLabel("pending") },
  { v: "needs_correction", l: paymentStatusLabel("needs_correction") },
  { v: "paid", l: paymentStatusLabel("paid") },
  { v: "rejected", l: paymentStatusLabel("rejected") },
  { v: "all", l: t("common.all") },
]);

function selectPaymentStatusFilter(value) {
  paymentStatusFilter.value = value;
  onPaymentFilterChange();
}

const exportUrl = computed(() =>
  paymentService.exportUrl(
    paymentStatusFilter.value === "all" ? {} : { status: paymentStatusFilter.value },
  ),
);

// FIX: (item 14) نفس نمط تصدير تبويب الدفعات أعلاه، لتبويب الفواتير —
// كان الزر غائب كليًا هون رغم وجود GET /invoices/export بالباك اند.
const invoiceExportUrl = computed(() =>
  invoiceService.exportUrl(
    invoiceStatusFilter.value ? { status: invoiceStatusFilter.value } : {},
  ),
);

const isPanelOpen = ref(false);
const selectedPayment = ref(null);

async function openReview(payment) {
  isPanelOpen.value = true;
  selectedPayment.value = null;
  selectedPayment.value = await fetchPaymentDetail(payment.id);
}

async function handleApprove() {
  const ok = await approvePayment(selectedPayment.value.id);
  if (ok) {
    isPanelOpen.value = false;
    toast.show({ type: "success", title: t("owner_payments.approved_toast_title"), message: t("owner_payments.approved_toast_message") });
  } else {
    toast.show({ type: "danger", title: t("owner_payments.action_failed_title"), message: actionError.value ?? t("owner_payments.approve_error") });
  }
}

async function handleReject(reason) {
  const ok = await rejectPayment(selectedPayment.value.id, reason);
  if (ok) {
    isPanelOpen.value = false;
    toast.show({ type: "success", title: t("owner_payments.rejected_toast_title"), message: t("owner_payments.rejected_toast_message") });
  } else {
    toast.show({ type: "danger", title: t("owner_payments.action_failed_title"), message: actionError.value ?? t("owner_payments.reject_error") });
  }
}

async function handleCorrection(note) {
  const ok = await requestCorrection(selectedPayment.value.id, note);
  if (ok) {
    isPanelOpen.value = false;
    toast.show({ type: "success", title: t("owner_payments.correction_toast_title"), message: t("owner_payments.correction_toast_message") });
  } else {
    toast.show({ type: "danger", title: t("owner_payments.action_failed_title"), message: actionError.value ?? t("owner_payments.correction_error") });
  }
}

/* ==========================================================================
 * =========================  تبويب: دفعات الفنيين  ==========================
 * ========================================================================== */
const {
  payments: technicianPayments,
  pagination: technicianPaymentsPagination,
  isLoading: isTechnicianPaymentsLoading,
  error: technicianPaymentsError,
  technicianFilter,
  fetchPayments: fetchTechnicianPayments,
  onFilterChange: onTechnicianPaymentFilterChange,
  isCreating: isCreatingTechnicianPayment,
  createError: technicianPaymentCreateError,
  createPayment: createTechnicianPayment,
} = useOwnerTechnicianPayments();

const TECHNICIAN_PAYMENT_STATUS_META = {
  pending: "chip-warning",
  approved: "chip-success",
  rejected: "chip-danger",
};
function technicianPaymentStatusLabel(status) {
  return t(`owner_technician_payments.status.${status}`, status);
}

const ownerTechnicianOptions = ref([]);
async function ensureOwnerTechnicianOptionsLoaded() {
  if (ownerTechnicianOptions.value.length > 0) return;
  const { data } = await technicianService.list({ per_page: 200 });
  const payload = data.data;
  ownerTechnicianOptions.value = payload.data ?? payload;
}
const technicianSelectOptions = computed(() => ownerTechnicianOptions.value.map((tech) => ({ value: tech.id, label: tech.name })));
const technicianFilterOptions = computed(() => [
  { value: "", label: t("owner_technician_payments.all_technicians") },
  ...technicianSelectOptions.value,
]);

const isCreateTechnicianPaymentOpen = ref(false);
const newTechnicianPaymentForm = reactive({
  technician_id: "",
  amount: "",
  currency: "ILS",
  note: "",
});

/* ---------------- بيانات دفع الفني (عرض فقط داخل نافذة إضافة الدفعة) ---------------- */
const technicianPaymentMethodsInfo = ref([]);
const isLoadingTechnicianPaymentMethodsInfo = ref(false);

async function loadTechnicianPaymentMethodsInfo(technicianId) {
  technicianPaymentMethodsInfo.value = [];
  if (!technicianId) return;
  isLoadingTechnicianPaymentMethodsInfo.value = true;
  try {
    const { data } = await paymentMethodService.list({ technician_id: technicianId });
    const payload = data.data;
    technicianPaymentMethodsInfo.value = payload.data ?? payload;
  } catch {
    technicianPaymentMethodsInfo.value = [];
  } finally {
    isLoadingTechnicianPaymentMethodsInfo.value = false;
  }
}

watch(
  () => newTechnicianPaymentForm.technician_id,
  (technicianId) => {
    if (isCreateTechnicianPaymentOpen.value) loadTechnicianPaymentMethodsInfo(technicianId);
  }
);

function closeCreateTechnicianPaymentModal() {
  isCreateTechnicianPaymentOpen.value = false;
  technicianPaymentMethodsInfo.value = [];
}

async function openCreateTechnicianPayment() {
  newTechnicianPaymentForm.technician_id = technicianFilter.value || "";
  newTechnicianPaymentForm.amount = "";
  newTechnicianPaymentForm.currency = "ILS";
  newTechnicianPaymentForm.note = "";
  technicianPaymentCreateError.value = null;
  technicianPaymentMethodsInfo.value = [];
  isCreateTechnicianPaymentOpen.value = true;
  await ensureOwnerTechnicianOptionsLoaded();
  await loadTechnicianPaymentMethodsInfo(newTechnicianPaymentForm.technician_id);
}

async function handleCreateTechnicianPayment() {
  const ok = await createTechnicianPayment({ ...newTechnicianPaymentForm });
  if (ok) {
    closeCreateTechnicianPaymentModal();
    toast.show({
      type: "success",
      title: t("owner_technician_payments.created_toast_title"),
      message: t("owner_technician_payments.created_toast_message"),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_technician_payments.save_failed_title"),
      message: technicianPaymentCreateError.value?.message ?? t("owner_technician_payments.create_error"),
    });
  }
}

onMounted(() => {
  // يسمح بفتح هذه الصفحة مع بحث مسبَق التعبئة (مثلاً من زر "فواتير المشترك"
  // بصفحة المشتركين): ?search=اسم المشترك
  if (route.query.search) invoiceSearch.value = String(route.query.search);

  // يسمح بفتح الصفحة مباشرة على تاب معيّن (مثلاً من الداشبورد): ?tab=payments
  if (route.query.tab === "payments") activeTab.value = "payments";
  if (route.query.tab === "technician_payments") activeTab.value = "technician_payments";

  fetchInvoices();
  fetchPayments();
  fetchTechnicianPayments();
  ensureOwnerTechnicianOptionsLoaded();
});
</script>

<template>
  <div class="space-y-6">
    <!-- ===== HEADER ===== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>
      <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] mb-2">
        <span>{{ t("owner_dashboard.breadcrumb") }}</span>
        <ChevronLeft class="text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">
          {{ TAB_META[activeTab].title }}
        </span>
      </nav>

      <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div>
          <p class="text-[11px] font-bold text-[#8A6D1F] tracking-wide mb-1">
            {{ TAB_META[activeTab].eyebrow }}
          </p>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-base">
              <AppIcon :name="TAB_META[activeTab].icon" />
            </span>
            {{ TAB_META[activeTab].title }}
          </h1>

          <template v-if="activeTab === 'invoices'">
            <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab]">
              <span class="font-bold text-[#3a3a38] dark:text-[#eef0ec]" dir="ltr">{{ totalOutstanding.toFixed(2) }} ₪</span>
              {{ t("owner_invoices.outstanding_suffix") }}{{ t("common.this_page_suffix") }}
              <span v-if="overdueCount">
                · <span class="text-[#D9534F] font-bold">{{ overdueCount }}</span> {{ t("owner_invoices.overdue_suffix") }}
              </span>
            </p>
          </template>
          <template v-else-if="activeTab === 'payments'">
            <p v-if="pendingCount > 0" class="text-[12.5px] font-bold text-[#FFC107] flex items-center gap-1.5">
              <Hourglass class="text-[10px]" aria-hidden="true" />
              {{ t("owner_payments.pending_notice", { count: pendingCount }) }}
            </p>
          </template>
        </div>

        <div class="flex items-center gap-3 flex-wrap">
          <button
            v-if="activeTab === 'technician_payments'"
            type="button" @click="openCreateTechnicianPayment"
            class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md flex items-center gap-2"
          >
            <Plus aria-hidden="true" />
            {{ t("owner_technician_payments.add_button") }}
          </button>

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
      </div>
    </section>

    <!-- ==========================================================
         ==================  تبويب: الفواتير  =======================
         ========================================================== -->
    <template v-if="activeTab === 'invoices'">
      <!-- ===== TOOLBAR ===== -->
      <section v-reveal class="glass-card p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[220px]">
            <div class="relative flex-1 min-w-[160px] max-w-xs">
              <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] text-[10px]" aria-hidden="true" />
              <input
                v-model="invoiceSearch" type="text" @input="onInvoiceSearchInput"
                :placeholder="t('owner_invoices.search_placeholder')"
                class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F]"
              />
            </div>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
              <button
                v-for="pill in INVOICE_STATUS_PILLS" :key="pill.v" type="button"
                @click="selectInvoiceStatusFilter(pill.v)"
                class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
                :class="invoiceStatusFilter === pill.v ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
              >{{ pill.l }}</button>
            </div>
            <div class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
            <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1">
              <button :aria-label="t('common.view_as_table')" type="button" @click="invoicesViewMode = 'table'" class="icon-btn !w-8 !h-8" :class="{ '!bg-white dark:!bg-white/10': invoicesViewMode === 'table' }"><Table2 class="text-[11px]" aria-hidden="true" /></button>
              <button :aria-label="t('common.view_as_grid')" type="button" @click="invoicesViewMode = 'grid'" class="icon-btn !w-8 !h-8" :class="{ '!bg-white dark:!bg-white/10': invoicesViewMode === 'grid' }"><GripVertical class="text-[11px]" aria-hidden="true" /></button>
            </div>
            <!-- FIX: (item 14) زر تصدير حقيقي جديد — نفس نمط زر تبويب الدفعات المجاور -->
            <a
              :href="invoiceExportUrl" target="_blank" rel="noopener"
              class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
              :title="t('owner_invoices.export_excel')"
              :aria-label="t('owner_invoices.export_excel')"
            >
              <FileSpreadsheet class="text-[11px]" aria-hidden="true" />
            </a>
            <button
              type="button" @click="printPage"
              class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
              :title="t('owner_applications_page.print')"
            >
              <Printer class="text-[11px]" aria-hidden="true" />
            </button>
          </div>
        </div>
      </section>

      <!-- ===== TABLE ===== -->
      <section v-reveal class="glass-card p-4 overflow-hidden">
        <div v-if="isInvoicesLoading" class="space-y-2">
          <div v-for="i in 6" :key="i" class="h-14 rounded-lg thumb-loading"></div>
        </div>

        <div v-else-if="invoicesError" class="text-center py-8 text-[12px] text-[#D9534F]">{{ invoicesError }}</div>

        <div v-else-if="invoices.length === 0" class="text-center py-10">
          <Receipt class="text-2xl text-[#c9cdc2] mb-2" aria-hidden="true" />
          <p class="text-[12px] text-[#9a9d97]">{{ t("owner_invoices.no_matching_invoices") }}</p>
        </div>

        <template v-else>
        <div v-if="invoicesViewMode === 'table'" class="overflow-x-auto -mx-1">
          <table class="data-table w-full text-[12px] min-w-[900px]">
            <thead>
              <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
                <th class="py-2.5 px-3 rounded-s-lg">{{ t("owner_invoices.subscriber_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_invoices.generator_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_invoices.amount_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_invoices.remaining_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_invoices.due_date_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_invoices.status_col") }}</th>
                <th class="py-2.5 px-3 rounded-e-lg">{{ t("owner_invoices.actions_col") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="invoice in invoices" :key="invoice.id"
                class="border-b border-[#f0ece0] dark:border-white/5 last:border-0 text-center"
                :class="{ 'opacity-50 pointer-events-none': cancellingId === invoice.id }"
              >
                <td class="py-2.5 px-3 font-bold">{{ invoice.subscriber?.name ?? "-" }}</td>
                <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">{{ invoice.generator?.name ?? "-" }}</td>
                <td class="py-2.5 px-3 font-bold" dir="ltr">{{ invoice.final_amount }} {{ invoice.currency }}</td>
                <td class="py-2.5 px-3 font-bold" dir="ltr" :class="invoice.remaining_balance_ils > 0 ? 'text-[#FFC107]' : 'text-[#9a9d97]'">
                  {{ invoice.remaining_balance_ils.toFixed(2) }} ₪
                </td>
                <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]" dir="ltr">{{ invoice.due_date ?? "-" }}</td>
                <td class="py-2.5 px-3"><span class="status-chip" :class="INVOICE_STATUS_META[invoice.status]">{{ invoiceStatusLabel(invoice.status) }}</span></td>
                <td class="py-2.5 px-3">
                  <div class="row-actions">
                    <a
                      :href="invoiceService.downloadPdfUrl(invoice.id)" target="_blank" rel="noopener"
                      class="action-btn action-btn--view" :title="t('owner_invoices.download_pdf')"
                    >
                      <FileDown aria-hidden="true" />
                    </a>
                    <template v-if="['pending', 'overdue'].includes(invoice.status)">
                      <span class="row-actions-divider"></span>
                      <button type="button" @click="handleCancelInvoice(invoice)" :disabled="cancellingId === invoice.id" class="action-btn action-btn--delete" :title="t('owner_invoices.cancel_action')">
                        <LoaderCircle class="animate-spin" aria-hidden="true" v-if="cancellingId === invoice.id" /><Ban aria-hidden="true" v-else />
                      </button>
                    </template>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
          <div
            v-for="invoice in invoices" :key="invoice.id"
            class="glass-card p-3.5"
            :class="{ 'opacity-50 pointer-events-none': cancellingId === invoice.id }"
          >
            <div class="flex items-start justify-between mb-2.5">
              <div class="min-w-0">
                <div class="font-bold text-[12.5px] truncate">{{ invoice.subscriber?.name ?? "-" }}</div>
                <div class="text-[10px] text-[#9a9d97]">{{ invoice.generator?.name ?? "-" }}</div>
              </div>
              <span class="status-chip shrink-0" :class="INVOICE_STATUS_META[invoice.status]">{{ invoiceStatusLabel(invoice.status) }}</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-[11px] mb-2.5">
              <div><span class="text-[#9a9d97]">{{ t("owner_invoices.amount_col") }}:</span> <b dir="ltr">{{ invoice.final_amount }} {{ invoice.currency }}</b></div>
              <div><span class="text-[#9a9d97]">{{ t("owner_invoices.due_date_col") }}:</span> <b dir="ltr">{{ invoice.due_date ?? "-" }}</b></div>
            </div>
            <div class="flex items-center justify-between">
              <span class="font-extrabold text-[12.5px]" dir="ltr" :class="invoice.remaining_balance_ils > 0 ? 'text-[#FFC107]' : 'text-[#9a9d97]'">
                {{ invoice.remaining_balance_ils.toFixed(2) }} ₪
              </span>
              <div class="row-actions">
                <a
                  :href="invoiceService.downloadPdfUrl(invoice.id)" target="_blank" rel="noopener"
                  class="action-btn action-btn--view" :title="t('owner_invoices.download_pdf')"
                >
                  <FileDown aria-hidden="true" />
                </a>
                <template v-if="['pending', 'overdue'].includes(invoice.status)">
                  <span class="row-actions-divider"></span>
                  <button type="button" @click="handleCancelInvoice(invoice)" :disabled="cancellingId === invoice.id" class="action-btn action-btn--delete" :title="t('owner_invoices.cancel_action')">
                    <LoaderCircle class="animate-spin" aria-hidden="true" v-if="cancellingId === invoice.id" /><Ban aria-hidden="true" v-else />
                  </button>
                </template>
              </div>
            </div>
          </div>
        </div>
        </template>

        <div v-if="invoicesPagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-2 mt-3 text-[11px] text-[#9a9d97]">
          <span>{{ t("owner_invoices.pagination_text", { current: invoicesPagination.current_page, last: invoicesPagination.last_page, total: invoicesPagination.total }) }}</span>
          <div class="flex items-center gap-1">
            <button :aria-label="t('common.previous_page')" type="button" :disabled="invoicesPagination.current_page <= 1" @click="fetchInvoices(invoicesPagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronRight class="text-[10px]" aria-hidden="true" /></button>
            <button :aria-label="t('common.next_page')" type="button" :disabled="invoicesPagination.current_page >= invoicesPagination.last_page" @click="fetchInvoices(invoicesPagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronLeft class="text-[10px]" aria-hidden="true" /></button>
          </div>
        </div>
      </section>
    </template>

    <!-- ==========================================================
         ==================  تبويب: الدفعات  ========================
         ========================================================== -->
    <!-- FIX: كانت v-else بدل v-else-if، فكانت تظهر معًا مع تبويب "دفعات
         الفنيين" (activeTab === 'technician_payments') بنفس الوقت. -->
    <template v-else-if="activeTab === 'payments'">
      <!-- ===== TOOLBAR ===== -->
      <section v-reveal class="glass-card p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[220px]">
            <div class="relative flex-1 min-w-[160px] max-w-xs">
              <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] text-[10px]" aria-hidden="true" />
              <input
                v-model="paymentSearch" type="text" @input="onPaymentSearchInput"
                :placeholder="t('owner_payments.search_placeholder')"
                class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F]"
              />
            </div>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
              <button
                v-for="pill in PAYMENT_STATUS_PILLS" :key="pill.v" type="button"
                @click="selectPaymentStatusFilter(pill.v)"
                class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
                :class="paymentStatusFilter === pill.v ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
              >{{ pill.l }}</button>
            </div>
            <div class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
            <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1">
              <button :aria-label="t('common.view_as_table')" type="button" @click="paymentsViewMode = 'table'" class="icon-btn !w-8 !h-8" :class="{ '!bg-white dark:!bg-white/10': paymentsViewMode === 'table' }"><Table2 class="text-[11px]" aria-hidden="true" /></button>
              <button :aria-label="t('common.view_as_grid')" type="button" @click="paymentsViewMode = 'grid'" class="icon-btn !w-8 !h-8" :class="{ '!bg-white dark:!bg-white/10': paymentsViewMode === 'grid' }"><GripVertical class="text-[11px]" aria-hidden="true" /></button>
            </div>
            <a
              :href="exportUrl" target="_blank" rel="noopener"
              class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
              :title="t('owner_payments.export_excel')"
            >
              <FileSpreadsheet class="text-[11px]" aria-hidden="true" />
            </a>
            <button
              type="button" @click="printPage"
              class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
              :title="t('owner_applications_page.print')"
            >
              <Printer class="text-[11px]" aria-hidden="true" />
            </button>
          </div>
        </div>
      </section>

      <!-- ===== TABLE ===== -->
      <section v-reveal class="glass-card p-4 overflow-hidden">
        <div v-if="isPaymentsLoading" class="space-y-2">
          <div v-for="i in 6" :key="i" class="h-14 rounded-lg thumb-loading"></div>
        </div>

        <div v-else-if="paymentsError" class="text-center py-8 text-[12px] text-[#D9534F]">{{ paymentsError }}</div>

        <div v-else-if="payments.length === 0" class="text-center py-10">
          <Check class="text-2xl text-[#c9cdc2] mb-2" aria-hidden="true" />
          <p class="text-[12px] text-[#9a9d97]">{{ t("owner_payments.no_matching_payments") }}</p>
        </div>

        <template v-else>
        <div v-if="paymentsViewMode === 'table'" class="overflow-x-auto -mx-1">
          <table class="data-table w-full text-[12px] min-w-[820px]">
            <thead>
              <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
                <th class="py-2.5 px-3 rounded-s-lg">{{ t("owner_payments.subscriber_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_payments.method_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_payments.generator_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_payments.amount_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_payments.attachments_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_payments.status_col") }}</th>
                <th class="py-2.5 px-3 rounded-e-lg">{{ t("owner_payments.actions_col") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="payment in payments" :key="payment.id"
                class="border-b border-[#f0ece0] dark:border-white/5 last:border-0 text-center cursor-pointer hover:bg-[#f4efe5]/40 dark:hover:bg-white/5"
                @click="openReview(payment)"
              >
                <td class="py-2.5 px-3 font-bold">{{ payment.subscriber?.name ?? t("owner_payments.unknown_subscriber") }}</td>
                <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">
                  <AppIcon :name="methodIcon(payment.payment_method_type)" class="text-[10px] me-1" />
                  {{ methodLabel(payment.payment_method_type) }}
                </td>
                <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">{{ payment.generator?.name ?? "-" }}</td>
                <td class="py-2.5 px-3 font-bold" dir="ltr">{{ payment.amount }} {{ payment.currency }}</td>
                <td class="py-2.5 px-3 text-[#9a9d97]">
                  <span v-if="payment.attachments_count"><Paperclip aria-hidden="true" /> {{ payment.attachments_count }}</span>
                  <span v-else>-</span>
                </td>
                <td class="py-2.5 px-3"><span class="status-chip" :class="PAYMENT_STATUS_META[payment.status] ?? 'chip-info'">{{ paymentStatusLabel(payment.status) }}</span></td>
                <td class="py-2.5 px-3">
                  <div class="row-actions">
                    <button type="button" @click.stop="openReview(payment)" class="action-btn action-btn--view" :title="t('common.view')"><Eye aria-hidden="true" /></button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
          <button
            v-for="payment in payments" :key="payment.id" type="button"
            class="glass-card p-3.5 text-start hover:bg-[#f4efe5]/40 dark:hover:bg-white/5 transition-colors"
            @click="openReview(payment)"
          >
            <div class="flex items-start justify-between mb-2.5">
              <div class="min-w-0">
                <div class="font-bold text-[12.5px] truncate">{{ payment.subscriber?.name ?? t("owner_payments.unknown_subscriber") }}</div>
                <div class="text-[10px] text-[#9a9d97]">{{ payment.generator?.name ?? "-" }}</div>
              </div>
              <span class="status-chip shrink-0" :class="PAYMENT_STATUS_META[payment.status] ?? 'chip-info'">{{ paymentStatusLabel(payment.status) }}</span>
            </div>
            <div class="grid grid-cols-2 gap-2 text-[11px] mb-2.5">
              <div>
                <span class="text-[#9a9d97]">{{ t("owner_payments.method_col") }}:</span>
                <b><AppIcon :name="methodIcon(payment.payment_method_type)" class="text-[10px] me-1" />{{ methodLabel(payment.payment_method_type) }}</b>
              </div>
              <div>
                <span class="text-[#9a9d97]">{{ t("owner_payments.attachments_col") }}:</span>
                <b v-if="payment.attachments_count"><Paperclip aria-hidden="true" /> {{ payment.attachments_count }}</b>
                <b v-else>-</b>
              </div>
            </div>
            <div class="flex items-center justify-between">
              <span class="font-extrabold text-[12.5px]" dir="ltr">{{ payment.amount }} {{ payment.currency }}</span>
              <div class="row-actions">
                <button type="button" @click.stop="openReview(payment)" class="action-btn action-btn--view" :title="t('common.view')"><Eye aria-hidden="true" /></button>
              </div>
            </div>
          </button>
        </div>
        </template>

        <div v-if="paymentsPagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-2 mt-3 text-[11px] text-[#9a9d97]">
          <span>{{ t("owner_payments.pagination_text", { current: paymentsPagination.current_page, last: paymentsPagination.last_page, total: paymentsPagination.total }) }}</span>
          <div class="flex items-center gap-1">
            <button :aria-label="t('common.previous_page')" type="button" :disabled="paymentsPagination.current_page <= 1" @click="fetchPayments(paymentsPagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronRight class="text-[10px]" aria-hidden="true" /></button>
            <button :aria-label="t('common.next_page')" type="button" :disabled="paymentsPagination.current_page >= paymentsPagination.last_page" @click="fetchPayments(paymentsPagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronLeft class="text-[10px]" aria-hidden="true" /></button>
          </div>
        </div>
      </section>
    </template>

    <!-- ==========================================================
         ==================  تبويب: دفعات الفنيين  ===================
         ========================================================== -->
    <template v-else-if="activeTab === 'technician_payments'">
      <!-- ===== TOOLBAR (فلتر الفني) ===== -->
      <section v-reveal class="glass-card p-4">
        <div class="flex flex-wrap items-center gap-2">
          <label class="text-[11.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t("owner_technician_payments.filter_label") }}</label>
          <AppDropdownSelect
            v-model="technicianFilter" @update:model-value="onTechnicianPaymentFilterChange"
            :options="technicianFilterOptions" width-class="w-48" :panel-width="200"
          />
        </div>
      </section>

      <!-- ===== LIST ===== -->
      <section v-reveal class="glass-card p-4 overflow-hidden">
        <div v-if="isTechnicianPaymentsLoading" class="space-y-2">
          <div v-for="i in 5" :key="i" class="h-16 rounded-lg thumb-loading"></div>
        </div>

        <div v-else-if="technicianPaymentsError" class="text-center py-8 text-[12px] text-[#D9534F]">{{ technicianPaymentsError }}</div>

        <div v-else-if="technicianPayments.length === 0" class="text-center py-12">
          <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center">
            <UserCog class="text-2xl text-[#52733D] dark:text-[#8cc35a]" aria-hidden="true" />
          </div>
          <h3 class="text-[13.5px] font-bold mb-1">{{ t("owner_technician_payments.empty_title") }}</h3>
          <p class="text-[12px] text-[#9a9d97]">{{ t("owner_technician_payments.empty_desc") }}</p>
        </div>

        <div v-else class="overflow-x-auto -mx-1">
          <table class="data-table w-full text-[12px] min-w-[720px]">
            <thead>
              <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
                <th class="py-2.5 px-3 rounded-s-lg">{{ t("owner_technician_payments.technician_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_technician_payments.amount_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_technician_payments.note_col") }}</th>
                <th class="py-2.5 px-3">{{ t("owner_technician_payments.status_col") }}</th>
                <th class="py-2.5 px-3 rounded-e-lg">{{ t("owner_technician_payments.date_col") }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="payment in technicianPayments" :key="payment.id" class="border-b border-[#f0ece0] dark:border-white/5 last:border-0 text-center">
                <td class="py-2.5 px-3 font-bold">{{ payment.technician_name ?? "-" }}</td>
                <td class="py-2.5 px-3 font-bold" dir="ltr">{{ payment.amount }} {{ payment.currency }}</td>
                <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5] truncate max-w-[16rem]">
                  <span v-if="payment.status === 'rejected' && payment.rejection_reason" class="text-[#D9534F]">{{ payment.rejection_reason }}</span>
                  <span v-else>{{ payment.note || "-" }}</span>
                </td>
                <td class="py-2.5 px-3"><span class="status-chip" :class="TECHNICIAN_PAYMENT_STATUS_META[payment.status] ?? 'chip-info'">{{ technicianPaymentStatusLabel(payment.status) }}</span></td>
                <td class="py-2.5 px-3 text-[#9a9d97]" dir="ltr">{{ payment.created_at ? new Date(payment.created_at).toLocaleDateString() : "-" }}</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="technicianPaymentsPagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-2 mt-3 text-[11px] text-[#9a9d97]">
          <span>{{ t("owner_technician_payments.pagination_text", { current: technicianPaymentsPagination.current_page, last: technicianPaymentsPagination.last_page, total: technicianPaymentsPagination.total }) }}</span>
          <div class="flex items-center gap-1">
            <button :aria-label="t('common.previous_page')" type="button" :disabled="technicianPaymentsPagination.current_page <= 1" @click="fetchTechnicianPayments(technicianPaymentsPagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronRight class="text-[10px]" aria-hidden="true" /></button>
            <button :aria-label="t('common.next_page')" type="button" :disabled="technicianPaymentsPagination.current_page >= technicianPaymentsPagination.last_page" @click="fetchTechnicianPayments(technicianPaymentsPagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronLeft class="text-[10px]" aria-hidden="true" /></button>
          </div>
        </div>
      </section>
    </template>

    <!-- ===== نافذة إضافة دفعة فني ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isCreateTechnicianPaymentOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeCreateTechnicianPaymentModal">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--green">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><UserCog aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title">{{ t("owner_technician_payments.add_modal_title") }}</h3>
                </div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="closeCreateTechnicianPaymentModal" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>

            <div class="p-5 space-y-3.5">
              <div v-if="technicianPaymentCreateError?.message" class="alert-box"><CircleAlert class="shrink-0" aria-hidden="true" /> {{ technicianPaymentCreateError.message }}</div>

              <div>
                <label class="field-label">{{ t("owner_technician_payments.technician_label") }}</label>
                <AppDropdownSelect
                  v-model="newTechnicianPaymentForm.technician_id"
                  :options="technicianSelectOptions"
                  :placeholder="t('owner_technician_payments.choose_technician')"
                  variant="field" width-class="w-full" match-trigger-width
                />
              </div>

              <!-- ===== بيانات دفع الفني (معلوماتي فقط — لا يُرسَل ضمن نموذج الدفعة) ===== -->
              <div v-if="newTechnicianPaymentForm.technician_id" class="alert-box !text-[#6B6B6B] dark:!text-[#a8aaa5] !bg-[#EBF1E7] dark:!bg-white/5 !border-[#e7e2d6] dark:!border-white/10 flex-col !items-stretch gap-1.5">
                <span class="font-bold text-[#3E582E] dark:text-[#8cc35a]"><Info class="me-1" aria-hidden="true" />{{ t("owner_technician_payments.technician_payment_info_title") }}</span>
                <span v-if="isLoadingTechnicianPaymentMethodsInfo">{{ t("common.loading") }}</span>
                <template v-else-if="technicianPaymentMethodsInfo.length === 0">
                  <span>{{ t("owner_technician_payments.technician_payment_info_empty") }}</span>
                </template>
                <ul v-else class="space-y-1">
                  <li v-for="method in technicianPaymentMethodsInfo" :key="method.id">
                    {{ t(`owner_technician_payments.technician_payment_info_type_${method.type}`) }}
                    <template v-if="method.type !== 'cash'"> — {{ method.bank_name }} — {{ method.account_name }} — {{ method.account_number_masked }}</template>
                  </li>
                </ul>
              </div>

              <div>
                <label class="field-label">{{ t("owner_technician_payments.amount_label") }}</label>
                <input v-model.number="newTechnicianPaymentForm.amount" type="number" min="0.01" step="0.01" class="field-input" />
              </div>

              <div>
                <label class="field-label">{{ t("owner_technician_payments.note_label") }} <span class="text-[#9a9d97] font-normal">({{ t("owner_technicians.optional") }})</span></label>
                <textarea v-model="newTechnicianPaymentForm.note" rows="2" maxlength="500" class="field-input resize-none"></textarea>
              </div>
            </div>

            <div class="modal-footer-brand">
              <button type="button" @click="closeCreateTechnicianPaymentModal" class="btn-outline-brand">{{ t("owner_technicians.cancel") }}</button>
              <button
                type="button" @click="handleCreateTechnicianPayment"
                :disabled="isCreatingTechnicianPayment || !newTechnicianPaymentForm.technician_id || !newTechnicianPaymentForm.amount"
                class="btn-fill-brand"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isCreatingTechnicianPayment" /><Plus aria-hidden="true" v-else />
                {{ isCreatingTechnicianPayment ? t("owner_technician_payments.saving_ellipsis") : t("owner_technician_payments.add_button") }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ===================== نافذة مراجعة الدفعة ===================== -->
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
  </div>
</template>