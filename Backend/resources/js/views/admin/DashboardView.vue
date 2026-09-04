<script setup>
import { onMounted, computed, ref } from "vue";
import { RouterLink } from "vue-router";
import { useI18n } from "vue-i18n";
import { Chart as ChartJS, registerables } from "chart.js";
import { Line, Doughnut, Bar } from "vue-chartjs";
import { useAdminDashboardFull } from "@/composables/useAdminDashboardFull";
import { useAdminDashboardCharts } from "@/composables/useAdminDashboardCharts";
import { useAuthStore } from "@/stores/auth";
import { useToastStore } from "@/stores/toast";
import { vReveal } from "@/directives/reveal";
import { vCountUp } from "@/directives/countUp";
import AdminAnnouncementModal from "@/components/admin/AdminAnnouncementModal.vue";
import { useAdminQuickCreate } from "@/composables/useAdminQuickCreate";
import { useAdminDashboardExtras } from "@/composables/useAdminDashboardExtras";
import AdminGeneratorsMap from "@/components/admin/AdminGeneratorsMap.vue";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import GeneratorsTablePanel from "@/components/generators/GeneratorsTablePanel.vue";
import GazaWeatherCard from "@/components/dashboard/GazaWeatherCard.vue";
import { downloadCsv } from "@/utils/csv";
import { CalendarDays, Check, CircleAlert, Database, Eye, EyeOff, FileDown, ListChecks, LoaderCircle, Megaphone, UserPlus, UserRound, UsersRound, WandSparkles, Wrench, X } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


ChartJS.register(...registerables);

const authStore = useAuthStore();
const generatorsPanelRef = ref(null);
const toast = useToastStore();
const { t, locale } = useI18n();

const {
  stats, isLoadingStats,
  invoicePercentages, isLoadingBreakdown,
  alerts, isLoadingAlerts,
  generators,
  loadAll,
} = useAdminDashboardFull();

const {
  timeline,
  isLoadingTimeline,
  fetchTimeline,
  allGeneratorsForSelect,
  ensureGeneratorsForSelectLoaded,
  isSavingMaintenance,
  maintenanceError,
  scheduleMaintenance,
} = useAdminDashboardExtras();

const maintenanceGeneratorOptions = computed(() =>
  allGeneratorsForSelect.value.map((g) => ({ value: g.id, label: `${g.name} - ${g.location?.city ?? ""}` })),
);

const {
  revenue, isLoadingRevenue, fetchRevenue,
  subscriberGrowth, isLoadingSubscriberGrowth,
  fuel, isLoadingFuel,
  maintenance, isLoadingMaintenance,
  loadAllCharts,
} = useAdminDashboardCharts();

/* ---------------- بطاقات KPI (بيانات حقيقية فقط - لا نسب نمو وهمية) ---------------- */
const KPI_CARDS = computed(() => {
  if (!stats.value) return [];
  return [
    { key: "generators_count", icon: "fa-plug-circle-bolt", c1: "#52733D", c2: "#3E582E" },
    { key: "owners_count", icon: "fa-user-tie", c1: "#8A6D1F", c2: "#D4AF37" },
    { key: "subscribers_count", icon: "fa-users", c1: "#52733D", c2: "#8cc35a" },
    { key: "active_subscriptions_count", icon: "fa-file-signature", c1: "#52733D", c2: "#3E582E" },
    { key: "invoices_overdue_count", icon: "fa-file-invoice-dollar", c1: "#D9534F", c2: "#b8352f" },
    { key: "payments_pending_count", icon: "fa-wallet", c1: "#17A2B8", c2: "#0f6c7d" },
    { key: "open_faults_count", icon: "fa-triangle-exclamation", c1: "#D9534F", c2: "#b8352f" },
    // --- إضافة: بطاقة عدد طلبات/عمليات الصيانة (مستقلة عن عدد الأعطال المفتوحة) ---
    { key: "maintenance_count", icon: "fa-screwdriver-wrench", c1: "#FFC107", c2: "#a3760a" },
    { key: "complaints_open_count", icon: "fa-comment-dots", c1: "#D9534F", c2: "#8A6D1F" },
    { key: "technicians_count", icon: "fa-screwdriver-wrench", c1: "#52733D", c2: "#3E582E" },
    { key: "new_service_requests_count", icon: "fa-hand-holding-hand", c1: "#FFC107", c2: "#a3760a" },
    // FIX: إضافة (item 11) — هذين الحقلين كانا موجودين أصلًا بنفس رد
    // /admin/dashboard/stats المستخدَم هون (stats.value يحتوي كامل الرد
    // بدون تصفية)، بس ما كانا معروضين بأي مكان بلوحة التحكم الرئيسية —
    // كانا معروضين فقط بمكوّن AdminStatsGrid.vue المعزول (زيرو استخدام).
    // دمجناهم هون بدل إعادة استخدام AdminStatsGrid نفسه لنتفادى نداء API
    // مكرر لنفس الـ endpoint بنفس الصفحة.
    { key: "invoices_issued_count", icon: "fa-file-invoice", c1: "#17A2B8", c2: "#0f6c7d" },
    { key: "invoices_paid_count", icon: "fa-circle-check", c1: "#28A745", c2: "#1f7a37" },
  ];
});

// FIX: (item 11) stats.total_revenue_ils — نفس الملاحظة أعلاه: كان موجود
// بالرد أصلًا وغير معروض إلا بـ AdminStatsGrid.vue المعزول. عرض عملة
// بصيغة مختلفة عن بطاقات KPI الرقمية العادية (v-count-up)، فمنعرضه ببطاقة
// مميّزة منفصلة بدل إقحامه بنفس شبكة KPI_CARDS.
const totalRevenueDisplay = computed(() =>
  stats.value ? Number(stats.value.total_revenue_ils ?? 0).toFixed(2) : "0.00",
);

/* ---------------- أولويات اليوم (مبنية على stats الحقيقية) ---------------- */
const TODAY_PRIORITIES = computed(() => {
  if (!stats.value) return [];
  return [
    { key: "invoices_overdue_count", icon: "fa-file-invoice-dollar", color: "#D9534F", to: { name: "admin.invoices" } },
    { key: "payments_pending_count", icon: "fa-wallet", color: "#FFC107", to: { name: "admin.payments" } },
    { key: "open_faults_count", icon: "fa-triangle-exclamation", color: "#D9534F", to: { name: "admin.technicians" } },
    { key: "complaints_open_count", icon: "fa-comment-dots", color: "#17A2B8", to: { name: "admin.subscribers" } },
    { key: "new_service_requests_count", icon: "fa-hand-holding-hand", color: "#8A6D1F", to: { name: "admin.subscriptions" } },
  ]
    .map((p) => ({ ...p, label: t(`kpi.${p.key}`), count: stats.value[p.key] ?? 0 }))
    .filter((p) => p.count > 0)
    .sort((a, b) => b.count - a.count);
});

/**
 * تنسيق موحّد لعرض المبالغ المالية (₪).
 * تم استخراجها كدالة واحدة بدل تكرارها 3 مرات داخل القالب (DRY).
 */
function fmtMoney(amount) {
  return "₪ " + Number(amount ?? 0).toLocaleString(locale.value === "ar" ? "ar-EG" : "en-US");
}


const ALERT_ICONS = {
  fuel_low: { icon: "fa-gas-pump", color: "#D9534F" },
  fault_critical: { icon: "fa-triangle-exclamation", color: "#D9534F" },
  invoice_overdue: { icon: "fa-file-invoice", color: "#FFC107" },
  complaint_open: { icon: "fa-comment-dots", color: "#17A2B8" },
};
const ALERT_CHIPS = { critical: "chip-danger", warning: "chip-warning", info: "chip-info" };
function alertTag(severity) {
  return t(`alert.${severity}`, t("alert.info"));
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

/* ---------------- تنسيق التاريخ الحالي (حقيقي، ثنائي اللغة) ---------------- */
const todayLabel = computed(() =>
  new Date().toLocaleDateString(locale.value === "ar" ? "ar-EG-u-ca-gregory" : "en-US", {
    weekday: "long", day: "numeric", month: "long", year: "numeric",
  }),
);

/* ---------------- إعدادات مخطط الإيرادات مقابل المستحقات (مع تبديل الفترة) ----------------
   نفس نمط تبديل الفترة (6/12 شهر أو سنة محدَّدة) المستخدَم بمخطط "الإيرادات
   الموزّعة على المالكين" بصفحة أصحاب المولدات — لتوحيد سلوك عناصر التحكّم
   بالفترة بين الواجهتين. */
const revenuePeriod = ref("6"); // '6' | '12' | 'year'
const revenueYear = ref(null);
const availableRevenueYears = ref([]);

async function fetchRevenueData() {
  const params =
    revenuePeriod.value === "year" && revenueYear.value
      ? { year: revenueYear.value }
      : { period: revenuePeriod.value };
  await fetchRevenue(params);
  if (revenue.value?.available_years?.length) {
    availableRevenueYears.value = revenue.value.available_years;
    if (!revenueYear.value) revenueYear.value = revenue.value.available_years[0];
  }
}

function setRevenuePeriod(period) {
  revenuePeriod.value = period;
  fetchRevenueData();
}

function setRevenueYear(year) {
  revenueYear.value = year;
  revenuePeriod.value = "year";
  fetchRevenueData();
}

const revenueYearOptions = computed(() => availableRevenueYears.value.map((y) => ({ value: y, label: String(y) })));

const revenueChartData = computed(() => ({
  labels: revenue.value?.labels ?? [],
  datasets: [
    { label: t("dashboard.paid"), data: revenue.value?.revenue ?? [], borderColor: "#52733D", backgroundColor: "rgba(82,115,61,0.12)", fill: true, tension: 0.4 },
    { label: t("dashboard.overdue"), data: revenue.value?.outstanding ?? [], borderColor: "#D9534F", backgroundColor: "rgba(217,83,79,0.08)", fill: true, tension: 0.4 },
  ],
}));
const lineChartOptions = computed(() => ({
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { position: "bottom", labels: { boxWidth: 10, font: { size: 11 } } } },
  scales: { x: { grid: { display: false } }, y: { grid: { color: "rgba(82,115,61,0.08)" } } },
}));

/* ---------------- إعدادات مخطط حالة الفواتير ---------------- */
const invoiceDoughnutData = computed(() => ({
  labels: [t("dashboard.paid"), t("dashboard.pending"), t("dashboard.overdue")],
  datasets: [{
    data: [invoicePercentages.value.paid, invoicePercentages.value.pending, invoicePercentages.value.overdue],
    backgroundColor: ["#28A745", "#FFC107", "#D9534F"],
    borderWidth: 0,
  }],
}));
const doughnutOptions = { responsive: true, maintainAspectRatio: false, cutout: "70%", plugins: { legend: { display: false } } };

/* ---------------- إعدادات مخطط نمو الاشتراكات ---------------- */
const subscriberGrowthData = computed(() => ({
  labels: subscriberGrowth.value?.labels ?? [],
  datasets: [{ label: t("kpi.subscribers_count"), data: subscriberGrowth.value?.counts ?? [], backgroundColor: "#8A6D1F", borderRadius: 6 }],
}));
const barNoLegendOptions = {
  responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
  scales: { x: { grid: { display: false } }, y: { grid: { color: "rgba(82,115,61,0.08)" } } },
};

/* ---------------- إعدادات مخطط مشتريات الوقود ---------------- */
const fuelChartData = computed(() => ({
  labels: fuel.value?.labels ?? [],
  datasets: [{ label: "L", data: fuel.value?.liters ?? [], borderColor: "#17A2B8", backgroundColor: "rgba(23,162,184,0.12)", fill: true, tension: 0.4 }],
}));

/* ---------------- إعدادات مخطط الصيانة والأعطال حسب المدينة (Stacked Bar) ---------------- */
const maintenanceChartData = computed(() => ({
  labels: maintenance.value?.labels ?? [],
  datasets: [
    { label: t("status.maintenance"), data: maintenance.value?.maintenance ?? [], backgroundColor: "#FFC107", borderRadius: 6 },
    { label: t("dashboard.faults_label"), data: maintenance.value?.faults ?? [], backgroundColor: "#D9534F", borderRadius: 6 },
  ],
}));
const stackedBarOptions = {
  responsive: true, maintainAspectRatio: false,
  plugins: { legend: { position: "bottom", labels: { boxWidth: 10, font: { size: 10 } } } },
  scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, grid: { color: "rgba(82,115,61,0.08)" } } },
};

/* ---------------- جدولة صيانة (حقيقية - GeneratorSchedule) ---------------- */
const isMaintenanceModalOpen = ref(false);
const maintenanceForm = ref({ generator_id: "", starts_at: "", ends_at: "", note: "" });

async function openMaintenanceModal() {
  maintenanceForm.value = { generator_id: "", starts_at: "", ends_at: "", note: "" };
  maintenanceError.value = null;
  await ensureGeneratorsForSelectLoaded();
  isMaintenanceModalOpen.value = true;
}

async function handleScheduleMaintenance() {
  if (!maintenanceForm.value.generator_id || !maintenanceForm.value.starts_at || !maintenanceForm.value.ends_at) return;
  const ok = await scheduleMaintenance(maintenanceForm.value.generator_id, {
    starts_at: maintenanceForm.value.starts_at,
    ends_at: maintenanceForm.value.ends_at,
    note: maintenanceForm.value.note || undefined,
  });
  if (ok) {
    isMaintenanceModalOpen.value = false;
    toast.show({ type: "success", title: t("dashboard.maintenance_scheduled") });
  }
}

/* ---------------- مالك/مشترك/فني جديد + النسخة الاحتياطية (FE-01: منقولة
   إلى useAdminQuickCreate composable بدل نداء الخدمات مباشرة من الـ view) ---------------- */
const {
  isOwnerModalOpen,
  isSavingOwner,
  ownerError,
  ownerForm,
  openOwnerModal,
  handleCreateOwner,
  isNewSubscriberModalOpen,
  isSavingSubscriber,
  subscriberError,
  subscriberForm,
  openNewSubscriberModal,
  handleCreateSubscriber,
  isTechnicianModalOpen,
  isSavingTechnician,
  technicianError,
  technicianForm,
  technicianOwnerOptions,
  isLoadingTechnicianOwners,
  openTechnicianModal,
  handleCreateTechnician,
  isRunningBackup,
  handleRunBackup,
} = useAdminQuickCreate({ onSubscriberCreated: loadAll });

/* ---------------- إظهار/إخفاء كلمة السر بنوافذ الإنشاء السريع ---------------- */
const showOwnerPassword = ref(false);
const showOwnerPasswordConfirmation = ref(false);
const showSubscriberPassword = ref(false);
const showSubscriberPasswordConfirmation = ref(false);
const showTechnicianPassword = ref(false);
const showTechnicianPasswordConfirmation = ref(false);

/* ---------------- إضافة: تصدير تقرير اليوم (زر الهيرو) — يعتمد على بيانات KPI الحقيقية المحمّلة أصلًا ---------------- */
function exportTodayReport() {
  if (!stats.value) return;
  const rows = [
    [t("dashboard.metric_label"), t("dashboard.value_label")],
    ...KPI_CARDS.value.map((card) => [t(`kpi.${card.key}`), stats.value[card.key] ?? 0]),
  ];
  downloadCsv(
    `${t("dashboard.daily_report_filename")}-${new Date().toISOString().slice(0, 10)}.csv`,
    rows,
  );
}

const announcementModalRef = ref(null);
function openAnnouncementModal() {
  announcementModalRef.value?.open();
}

const aiInsights = computed(() => {
  if (!stats.value) return [];
  const list = [];

  const fuelLowCount = alerts.value.filter((a) => a.type === "fuel_low").length;
  if (fuelLowCount > 0) {
    list.push({
      id: "fuel-risk",
      severity: "critical",
      icon: "fa-gas-pump",
      color: "#D9534F",
      title: t("dashboard.insight_fuel_risk_title"),
      description: t("dashboard.insight_fuel_risk_desc", { count: fuelLowCount }),
      to: { name: "admin.generators" },
    });
  }

  if (stats.value.invoices_overdue_count > 0 && stats.value.subscribers_count > 0) {
    const overdueRatio = Math.round((stats.value.invoices_overdue_count / stats.value.subscribers_count) * 100);
    if (overdueRatio >= 5) {
      list.push({
        id: "collection-risk",
        severity: "warning",
        icon: "fa-file-invoice-dollar",
        color: "#FFC107",
        title: t("dashboard.insight_collection_risk_title"),
        description: t("dashboard.insight_collection_risk_desc", { ratio: overdueRatio }),
        to: { name: "admin.invoices" },
      });
    }
  }

  if (stats.value.complaints_open_count > 0) {
    list.push({
      id: "complaints-trend",
      severity: "info",
      icon: "fa-comment-dots",
      color: "#17A2B8",
      title: t("dashboard.insight_complaints_title"),
      description: t("dashboard.insight_complaints_desc", { count: stats.value.complaints_open_count }),
      to: { name: "admin.subscribers" },
    });
  }

  const topGenerator = [...generators.value]
    .filter((g) => g.monthly_revenue_ils)
    .sort((a, b) => (b.monthly_revenue_ils ?? 0) - (a.monthly_revenue_ils ?? 0))[0];
  if (topGenerator) {
    list.push({
      id: "top-generator",
      severity: "positive",
      icon: "fa-arrow-trend-up",
      color: "#28A745",
      title: t("dashboard.insight_top_generator_title"),
      description: t("dashboard.insight_top_generator_desc", { name: topGenerator.name, amount: fmtMoney(topGenerator.monthly_revenue_ils) }),
      to: { name: "admin.generators" },
    });
  }

  return list;
});
const AI_SEVERITY_CHIP = { critical: "chip-danger", warning: "chip-warning", info: "chip-info", positive: "chip-success" };

onMounted(async () => {
  await loadAll();
  fetchTimeline();
  loadAllCharts();
  fetchRevenueData();
});
</script>

<template>
  <div class="space-y-6">
    <!-- ===== HERO + الطقس ===== -->
    <section v-reveal class="grid lg:grid-cols-[1.5fr_1fr] gap-4">
      <div class="glass-card relative overflow-hidden p-6 lg:p-8">
        <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
        <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>
        <div class="relative">
          <span class="inline-flex items-center gap-2 text-[11px] font-bold text-[#52733D] dark:text-[#8cc35a] bg-[#EBF1E7] dark:bg-white/5 border border-[#D4AF37]/30 rounded-full px-3 py-1.5 mb-4">
            <CalendarDays class="text-[#8A6D1F] dark:text-[#D4AF37]" aria-hidden="true" /> {{ todayLabel }}
          </span>
          <h1 class="text-2xl lg:text-[28px] font-extrabold mb-2">
            {{ t("dashboard.welcome", { name: authStore.user?.name ?? "-" }) }}
          </h1>
          <p v-if="stats" class="text-[13.5px] text-[#6B6B6B] dark:text-[#aeb1ab] leading-relaxed max-w-lg mb-5">
            {{ t("dashboard.summary", { activeGenerators: stats.active_generators_count, subscribers: stats.subscribers_count }) }}
          </p>
          <!-- ===== إضافة: أزرار الإجراءات السريعة الثلاثة المطلوبة ===== -->
          <div class="flex flex-wrap gap-2.5">
            <button
              type="button"
              @click="exportTodayReport"
              class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2"
            >
              <FileDown aria-hidden="true" />
              {{ t("dashboard.export_report") }}
            </button>
            <button
              type="button"
              @click="openNewSubscriberModal"
              class="btn-fill relative text-[12.5px] font-bold px-4 py-2.5 rounded-full border border-[#D4AF37]/50 text-[#3E582E] dark:text-[#F4E0A5] hover:text-white dark:hover:text-white hover:border-transparent transition-colors duration-300 flex items-center gap-2"
            >
              <UserPlus aria-hidden="true" />
              {{ t("dashboard.new_subscriber_hero") }}
            </button>
          </div>
        </div>
      </div>

      <!-- الطقس -->
      <GazaWeatherCard />
    </section>

    <!-- ===== KPI CARDS ===== -->
    <section v-reveal>
      <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
        <h2 class="text-[15px] font-extrabold">{{ t("dashboard.quick_overview") }}</h2>
      </div>
      <div v-if="isLoadingStats" class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3.5">
        <div v-for="i in 8" :key="i" class="h-28 rounded-2xl thumb-loading"></div>
      </div>
      <div v-else class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3.5">
        <div
          v-for="card in KPI_CARDS"
          :key="card.key"
          class="kpi-card glass-card hoverable"
          :style="{ '--kpi-color': card.c1, '--kpi-color2': card.c2 }"
        >
          <div class="flex items-start justify-between mb-3">
            <div class="kpi-icon"><AppIcon :name="card.icon" /></div>
          </div>
          <div class="text-xl font-extrabold" v-count-up="stats[card.key]">0</div>
          <div class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-2.5">{{ t(`kpi.${card.key}`) }}</div>
          <div class="bar-track"><div class="bar-fill" style="width:100%; opacity:.35" :style="{ background: card.c1 }"></div></div>
        </div>
      </div>

      <!-- FIX: (item 11) بطاقة الإيراد الإجمالي — كانت موجودة فقط بمكوّن
           AdminStatsGrid.vue المعزول، دُمجت هون كجزء من نفس تدفق بيانات
           لوحة التحكم الرئيسية (stats الحقيقي نفسه، بدون نداء API إضافي). -->
      <div v-if="!isLoadingStats && stats" class="glass-card !bg-gradient-to-l !from-[#3E582E]/10 !via-[#52733D]/10 !to-[#8A6D1F]/10 dark:!from-[#3E582E]/25 dark:!via-[#52733D]/20 dark:!to-[#8A6D1F]/20 p-5 mt-3.5 flex items-center justify-between">
        <p class="text-[12.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t("admin_stats_grid.total_revenue_label") }}</p>
        <p class="font-mono text-2xl font-extrabold text-[#3E582E] dark:text-[#8cc35a]">{{ totalRevenueDisplay }} ₪</p>
      </div>
    </section>

    <!-- ===== CHARTS ===== -->
    <section v-reveal class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div class="glass-card p-4 md:col-span-2 lg:col-span-2 flex flex-col">
        <!-- نفس نمط رأس/عناصر تحكّم مخطط "الإيرادات الموزّعة على المالكين" بصفحة أصحاب المولدات -->
        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
          <h3 class="text-[13.5px] font-bold">{{ t("dashboard.revenue_chart_title") }}</h3>

          <div class="flex items-center gap-2 flex-wrap">
            <AppDropdownSelect
              v-if="revenuePeriod === 'year'"
              :model-value="revenueYear"
              @update:model-value="setRevenueYear"
              :options="revenueYearOptions"
              width-class="w-24"
              :panel-width="96"
            />
            <div class="flex items-center gap-1 bg-[#f4efe5]/60 dark:bg-white/5 rounded-full p-1">
              <button
                type="button"
                @click="setRevenuePeriod('6')"
                class="px-2.5 py-1 rounded-full text-[10px] font-bold transition-colors"
                :class="revenuePeriod === '6' ? 'bg-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5]'"
              >
                {{ t("dashboard.period_6m") }}
              </button>
              <button
                type="button"
                @click="setRevenuePeriod('12')"
                class="px-2.5 py-1 rounded-full text-[10px] font-bold transition-colors"
                :class="revenuePeriod === '12' ? 'bg-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5]'"
              >
                {{ t("dashboard.period_12m") }}
              </button>
              <button
                type="button"
                @click="setRevenuePeriod('year')"
                class="px-2.5 py-1 rounded-full text-[10px] font-bold transition-colors"
                :class="revenuePeriod === 'year' ? 'bg-[#52733D] text-white' : 'text-[#6B6B6B] dark:text-[#a8aaa5]'"
              >
                {{ t("dashboard.period_year") }}
              </button>
            </div>
          </div>
        </div>
        <div v-if="isLoadingRevenue" class="flex-1 min-h-[16rem] thumb-loading rounded-lg"></div>
        <div v-else class="flex-1 min-h-[16rem]"><Line :data="revenueChartData" :options="lineChartOptions" /></div>
      </div>

      <div class="glass-card p-4">
        <h3 class="text-[13.5px] font-bold mb-3">{{ t("dashboard.invoice_status_title") }}</h3>
        <div v-if="isLoadingBreakdown" class="h-44 thumb-loading rounded-lg"></div>
        <template v-else>
          <div class="h-44"><Doughnut :data="invoiceDoughnutData" :options="doughnutOptions" /></div>
          <div class="grid grid-cols-3 gap-2 mt-3 text-center">
            <div><div class="text-sm font-extrabold text-[#28A745]">{{ invoicePercentages.paid }}%</div><div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("dashboard.paid") }}</div></div>
            <div><div class="text-sm font-extrabold text-[#FFC107]">{{ invoicePercentages.pending }}%</div><div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("dashboard.pending") }}</div></div>
            <div><div class="text-sm font-extrabold text-[#D9534F]">{{ invoicePercentages.overdue }}%</div><div class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("dashboard.overdue") }}</div></div>
          </div>
        </template>
      </div>

      <div class="glass-card p-4">
        <h3 class="text-[13.5px] font-bold mb-3">{{ t("dashboard.subscriber_growth_title") }}</h3>
        <div v-if="isLoadingSubscriberGrowth" class="h-44 thumb-loading rounded-lg"></div>
        <div v-else class="h-44"><Bar :data="subscriberGrowthData" :options="barNoLegendOptions" /></div>
      </div>

      <div class="glass-card p-4">
        <h3 class="text-[13.5px] font-bold mb-3">{{ t("dashboard.fuel_chart_title") }}</h3>
        <div v-if="isLoadingFuel" class="h-44 thumb-loading rounded-lg"></div>
        <div v-else class="h-44"><Line :data="fuelChartData" :options="barNoLegendOptions" /></div>
      </div>

      <!-- طلبات الصيانة والأعطال حسب المدينة — Stacked Bar Chart -->
      <div class="glass-card p-4">
        <h3 class="text-[13.5px] font-bold mb-3">{{ t("dashboard.maintenance_chart_title") }}</h3>
        <div v-if="isLoadingMaintenance" class="h-44 thumb-loading rounded-lg"></div>
        <div v-else-if="!maintenance?.labels?.length" class="h-44 flex items-center justify-center text-[11px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("dashboard.no_enough_data") }}</div>
        <div v-else class="h-44"><Bar :data="maintenanceChartData" :options="stackedBarOptions" /></div>
      </div>
    </section>

    <!-- ===== جدول المولدات الكامل (كاردات KPI + شريط الأدوات + الجدول) — مكوّن مشترك، نفس الموجود بصفحة "إدارة المولدات" ===== -->
    <GeneratorsTablePanel ref="generatorsPanelRef" :show-kpis="false" />

      <div class="glass-card p-4">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
          <h3 class="text-[13.5px] font-bold">{{ t("dashboard.map_title") }}</h3>
          <span v-if="stats" class="text-[10px] text-[#9a9d97] dark:text-[#8f938a]">{{ stats.generators_count }}</span>
        </div>
        <AdminGeneratorsMap />
        <p class="text-[10px] text-[#9a9d97] dark:text-[#8f938a] mt-2">{{ t("dashboard.map_note") }}</p>
      </div>


    <!-- ===== تنبيهات + خط زمني + أولويات اليوم ===== -->
    <section v-reveal class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
      <div class="glass-card p-4">
        <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
          <h3 class="text-[13.5px] font-bold">{{ t("dashboard.instant_alerts") }}</h3>
          <span v-if="alerts.length" class="text-[10px] font-bold text-white bg-[#D9534F] rounded-full px-2 py-0.5">{{ alerts.length }}</span>
        </div>
        <div v-if="isLoadingAlerts" class="space-y-2">
          <div v-for="i in 3" :key="i" class="h-12 rounded-lg thumb-loading"></div>
        </div>
        <div v-else-if="alerts.length === 0" class="dropdown-empty py-8">{{ t("dashboard.no_alerts") }}</div>
        <div v-else class="space-y-2.5">
          <div v-for="(a, i) in alerts" :key="i" class="flex items-start gap-2.5 p-2.5 rounded-lg hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 transition-colors">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shrink-0" :style="{ background: ALERT_ICONS[a.type]?.color ?? '#52733D' }">
              <AppIcon :name="ALERT_ICONS[a.type]?.icon ?? 'fa-bell'" class="text-[11px]" />
            </div>
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between gap-2">
                <span class="text-[12px] font-bold truncate">{{ a.title }}</span>
                <span class="status-chip shrink-0" :class="ALERT_CHIPS[a.severity] ?? 'chip-info'">{{ alertTag(a.severity) }}</span>
              </div>
              <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5">{{ a.description }}</p>
            </div>
          </div>
        </div>
      </div>

      <div class="glass-card p-4">
        <h3 class="text-[13.5px] font-bold mb-3">{{ t("dashboard.recent_activity") }}</h3>
        <div v-if="isLoadingTimeline" class="space-y-2">
          <div v-for="i in 3" :key="i" class="h-10 rounded-lg thumb-loading"></div>
        </div>
        <div v-else-if="timeline.length === 0" class="dropdown-empty py-8">{{ t("dashboard.no_activity") }}</div>
        <div v-else class="mt-1">
          <div v-for="log in timeline" :key="log.id" class="timeline-item" style="--dot-color:#52733D">
            <p class="text-[12px] font-semibold">
              {{ log.causer?.name ?? "-" }}
              <span class="font-normal text-[#6B6B6B] dark:text-[#a8aaa5]">- {{ log.description }}</span>
            </p>
            <span class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ timeAgo(log.created_at) }}</span>
          </div>
        </div>
      </div>

      <div class="glass-card p-4 relative overflow-hidden">
        <div class="absolute -start-8 -top-8 w-40 h-40 bg-[#D4AF37]/15 dark:bg-[#D4AF37]/20 rounded-full blur-[70px] pointer-events-none"></div>
        <h3 class="text-[13.5px] font-bold mb-3 flex items-center gap-2"><ListChecks class="text-[#8A6D1F] dark:text-[#D4AF37]" aria-hidden="true" /> {{ t("dashboard.today_priorities") }}</h3>
        <div v-if="isLoadingStats" class="space-y-2.5">
          <div v-for="i in 3" :key="i" class="h-14 rounded-lg thumb-loading"></div>
        </div>
        <div v-else-if="TODAY_PRIORITIES.length === 0" class="dropdown-empty py-8">{{ t("dashboard.no_priorities") }}</div>
        <div v-else class="space-y-2.5">
          <RouterLink
            v-for="p in TODAY_PRIORITIES"
            :key="p.key"
            :to="p.to"
            class="flex items-center justify-between gap-2.5 p-2.5 rounded-lg bg-white/50 dark:bg-white/[0.04] border border-[#eee8da] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/[0.07] transition-colors"
          >
            <div class="flex items-center gap-2.5 min-w-0">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shrink-0" :style="{ background: p.color }">
                <AppIcon :name="p.icon" class="text-[11px]" />
              </div>
              <span class="text-[12px] font-bold truncate">{{ p.label }}</span>
            </div>
            <span class="text-[12px] font-extrabold shrink-0" :style="{ color: p.color }">{{ p.count }}</span>
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- ===== إضافة: سكشن رؤى الذكاء الاصطناعي ===== -->
    <section v-reveal class="glass-card p-4 lg:p-5 relative overflow-hidden">
      <div class="absolute -start-10 -top-10 w-48 h-48 bg-[#D4AF37]/15 dark:bg-[#D4AF37]/20 rounded-full blur-[80px] pointer-events-none"></div>
      <div class="relative flex items-center justify-between flex-wrap gap-2 mb-3.5">
        <h3 class="text-[13.5px] font-bold flex items-center gap-2">
          <span class="w-7 h-7 rounded-lg flex items-center justify-center text-white text-[11px] shrink-0" style="background:linear-gradient(135deg,#8A6D1F,#D4AF37)">
            <WandSparkles aria-hidden="true" />
          </span>
          {{ t("dashboard.ai_insights_title") }}
        </h3>
        <span class="text-[10px] font-bold text-[#8A6D1F] dark:text-[#F4E0A5] bg-[#F4E0A5]/25 dark:bg-white/5 border border-[#D4AF37]/30 rounded-full px-2.5 py-1">
          {{ t("dashboard.ai_powered") }}
        </span>
      </div>

      <div v-if="isLoadingStats" class="grid sm:grid-cols-2 xl:grid-cols-4 gap-3">
        <div v-for="i in 4" :key="i" class="h-24 rounded-2xl thumb-loading"></div>
      </div>
      <div v-else-if="aiInsights.length === 0" class="dropdown-empty py-8">
        {{ t("dashboard.no_insights") }}
      </div>
      <div v-else class="relative grid sm:grid-cols-2 xl:grid-cols-4 gap-3">
        <RouterLink
          v-for="insight in aiInsights"
          :key="insight.id"
          :to="insight.to"
          class="glass-card hoverable p-3.5 flex flex-col gap-2.5"
        >
          <div class="flex items-center justify-between">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white shrink-0" :style="{ background: insight.color }">
              <AppIcon :name="insight.icon" class="text-[11px]" />
            </div>
            <span class="status-chip" :class="AI_SEVERITY_CHIP[insight.severity]">
              {{ insight.severity === "positive" ? t("dashboard.positive_label") : t(`alert.${insight.severity}`, insight.severity) }}
            </span>
          </div>
          <h4 class="text-[12.5px] font-bold leading-snug">{{ insight.title }}</h4>
          <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-relaxed">{{ insight.description }}</p>
        </RouterLink>
      </div>
    </section>

    <!-- ===== أدوات سريعة (آخر سكشن بالصفحة) ===== -->
    <!-- ===== تعديل: كل أزرار "أدوات سريعة" تفتح نافذة (Modal) بدل التنقّل لصفحة كاملة ===== -->
    <!-- ===== تعديل: صف أفقي واحد بدون سكرول — الأزرار تتقلّص (أيقونة/مسافة/خط) على الشاشات الصغيرة بدل ما تلف أو تفيض ===== -->
    <section v-reveal class="glass-card p-4 lg:p-5">
      <h3 class="text-[13.5px] font-bold mb-3.5">{{ t("dashboard.quick_actions") }}</h3>
      <div class="flex items-stretch gap-1 sm:gap-2 lg:gap-2.5">
        <button type="button" class="qw-btn flex-1 min-w-0 basis-0 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5" @click="openOwnerModal">
          <span class="qw-icon !w-7 !h-7 sm:!w-8 sm:!h-8 lg:!w-9 lg:!h-9 !text-[10px] sm:!text-[11px]" style="background:linear-gradient(135deg,#52733D,#3E582E)"><UserRound aria-hidden="true" /></span>
          <span class="block w-full truncate text-center text-[8.5px] sm:text-[9.5px] lg:text-[10.5px] font-semibold">{{ t("menu.generator_owners") }}</span>
        </button>
        <button type="button" class="qw-btn flex-1 min-w-0 basis-0 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5" @click="openNewSubscriberModal">
          <span class="qw-icon !w-7 !h-7 sm:!w-8 sm:!h-8 lg:!w-9 lg:!h-9 !text-[10px] sm:!text-[11px]" style="background:linear-gradient(135deg,#17A2B8,#0f6c7d)"><UsersRound aria-hidden="true" /></span>
          <span class="block w-full truncate text-center text-[8.5px] sm:text-[9.5px] lg:text-[10.5px] font-semibold">{{ t("menu.subscribers") }}</span>
        </button>
        <button type="button" class="qw-btn flex-1 min-w-0 basis-0 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5" @click="openTechnicianModal">
          <span class="qw-icon !w-7 !h-7 sm:!w-8 sm:!h-8 lg:!w-9 lg:!h-9 !text-[10px] sm:!text-[11px]" style="background:linear-gradient(135deg,#FFC107,#a3760a)"><Wrench aria-hidden="true" /></span>
          <span class="block w-full truncate text-center text-[8.5px] sm:text-[9.5px] lg:text-[10.5px] font-semibold">{{ t("menu.technicians") }}</span>
        </button>
        <button type="button" class="qw-btn flex-1 min-w-0 basis-0 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5" @click="openAnnouncementModal">
          <span class="qw-icon !w-7 !h-7 sm:!w-8 sm:!h-8 lg:!w-9 lg:!h-9 !text-[10px] sm:!text-[11px]" style="background:linear-gradient(135deg,#D4AF37,#8A6D1F)"><Megaphone aria-hidden="true" /></span>
          <span class="block w-full truncate text-center text-[8.5px] sm:text-[9.5px] lg:text-[10.5px] font-semibold">{{ t("dashboard.send_announcement_label") }}</span>
        </button>
        <button type="button" class="qw-btn flex-1 min-w-0 basis-0 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 disabled:opacity-50" :disabled="isRunningBackup" @click="handleRunBackup">
          <span class="qw-icon !w-7 !h-7 sm:!w-8 sm:!h-8 lg:!w-9 lg:!h-9 !text-[10px] sm:!text-[11px]" style="background:linear-gradient(135deg,#17A2B8,#0f4c58)">
            <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isRunningBackup" /><Database aria-hidden="true" v-else />
          </span>
          <span class="block w-full truncate text-center text-[8.5px] sm:text-[9.5px] lg:text-[10.5px] font-semibold">{{ t("dashboard.backup_label") }}</span>
        </button>
        <button type="button" class="qw-btn flex-1 min-w-0 basis-0 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5" @click="openMaintenanceModal">
          <span class="qw-icon !w-7 !h-7 sm:!w-8 sm:!h-8 lg:!w-9 lg:!h-9 !text-[10px] sm:!text-[11px]" style="background:linear-gradient(135deg,#FFC107,#a3760a)"><Wrench aria-hidden="true" /></span>
          <span class="block w-full truncate text-center text-[8.5px] sm:text-[9.5px] lg:text-[10.5px] font-semibold">{{ t("dashboard.schedule_maintenance_label") }}</span>
        </button>
      </div>
    </section>

    <AdminAnnouncementModal ref="announcementModalRef" />

    <!-- ===== نافذة جدولة الصيانة ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="isMaintenanceModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="isMaintenanceModalOpen = false">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-md shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--gold">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><Wrench aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ t("dashboard.schedule_maintenance_label") }}</h3>
                <p class="modal-head-brand__subtitle">{{ t("dashboard.maintenance_subtitle") }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="isMaintenanceModalOpen = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <form @submit.prevent="handleScheduleMaintenance" class="p-5 space-y-3.5">
            <div v-if="maintenanceError" class="alert-box">
              <CircleAlert class="shrink-0" aria-hidden="true" /> {{ maintenanceError }}
            </div>
            <div>
              <label class="field-label">{{ t("dashboard.generator_col") }}</label>
              <AppDropdownSelect
                v-model="maintenanceForm.generator_id"
                :options="maintenanceGeneratorOptions"
                :placeholder="t('dashboard.select_generator_placeholder')"
                variant="field"
                width-class="w-full"
                match-trigger-width
              />
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="field-label">{{ t("dashboard.from") }}</label>
                <input v-model="maintenanceForm.starts_at" type="datetime-local" required class="field-input" />
              </div>
              <div>
                <label class="field-label">{{ t("dashboard.to") }}</label>
                <input v-model="maintenanceForm.ends_at" type="datetime-local" required class="field-input" />
              </div>
            </div>
            <div>
              <label class="field-label">{{ t("dashboard.note_optional") }}</label>
              <input v-model="maintenanceForm.note" type="text" maxlength="255" class="field-input" />
            </div>
          </form>

          <div class="modal-footer-brand">
            <button type="button" @click="isMaintenanceModalOpen = false" class="btn-outline-brand">{{ t("dashboard.cancel") }}</button>
            <button type="button" @click="handleScheduleMaintenance" :disabled="isSavingMaintenance" class="btn-fill-brand">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSavingMaintenance" /><Check aria-hidden="true" v-else />
              {{ t("dashboard.schedule_button") }}
            </button>
          </div>
        </div>
      </div>
      </Transition>
    </Teleport>

    <!-- ===== نافذة مالك جديد ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="isOwnerModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="isOwnerModalOpen = false">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-md shadow-2xl overflow-hidden rounded-2xl max-h-[90vh] flex flex-col">
          <div class="modal-head-brand modal-head-brand--gold shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><UserRound aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ t("dashboard.owner_modal_title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ t("dashboard.owner_modal_subtitle") }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="isOwnerModalOpen = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <form @submit.prevent="handleCreateOwner" class="p-5 space-y-3.5 overflow-y-auto">
            <div v-if="ownerError" class="alert-box">
              <CircleAlert class="shrink-0" aria-hidden="true" />
              <div>
                {{ ownerError.message }}
                <ul v-if="ownerError.errors" class="mt-1 space-y-0.5">
                  <li v-for="(msgs, field) in ownerError.errors" :key="field">{{ msgs[0] }}</li>
                </ul>
              </div>
            </div>
            <div>
              <label class="field-label">{{ t("dashboard.full_name") }}</label>
              <input v-model="ownerForm.name" type="text" required class="field-input" />
            </div>
            <div>
              <label class="field-label">{{ t("dashboard.email") }}</label>
              <input v-model="ownerForm.email" type="email" dir="ltr" required class="field-input" />
            </div>
            <div>
              <label class="field-label">{{ t("dashboard.phone_optional") }}</label>
              <input v-model="ownerForm.phone" type="tel" dir="ltr" class="field-input" />
            </div>
            <div class="grid sm:grid-cols-2 gap-3.5">
              <div>
                <label class="field-label">{{ t("dashboard.password") }}</label>
                <div class="relative">
                  <input v-model="ownerForm.password" :type="showOwnerPassword ? 'text' : 'password'" dir="ltr" required class="field-input pr-8" />
                  <button type="button" @click="showOwnerPassword = !showOwnerPassword" class="absolute top-1/2 -translate-y-1/2 right-2 text-[#9a9d97] dark:text-[#8f938a] hover:text-[#8A6D1F]" :aria-label="showOwnerPassword ? t('common.hide_password') : t('common.show_password')">
                    <EyeOff class="text-[12px]" aria-hidden="true" v-if="showOwnerPassword" /><Eye class="text-[12px]" aria-hidden="true" v-else />
                  </button>
                </div>
              </div>
              <div>
                <label class="field-label">{{ t("dashboard.confirm_password") }}</label>
                <div class="relative">
                  <input v-model="ownerForm.password_confirmation" :type="showOwnerPasswordConfirmation ? 'text' : 'password'" dir="ltr" required class="field-input pr-8" />
                  <button type="button" @click="showOwnerPasswordConfirmation = !showOwnerPasswordConfirmation" class="absolute top-1/2 -translate-y-1/2 right-2 text-[#9a9d97] dark:text-[#8f938a] hover:text-[#8A6D1F]" :aria-label="showOwnerPasswordConfirmation ? t('common.hide_password') : t('common.show_password')">
                    <EyeOff class="text-[12px]" aria-hidden="true" v-if="showOwnerPasswordConfirmation" /><Eye class="text-[12px]" aria-hidden="true" v-else />
                  </button>
                </div>
              </div>
            </div>
            <p class="text-[10.5px] text-[#9a9d97] dark:text-[#8f938a]">{{ t("dashboard.password_hint") }}</p>
          </form>

          <div class="modal-footer-brand shrink-0">
            <button type="button" @click="isOwnerModalOpen = false" class="btn-outline-brand">{{ t("dashboard.cancel") }}</button>
            <button type="button" @click="handleCreateOwner" :disabled="isSavingOwner" class="btn-fill-brand">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSavingOwner" /><Check aria-hidden="true" v-else />
              {{ isSavingOwner ? t("dashboard.creating") : t("dashboard.create_account") }}
            </button>
          </div>
        </div>
      </div>
      </Transition>
    </Teleport>

    <!-- ===== إضافة: نافذة اشتراك مشترك جديد ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="isNewSubscriberModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="isNewSubscriberModalOpen = false">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-md shadow-2xl overflow-hidden rounded-2xl max-h-[90vh] flex flex-col">
          <div class="modal-head-brand modal-head-brand--gold shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><UserPlus aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ t("dashboard.subscriber_modal_title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ t("dashboard.subscriber_modal_subtitle") }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="isNewSubscriberModalOpen = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <form @submit.prevent="handleCreateSubscriber" class="p-5 space-y-3.5 overflow-y-auto">
            <div v-if="subscriberError" class="alert-box">
              <CircleAlert class="shrink-0" aria-hidden="true" />
              <div>
                {{ subscriberError.message }}
                <ul v-if="subscriberError.errors" class="mt-1 space-y-0.5">
                  <li v-for="(msgs, field) in subscriberError.errors" :key="field">{{ msgs[0] }}</li>
                </ul>
              </div>
            </div>
            <div>
              <label class="field-label">{{ t("dashboard.subscriber_name_label") }}</label>
              <input v-model="subscriberForm.name" type="text" required class="field-input" />
            </div>
            <div>
              <label class="field-label">{{ t("dashboard.email") }}</label>
              <input v-model="subscriberForm.email" type="email" required class="field-input" dir="ltr" />
            </div>
            <div>
              <label class="field-label">{{ t("dashboard.phone") }}</label>
              <input v-model="subscriberForm.phone" type="tel" dir="ltr" required class="field-input" />
            </div>
            <div>
              <label class="field-label">{{ t("dashboard.password") }}</label>
              <div class="relative">
                <input v-model="subscriberForm.password" :type="showSubscriberPassword ? 'text' : 'password'" dir="ltr" required autocomplete="new-password" class="field-input pr-8" />
                <button type="button" @click="showSubscriberPassword = !showSubscriberPassword" class="absolute top-1/2 -translate-y-1/2 right-2 text-[#9a9d97] dark:text-[#8f938a] hover:text-[#8A6D1F]" :aria-label="showSubscriberPassword ? t('common.hide_password') : t('common.show_password')">
                  <EyeOff class="text-[12px]" aria-hidden="true" v-if="showSubscriberPassword" /><Eye class="text-[12px]" aria-hidden="true" v-else />
                </button>
              </div>
            </div>
            <div>
              <label class="field-label">{{ t("dashboard.confirm_password") }}</label>
              <div class="relative">
                <input v-model="subscriberForm.password_confirmation" :type="showSubscriberPasswordConfirmation ? 'text' : 'password'" dir="ltr" required autocomplete="new-password" class="field-input pr-8" />
                <button type="button" @click="showSubscriberPasswordConfirmation = !showSubscriberPasswordConfirmation" class="absolute top-1/2 -translate-y-1/2 right-2 text-[#9a9d97] dark:text-[#8f938a] hover:text-[#8A6D1F]" :aria-label="showSubscriberPasswordConfirmation ? t('common.hide_password') : t('common.show_password')">
                  <EyeOff class="text-[12px]" aria-hidden="true" v-if="showSubscriberPasswordConfirmation" /><Eye class="text-[12px]" aria-hidden="true" v-else />
                </button>
              </div>
            </div>
            <div>
              <label class="field-label">{{ t("dashboard.address_optional") }}</label>
              <input v-model="subscriberForm.address" type="text" class="field-input" />
            </div>
          </form>

          <div class="modal-footer-brand shrink-0">
            <button type="button" @click="isNewSubscriberModalOpen = false" class="btn-outline-brand">{{ t("dashboard.cancel") }}</button>
            <button type="button" @click="handleCreateSubscriber" :disabled="isSavingSubscriber" class="btn-fill-brand">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSavingSubscriber" /><Check aria-hidden="true" v-else />
              {{ isSavingSubscriber ? t("dashboard.creating") : t("dashboard.create_subscriber_btn") }}
            </button>
          </div>
        </div>
      </div>
      </Transition>
    </Teleport>

    <!-- ===== إضافة: نافذة فني جديد سريع ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
      <div v-if="isTechnicianModalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="isTechnicianModalOpen = false">
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-md shadow-2xl overflow-hidden rounded-2xl max-h-[90vh] flex flex-col">
          <div class="modal-head-brand modal-head-brand--gold shrink-0">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><Wrench aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ t("dashboard.technician_modal_title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ t("dashboard.technician_modal_subtitle") }}</p>
              </div>
            </div>
            <button :aria-label="$t('common.close')" type="button" @click="isTechnicianModalOpen = false" class="modal-head-brand__close"><X aria-hidden="true" /></button>
          </div>

          <form @submit.prevent="handleCreateTechnician" class="p-5 space-y-3.5 overflow-y-auto">
            <div v-if="technicianError" class="alert-box">
              <CircleAlert class="shrink-0" aria-hidden="true" />
              <div>
                {{ technicianError.message }}
                <ul v-if="technicianError.errors" class="mt-1 space-y-0.5">
                  <li v-for="(msgs, field) in technicianError.errors" :key="field">{{ msgs[0] }}</li>
                </ul>
              </div>
            </div>
            <div>
              <label class="field-label">{{ t("users_page.owner_field_label") }}</label>
              <AppDropdownSelect
                v-model="technicianForm.owner_id"
                :options="technicianOwnerOptions"
                :disabled="isLoadingTechnicianOwners"
                :placeholder="isLoadingTechnicianOwners ? t('common.loading') : t('users_page.select_owner_placeholder')"
                variant="field"
                width-class="w-full"
                match-trigger-width
              />
            </div>
            <div>
              <label class="field-label">{{ t("dashboard.name") }}</label>
              <input v-model="technicianForm.name" type="text" required class="field-input" />
            </div>
            <div>
              <label class="field-label">{{ t("dashboard.email_optional") }}</label>
              <input v-model="technicianForm.email" type="email" dir="ltr" required class="field-input" />
            </div>
            <div>
              <label class="field-label">{{ t("dashboard.password") }}</label>
              <div class="relative">
                <input v-model="technicianForm.password" :type="showTechnicianPassword ? 'text' : 'password'" dir="ltr" autocomplete="new-password" required class="field-input pr-8" />
                <button type="button" @click="showTechnicianPassword = !showTechnicianPassword" class="absolute top-1/2 -translate-y-1/2 right-2 text-[#9a9d97] dark:text-[#8f938a] hover:text-[#8A6D1F]" :aria-label="showTechnicianPassword ? t('common.hide_password') : t('common.show_password')">
                  <EyeOff class="text-[12px]" aria-hidden="true" v-if="showTechnicianPassword" /><Eye class="text-[12px]" aria-hidden="true" v-else />
                </button>
              </div>
            </div>
            <div>
              <label class="field-label">{{ t("dashboard.confirm_password") }}</label>
              <div class="relative">
                <input v-model="technicianForm.password_confirmation" :type="showTechnicianPasswordConfirmation ? 'text' : 'password'" dir="ltr" autocomplete="new-password" required class="field-input pr-8" />
                <button type="button" @click="showTechnicianPasswordConfirmation = !showTechnicianPasswordConfirmation" class="absolute top-1/2 -translate-y-1/2 right-2 text-[#9a9d97] dark:text-[#8f938a] hover:text-[#8A6D1F]" :aria-label="showTechnicianPasswordConfirmation ? t('common.hide_password') : t('common.show_password')">
                  <EyeOff class="text-[12px]" aria-hidden="true" v-if="showTechnicianPasswordConfirmation" /><Eye class="text-[12px]" aria-hidden="true" v-else />
                </button>
              </div>
            </div>
            <div>
              <label class="field-label">{{ t("users_page.notes_optional_label") }}</label>
              <textarea v-model="technicianForm.notes" rows="2" maxlength="1000" class="field-input"></textarea>
            </div>
          </form>

          <div class="modal-footer-brand shrink-0">
            <button type="button" @click="isTechnicianModalOpen = false" class="btn-outline-brand">{{ t("dashboard.cancel") }}</button>
            <button type="button" @click="handleCreateTechnician" :disabled="isSavingTechnician" class="btn-fill-brand">
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSavingTechnician" /><Check aria-hidden="true" v-else />
              {{ isSavingTechnician ? t("dashboard.adding") : t("dashboard.add_technician_btn") }}
            </button>
          </div>
        </div>
      </div>
      </Transition>
    </Teleport>

  </div>
</template>