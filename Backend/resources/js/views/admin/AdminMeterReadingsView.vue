<script setup>
import { ref, reactive, computed, onMounted, onBeforeUnmount, watch, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import { useAdminMeterReadings } from "@/composables/useAdminMeterReadings";
import { vReveal } from "@/directives/reveal";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import subscriptionService from "@/services/subscriptionService";
import generatorService from "@/services/generatorService";
import meterReadingService from "@/services/meterReadingService";
import { useToastStore } from "@/stores/toast";
import { CalendarDays, Check, ChevronLeft, ChevronRight, Circle, Eye, FileDown, FileSpreadsheet, Gauge, LoaderCircle, Pencil, PlugZap, Plus, Printer, Search, SquarePen, Trash2, TriangleAlert, User, UserCheck, X, Zap } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const { t, locale } = useI18n();
const toast = useToastStore();

const {
  readings, pagination, isLoading, error,
  search, statusFilter,
  approvingId, approveError,
  rejectingId, rejectError,
  isSaving, saveError, deletingId, deleteError,
  fetchReadings, onSearchInput, onFilterChange,
  approveReading, rejectReading, createReading, updateReading, deleteReading,
} = useAdminMeterReadings();

/* ---------------- حالة القراءة ---------------- */
const STATUS_META = {
  pending_approval: { chip: "chip-warning", color: "#FFC107", key: "meter_readings_page.status_pending_approval" },
  approved: { chip: "chip-success", color: "#28A745", key: "meter_readings_page.status_approved" },
  rejected: { chip: "chip-danger", color: "#D9534F", key: "meter_readings_page.status_rejected" },
};
function statusLabel(s) {
  const m = STATUS_META[s];
  return m ? t(m.key) : s;
}
const STATUS_PILLS = computed(() => [
  { value: "", label: t("common.all") },
  ...Object.entries(STATUS_META).map(([value, m]) => ({ value, label: t(m.key) })),
]);

/* ---------------- قائمة المولدات (لفلتر الجدول) ---------------- */
const generatorOptions = ref([]);
async function fetchGeneratorOptions() {
  const { data } = await generatorService.list({ per_page: 200 });
  generatorOptions.value = data.data.data ?? data.data;
}
function generatorLabel(g) {
  return g?.name ?? "—";
}
const generatorFilter = ref("");
/* خيارات فلتر المولد بصيغة AppDropdownSelect — نفس أسلوب فلاتر المنطقة/المالك
 * بواجهة إدارة المولدات، بدل عنصر <select> الأصلي عديم الهوية البصرية */
const generatorFilterOptions = computed(() => [
  { value: "", label: t("meter_readings_page.all_generators") },
  ...generatorOptions.value.map((g) => ({ value: g.id, label: generatorLabel(g) })),
]);

/* ---------------- قائمة الاشتراكات الفعّالة (لنموذج تسجيل قراءة جديدة) ---------------- */
const subscriptionOptions = ref([]);
const isLoadingSubscriptions = ref(false);
async function ensureSubscriptionOptionsLoaded() {
  if (subscriptionOptions.value.length > 0) return;
  isLoadingSubscriptions.value = true;
  try {
    const { data } = await subscriptionService.list({ per_page: 200 });
    const list = data.data.data ?? data.data;
    subscriptionOptions.value = list.filter((s) => s.status === "active");
  } finally {
    isLoadingSubscriptions.value = false;
  }
}
function subscriptionLabel(s) {
  if (!s) return "—";
  return `${s.subscriber?.name ?? "—"} — ${s.generator?.name ?? "—"}`;
}
const subscriptionSelectOptions = computed(() =>
  subscriptionOptions.value.map((s) => ({ value: s.id, label: subscriptionLabel(s) })),
);

/* ---------------- فرز عبر رؤوس الأعمدة ---------------- */
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

/* ---------------- فلترة (مولد) + فرز — تُطبَّق على الصفحة المحمّلة حاليًا ---------------- */
const visibleReadings = computed(() => {
  let list = readings.value;
  if (generatorFilter.value) {
    list = list.filter((r) => String(r.generator?.id ?? r.generator_id) === String(generatorFilter.value));
  }
  return list;
});
const sortedReadings = computed(() => {
  if (!sortKey.value) return visibleReadings.value;
  const list = [...visibleReadings.value];
  list.sort((a, b) => {
    let va, vb;
    if (sortKey.value === "subscriber") {
      va = (a.subscriber?.name ?? "").toLowerCase();
      vb = (b.subscriber?.name ?? "").toLowerCase();
    } else if (sortKey.value === "date") {
      va = new Date(a.reading_date ?? 0).getTime();
      vb = new Date(b.reading_date ?? 0).getTime();
    } else if (sortKey.value === "consumed") {
      va = Number(a.consumed_kw ?? 0);
      vb = Number(b.consumed_kw ?? 0);
    }
    if (va < vb) return sortDir.value === "asc" ? -1 : 1;
    if (va > vb) return sortDir.value === "asc" ? 1 : -1;
    return 0;
  });
  return list;
});

/* ---------------- KPI Cards ----------------
 * "الإجمالي" دقيق دايمًا (من pagination.total). باقي البطاقات محسوبة من
 * readings.value (الصفحة المحمّلة حاليًا بس) — نفس منهجية صفحتَي المشتركين والاشتراكات.
 */
const countOnPage = (status) => readings.value.filter((r) => r.status === status).length;
const totalConsumptionOnPage = computed(() =>
  readings.value.reduce((sum, r) => sum + Number(r.consumed_kw ?? 0), 0),
);
const avgConsumptionOnPage = computed(() =>
  readings.value.length ? Math.round(totalConsumptionOnPage.value / readings.value.length) : 0,
);
const KPI_CARDS = computed(() => [
  {
    icon: "fa-gauge-high", c1: "#52733D", c2: "#3E582E",
    value: pagination.value.total,
    label: t("meter_readings_page.total_readings"),
  },
  {
    icon: "fa-bolt", c1: "#8A6D1F", c2: "#D4AF37",
    value: `${totalConsumptionOnPage.value} ${t("dashboard.kw_label")}`,
    label: t("meter_readings_page.consumption_this_page"),
  },
  {
    icon: "fa-chart-simple", c1: "#17A2B8", c2: "#0f6c7d",
    value: `${avgConsumptionOnPage.value} ${t("dashboard.kw_label")}`,
    label: t("meter_readings_page.avg_consumption_per_reading"),
  },
  {
    icon: "fa-hourglass-half", c1: "#FFC107", c2: "#a3760a",
    value: countOnPage("pending_approval"),
    label: statusLabel("pending_approval") + t("common.this_page_suffix"),
  },
]);

/* ---------------- مؤشر حالة النظام بالهيدر ---------------- */
const systemStatusInfo = computed(() => {
  const pendingCount = countOnPage("pending_approval");
  if (pendingCount === 0) {
    return { label: t("meter_readings_page.no_readings_awaiting_approval"), color: "#28A745" };
  }
  return {
    label: t("meter_readings_page.readings_awaiting_approval", { count: pendingCount }),
    color: "#FFC107",
  };
});

async function handleApprove(r) {
  await approveReading(r.id);
  if (approveError.value) toast.show({ type: "danger", title: approveError.value });
}

/* ---------------- رفض قراءة (بانتظار الاعتماد فقط، بسبب مطلوب) ----------------
 * FIX: القراءات كان ينفع اعتمادها بس، مش رفضها، رغم إنّ الباك اند وحالة
 * "rejected" جاهزين أصلًا. اخترنا نافذة بسبب مطلوب (مش one-click) لتماشي
 * نفس النمط المعتمد لرفض دفعات الفنيين (TechnicianPaymentsView.vue) —
 * الرفض قرار يحتاج تبرير يوصل للمشترك/الفني، خلافًا للاعتماد اللي ما
 * بيحتاج تفسير. */
const rejectTarget = ref(null);
const rejectReason = ref("");
function openReject(r) {
  if (r.status !== "pending_approval") return;
  rejectTarget.value = r;
  rejectReason.value = "";
}
function closeReject() {
  if (rejectingId.value) return;
  rejectTarget.value = null;
}
async function handleReject() {
  if (!rejectReason.value.trim()) return;
  const ok = await rejectReading(rejectTarget.value.id, rejectReason.value.trim());
  if (ok) {
    rejectTarget.value = null;
    rejectReason.value = "";
  } else if (rejectError.value) {
    toast.show({ type: "danger", title: rejectError.value });
  }
}

/* =========================================================================
 * الرسوم البيانية (اتجاه الاستهلاك + توزيع الحالات)
 * يتطلب حزمة "chart.js" مثبّتة بالمشروع (npm i chart.js) — نفس المكتبة
 * المستخدَمة بالنموذج المرجعي (HTML).
 * ملاحظة: اتجاه الاستهلاك محسوب من قراءات الصفحة الحالية فقط (client-side)،
 * وليس عبر endpoint تجميعي (aggregated by day) — مقصود لتفادي حمل إضافي
 * على الباك اند لغرض عرض بصري تقريبي فقط، ودقّته مرتبطة بحجم الصفحة الحالية.
 * ========================================================================= */
let ChartJS = null;
const trendCanvas = ref(null);
const statusCanvas = ref(null);
let trendChart = null;
let statusChart = null;

function fmtShortDate(d) {
  try {
    return new Date(d).toLocaleDateString(locale.value === "ar" ? "ar-EG-u-ca-gregory" : "en-US", { day: "numeric", month: "short" });
  } catch {
    return d ?? "—";
  }
}

const statusLegend = computed(() => {
  const total = readings.value.length || 1;
  return Object.keys(STATUS_META).map((key) => {
    const count = countOnPage(key);
    return { key, pct: Math.round((count / total) * 100), color: STATUS_META[key].color, label: statusLabel(key) };
  });
});

async function ensureChartJs() {
  if (ChartJS) return ChartJS;
  const mod = await import("chart.js");
  mod.Chart.register(...mod.registerables);
  ChartJS = mod.Chart;
  return ChartJS;
}

async function buildCharts() {
  if (!trendCanvas.value || !statusCanvas.value) return;
  const Chart = await ensureChartJs();
  Chart.defaults.font.family = "Cairo, sans-serif";
  Chart.defaults.color = "#6B6B6B";

  // اتجاه الاستهلاك: تجميع قراءات الصفحة الحالية حسب التاريخ
  const byDate = new Map();
  for (const r of readings.value) {
    const key = r.reading_date;
    byDate.set(key, (byDate.get(key) ?? 0) + Number(r.consumed_kw ?? 0));
  }
  const sortedDates = [...byDate.keys()].sort((a, b) => new Date(a) - new Date(b));
  const labels = sortedDates.map(fmtShortDate);
  const values = sortedDates.map((d) => byDate.get(d));

  trendChart?.destroy();
  trendChart = new Chart(trendCanvas.value, {
    type: "line",
    data: {
      labels,
      datasets: [{
        label: t("meter_readings_page.chart_consumption_kw"),
        data: values,
        borderColor: "#52733D",
        backgroundColor: "rgba(82,115,61,0.12)",
        fill: true,
        tension: 0.4,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false } },
        y: { grid: { color: "rgba(82,115,61,0.08)" } },
      },
    },
  });

  const statusCounts = Object.keys(STATUS_META).map((k) => countOnPage(k));
  statusChart?.destroy();
  statusChart = new Chart(statusCanvas.value, {
    type: "doughnut",
    data: {
      labels: Object.keys(STATUS_META).map((k) => statusLabel(k)),
      datasets: [{ data: statusCounts, backgroundColor: Object.keys(STATUS_META).map((k) => STATUS_META[k].color), borderWidth: 0 }],
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: "70%", plugins: { legend: { display: false } } },
  });
}

watch(readings, () => nextTick(buildCharts), { deep: false });
watch(locale, () => nextTick(buildCharts));
onBeforeUnmount(() => { trendChart?.destroy(); statusChart?.destroy(); });

/* =========================================================================
 * نافذة إضافة/تعديل قراءة
 * إضافة: تختار اشتراكًا فعّالًا (subscription_id) + تاريخ + قراءة حالية —
 * القراءة السابقة تُحسب تلقائيًا بالباك اند من آخر قراءة لنفس الاشتراك.
 * تعديل: يقتصر على تصحيح "القراءة الحالية" فقط، ولقراءات بانتظار الاعتماد
 * فقط (بمجرد اعتماد القراءة تُصدر فاتورة مرتبطة بها فلا يمكن تعديلها/حذفها).
 * ========================================================================= */
const isFormOpen = ref(false);
const formMode = ref("add"); // "add" | "edit"
const editingReading = ref(null);
const formErrors = reactive({});
const emptyForm = () => ({
  subscriptionId: "",
  currentReading: "",
  readingDate: new Date().toISOString().slice(0, 10),
});
const form = reactive(emptyForm());

function resetForm() {
  Object.assign(form, emptyForm());
  Object.keys(formErrors).forEach((k) => delete formErrors[k]);
}
async function openAddForm() {
  formMode.value = "add";
  editingReading.value = null;
  resetForm();
  saveError.value = null;
  isFormOpen.value = true;
  await ensureSubscriptionOptionsLoaded();
}
function openEditForm(r) {
  if (r.status !== "pending_approval") return;
  formMode.value = "edit";
  editingReading.value = r;
  Object.assign(form, {
    subscriptionId: r.subscription_id,
    currentReading: r.current_reading,
    readingDate: r.reading_date,
  });
  Object.keys(formErrors).forEach((k) => delete formErrors[k]);
  saveError.value = null;
  isFormOpen.value = true;
}
function closeForm() {
  if (isSaving.value) return;
  isFormOpen.value = false;
}
function validateForm() {
  Object.keys(formErrors).forEach((k) => delete formErrors[k]);
  if (formMode.value === "add") {
    if (!form.subscriptionId) formErrors.subscriptionId = t("meter_readings_page.select_subscription_error");
    if (!form.readingDate) formErrors.readingDate = t("meter_readings_page.date_required_error");
  }
  if (form.currentReading === "" || Number(form.currentReading) < 0) {
    formErrors.currentReading = t("meter_readings_page.invalid_value_error");
  }
  return Object.keys(formErrors).length === 0;
}
async function submitForm() {
  if (!validateForm()) return;
  const ok = formMode.value === "add"
    ? await createReading({
        subscription_id: form.subscriptionId,
        reading_date: form.readingDate,
        current_reading: Number(form.currentReading),
      })
    : await updateReading(editingReading.value.id, {
        current_reading: Number(form.currentReading),
      });
  if (ok) isFormOpen.value = false;
}

/* ---------------- تأكيد حذف قراءة (بانتظار الاعتماد فقط) ---------------- */
const deletingReading = ref(null);
function openDeleteConfirm(r) {
  if (r.status !== "pending_approval") return;
  deletingReading.value = r;
}
function closeDeleteConfirm() {
  if (deletingId.value) return;
  deletingReading.value = null;
}
async function confirmDelete() {
  if (!deletingReading.value) return;
  const ok = await deleteReading(deletingReading.value.id);
  if (ok) deletingReading.value = null;
}

/* ---------------- نافذة عرض تفاصيل القراءة ---------------- */
const viewingReading = ref(null);
function openView(r) {
  viewingReading.value = r;
}

/* ---------------- تصدير CSV (الصفحة المحمّلة حاليًا) ---------------- */
const exportUrl = computed(() =>
  meterReadingService.exportUrl({
    search: search.value,
    status: statusFilter.value,
  })
);

/* ---------------- طباعة (الصفحة المحمّلة حاليًا) ---------------- */
function handlePrint() {
  const isAr = locale.value === "ar";
  const rowsHtml = sortedReadings.value
    .map((r) => `<tr>
      <td>${r.subscriber?.name ?? ""}</td><td>${r.generator?.name ?? ""}</td>
      <td>${r.previous_reading}</td><td>${r.current_reading}</td><td>${r.consumed_kw}</td>
      <td>${r.reading_date ?? ""}</td><td>${statusLabel(r.status)}</td>
    </tr>`)
    .join("");
  const printWin = window.open("", "_blank");
  if (!printWin) return;
  printWin.document.write(`<html dir="${isAr ? "rtl" : "ltr"}" lang="${isAr ? "ar" : "en"}"><head><meta charset="utf-8"><title>${t("meter_readings_page.readings_log_title")}</title>
    <style>
      body{font-family:sans-serif;padding:24px;color:#222}
      h1{color:#3E582E}
      table{width:100%;border-collapse:collapse;margin-top:16px}
      th,td{border:1px solid #ccc;padding:8px;text-align:${isAr ? "right" : "left"};font-size:13px}
      th{background:#EBF1E7}
    </style></head><body>
    <h1>${t("meter_readings_page.readings_log_title")}</h1>
    <p>${new Date().toLocaleString(isAr ? "ar-EG" : "en-GB")}</p>
    <table><thead><tr><th>${t("subscribers_page.subscriber_col")}</th><th>${t("dashboard.generator_col")}</th><th>${t("meter_readings_page.previous_col")}</th><th>${t("meter_readings_page.current_col")}</th><th>${t("meter_readings_page.consumed_col")}</th><th>${t("meter_readings_page.date_col")}</th><th>${t("dashboard.status_col")}</th></tr></thead>
    <tbody>${rowsHtml}</tbody></table></body></html>`);
  printWin.document.close();
  printWin.focus();
  setTimeout(() => printWin.print(), 400);
}

/* ---------------- ترقيم صفحات (أرقام مرئية بدل Prev/Next فقط) ---------------- */
const pageNumbers = computed(() => {
  const total = pagination.value.last_page ?? 1;
  const current = pagination.value.current_page ?? 1;
  const span = 2;
  const pages = [];
  for (let p = Math.max(1, current - span); p <= Math.min(total, current + span); p++) pages.push(p);
  return pages;
});

function initialsOf(name) {
  const parts = (name ?? "").trim().split(/\s+/);
  return (parts[0]?.[0] ?? "") + (parts[1]?.[0] ?? "");
}

onMounted(async () => {
  await Promise.all([fetchReadings(1), fetchGeneratorOptions()]);
  await nextTick();
  buildCharts();
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
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ $t("meter_readings_page.title") }}</span>
      </nav>
      <div class="relative flex flex-wrap items-start justify-between gap-4">
        <div>
          <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
            <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#17A2B8] to-[#0f6c7d] text-white flex items-center justify-center text-base">
              <Gauge aria-hidden="true" />
            </span>
            {{ $t("meter_readings_page.title") }}
          </h1>
          <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">
            {{ $t("meter_readings_page.subtitle") }}
          </p>
          <span v-if="systemStatusInfo" class="inline-flex items-center gap-2 text-[11px] font-bold mt-2.5" :style="{ color: systemStatusInfo.color }">
            <Circle class="text-[7px]" aria-hidden="true" /> {{ systemStatusInfo.label }}
          </span>
        </div>
        <div class="relative flex flex-wrap gap-2.5">
          <button
            type="button" @click="openAddForm"
            class="btn-fill relative bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white text-[12.5px] font-bold px-4 py-2.5 rounded-full shadow-md flex items-center gap-2"
          >
            <Plus aria-hidden="true" />
            {{ $t("meter_readings_page.record_new_reading") }}
          </button>
          <a
            :href="exportUrl"
            target="_blank"
            class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2"
          >
            <FileDown aria-hidden="true" />
            {{ $t("meter_readings_page.export_readings") }}
          </a>
        </div>
      </div>
    </section>

    <!-- ===== KPI CARDS ===== -->
    <section v-reveal>
      <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
        <h2 class="text-[15px] font-extrabold">{{ $t("owner_dashboard.quick_overview") }}</h2>
        <span class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("meter_readings_page.updated_per_visible_page") }}</span>
      </div>
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
        <div v-for="c in KPI_CARDS" :key="c.label" class="kpi-card glass-card hoverable" :style="{ '--kpi-color': c.c1, '--kpi-color2': c.c2 }">
          <div class="kpi-icon mb-2.5"><AppIcon :name="c.icon" /></div>
          <div class="text-lg font-extrabold">{{ c.value }}</div>
          <div class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5">{{ c.label }}</div>
        </div>
      </div>
    </section>

    <!-- ===== CHARTS ===== -->
    <section v-reveal class="grid lg:grid-cols-[1.6fr_1fr] gap-4">
      <div class="glass-card p-4">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
          <h3 class="text-[13.5px] font-bold">{{ $t("meter_readings_page.consumption_trend_title") }}</h3>
          <span class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("meter_readings_page.in_kw") }}</span>
        </div>
        <div class="h-64"><canvas ref="trendCanvas"></canvas></div>
      </div>
      <div class="glass-card p-4">
        <h3 class="text-[13.5px] font-bold mb-3">{{ $t("meter_readings_page.readings_by_status_title") }}</h3>
        <div class="h-44"><canvas ref="statusCanvas"></canvas></div>
        <div class="grid grid-cols-3 gap-2 mt-3 text-center">
          <div v-for="s in statusLegend" :key="s.key">
            <div class="text-sm font-extrabold" :style="{ color: s.color }">{{ s.pct }}%</div>
            <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ s.label }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== TOOLBAR (فوق الجدول — بنفس هوية شريط أدوات صفحة إدارة المولدات) ===== -->
    <section v-reveal class="glass-card p-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2 flex-1 min-w-[220px]">
          <div class="relative flex-1 min-w-[160px] max-w-xs">
            <Search class="absolute top-1/2 -translate-y-1/2 start-3 text-[#9a9d97] dark:text-[#8f938a] text-[10px]" aria-hidden="true" />
            <input
              v-model="search" type="text" @input="onSearchInput"
              :placeholder="$t('meter_readings_page.search_placeholder')"
              class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-full py-2 ps-8 pe-3 text-[11.5px] outline-none focus:border-[#8A6D1F]"
            />
          </div>
          <AppDropdownSelect
            v-model="generatorFilter"
            :options="generatorFilterOptions"
            width-class="w-44"
            :panel-width="176"
          />
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <div class="flex items-center gap-1 bg-[#f4efe5]/70 dark:bg-white/5 rounded-full p-1 flex-wrap">
            <button
              v-for="pill in STATUS_PILLS" :key="pill.value" type="button"
              @click="statusFilter = pill.value; onFilterChange()"
              class="px-3 py-1.5 rounded-full text-[11px] font-bold transition-colors"
              :class="statusFilter === pill.value ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/5'"
            >{{ pill.label }}</button>
          </div>
          <div class="w-px h-6 bg-[#e0dccf] dark:bg-white/10"></div>
          <a :href="exportUrl" target="_blank" class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5" :title="$t('meter_readings_page.export_excel_csv')" :aria-label="$t('meter_readings_page.export_excel_csv')">
            <FileSpreadsheet class="text-[11px]" aria-hidden="true" />
          </a>
          <button type="button" @click="handlePrint" class="icon-btn !w-8 !h-8 !bg-[#f4efe5]/70 dark:!bg-white/5" :title="$t('owner_applications_page.print')" :aria-label="$t('owner_applications_page.print')">
            <Printer class="text-[11px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===== TABLE ===== -->
    <section v-reveal class="glass-card p-4 overflow-hidden">
      <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
        <h3 class="text-[13.5px] font-bold">{{ $t("meter_readings_page.readings_log_title") }}</h3>
        <span class="text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ sortedReadings.length }} {{ $t("meter_readings_page.readings_log_title") }}</span>
      </div>

      <div v-if="isLoading" class="space-y-2">
        <div v-for="i in 6" :key="i" class="h-12 rounded-lg thumb-loading"></div>
      </div>
      <div v-else-if="error" class="text-center py-8 text-[12px] text-[#D9534F]">{{ error }}</div>
      <div v-else-if="sortedReadings.length === 0" class="text-center py-10">
        <Gauge class="text-2xl text-[#c9cdc2] dark:text-[#565952] mb-2" aria-hidden="true" />
        <p class="text-[12px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("meter_readings_page.no_matching_readings") }}</p>
      </div>
      <div v-else class="overflow-x-auto -mx-1">
        <table class="data-table w-full text-[12px] min-w-[920px]">
          <thead>
            <tr class="text-center text-[10.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] bg-[#f4efe5]/80 dark:bg-white/5">
              <th class="py-2.5 px-3 rounded-s-lg cursor-pointer select-none" @click="toggleSort('subscriber')">
                {{ $t("subscribers_page.subscriber_col") }}
                <AppIcon :name="sortIconClass('subscriber')" class="text-[9px] ms-1 transition-all" />
              </th>
              <th class="py-2.5 px-3">{{ $t("dashboard.generator_col") }}</th>
              <th class="py-2.5 px-3">{{ $t("meter_readings_page.previous_col") }}</th>
              <th class="py-2.5 px-3">{{ $t("meter_readings_page.current_col") }}</th>
              <th class="py-2.5 px-3 cursor-pointer select-none" @click="toggleSort('consumed')">
                {{ $t("meter_readings_page.consumed_col") }}
                <AppIcon :name="sortIconClass('consumed')" class="text-[9px] ms-1 transition-all" />
              </th>
              <th class="py-2.5 px-3 cursor-pointer select-none" @click="toggleSort('date')">
                {{ $t("meter_readings_page.date_col") }}
                <AppIcon :name="sortIconClass('date')" class="text-[9px] ms-1 transition-all" />
              </th>
              <th class="py-2.5 px-3">{{ $t("dashboard.status_col") }}</th>
              <th class="py-2.5 px-3 rounded-e-lg">{{ $t("subscribers_page.actions_col") }}</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="r in sortedReadings" :key="r.id"
              class="border-b border-[#f0ece0] dark:border-white/5 last:border-0 text-center transition-colors hover:bg-[#f4efe5]/40 dark:hover:bg-white/[0.03]"
            >
              <td class="py-2.5 px-3">
                <button type="button" @click="openView(r)" class="flex items-center gap-2.5 hover:text-[#8A6D1F] transition-colors">
                  <span class="w-8 h-8 rounded-full bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-[10px] font-bold shrink-0">
                    {{ initialsOf(r.subscriber?.name) }}
                  </span>
                  <span class="min-w-0 text-start">
                    <span class="block font-semibold truncate max-w-[9rem]">{{ r.subscriber?.name ?? "—" }}</span>
                    <span class="block text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ r.created_by ?? "—" }}</span>
                  </span>
                </button>
              </td>
              <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">{{ r.generator?.name ?? "—" }}</td>
              <td class="py-2.5 px-3">{{ r.previous_reading }}</td>
              <td class="py-2.5 px-3 font-semibold">{{ r.current_reading }}</td>
              <td class="py-2.5 px-3 font-bold text-[#8A6D1F] dark:text-[#D4AF37]">{{ r.consumed_kw }} {{ $t("dashboard.kw_label") }}</td>
              <td class="py-2.5 px-3 text-[#6B6B6B] dark:text-[#a8aaa5]">{{ r.reading_date }}</td>
              <td class="py-2.5 px-3"><span class="status-chip" :class="STATUS_META[r.status]?.chip">{{ statusLabel(r.status) }}</span></td>
              <td class="py-2.5 px-3">
                <div class="row-actions">
                  <button type="button" @click="openView(r)" class="action-btn action-btn--view" :title="$t('common.view')" :aria-label="$t('common.view')">
                    <Eye aria-hidden="true" />
                  </button>
                  <span class="row-actions-divider"></span>
                  <button
                    type="button" @click="openEditForm(r)" :disabled="r.status !== 'pending_approval'"
                    class="action-btn action-btn--edit" :title="r.status === 'pending_approval' ? $t('common.edit') : $t('meter_readings_page.edit_disabled_title')"
                    :aria-label="r.status === 'pending_approval' ? $t('common.edit') : $t('meter_readings_page.edit_disabled_title')"
                  >
                    <Pencil aria-hidden="true" />
                  </button>
                  <span class="row-actions-divider"></span>
                  <button
                    type="button" @click="openDeleteConfirm(r)" :disabled="r.status !== 'pending_approval' || deletingId === r.id"
                    class="action-btn action-btn--delete" :title="r.status === 'pending_approval' ? $t('common.delete') : $t('meter_readings_page.delete_disabled_title')"
                    :aria-label="r.status === 'pending_approval' ? $t('common.delete') : $t('meter_readings_page.delete_disabled_title')"
                  >
                    <LoaderCircle class="animate-spin" aria-hidden="true" v-if="deletingId === r.id" /><Trash2 aria-hidden="true" v-else />
                  </button>
                  <template v-if="r.status === 'pending_approval'">
                    <span class="row-actions-divider"></span>
                    <button
                      type="button" @click="handleApprove(r)" :disabled="approvingId === r.id"
                      class="action-btn action-btn--approve"
                      :title="$t('generators_management_page.approve_action')"
                      :aria-label="$t('generators_management_page.approve_action')"
                    >
                      <LoaderCircle class="animate-spin" aria-hidden="true" v-if="approvingId === r.id" /><Check aria-hidden="true" v-else />
                    </button>
                    <span class="row-actions-divider"></span>
                    <button
                      type="button" @click="openReject(r)" :disabled="rejectingId === r.id"
                      class="action-btn action-btn--delete"
                      :title="$t('meter_readings_page.reject_action')"
                      :aria-label="$t('meter_readings_page.reject_action')"
                    >
                      <LoaderCircle class="animate-spin" aria-hidden="true" v-if="rejectingId === r.id" /><X aria-hidden="true" v-else />
                    </button>
                  </template>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="pagination.last_page > 1" class="flex items-center justify-between flex-wrap gap-2 mt-3 text-[11px] text-[#9a9d97] dark:text-[#8f938a]">
        <span>{{ $t("meter_readings_page.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}</span>
        <div class="flex items-center gap-1">
          <button :aria-label="$t('common.previous_page')" type="button" :disabled="pagination.current_page <= 1" @click="fetchReadings(pagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
            <ChevronRight class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronLeft class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>
          <button
            v-for="p in pageNumbers" :key="p" type="button" @click="fetchReadings(p)"
            class="w-7 h-7 rounded-lg text-[11px] font-bold transition-colors"
            :class="p === pagination.current_page ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white' : 'hover:bg-[#EBF1E7] dark:hover:bg-white/5'"
          >{{ p }}</button>
          <button :aria-label="$t('common.next_page')" type="button" :disabled="pagination.current_page >= pagination.last_page" @click="fetchReadings(pagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40">
            <ChevronLeft class="rtl:block ltr:hidden text-[10px]" aria-hidden="true" />
            <ChevronRight class="ltr:block rtl:hidden text-[10px]" aria-hidden="true" />
          </button>
        </div>
      </div>
    </section>

    <!-- ===================== نافذة عرض تفاصيل القراءة ===================== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="viewingReading" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="viewingReading = null">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-2xl max-h-[88vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
            <div class="relative shrink-0 px-5 py-4 bg-gradient-to-l from-[#17A2B8] via-[#0f6c7d] to-[#3E582E] text-white overflow-hidden">
              <div class="absolute inset-0 opacity-[0.08] pointer-events-none" style="background-image:radial-gradient(circle at 88% 15%, #fff 0%, transparent 40%)"></div>
              <div class="relative flex items-center justify-between gap-3">
                <div class="flex items-center gap-3 min-w-0">
                  <span class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center text-[13px] font-bold shrink-0">
                    {{ initialsOf(viewingReading.subscriber?.name) }}
                  </span>
                  <div class="min-w-0">
                    <h3 class="text-[15px] font-extrabold truncate">{{ viewingReading.subscriber?.name ?? "—" }}</h3>
                    <p class="text-[11px] text-white/75 truncate">{{ viewingReading.generator?.name ?? "—" }}</p>
                  </div>
                </div>
                <button :aria-label="$t('common.close')" type="button" @click="viewingReading = null" class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center shrink-0 transition-colors"><X aria-hidden="true" /></button>
              </div>
            </div>

            <div class="p-5 grid sm:grid-cols-2 gap-4 overflow-y-auto">
              <div class="space-y-4">
                <div class="glass-card p-4 flex items-center gap-4">
                  <div class="w-12 h-12 rounded-full flex items-center justify-center shrink-0 bg-[#17A2B8]/10">
                    <Gauge class="text-[16px] text-[#17A2B8]" aria-hidden="true" />
                  </div>
                  <div class="min-w-0">
                    <div class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a] mb-1.5">{{ $t("meter_readings_page.reading_status_label") }}</div>
                    <span class="status-chip" :class="STATUS_META[viewingReading.status]?.chip">{{ statusLabel(viewingReading.status) }}</span>
                  </div>
                </div>

                <div class="glass-card p-4 space-y-1">
                  <div class="info-row">
                    <span class="info-row-icon"><User aria-hidden="true" /></span>
                    <span class="info-row-label">{{ $t("subscribers_page.subscriber_col") }}</span>
                    <b class="info-row-value">{{ viewingReading.subscriber?.name ?? "—" }}</b>
                  </div>
                  <div class="info-row">
                    <span class="info-row-icon"><PlugZap aria-hidden="true" /></span>
                    <span class="info-row-label">{{ $t("dashboard.generator_col") }}</span>
                    <b class="info-row-value">{{ viewingReading.generator?.name ?? "—" }}</b>
                  </div>
                  <div class="info-row">
                    <span class="info-row-icon"><CalendarDays aria-hidden="true" /></span>
                    <span class="info-row-label">{{ $t("meter_readings_page.date_col") }}</span>
                    <b class="info-row-value">{{ viewingReading.reading_date ?? "—" }}</b>
                  </div>
                  <div class="info-row">
                    <span class="info-row-icon"><SquarePen aria-hidden="true" /></span>
                    <span class="info-row-label">{{ $t("meter_readings_page.recorded_by_label") }}</span>
                    <b class="info-row-value">{{ viewingReading.created_by ?? "—" }}</b>
                  </div>
                  <div v-if="viewingReading.approved_by" class="info-row">
                    <span class="info-row-icon"><UserCheck aria-hidden="true" /></span>
                    <span class="info-row-label">{{ $t("meter_readings_page.approved_by_label") }}</span>
                    <b class="info-row-value">{{ viewingReading.approved_by }}</b>
                  </div>
                </div>
                <p v-if="viewingReading.status === 'rejected' && viewingReading.rejection_reason" class="text-[12px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg p-3">
                  <strong>{{ $t("meter_readings_page.rejection_reason_label") }}:</strong> {{ viewingReading.rejection_reason }}
                </p>
              </div>

              <div class="space-y-4">
                <div class="grid grid-cols-3 gap-2.5">
                  <div class="glass-card p-3 text-center">
                    <Gauge class="text-[#9a9d97] dark:text-[#8f938a] text-[13px] mb-1" aria-hidden="true" />
                    <div class="text-sm font-extrabold">{{ viewingReading.previous_reading }}</div>
                    <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("meter_readings_page.previous_col") }}</div>
                  </div>
                  <div class="glass-card p-3 text-center">
                    <Gauge class="text-[#17A2B8] text-[13px] mb-1" aria-hidden="true" />
                    <div class="text-sm font-extrabold">{{ viewingReading.current_reading }}</div>
                    <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("meter_readings_page.current_col") }}</div>
                  </div>
                  <div class="glass-card p-3 text-center">
                    <Zap class="text-[#8A6D1F] dark:text-[#D4AF37] text-[13px] mb-1" aria-hidden="true" />
                    <div class="text-sm font-extrabold">{{ viewingReading.consumed_kw }} {{ $t("dashboard.kw_label") }}</div>
                    <div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ $t("meter_readings_page.consumed_col") }}</div>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer-brand">
              <button type="button" @click="viewingReading = null" class="btn-outline-brand">
                {{ $t("common.close") }}
              </button>
              <button
                v-if="viewingReading.status === 'pending_approval'"
                type="button" @click="openEditForm(viewingReading)" class="btn-outline-brand"
              >
                <Pencil aria-hidden="true" /> {{ $t("common.edit") }}
              </button>
              <button
                v-if="viewingReading.status === 'pending_approval'"
                type="button" @click="openReject(viewingReading)" :disabled="rejectingId === viewingReading.id"
                class="btn-outline-brand !text-[#D9534F] !border-[#D9534F]/40"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="rejectingId === viewingReading.id" /><X aria-hidden="true" v-else />
                {{ rejectingId === viewingReading.id ? $t("meter_readings_page.rejecting_ellipsis") : $t("meter_readings_page.reject_action") }}
              </button>
              <button
                v-if="viewingReading.status === 'pending_approval'"
                type="button" @click="handleApprove(viewingReading)" :disabled="approvingId === viewingReading.id"
                class="btn-fill-brand"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="approvingId === viewingReading.id" /><Check aria-hidden="true" v-else />
                {{ approvingId === viewingReading.id ? $t("meter_readings_page.approving_ellipsis") : $t("generators_management_page.approve_action") }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ===================== نافذة رفض قراءة (بسبب مطلوب) ===================== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="rejectTarget" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeReject">
          <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden p-5">
            <h3 class="text-[14.5px] font-extrabold mb-1.5">{{ $t("meter_readings_page.reject_modal_title") }}</h3>
            <p class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-3">{{ $t("meter_readings_page.reject_modal_desc") }}</p>
            <textarea
              v-model="rejectReason"
              rows="4"
              required
              maxlength="1000"
              :placeholder="$t('meter_readings_page.reject_reason_placeholder')"
              class="w-full rounded-lg border border-[#e7e2d6] dark:border-white/10 bg-transparent px-3.5 py-2.5 text-[12.5px] resize-none outline-none focus:ring-2 focus:ring-[#D9534F]/20 focus:border-[#D9534F]"
            ></textarea>
            <!-- FIX: (item 22) maxlength كان 500 بالغلط، والباك اند (RejectMeterReadingRequest)
                 بيسمح لحد 1000 حرف — صُحّح ليطابق تمامًا. -->
            <p v-if="!rejectReason.trim()" class="text-[11px] text-[#9a9d97] dark:text-[#8f938a] mt-1">{{ $t("meter_readings_page.reject_reason_required_error") }}</p>
            <div class="flex items-center gap-2.5 mt-4">
              <button type="button" @click="closeReject" class="flex-1 text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-white dark:hover:bg-white/5 transition-colors">
                {{ $t("meter_readings_page.reject_cancel_action") }}
              </button>
              <button
                type="button" @click="handleReject" :disabled="!rejectReason.trim() || rejectingId === rejectTarget?.id"
                class="flex-1 text-[12.5px] font-bold px-4 py-2.5 rounded-full bg-[#D9534F] text-white shadow-md flex items-center justify-center gap-2 disabled:opacity-60"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="rejectingId === rejectTarget?.id" /><X aria-hidden="true" v-else />
                {{ $t("meter_readings_page.reject_confirm_action") }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ===================== نافذة إضافة/تعديل قراءة ===================== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="isFormOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeForm">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-2xl max-h-[92vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--green shrink-0">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><Gauge aria-hidden="true" v-if="formMode === 'add'" /><Pencil aria-hidden="true" v-else /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title">{{ formMode === "add" ? $t("meter_readings_page.record_new_reading") : $t("meter_readings_page.edit_reading_title") }}</h3>
                </div>
              </div>
              <button :aria-label="$t('common.close')" type="button" @click="closeForm" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>

            <form class="p-5 grid sm:grid-cols-2 gap-4 overflow-y-auto" @submit.prevent="submitForm">
              <template v-if="formMode === 'add'">
                <div class="form-field sm:col-span-2">
                  <label>{{ $t("meter_readings_page.subscription_field_label") }}</label>
                  <AppDropdownSelect
                    v-model="form.subscriptionId"
                    :options="subscriptionSelectOptions"
                    :disabled="isLoadingSubscriptions"
                    :placeholder="isLoadingSubscriptions ? $t('common.loading') : $t('meter_readings_page.select_active_subscription_placeholder')"
                    variant="field"
                    width-class="w-full"
                    match-trigger-width
                  />
                  <span v-if="formErrors.subscriptionId" class="form-error">{{ formErrors.subscriptionId }}</span>
                </div>
                <div class="form-field sm:col-span-2">
                  <label>{{ $t("meter_readings_page.reading_date_field_label") }}</label>
                  <input v-model="form.readingDate" type="date" />
                  <span v-if="formErrors.readingDate" class="form-error">{{ formErrors.readingDate }}</span>
                </div>
              </template>
              <template v-else>
                <div class="form-field sm:col-span-1">
                  <label>{{ $t("subscribers_page.subscriber_col") }}</label>
                  <input type="text" disabled :value="editingReading?.subscriber?.name ?? '—'" />
                </div>
                <div class="form-field sm:col-span-1">
                  <label>{{ $t("dashboard.generator_col") }}</label>
                  <input type="text" disabled :value="editingReading?.generator?.name ?? '—'" />
                </div>
                <div class="form-field">
                  <label>{{ $t("meter_readings_page.previous_reading_field_label") }}</label>
                  <input type="text" disabled :value="editingReading?.previous_reading" />
                </div>
                <div class="form-field">
                  <label>{{ $t("meter_readings_page.reading_date_field_label") }}</label>
                  <input type="text" disabled :value="editingReading?.reading_date" />
                </div>
              </template>
              <div class="form-field" :class="{ 'sm:col-span-2': formMode === 'add' }">
                <label>{{ $t("meter_readings_page.current_reading_field_label") }}</label>
                <input v-model.number="form.currentReading" type="number" min="0" step="0.01" placeholder="4340" />
                <span v-if="formErrors.currentReading" class="form-error">{{ formErrors.currentReading }}</span>
              </div>
              <p v-if="saveError" class="sm:col-span-2 form-error">{{ saveError }}</p>
            </form>

            <div class="modal-footer-brand">
              <button type="button" @click="closeForm" class="btn-outline-brand">
                {{ $t("dashboard.cancel") }}
              </button>
              <button
                type="button" @click="submitForm" :disabled="isSaving"
                class="btn-fill-brand"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" /><Check aria-hidden="true" v-else />
                {{ isSaving ? $t("users_page.saving_ellipsis") : $t("meter_readings_page.save_reading_button") }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- ===================== نافذة تأكيد الحذف ===================== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="deletingReading" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="closeDeleteConfirm">
          <div class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden p-5 text-center">
            <div class="w-14 h-14 rounded-full bg-[#D9534F]/12 text-[#D9534F] flex items-center justify-center mx-auto mb-4 text-xl">
              <TriangleAlert aria-hidden="true" />
            </div>
            <h3 class="text-[14.5px] font-extrabold mb-1.5">{{ $t("meter_readings_page.delete_reading_confirm_title") }}</h3>
            <p class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-4">
              {{ $t("meter_readings_page.delete_reading_message", { name: deletingReading.subscriber?.name ?? '' }) }}
            </p>
            <p v-if="deleteError" class="form-error mb-3">{{ deleteError }}</p>
            <div class="flex items-center justify-center gap-2.5">
              <button type="button" @click="closeDeleteConfirm" class="text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-white dark:hover:bg-white/5 transition-colors">
                {{ $t("dashboard.cancel") }}
              </button>
              <button
                type="button" @click="confirmDelete" :disabled="deletingId === deletingReading.id"
                class="text-[12.5px] font-bold px-4 py-2.5 rounded-full bg-[#D9534F] text-white shadow-md flex items-center gap-2 disabled:opacity-60"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="deletingId === deletingReading.id" /><Trash2 aria-hidden="true" v-else />
                {{ deletingId === deletingReading.id ? $t("meter_readings_page.deleting_ellipsis") : $t("dashboard.confirm_delete") }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>