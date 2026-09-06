<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
import { useAdminInvoices } from "@/composables/useAdminInvoices";
import { useConfirm } from "@/composables/useConfirm";
import { vReveal } from "@/directives/reveal";
import InvoiceCorrectionModal from "@/components/admin/InvoiceCorrectionModal.vue";
import invoiceService from "@/services/invoiceService";
import { useToastStore } from "@/stores/toast";
import { Ban, CalendarCheck, CalendarDays, ChevronLeft, ChevronRight, Circle, Eye, FileSpreadsheet, HandCoins, LoaderCircle, Pencil, Printer, QrCode, Receipt, RotateCw, Search, User, X, ZoomOut } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";
import StatCard from "@/components/dashboard/StatCard.vue";


const { t, locale } = useI18n();
const route = useRoute();
const { confirm } = useConfirm();
const toast = useToastStore();

const {
  invoices,
  pagination,
  isLoading,
  error,
  statusFilter,
  search,
  fetchInvoices,
  applyFilter,
  onSearchInput,
  replaceInvoice,
  cancellingId,
  cancelError,
  cancelInvoice,
  reissuingId,
  reissueError,
  reissueInvoice,
} = useAdminInvoices();

/* ---------------- نافذة QR للتحقق من الفاتورة ----------------
 * FIX (تدقيق شامل للوحة الأدمن — بند 9): GET /invoices/{id}/qr جاهز بالكامل
 * بالباك اند (invoiceService.qrCode) لكنه كان Endpoint ميتًا بلا أي استخدام
 * بالفرونت. نفس نمط عرض QR المعتمَد فعليًا بواجهة المالك (owner/GeneratorsView.vue).
 */
const invoiceQrModal = ref({ open: false, loading: false, src: null, invoiceId: null });
async function handleShowInvoiceQr(invoice) {
  invoiceQrModal.value = { open: true, loading: true, src: null, invoiceId: invoice.id };
  try {
    const { data } = await invoiceService.qrCode(invoice.id);
    invoiceQrModal.value.src = data.data.qr;
  } finally {
    invoiceQrModal.value.loading = false;
  }
}

/* ---------------- حالة الفاتورة ---------------- */
const STATUS_META = {
  pending: { key: "invoices_page.status_pending", chip: "chip-warning", color: "#FFC107" },
  paid: { key: "invoices_page.status_paid", chip: "chip-success", color: "#28A745" },
  partially_paid: { key: "invoices_page.status_partially_paid", chip: "chip-info", color: "#17A2B8" },
  overdue: { key: "invoices_page.status_overdue", chip: "chip-danger", color: "#D9534F" },
  cancelled: { key: "subscriptions_page.status_cancelled", chip: null, color: "#9a9d97" },
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

const STATUS_PILLS = computed(() => [
  { value: "all", label: t("common.all") },
  { value: "pending", label: statusLabel("pending") },
  { value: "paid", label: statusLabel("paid") },
  { value: "partially_paid", label: statusLabel("partially_paid") },
  { value: "overdue", label: statusLabel("overdue") },
  { value: "cancelled", label: statusLabel("cancelled") },
]);

/* ---------------- فرز عبر رؤوس الأعمدة (سهم تصاعدي/تنازلي - نفس أسلوب باقي صفحات الأدمن) ---------------- */
const sortKey = ref("");
const sortDir = ref("desc");
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
const sortedInvoices = computed(() => {
  if (!sortKey.value) return invoices.value;
  const list = [...invoices.value];
  list.sort((a, b) => {
    let va, vb;
    if (sortKey.value === "id") {
      va = a.id;
      vb = b.id;
    } else if (sortKey.value === "due_date") {
      va = new Date(a.due_date ?? 0).getTime();
      vb = new Date(b.due_date ?? 0).getTime();
    }
    if (va < vb) return sortDir.value === "asc" ? -1 : 1;
    if (va > vb) return sortDir.value === "asc" ? 1 : -1;
    return 0;
  });
  return list;
});

/* ---------------- KPI Cards ---------------- */
const countOnPage = (status) => invoices.value.filter((i) => i.status === status).length;
const KPI_CARDS = computed(() => [
  { icon: "fa-file-invoice-dollar", label: t("invoices_page.total_invoices"), value: pagination.value.total, tone: "secondary" },
  { icon: "fa-circle-check", label: statusLabel("paid") + t("common.this_page_suffix"), value: countOnPage("paid"), tone: "success" },
  { icon: "fa-hourglass-half", label: statusLabel("pending") + t("common.this_page_suffix"), value: countOnPage("pending"), tone: "warning" },
  { icon: "fa-triangle-exclamation", label: statusLabel("overdue") + t("common.this_page_suffix"), value: countOnPage("overdue"), tone: "danger" },
]);

/* ---------------- مؤشر حالة النظام بالهيدر (نفس أسلوب صفحة قراءات العدادات) ----------------
 * ملاحظة نطاق البيانات: مبني على invoices.value (سجلات الصفحة الحالية فقط)، نفس ملاحظة
 * بطاقات KPI أعلاه المعلَّم عليها "(هذه الصفحة)".
 */
const systemStatusInfo = computed(() => {
  const overdueCount = countOnPage("overdue");
  if (overdueCount === 0) {
    return {
      label: t("invoices_page.no_overdue_invoices"),
      color: "#28A745",
    };
  }
  return {
    label: t("invoices_page.overdue_invoices_count", { count: overdueCount }),
    color: "#D9534F",
  };
});

/* ---------------- Pagination بأزرار محدودة ---------------- */
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

/* ---------------- تصحيح الفاتورة ---------------- */
const correctionModal = ref({ open: false, invoice: null });
function openCorrection(invoice) {
  correctionModal.value = { open: true, invoice };
}

/* ---------------- إلغاء / إعادة إصدار ---------------- */
async function handleCancel(invoice) {
  const confirmed = await confirm({
    title: t("invoices_page.cancel_invoice_title", { id: invoice.id }),
    message: t("invoices_page.cancel_invoice_message"),
    confirmLabel: t("invoices_page.cancel_invoice_button"),
    variant: "danger",
  });
  if (confirmed) {
    await cancelInvoice(invoice.id);
    if (cancelError.value) toast.show({ type: "danger", title: cancelError.value });
  }
}

async function handleReissue(invoice) {
  const confirmed = await confirm({
    title: t("invoices_page.reissue_invoice_title", { id: invoice.id }),
    message: t("invoices_page.reissue_invoice_message"),
    confirmLabel: t("invoices_page.reissue_action"),
  });
  if (confirmed) {
    await reissueInvoice(invoice.id);
    if (reissueError.value) toast.show({ type: "danger", title: reissueError.value });
  }
}

/* ---------------- نافذة عرض التفاصيل ---------------- */
const viewingInvoice = ref(null);
function openView(invoice) {
  viewingInvoice.value = invoice;
}
function formatDate(dateStr) {
  return dateStr ? String(dateStr).slice(0, 10) : "-";
}

/* ---------------- تصدير CSV ----------------
 * على مستوى الصفحة المحمّلة حاليًا فقط، مو كل السجلات (نفس منهجية باقي الصفحات).
 * مترجم بالكامل حسب اللغة الحالية بدل الاعتماد على نص عربي ثابت.
 */
function handleExportCsv() {
  const escapeCsv = (val) => {
    const s = String(val ?? "");
    return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
  };
  const header = [
    t("invoices_page.invoice_no_col"),
    t("invoices_page.amount_col"),
    t("dashboard.currency"),
    t("invoices_page.due_date_col"),
    t("dashboard.status_col"),
  ];
  const rows = invoices.value.map((i) => [i.id, i.final_amount, i.currency, i.due_date ?? "", statusLabel(i.status)]);
  const csv = "\uFEFF" + [header, ...rows].map((r) => r.map(escapeCsv).join(",")).join("\n");
  const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = `invoices-${new Date().toISOString().slice(0, 10)}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

/* ---------------- طباعة (الصفحة المحمّلة حاليًا) — نفس أسلوب صفحة المدفوعات (isAr-aware) ---------------- */
function handlePrint() {
  const isAr = locale.value === "ar";
  const rowsHtml = sortedInvoices.value
    .map(
      (i) => `<tr>
      <td>#${i.id}</td><td>${i.subscriber?.name ?? "-"}</td>
      <td>${i.final_amount} ${i.currency}</td><td>${formatDate(i.due_date)}</td>
      <td>${statusLabel(i.status)}</td>
    </tr>`,
    )
    .join("");
  const printWin = window.open("", "_blank");
  if (!printWin) return;
  printWin.document.write(`<html dir="${isAr ? "rtl" : "ltr"}" lang="${isAr ? "ar" : "en"}"><head><meta charset="utf-8"><title>${t("invoices_page.invoices_list_title")}</title>
    <style>
      body{font-family:sans-serif;padding:24px;color:#222}
      h1{color:#3E582E}
      table{width:100%;border-collapse:collapse;margin-top:16px}
      th,td{border:1px solid #ccc;padding:8px;text-align:${isAr ? "right" : "left"};font-size:13px}
      th{background:#EBF1E7}
    </style></head><body>
    <h1>${t("invoices_page.invoices_list_title")}</h1>
    <p>${new Date().toLocaleString(isAr ? "ar-EG" : "en-GB")}</p>
    <table><thead><tr><th>${t("invoices_page.invoice_no_col")}</th><th>${t("subscribers_page.subscriber_col")}</th><th>${t("invoices_page.amount_col")}</th><th>${t("invoices_page.due_date_col")}</th><th>${t("dashboard.status_col")}</th></tr></thead>
    <tbody>${rowsHtml}</tbody></table></body></html>`);
  printWin.document.close();
  printWin.focus();
  setTimeout(() => printWin.print(), 400);
}

onMounted(() => {
  if (route.query.q) search.value = String(route.query.q);
  // دعم الوصول المباشر من تنبيهات صفحة أصحاب المولدات (?status=overdue)
  // — يفتح الفواتير المتأخرة مباشرة بدل ما الأدمن يفلتر يدويًا.
  if (route.query.status) statusFilter.value = String(route.query.status);
  fetchInvoices();
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
        <ChevronLeft class="rtl:block ltr:hidden text-[9px]" aria-hidden="true" />
        <ChevronRight class="ltr:block rtl:hidden text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ $t("invoices_page.title") }}</span>
      </nav>
      <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] text-white flex items-center justify-center text-base">
              <Receipt aria-hidden="true" />
            </span>
            {{ $t("invoices_page.title") }}
          </h1>
          <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
            {{ $t("invoices_page.subtitle") }}
          </p>
          <span
            v-if="systemStatusInfo"
            class="inline-flex items-center gap-2 text-[11px] font-bold mt-2.5"
            :style="{ color: systemStatusInfo.color }"
          >
            <Circle class="text-[7px]" aria-hidden="true" /> {{ systemStatusInfo.label }}
          </span>
        </div>
      </div>
    </section>

    <!-- ===== KPI CARDS ===== -->
    <section v-reveal>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
        <StatCard
          v-for="c in KPI_CARDS" :key="c.label"
          :label="c.label" :value="c.value" :icon="c.icon" :tone="c.tone"
        />
      </div>
    </section>

    <!-- ===== TOOLBAR (فوق الجدول — بنفس هوية شريط أدوات صفحة إدارة المولدات) ===== -->
    <section v-reveal class="glass-card p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[220px]">
          <div class="relative flex-1 min-w-[260px] max-w-lg">
            <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
            <input
              v-model="search"
              type="text"
              @input="onSearchInput"
              :placeholder="$t('invoices_page.search_placeholder')"
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
              @click="applyFilter(pill.value)"
              class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
              :class="statusFilter === pill.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
            >
              {{ pill.label }}
            </button>
          </div>
          <div class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
          <button
            type="button"
            @click="handleExportCsv"
            class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
            :title="$t('users_page.export_csv')"
            :aria-label="$t('users_page.export_csv')"
          >
            <FileSpreadsheet class="text-[11px]" aria-hidden="true" />
          </button>
          <button
            type="button"
            @click="handlePrint"
            class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5"
            :title="$t('owner_applications_page.print')"
            :aria-label="$t('owner_applications_page.print')"
          >
            <Printer class="text-[11px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===== TABLE ===== -->
    <section v-reveal class="glass-card p-4 overflow-hidden">
      <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
        <h3 class="text-[13.5px] font-bold">{{ $t("invoices_page.invoices_list_title") }}</h3>
        <span class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ sortedInvoices.length }} {{ $t("invoices_page.invoices_list_title") }}</span>
      </div>

      <div v-if="error" class="mb-3 text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2">{{ error }}</div>
      <div v-if="cancelError" class="mb-3 text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2">{{ cancelError }}</div>

      <div v-if="isLoading" class="space-y-2">
        <div v-for="i in 6" :key="i" class="h-14 rounded-lg thumb-loading"></div>
      </div>

      <div v-else-if="invoices.length === 0" class="text-center py-10">
        <ZoomOut class="text-2xl text-[#c9cdc2] dark:text-[#565952] mb-2" aria-hidden="true" />
        <p class="text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("invoices_page.no_matching_invoices") }}</p>
      </div>

      <div v-else class="overflow-x-auto -mx-1">
        <table class="data-table w-full text-[12px] min-w-[820px]">
          <thead>
            <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
              <th class="py-2.5 px-3 rounded-s-lg cursor-pointer select-none" @click="toggleSort('id')">
                {{ $t("invoices_page.invoice_no_col") }}
                <AppIcon :name="sortIconClass('id')" class="text-[9px] ms-1 transition-all" />
              </th>
              <th class="py-2.5 px-3">{{ $t("invoices_page.amount_col") }}</th>
              <th class="py-2.5 px-3 cursor-pointer select-none" @click="toggleSort('due_date')">
                {{ $t("invoices_page.due_date_col") }}
                <AppIcon :name="sortIconClass('due_date')" class="text-[9px] ms-1 transition-all" />
              </th>
              <th class="py-2.5 px-3">{{ $t("dashboard.status_col") }}</th>
              <th class="py-2.5 px-3 rounded-e-lg">{{ $t("subscribers_page.actions_col") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="invoice in sortedInvoices"
              :key="invoice.id"
              class="border-b border-[#f0ece0] dark:border-white/5 last:border-0 text-center transition-colors hover:bg-[#f4efe5]/40 dark:hover:bg-white/[0.03]"
              :class="{ 'opacity-50 pointer-events-none': cancellingId === invoice.id || reissuingId === invoice.id }"
            >
              <td class="py-2.5 px-3">
                <button type="button" @click="openView(invoice)" class="font-bold hover:text-[#8A6D1F] transition-colors">
                  #{{ invoice.id }}
                </button>
              </td>
              <td class="py-2.5 px-3 font-bold text-[#8A6D1F] dark:text-[#D4AF37]">{{ invoice.final_amount }} {{ invoice.currency }}</td>
              <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">{{ formatDate(invoice.due_date) }}</td>
              <td class="py-2.5 px-3">
                <span class="status-chip" :class="statusChip(invoice.status)" :style="statusChipStyle(invoice.status)">{{ statusLabel(invoice.status) }}</span>
              </td>
              <td class="py-2.5 px-3">
                <div class="row-actions">
                  <button type="button" @click="openView(invoice)" class="action-btn action-btn--view" :title="$t('common.view')" :aria-label="$t('common.view')">
                    <Eye aria-hidden="true" />
                  </button>
                  <button type="button" @click="openCorrection(invoice)" class="action-btn action-btn--edit" :title="$t('invoices_page.correct_action')" :aria-label="$t('invoices_page.correct_action')">
                    <Pencil aria-hidden="true" />
                  </button>
                  <button type="button" @click="handleShowInvoiceQr(invoice)" class="action-btn action-btn--view" :title="$t('invoices_page.show_qr')" :aria-label="$t('invoices_page.show_qr')">
                    <QrCode aria-hidden="true" />
                  </button>
                  <span class="row-actions-divider"></span>
                  <button
                    v-if="invoice.status === 'cancelled'"
                    type="button"
                    :disabled="reissuingId === invoice.id"
                    @click="handleReissue(invoice)"
                    class="action-btn action-btn--reissue"
                    :title="$t('invoices_page.reissue_action')"
                    :aria-label="$t('invoices_page.reissue_action')"
                  >
                    <LoaderCircle class="animate-spin" aria-hidden="true" v-if="reissuingId === invoice.id" /><RotateCw aria-hidden="true" v-else />
                  </button>
                  <button
                    v-else
                    type="button"
                    :disabled="cancellingId === invoice.id"
                    @click="handleCancel(invoice)"
                    class="action-btn action-btn--cancel"
                    :title="$t('dashboard.cancel')"
                    :aria-label="$t('dashboard.cancel')"
                  >
                    <LoaderCircle class="animate-spin" aria-hidden="true" v-if="cancellingId === invoice.id" /><Ban aria-hidden="true" v-else />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination بأزرار محدودة -->
      <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-3.5">
        <span class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
          {{ $t("invoices_page.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}
        </span>
        <div class="flex items-center gap-1">
          <button :aria-label="$t('common.previous_page')"
            type="button"
            :disabled="pagination.current_page <= 1"
            @click="fetchInvoices(pagination.current_page - 1)"
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
              @click="fetchInvoices(page)"
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
            @click="fetchInvoices(pagination.current_page + 1)"
            class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
          >
            <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===================== نافذة عرض التفاصيل (بنفس أسلوب نوافذ العرض بباقي صفحات الأدمن) ===================== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div
        v-if="viewingInvoice"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        @click.self="viewingInvoice = null"
      >
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-2xl max-h-[88vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
          <!-- Header -->
          <div class="modal-head-brand modal-head-brand--gold shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><Receipt aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ $t("common.invoice_hash", { id: viewingInvoice.id }) }}</h3>
                <p class="modal-head-brand__subtitle">{{ viewingInvoice.subscriber?.name ?? "-" }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="viewingInvoice = null" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <div class="p-5 grid sm:grid-cols-2 gap-4 overflow-y-auto">
            <div class="space-y-4">
              <div class="glass-card p-4 flex items-center gap-4">
                <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0" :style="{ background: (STATUS_META[viewingInvoice.status]?.color ?? '#9a9d97') + '1A' }">
                  <Receipt class="text-[16px]" aria-hidden="true" :style="{ color: STATUS_META[viewingInvoice.status]?.color ?? '#9a9d97' }" />
                </div>
                <div class="min-w-0">
                  <div class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] mb-1.5">{{ $t("invoices_page.invoice_status_label") }}</div>
                  <span class="status-chip" :class="statusChip(viewingInvoice.status)" :style="statusChipStyle(viewingInvoice.status)">{{ statusLabel(viewingInvoice.status) }}</span>
                </div>
              </div>

              <div class="glass-card p-4 space-y-1">
                <div class="info-row">
                  <span class="info-row-icon"><User aria-hidden="true" /></span>
                  <span class="info-row-label">{{ $t("subscribers_page.subscriber_col") }}</span>
                  <b class="info-row-value">{{ viewingInvoice.subscriber?.name ?? "-" }}</b>
                </div>
                <div class="info-row">
                  <span class="info-row-icon"><HandCoins aria-hidden="true" /></span>
                  <span class="info-row-label">{{ $t("invoices_page.amount_col") }}</span>
                  <b class="info-row-value">{{ viewingInvoice.final_amount }} {{ viewingInvoice.currency }}</b>
                </div>
              </div>
            </div>

            <div class="space-y-4">
              <div class="grid grid-cols-2 gap-2.5">
                <div class="glass-card p-3 text-center">
                  <CalendarDays class="text-[#52733D] dark:text-[#8cc35a] text-[13px] mb-1" aria-hidden="true" />
                  <div class="text-sm font-extrabold">{{ formatDate(viewingInvoice.issued_at) }}</div>
                  <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("invoices_page.issued_label") }}</div>
                </div>
                <div class="glass-card p-3 text-center">
                  <CalendarCheck class="text-[#D9534F] text-[13px] mb-1" aria-hidden="true" />
                  <div class="text-sm font-extrabold">{{ formatDate(viewingInvoice.due_date) }}</div>
                  <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("invoices_page.due_date_col") }}</div>
                </div>
              </div>

              <button type="button" @click="openCorrection(viewingInvoice); viewingInvoice = null" class="glass-card p-4 w-full flex items-center gap-2.5 text-start hover:bg-[#f4efe5]/50 dark:hover:bg-white/5 transition-colors">
                <span class="w-9 h-9 rounded-lg bg-[#8A6D1F]/10 text-[#8A6D1F] flex items-center justify-center shrink-0"><Pencil aria-hidden="true" /></span>
                <span class="text-[12px] font-bold">{{ $t("invoices_page.correct_this_invoice") }}</span>
              </button>
            </div>
          </div>

          <div class="modal-footer-brand">
            <button type="button" @click="viewingInvoice = null" class="btn-outline-brand">
              {{ $t("common.close") }}
            </button>
            <button
              type="button"
              @click="openCorrection(viewingInvoice); viewingInvoice = null"
              class="btn-fill-brand"
            >
              <Pencil aria-hidden="true" />
              {{ $t("invoices_page.correct_action") }}
            </button>
          </div>
        </div>
      </div>
      </Transition>
    </Teleport>

    <InvoiceCorrectionModal
      :open="correctionModal.open"
      :invoice="correctionModal.invoice"
      @close="correctionModal.open = false"
      @updated="replaceInvoice"
    />

    <!-- ===================== نافذة QR للتحقق من الفاتورة ===================== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="invoiceQrModal.open" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="invoiceQrModal.open = false">
          <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-xs shadow-2xl overflow-hidden">
            <div class="modal-head-brand modal-head-brand--gold shrink-0">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><QrCode aria-hidden="true" /></span>
                <h3 class="modal-head-brand__title truncate">{{ $t("common.invoice_hash", { id: invoiceQrModal.invoiceId }) }}</h3>
              </div>
              <button :aria-label="$t('common.close')" type="button" @click="invoiceQrModal.open = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>
            <div class="p-5 text-center">
              <p class="text-[11px] text-[#9a9d97] mb-4">{{ $t("invoices_page.qr_scan_hint") }}</p>
              <div class="flex items-center justify-center h-44">
                <LoaderCircle class="text-2xl text-[#8A6D1F] animate-spin" aria-hidden="true" v-if="invoiceQrModal.loading" />
                <img v-else-if="invoiceQrModal.src" :src="invoiceQrModal.src" :alt="$t('invoices_page.show_qr')" class="w-40 h-40" />
              </div>
              <button type="button" class="btn-outline-brand w-full mt-2" @click="invoiceQrModal.open = false">{{ $t("common.close") }}</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>