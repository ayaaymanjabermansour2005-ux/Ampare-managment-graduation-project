<script setup>
import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useAdminComplaints } from "@/composables/useAdminComplaints";
import complaintService from "@/services/complaintService";
import userService from "@/services/userService";
import { useConfirm } from "@/composables/useConfirm";
import { useToastStore } from "@/stores/toast";
import { useAuthStore } from "@/stores/auth";
import { normalizeApiError } from "@/utils/normalizeApiError";
import { vReveal } from "@/directives/reveal";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import ColumnFilterPopover from "@/components/ui/ColumnFilterPopover.vue";
import AttachmentPreviewModal from "@/components/ui/AttachmentPreviewModal.vue";
import { ChartColumn, Check, ChevronLeft, ChevronRight, CircleCheck, Eye, File, FileSpreadsheet, FileText, LoaderCircle, MessageCircleMore, Paperclip, Search, SlidersHorizontal, Trash2, User, X } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";
import StatCard from "@/components/dashboard/StatCard.vue";


const { t, locale } = useI18n();
const { confirm } = useConfirm();
const toast = useToastStore();
const authStore = useAuthStore();

const {
  complaints, pagination, isLoading, error,
  search, statusFilter, channelFilter, priorityFilter, assignedToFilter,
  isResolving, resolveError,
  isAssigning, assignError,
  deletingId, deleteError,
  typeCounts, fetchTypeCounts,
  fetchComplaints, onSearchInput, onFilterChange,
  resolveComplaint, assignComplaint, deleteComplaint,
} = useAdminComplaints();

/* ---------------- قائمة الأدمنز (لفلتر "المسؤول" ولتعيين شكوى لأحدهم) ----------------
 * FIX (بند 18): channel/priority/assigned_to صارت أعمدة حقيقية بالباك اند
 * (migration + enums + Resource/Service/Export)، فاستبدلنا الفلترة المحلية
 * (على الصفحة الحالية فقط) بفلترة حقيقية من السيرفر، وأضفنا فعليًا إمكانية
 * تعيين شكوى لأدمن معيّن (بدل حقل نص حر كان بلا أي تأثير فعلي بالباك).
 *
 * FIX (تدقيق شامل — الجولة الرابعة): GET /users يتطلب صلاحية users.view
 * منفصلة عن complaints.*، وما كان في أي معالجة لفشلها (403 لأدمن فرعي ما
 * معه هاي الصلاحية، أو خطأ شبكة) — الطلب كان يفشل بصمت (Unhandled Promise
 * Rejection). صرنا نتحقق من الصلاحية قبل المحاولة، ونخفي ميزة "تعيين
 * مسؤول" كليًا لمن لا يملكها بدل ما تنكسر بصمت.
 */
const canAssignComplaints = computed(() => authStore.can("users.view"));
const adminUsers = ref([]);
const isLoadingAdmins = ref(false);
async function fetchAdminUsers() {
  if (!canAssignComplaints.value) return;
  isLoadingAdmins.value = true;
  try {
    const { data } = await userService.list({ role: "admin", per_page: 100 });
    const payload = data.data;
    adminUsers.value = payload.data ?? payload;
  } catch (err) {
    toast.show({ type: "danger", title: normalizeApiError(err, t("complaints_page.admins_load_error")).message });
  } finally {
    isLoadingAdmins.value = false;
  }
}
const adminSelectOptions = computed(() => adminUsers.value.map((u) => ({ value: u.id, label: u.name })));

const STATUS_META = {
  pending: { chip: "chip-warning", key: "complaints_page.status_new" },
  in_progress: { chip: "chip-info", key: "complaints_page.status_in_progress" },
  waiting_subscriber: { chip: "chip-purple", key: "complaints_page.status_waiting_subscriber" },
  resolved: { chip: "chip-success", key: "complaints_page.status_resolved" },
};
function statusLabel(s) {
  const m = STATUS_META[s];
  if (!m) return s;
  return t(m.key);
}
const STATUS_OPTIONS = computed(() => [
  { value: "", label: t("complaints_page.all_statuses") },
  { value: "pending", label: statusLabel("pending") },
  { value: "in_progress", label: statusLabel("in_progress") },
  { value: "waiting_subscriber", label: statusLabel("waiting_subscriber") },
  { value: "resolved", label: statusLabel("resolved") },
]);

const PRIORITY_META = {
  low: { chip: "chip-success", key: "complaints_page.priority_low" },
  medium: { chip: "chip-info", key: "complaints_page.priority_medium" },
  high: { chip: "chip-warning", key: "complaints_page.priority_high" },
  urgent: { chip: "chip-danger", key: "complaints_page.priority_urgent" },
};
function priorityLabel(p) {
  const m = PRIORITY_META[p];
  if (!m) return "—";
  return t(m.key);
}
const PRIORITY_OPTIONS = computed(() => [
  { value: "", label: t("complaints_page.all_priorities") },
  { value: "urgent", label: priorityLabel("urgent") },
  { value: "high", label: priorityLabel("high") },
  { value: "medium", label: priorityLabel("medium") },
  { value: "low", label: priorityLabel("low") },
]);

const CHANNEL_META = {
  app: { icon: "fa-solid fa-mobile-screen-button", key: "complaints_page.channel_app" },
  phone: { icon: "fa-solid fa-phone", key: "complaints_page.channel_phone" },
  whatsapp: { icon: "fa-brands fa-whatsapp", key: "complaints_page.channel_whatsapp" },
  web: { icon: "fa-solid fa-globe", key: "complaints_page.channel_web" },
};
function channelLabel(ch) {
  const m = CHANNEL_META[ch];
  if (!m) return "—";
  return t(m.key);
}

const resolveStatusOptions = computed(() => [
  { value: "in_progress", label: statusLabel("in_progress") },
  { value: "waiting_subscriber", label: statusLabel("waiting_subscriber") },
  { value: "resolved", label: statusLabel("resolved") },
]);
const CHANNEL_OPTIONS = computed(() => [
  { value: "", label: t("complaints_page.all_channels") },
  { value: "app", label: channelLabel("app") },
  { value: "phone", label: channelLabel("phone") },
  { value: "whatsapp", label: channelLabel("whatsapp") },
  { value: "web", label: channelLabel("web") },
]);

const COMPLAINABLE_TYPE_KEYS = {
  Generator: "complaints_page.complainable_generator",
  Fault: "complaints_page.complainable_fault",
  Subscription: "complaints_page.complainable_subscription",
  Invoice: "complaints_page.complainable_invoice",
  Payment: "complaints_page.complainable_payment",
  User: "complaints_page.complainable_user",
};
function complainableLabel(c) {
  if (!c.complainable) return t("complaints_page.complainable_general");
  const key = COMPLAINABLE_TYPE_KEYS[c.complainable.type];
  return `${key ? t(key) : c.complainable.type} #${c.complainable.id}`;
}
function complainableType(c) {
  return c.complainable?.type ?? "General";
}
const TYPE_OPTIONS = computed(() => [
  { value: "", label: t("complaints_page.all_types") },
  { value: "Generator", label: t("complaints_page.complainable_generator") },
  { value: "Fault", label: t("complaints_page.complainable_fault") },
  { value: "Subscription", label: t("complaints_page.complainable_subscription") },
  { value: "Invoice", label: t("complaints_page.complainable_invoice") },
  { value: "Payment", label: t("complaints_page.complainable_payment") },
  { value: "User", label: t("complaints_page.complainable_user") },
  { value: "General", label: t("complaints_page.complainable_general") },
]);

function timeAgo(str) {
  if (!str) return "—";
  const diffMs = Date.now() - new Date(str.replace(" ", "T")).getTime();
  const mins = Math.floor(diffMs / 60000);
  if (mins < 1) return t("subscribers_page.time_now");
  if (mins < 60) return t("subscribers_page.time_mins_ago", { mins });
  const hours = Math.floor(mins / 60);
  if (hours < 24) return t("subscribers_page.time_hours_ago", { hours });
  return t("subscribers_page.time_days_ago", { days: Math.floor(hours / 24) });
}
function formatDate(str) {
  if (!str) return "—";
  const d = new Date(str.replace(" ", "T"));
  return d.toLocaleDateString(locale.value === "ar" ? "ar-EG" : "en-GB", {
    year: "numeric", month: "2-digit", day: "2-digit",
  });
}

function slaInfo(c) {
  if (!c.sla_due_at) return { label: "—", late: false };
  const due = new Date(c.sla_due_at.replace(" ", "T")).getTime();
  if (c.status === "resolved") {
    return { label: t("complaints_page.sla_within"), late: false };
  }
  const now = Date.now();
  if (now > due) {
    return { label: t("complaints_page.sla_breached"), late: true };
  }
  const hrs = Math.max(0, Math.round((due - now) / 3600000));
  return { label: t("complaints_page.sla_hours_left", { hrs }), late: false };
}

const countOnPage = (status) => complaints.value.filter((c) => c.status === status).length;
const countSlaBreached = computed(() => complaints.value.filter((c) => slaInfo(c).late).length);
const KPI_CARDS = computed(() => [
  { icon: "fa-comment-dots", label: t("complaints_page.total_complaints"), value: pagination.value.total, tone: "danger" },
  { icon: "fa-sparkles", label: statusLabel("pending"), value: countOnPage("pending"), tone: "warning" },
  { icon: "fa-spinner", label: statusLabel("in_progress"), value: countOnPage("in_progress"), tone: "info" },
  { icon: "fa-comment-medical", label: statusLabel("waiting_subscriber"), value: countOnPage("waiting_subscriber"), tone: "secondary" },
  { icon: "fa-circle-check", label: statusLabel("resolved"), value: countOnPage("resolved"), tone: "success" },
  { icon: "fa-triangle-exclamation", label: t("complaints_page.sla_breached_kpi"), value: countSlaBreached.value, tone: "danger" },
]);

/* FIX (تدقيق شامل — D6): توزيع حقيقي حسب complainable_type (آخر 30 يومًا)
 * بدل قيم وهمية ثابتة — يعتمد نفس تسميات COMPLAINABLE_TYPE_KEYS أعلاه. */
const COMPLAINT_CATEGORIES = computed(() =>
  Object.entries(typeCounts.value)
    .map(([type, value]) => ({
      label: COMPLAINABLE_TYPE_KEYS[type] ? t(COMPLAINABLE_TYPE_KEYS[type]) : type,
      value,
    }))
    .sort((a, b) => b.value - a.value)
);
const maxCategoryValue = computed(() => Math.max(1, ...COMPLAINT_CATEGORIES.value.map((c) => c.value)));

/* ملاحظة: priorityFilter/channelFilter/assignedToFilter الآن من useAdminComplaints
 * (فلترة حقيقية بالسيرفر — راجع الـ FIX أعلاه). typeFilter/dateFrom/dateTo
 * تبقى فلترة محلية على الصفحة الحالية فقط — لا نوع كيان ولا مدى تاريخ
 * مدعومين بـ ComplaintService::list() حاليًا (خارج نطاق بند 18). */
const typeFilter = ref("");
const dateFrom = ref("");
const dateTo = ref("");
const showAdvancedFilters = ref(false);

const openedAtFilter = computed({
  get: () => ({ min: dateFrom.value, max: dateTo.value }),
  set: (val) => {
    dateFrom.value = val.min;
    dateTo.value = val.max;
  },
});

const filteredComplaints = computed(() => {
  return complaints.value.filter((c) => {
    if (typeFilter.value && complainableType(c) !== typeFilter.value) return false;
    if (dateFrom.value && new Date(c.created_at) < new Date(dateFrom.value)) return false;
    if (dateTo.value && new Date(c.created_at) > new Date(`${dateTo.value}T23:59:59`)) return false;
    return true;
  });
});

function resetAdvancedFilters() {
  channelFilter.value = "";
  assignedToFilter.value = "";
  dateFrom.value = "";
  dateTo.value = "";
  onFilterChange();
}

function exportRows() {
  return filteredComplaints.value.map((c) => ({
    subject: c.subject,
    submitter: c.submitted_by?.name ?? "—",
    type: complainableLabel(c),
    channel: channelLabel(c.channel),
    priority: priorityLabel(c.priority),
    assignee: c.assigned_to?.name ?? "—",
    openedAt: formatDate(c.created_at),
    sla: slaInfo(c).label,
    status: statusLabel(c.status),
  }));
}

function exportHeaders() {
  return [
    t("complaints_page.col_complaint"),
    t("subscribers_page.subscriber_col"),
    t("complaints_page.col_type"),
    t("complaints_page.col_channel"),
    t("complaints_page.col_priority"),
    t("complaints_page.col_assignee"),
    t("complaints_page.col_opened_at"),
    t("complaints_page.sla_label"),
    t("dashboard.status_col"),
  ];
}

const exportUrl = computed(() =>
  complaintService.exportUrl({
    search: search.value,
    status: statusFilter.value,
    channel: channelFilter.value,
    priority: priorityFilter.value,
    assigned_to: assignedToFilter.value,
    date_from: dateFrom.value,
    date_to: dateTo.value,
  })
);

function exportPDF() {
  const isAr = locale.value === "ar";
  const headers = exportHeaders();
  const reportTitle = t("complaints_page.report_title");
  const rowsHtml = exportRows()
    .map((r) => `<tr>${Object.values(r).map((v) => `<td>${v}</td>`).join("")}</tr>`)
    .join("");
  const win = window.open("", "_blank");
  if (!win) return;
  win.document.write(`
    <html dir="${isAr ? "rtl" : "ltr"}" lang="${isAr ? "ar" : "en"}">
      <head>
        <meta charset="utf-8" />
        <title>${reportTitle}</title>
        <style>
          body { font-family: Tahoma, Arial, sans-serif; padding: 24px; color: #222; }
          h2 { margin-bottom: 4px; }
          p.meta { color: #777; font-size: 12px; margin-top: 0; margin-bottom: 16px; }
          table { width: 100%; border-collapse: collapse; font-size: 11.5px; }
          th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: ${isAr ? "right" : "left"}; }
          th { background: #f4efe5; }
        </style>
      </head>
      <body>
        <h2>${reportTitle}</h2>
        <p class="meta">${new Date().toLocaleString(isAr ? "ar-EG" : "en-GB")}</p>
        <table>
          <thead><tr>${headers.map((h) => `<th>${h}</th>`).join("")}</tr></thead>
          <tbody>${rowsHtml}</tbody>
        </table>
      </body>
    </html>
  `);
  win.document.close();
  win.focus();
  win.print();
}

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

const isDetailOpen = ref(false);
const activeComplaint = ref(null);
const resolutionNote = ref("");
const newStatus = ref("in_progress");
const assignDraft = ref("");

/* ---------------- مرفقات الشكوى (عرض فقط) ----------------
 * FIX (تدقيق شامل للوحة الأدمن): كانت GET /complaints/{id}/attachments غير
 * موجودة إطلاقًا بالباك اند، فمرفقات أي شكوى (المرفوعة من المشترك/المالك/الفني
 * وقت تقديمها) ما كان لها أي مكان تنعرض فيه بلوحة الأدمن رغم توفر الرفع.
 */
const complaintAttachments = ref([]);
const isLoadingAttachments = ref(false);
const attachmentsError = ref(null);
const previewingAttachment = ref(null);

async function fetchComplaintAttachments(complaintId) {
  isLoadingAttachments.value = true;
  attachmentsError.value = null;
  try {
    const { data } = await complaintService.attachments(complaintId);
    complaintAttachments.value = data.data;
  } catch {
    attachmentsError.value = t("complaints_page.attachments_load_error");
  } finally {
    isLoadingAttachments.value = false;
  }
}

function fmtFileSize(bytes) {
  if (!bytes) return "-";
  const kb = bytes / 1024;
  if (kb < 1024) return `${Math.round(kb)} KB`;
  return `${(kb / 1024).toFixed(1)} MB`;
}

function openDetail(c) {
  activeComplaint.value = c;
  resolutionNote.value = c.resolution_note ?? "";
  newStatus.value = c.status === "pending" ? "in_progress" : "resolved";
  assignDraft.value = c.assigned_to?.id ?? "";
  // FIX (تدقيق شامل — الجولة الثالثة): بدون هذا، خطأ فشل من شكوى سابقة
  // (حل/تعيين) كان يظهر لحظيًا عند فتح شكوى تانية قبل أي محاولة جديدة.
  resolveError.value = null;
  assignError.value = null;
  isDetailOpen.value = true;
  complaintAttachments.value = [];
  fetchComplaintAttachments(c.id);
}

async function handleResolveSubmit() {
  if (newStatus.value === "resolved" && !resolutionNote.value.trim()) return;
  const ok = await resolveComplaint(activeComplaint.value.id, {
    status: newStatus.value,
    resolution_note: resolutionNote.value.trim() || undefined,
  });
  if (ok) isDetailOpen.value = false;
}

/* ---------------- تعيين "المسؤول" عن الشكوى (بند 18) ---------------- */
async function handleAssignSubmit() {
  const ok = await assignComplaint(activeComplaint.value.id, assignDraft.value || null);
  if (ok) {
    activeComplaint.value = complaints.value.find((c) => c.id === activeComplaint.value.id) ?? activeComplaint.value;
    toast.show({ type: "success", title: t("complaints_page.assign_success") });
  } else if (assignError.value) {
    toast.show({ type: "danger", title: assignError.value });
  }
}

async function handleDelete(c) {
  const confirmed = await confirm({
    title: t("complaints_page.delete_complaint_title"),
    message: t("complaints_page.delete_complaint_message", { subject: c.subject }),
    confirmLabel: t("common.delete"),
    variant: "danger",
  });
  if (!confirmed) return;
  const ok = await deleteComplaint(c.id);
  if (!ok) toast.show({ type: "danger", title: deleteError.value });
}

onMounted(() => {
  fetchComplaints(1);
  fetchAdminUsers();
  fetchTypeCounts();
});
</script>

<template>
  <div class="space-y-6">
    <!-- ===== HEADER ===== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D9534F]/20 dark:bg-[#D9534F]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>
      <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] dark:text-[#8f938a] mb-2">
        <span>{{ $t("common.home") }}</span>
        <ChevronLeft class="rtl:block ltr:hidden text-[9px]" aria-hidden="true" />
        <ChevronRight class="ltr:block rtl:hidden text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ $t("complaints_page.title") }}</span>
      </nav>
      <div class="relative flex flex-wrap items-start justify-between gap-3">
        <div>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#D9534F] to-[#8A6D1F] text-white flex items-center justify-center text-base">
              <MessageCircleMore aria-hidden="true" />
            </span>
            {{ $t("complaints_page.title") }}
          </h1>
          <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
            {{ $t("complaints_page.subtitle") }}
          </p>
        </div>
        <div class="flex flex-wrap items-center gap-2 shrink-0">
          <a
            :href="exportUrl"
            target="_blank"
            class="text-[12px] font-bold px-3.5 py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 flex items-center gap-2 transition"
          >
            <FileSpreadsheet class="text-[#28A745]" aria-hidden="true" />
            {{ $t("complaints_page.export_excel_title") }}
          </a>
          <button
            type="button"
            @click="exportPDF"
            class="text-[12px] font-bold px-3.5 py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 flex items-center gap-2 transition"
          >
            <FileText class="text-[#D9534F]" aria-hidden="true" />
            {{ $t("subscriptions_page.export_pdf") }}
          </button>
        </div>
      </div>
    </section>

    <!-- ===== KPI CARDS ===== -->
    <section v-reveal>
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
        <StatCard
          v-for="c in KPI_CARDS" :key="c.label"
          :label="c.label" :value="c.value" :icon="c.icon" :tone="c.tone"
        />
      </div>
    </section>

    <!-- ===== توزيع الشكاوى حسب النوع ===== -->
    <section v-reveal class="glass-card p-4 lg:p-5">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-[13.5px] font-bold flex items-center gap-2">
          <ChartColumn class="text-[#8A6D1F]" aria-hidden="true" />
          {{ $t("complaints_page.by_category_title") }}
        </h3>
        <span class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("complaints_page.last_30_days") }}</span>
      </div>
      <div v-if="COMPLAINT_CATEGORIES.length === 0" class="text-center py-6 text-[11.5px] text-[#9a9d97] dark:text-[#8f938a]">
        {{ $t("complaints_page.no_category_data") }}
      </div>
      <div v-else class="space-y-2.5">
        <div v-for="cat in COMPLAINT_CATEGORIES" :key="cat.label" class="flex items-center gap-3">
          <span class="text-[11.5px] w-48 shrink-0 truncate">{{ cat.label }}</span>
          <div class="flex-1 h-2 rounded-full bg-[#f4efe5]/70 dark:bg-white/5 overflow-hidden">
            <div
              class="h-full rounded-full bg-gradient-to-l from-[#3E582E] to-[#D9534F]"
              :style="{ width: (cat.value / maxCategoryValue * 100) + '%' }"
            ></div>
          </div>
          <span class="text-[11.5px] font-bold w-6 text-end shrink-0">{{ cat.value }}</span>
        </div>
      </div>
    </section>

    <!-- ===== TOOLBAR ===== -->
    <section v-reveal class="glass-card p-4">
      <div class="flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[220px]">
          <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
          <input
            v-model="search" type="text" @input="onSearchInput"
            :placeholder="$t('complaints_page.search_placeholder')"
            class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F]"
          />
        </div>

        <AppDropdownSelect
          v-model="statusFilter" @update:model-value="onFilterChange"
          :options="STATUS_OPTIONS" width-class="w-40" :panel-width="180"
        />

        <AppDropdownSelect
          v-model="priorityFilter" @update:model-value="onFilterChange"
          :options="PRIORITY_OPTIONS" width-class="w-40" :panel-width="180"
        />

        <AppDropdownSelect
          v-model="typeFilter"
          :options="TYPE_OPTIONS" width-class="w-40" :panel-width="180"
        />

        <button
          type="button"
          @click="showAdvancedFilters = !showAdvancedFilters"
          class="w-9 h-9 rounded-full flex items-center justify-center border border-[#e7e2d6] dark:border-white/10 transition"
          :class="showAdvancedFilters ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-[#f4efe5]/60 dark:hover:bg-white/5'"
          :title="$t('complaints_page.advanced_filters_title')"
          :aria-label="$t('complaints_page.advanced_filters_title')"
        >
          <SlidersHorizontal class="text-[12px]" aria-hidden="true" />
        </button>

        <a
          :href="exportUrl"
          target="_blank"
          class="w-9 h-9 rounded-full flex items-center justify-center border border-[#e7e2d6] dark:border-white/10 text-[#28A745] hover:bg-[#28A745]/10 transition"
          :title="$t('complaints_page.export_excel_title')"
          :aria-label="$t('complaints_page.export_excel_title')"
        >
          <FileSpreadsheet class="text-[12px]" aria-hidden="true" />
        </a>
      </div>

      <!-- لوحة الفلاتر المتقدمة -->
      <div v-if="showAdvancedFilters" class="mt-3.5 pt-3.5 border-t border-[#eee8da] dark:border-white/10 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <AppDropdownSelect
          v-model="channelFilter" @update:model-value="onFilterChange"
          :options="CHANNEL_OPTIONS" variant="field" width-class="w-full" match-trigger-width
        />
        <AppDropdownSelect
          v-if="canAssignComplaints"
          v-model="assignedToFilter" @update:model-value="onFilterChange"
          :options="[{ value: '', label: $t('complaints_page.all_assignees') }, ...adminSelectOptions]"
          :disabled="isLoadingAdmins"
          variant="field" width-class="w-full" match-trigger-width
        />
        <input
          v-model="dateFrom" type="date"
          class="bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2 text-[11.5px] outline-none focus:border-[#8A6D1F]"
        />
        <div class="flex items-center gap-2">
          <input
            v-model="dateTo" type="date"
            class="flex-1 bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2 text-[11.5px] outline-none focus:border-[#8A6D1F]"
          />
          <button
            type="button" @click="resetAdvancedFilters"
            class="shrink-0 text-[11px] font-bold px-3 py-2 rounded-xl border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 transition"
          >
            {{ $t("complaints_page.reset_action") }}
          </button>
        </div>
      </div>
    </section>

    <!-- ===== TABLE ===== -->
    <section v-reveal class="glass-card p-4 overflow-hidden">
      <h3 class="text-[13.5px] font-bold mb-3">{{ $t("complaints_page.complaints_list_title") }}</h3>

      <div v-if="isLoading" class="space-y-2">
        <div v-for="i in 6" :key="i" class="h-12 rounded-lg thumb-loading"></div>
      </div>

      <div v-else-if="error" class="text-center py-8 text-[12px] text-[#D9534F]">{{ error }}</div>

      <div v-else-if="filteredComplaints.length === 0" class="text-center py-12">
        <CircleCheck class="text-2xl text-[#28A745] mb-2" aria-hidden="true" />
        <p class="text-[12.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("complaints_page.no_matching_complaints") }}</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-[11.5px] border-collapse">
          <thead>
            <tr class="text-start text-[#9a9d97] dark:text-[#8f938a] border-b border-[#eee8da] dark:border-white/10">
              <th class="py-2.5 px-2 font-bold text-start">{{ $t("complaints_page.col_complaint") }}</th>
              <th class="py-2.5 px-2 font-bold text-start">{{ $t("subscribers_page.subscriber_col") }}</th>
              <th class="py-2.5 px-2 font-bold text-start">{{ $t("complaints_page.col_type") }}</th>
              <th class="py-2.5 px-2 font-bold text-start">{{ $t("complaints_page.col_channel") }}</th>
              <th class="py-2.5 px-2 font-bold text-start">{{ $t("complaints_page.col_priority") }}</th>
              <th class="py-2.5 px-2 font-bold text-start">{{ $t("complaints_page.col_assignee") }}</th>
              <th class="py-2.5 px-2 font-bold text-start">
                <span class="inline-flex items-center gap-1">
                  {{ $t("complaints_page.col_opened_at") }}
                  <ColumnFilterPopover v-model="openedAtFilter" type="date" @click.stop />
                </span>
              </th>
              <th class="py-2.5 px-2 font-bold text-start">{{ $t("complaints_page.sla_label") }}</th>
              <th class="py-2.5 px-2 font-bold text-start">{{ $t("dashboard.status_col") }}</th>
              <th class="py-2.5 px-2 font-bold text-center">{{ $t("subscribers_page.actions_col") }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-[#f0ece0] dark:divide-white/5">
            <tr
              v-for="c in filteredComplaints" :key="c.id"
              class="hover:bg-[#f4efe5]/40 dark:hover:bg-white/[0.03] transition-colors"
              :class="{ 'opacity-50 pointer-events-none': deletingId === c.id }"
            >
              <td class="py-3 px-2 max-w-[180px]">
                <button type="button" @click="openDetail(c)" class="font-bold truncate block text-start hover:text-[#8A6D1F] transition">
                  {{ c.subject }}
                </button>
              </td>
              <td class="py-3 px-2 whitespace-nowrap">{{ c.submitted_by?.name ?? "—" }}</td>
              <td class="py-3 px-2 whitespace-nowrap">{{ complainableLabel(c) }}</td>
              <td class="py-3 px-2 whitespace-nowrap">
                <span class="inline-flex items-center gap-1.5">
                  <AppIcon :name="CHANNEL_META[c.channel]?.icon" class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]" v-if="c.channel" />
                  {{ channelLabel(c.channel) }}
                </span>
              </td>
              <td class="py-3 px-2 whitespace-nowrap">
                <span v-if="c.priority" class="status-chip" :class="PRIORITY_META[c.priority]?.chip">{{ priorityLabel(c.priority) }}</span>
                <span v-else class="text-[#9a9d97] dark:text-[#8f938a]">—</span>
              </td>
              <td class="py-3 px-2 whitespace-nowrap">{{ c.assigned_to?.name ?? "—" }}</td>
              <td class="py-3 px-2 whitespace-nowrap text-[#6B6B6B] dark:text-[#a8aaa5]">{{ formatDate(c.created_at) }}</td>
              <td class="py-3 px-2 whitespace-nowrap">
                <span :class="slaInfo(c).late ? 'text-[#D9534F] font-bold' : 'text-[#6B6B6B] dark:text-[#a8aaa5]'">
                  {{ slaInfo(c).label }}
                </span>
              </td>
              <td class="py-3 px-2 whitespace-nowrap">
                <span class="status-chip" :class="STATUS_META[c.status]?.chip">{{ statusLabel(c.status) }}</span>
              </td>
              <td class="py-3 px-2">
                <div class="flex items-center justify-center gap-1">
                  <button
                    type="button" @click="openDetail(c)"
                    class="w-7 h-7 rounded-full flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-[#17A2B8] hover:bg-[#17A2B8]/10 transition"
                    :title="$t('common.view')"
                    :aria-label="$t('common.view')"
                  >
                    <Eye class="text-[11px]" aria-hidden="true" />
                  </button>
                  <button
                    type="button" @click="handleDelete(c)" :disabled="deletingId === c.id"
                    class="w-7 h-7 rounded-full flex items-center justify-center text-[#9a9d97] dark:text-[#8f938a] hover:text-[#D9534F] hover:bg-[#D9534F]/10 transition"
                    :title="$t('common.delete')"
                    :aria-label="$t('common.delete')"
                  >
                    <LoaderCircle class="animate-spin" aria-hidden="true" v-if="deletingId === c.id" style="font-size:11px" /><Trash2 aria-hidden="true" v-else style="font-size:11px" />
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
          {{ $t("complaints_page.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}
        </span>
        <div class="flex items-center gap-1">
          <button :aria-label="$t('common.previous_page')"
            type="button"
            :disabled="pagination.current_page <= 1"
            @click="fetchComplaints(pagination.current_page - 1)"
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
              @click="fetchComplaints(page)"
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
            @click="fetchComplaints(pagination.current_page + 1)"
            class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"
          >
            <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===== نافذة التفاصيل/الحل ===== -->
    <Teleport to="body">
      <div v-if="isDetailOpen && activeComplaint" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" @click.self="isDetailOpen = false">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-lg max-h-[85vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--danger shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><MessageCircleMore aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ activeComplaint.subject }}</h3>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="isDetailOpen = false" class="modal-head-brand__close">
              <X aria-hidden="true" />
            </button>
          </div>
          <div class="p-5 space-y-4 overflow-y-auto">
            <div class="flex flex-wrap items-center gap-2 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
              <span class="inline-flex items-center gap-1.5"><User aria-hidden="true" /> {{ activeComplaint.submitted_by?.name }}</span>
              <span>·</span>
              <span>{{ complainableLabel(activeComplaint) }}</span>
              <span v-if="activeComplaint.priority" class="status-chip" :class="PRIORITY_META[activeComplaint.priority]?.chip">{{ priorityLabel(activeComplaint.priority) }}</span>
              <span class="status-chip ms-auto" :class="STATUS_META[activeComplaint.status]?.chip">{{ statusLabel(activeComplaint.status) }}</span>
            </div>
            <p class="text-[12.5px] leading-relaxed glass-card p-3.5">{{ activeComplaint.description }}</p>

            <div class="glass-card p-3.5">
              <h4 class="text-[11.5px] font-bold mb-2.5 flex items-center gap-1.5">
                <Paperclip class="text-[10px]" aria-hidden="true" /> {{ $t("complaints_page.attachments_title") }}
              </h4>
              <div v-if="isLoadingAttachments" class="space-y-2">
                <div v-for="i in 2" :key="i" class="h-9 rounded-lg thumb-loading"></div>
              </div>
              <p v-else-if="attachmentsError" class="text-[11px] text-[#D9534F]">{{ attachmentsError }}</p>
              <p v-else-if="complaintAttachments.length === 0" class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
                {{ $t("complaints_page.no_attachments") }}
              </p>
              <div v-else class="space-y-2">
                <div v-for="a in complaintAttachments" :key="a.id" class="flex items-center gap-2.5 p-2 rounded-lg bg-[#f4efe5]/50 dark:bg-white/5">
                  <File class="text-[#8A6D1F] text-[13px] shrink-0" aria-hidden="true" />
                  <div class="min-w-0 flex-1">
                    <button type="button" @click="previewingAttachment = a" class="text-[11.5px] font-semibold hover:underline truncate block text-start">{{ a.original_name }}</button>
                    <p class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ a.uploaded_by?.name ?? "—" }} · {{ fmtFileSize(a.file_size) }}</p>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="canAssignComplaints" class="flex items-center gap-2">
              <label class="text-[11.5px] font-bold shrink-0">{{ $t("complaints_page.col_assignee") }}</label>
              <AppDropdownSelect
                v-model="assignDraft"
                :options="[{ value: '', label: $t('complaints_page.unassigned_label') }, ...adminSelectOptions]"
                variant="field" width-class="flex-1" match-trigger-width
              />
              <button
                type="button" @click="handleAssignSubmit" :disabled="isAssigning"
                class="btn-outline-brand !text-[11px] !py-2 !px-3 shrink-0"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isAssigning" /><Check aria-hidden="true" v-else />
              </button>
            </div>

            <template v-if="activeComplaint.status !== 'resolved'">
              <div v-if="resolveError" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2">{{ resolveError }}</div>
              <div>
                <label class="text-[11.5px] font-bold block mb-1.5">{{ $t("complaints_page.action_field_label") }}</label>
                <AppDropdownSelect v-model="newStatus" :options="resolveStatusOptions" variant="field" width-class="w-full" match-trigger-width />
              </div>
              <div>
                <label class="text-[11.5px] font-bold block mb-1.5">
                  {{ $t("complaints_page.resolution_note_label") }}
                  <span v-if="newStatus === 'resolved'" class="text-[#D9534F]">*</span>
                </label>
                <textarea v-model="resolutionNote" rows="3" class="w-full bg-[#f4efe5]/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] resize-none"></textarea>
              </div>
            </template>
            <div v-else class="glass-card p-3.5">
              <p class="text-[11px] font-bold text-[#28A745] mb-1"><CircleCheck aria-hidden="true" /> {{ $t("complaints_page.complaint_resolved") }}</p>
              <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ activeComplaint.resolution_note }}</p>
            </div>
          </div>
          <div v-if="activeComplaint.status !== 'resolved'" class="modal-footer-brand">
            <button type="button" @click="isDetailOpen = false" class="btn-outline-brand">{{ $t("dashboard.cancel") }}</button>
            <button type="button" @click="handleResolveSubmit" :disabled="isResolving" class="btn-fill-brand">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isResolving" /><Check aria-hidden="true" v-else />
              {{ $t("users_page.save_action") }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <AttachmentPreviewModal :attachment="previewingAttachment" @close="previewingAttachment = null" />
  </div>
</template>