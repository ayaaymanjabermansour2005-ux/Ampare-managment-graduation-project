<script setup>
import { computed, onMounted, ref } from "vue";
import { RouterLink } from "vue-router";
import { useI18n } from "vue-i18n";
import { Chart as ChartJS, registerables } from "chart.js";
import { Doughnut, Bar, Line } from "vue-chartjs";
import { useAuthStore } from "@/stores/auth";
import { useOwnerDashboard } from "@/composables/useOwnerDashboard";
import { useOwnerDashboardExtras } from "@/composables/useOwnerDashboardExtras";
import { useOwnerTechnicianTasks } from "@/composables/useOwnerTechnicianTasks";
import { useOwnerComplaints } from "@/composables/useOwnerComplaints";
import { useOwnerOffers } from "@/composables/useOwnerOffers";
import { useCommissionReports } from "@/composables/useCommissionReports";
import { useOwnerMeterReadings } from "@/composables/useOwnerMeterReadings";
import { useOwnerRatings } from "@/composables/useOwnerRatings";
import { useConfirm } from "@/composables/useConfirm";
import { useLeafletMap } from "@/composables/useLeafletMap";
import { useToastStore } from "@/stores/toast";
import { vReveal } from "@/directives/reveal";
import StatCard from "@/components/dashboard/StatCard.vue";
import GazaWeatherCard from "@/components/dashboard/GazaWeatherCard.vue";
import GeneratorSummaryCard from "@/components/dashboard/GeneratorSummaryCard.vue";
import FaultPredictionsPanel from "@/components/faults/FaultPredictionsPanel.vue";
import { ChartColumn, ChartLine, Check, ClipboardList, Gauge, HandCoins, LoaderCircle, MessageCircleMore, Star, Tags, Zap } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


ChartJS.register(...registerables);

/* ==========================================================================
 * ============================  الحالة العامة  ============================
 * ========================================================================== */
const authStore = useAuthStore();
const { t, locale } = useI18n();
const { confirm } = useConfirm();
const toast = useToastStore();

/* ---------------- المولدات والدفعات المعلّقة + الإحصائيات العامة ---------------- */
const {
  generators,
  pendingPayments,
  isLoading,
  error,
  load,
  extraStats,
  isLoadingExtraStats,
  extraStatsError,
  loadExtraStats,
} = useOwnerDashboard();

/* ---------------- إحصائيات إضافية: توزيع الحالة / الخريطة / الأداء الشهري ---------------- */
const {
  allGenerators,
  isLoadingAllGenerators,
  fetchAllGenerators,
  statusBreakdown,
  statusChartData,
  statusLegendItems,
  lowestFuelGenerators,
  mapPoints,
  isLoadingMapPoints,
  fetchMapPoints,
  monthlyPerformance,
  isLoadingMonthlyPerformance,
  fetchMonthlyPerformance,
} = useOwnerDashboardExtras();

/* ---------------- أوامر شغل بانتظار المراجعة ---------------- */
const {
  needsReviewTasks,
  isLoading: isLoadingTasks,
  fetchTasks,
} = useOwnerTechnicianTasks();

/* ---------------- شكاوى / عروض / عمولة / قراءات معلّقة ---------------- */
const {
  complaints,
  isLoading: isLoadingComplaints,
  fetchComplaints,
} = useOwnerComplaints();

const {
  offers,
  isLoading: isLoadingOffers,
  fetchOffers,
} = useOwnerOffers();

const {
  isLoading: isLoadingCommissions,
  totalEarned,
  fetchCommissions,
} = useCommissionReports();

const {
  readings,
  isLoading: isLoadingReadings,
  fetchReadings,
  approvingId,
  approveError,
  approveReading,
} = useOwnerMeterReadings();

/* ---------------- تقييمات المشتركين لصاحب المولد ----------------
 * GET /owners/{id}/ratings كان مبنيًا بالكامل بالباك اند بدون أي واجهة تعرضه.
 */
const {
  ratings: ownerRatings,
  averageRating: ownerAverageRating,
  ratingsCount: ownerRatingsCount,
  isLoading: isLoadingOwnerRatings,
  fetchRatings: fetchOwnerRatings,
} = useOwnerRatings();

onMounted(() => {
  load();
  loadExtraStats();
  fetchTasks();
  fetchComplaints();
  fetchOffers();
  fetchCommissions();
  fetchReadings();
  fetchAllGenerators();
  fetchMapPoints().then(() => fillMap());
  fetchMonthlyPerformance();
  fetchOwnerRatings(authStore.user?.id);
});

/* ==========================================================================
 * =====================  قسم 1: ما يحتاج انتباهك الآن  =====================
 * ========================================================================== */

/* ---------------- أولويات اليوم (من extraStats الموجودة أصلاً) ---------------- */
const TODAY_PRIORITIES = computed(() => {
  if (!extraStats.value) return [];
  return [
    { key: "overdue_invoices", icon: "fa-file-invoice-dollar", color: "#D9534F", to: { name: "owner.invoices" }, count: extraStats.value.overdue_invoices_count },
    { key: "pending_payments", icon: "fa-wallet", color: "#FFC107", to: { name: "owner.invoices", query: { tab: "payments" } }, count: extraStats.value.pending_payments_count },
    { key: "open_faults", icon: "fa-triangle-exclamation", color: "#D9534F", to: { name: "owner.faults" }, count: extraStats.value.open_faults_count },
    { key: "pending_technician_tasks", icon: "fa-screwdriver-wrench", color: "#8A6D1F", to: { name: "owner.technicians", query: { tab: "maintenance" } }, count: extraStats.value.pending_technician_tasks_count },
    { key: "pending_verification_generators", icon: "fa-hourglass-half", color: "#17A2B8", to: { name: "owner.generators" }, count: extraStats.value.pending_verification_generators_count },
  ]
    .map((p) => ({ ...p, label: t(`owner_dashboard.kpi.${p.key}`) }))
    .filter((p) => p.count > 0)
    .sort((a, b) => b.count - a.count);
});

/* ==========================================================================
 * ========================  قسم 2: نظرة سريعة (KPIs)  ======================
 * ========================================================================== */

/* ---------------- كروت KPI — نفس هوية kpi-card بالأدمن ---------------- */
const TONE_COLORS = {
  primary: ["#52733D", "#3E582E"],
  warning: ["#FFC107", "#a3760a"],
  danger: ["#D9534F", "#8A2E2A"],
  secondary: ["#8A6D1F", "#D4AF37"],
};
const KPI_CARDS = computed(() => {
  if (!extraStats.value) return [];
  const s = extraStats.value;
  return [
    // FIX: أُلغي استخدام v-count-up هون بعد رصده مباشرة — الدايركتيف
    // بيعرض "." بدل "0" لما تكون القيمة صفرًا، وصفر حالة طبيعية وشائعة جدًا
    // بهاي البطاقات تحديدًا (لا فواتير متأخرة، لا أعطال مفتوحة... إلخ هي
    // أخبار جيدة، مش استثناء نادر). عرض بيانات خاطئة لمالك المولد (نقطة
    // بدل الرقم الحقيقي) خطأ جوهري بالبيانات وليس مجرّد مسألة شكلية، فالقيم
    // الثابتة (بدون حركة) هي الخيار الصحيح هنا حتى لو غابت الحركة المرئية
    // المطابقة للأدمن.
    { icon: "fa-bolt", tone: "primary", label: t("owner_dashboard.kpi.active_generators"), value: `${s.active_generators_count} / ${s.generators_count}` },
    { icon: "fa-hourglass-half", tone: "warning", label: t("owner_dashboard.kpi.pending_verification_generators"), value: s.pending_verification_generators_count },
    { icon: "fa-users", tone: "primary", label: t("owner_dashboard.kpi.active_subscriptions"), value: s.active_subscriptions_count },
    { icon: "fa-clock", tone: "warning", label: t("owner_dashboard.kpi.pending_payments"), value: s.pending_payments_count },
    { icon: "fa-file-invoice-dollar", tone: "secondary", label: t("owner_dashboard.kpi.outstanding_invoices"), value: `${Number(s.outstanding_invoices_total_ils).toLocaleString()} ₪` },
    { icon: "fa-triangle-exclamation", tone: "danger", label: t("owner_dashboard.kpi.overdue_invoices"), value: s.overdue_invoices_count },
    { icon: "fa-sack-dollar", tone: "secondary", label: t("owner_dashboard.kpi.total_revenue"), value: `${Number(s.total_revenue_ils).toLocaleString()} ₪` },
    { icon: "fa-triangle-exclamation", tone: "danger", label: t("owner_dashboard.kpi.open_faults"), value: s.open_faults_count },
    { icon: "fa-screwdriver-wrench", tone: "warning", label: t("owner_dashboard.kpi.pending_technician_tasks"), value: s.pending_technician_tasks_count },
  ];
});

/* ---------------- كاردات مالية/تشغيلية إضافية (عمولة · شكاوى · عروض) ---------------- */
const openComplaintsCount = computed(
  () => complaints.value?.filter((c) => c.status !== "resolved").length ?? 0,
);
const activeOffers = computed(() => offers.value?.filter((o) => o.status === "active") ?? []);
const pendingReadings = computed(
  () => readings.value?.filter((r) => r.status === "pending_approval").slice(0, 5) ?? [],
);

/* ==========================================================================
 * ========================  قسم 3: رسوم بيانية وتحليلات  ===================
 * ========================================================================== */

/* ---------------- رسم Doughnut توزيع حالة المولدات ---------------- */
const doughnutOptions = { responsive: true, maintainAspectRatio: false, cutout: "68%", plugins: { legend: { display: false } } };

/* ---------------- مخطط أداء آخر 6 أشهر (قابل للتبديل بين عمودي/خطي) ---------------- */
const financialChartType = ref("bar");
const financialChartComponent = computed(() => (financialChartType.value === "line" ? Line : Bar));
const monthlyChartData = computed(() => ({
  labels: monthlyPerformance.value.labels,
  datasets: [
    {
      label: t("owner_dashboard.collected_revenue_label"),
      data: monthlyPerformance.value.paidAmounts,
      backgroundColor: financialChartType.value === "line" ? "rgba(82,115,61,0.15)" : "#52733D",
      borderColor: "#52733D",
      borderWidth: financialChartType.value === "line" ? 2.5 : 0,
      borderRadius: 6,
      maxBarThickness: 34,
      tension: 0.35,
      fill: financialChartType.value === "line",
      pointRadius: financialChartType.value === "line" ? 3 : 0,
      pointBackgroundColor: "#52733D",
    },
  ],
}));
const barOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
  scales: { y: { beginAtZero: true } },
};

/* ==========================================================================
 * ====================  قسم 4: الخريطة والبيانات التشغيلية  ================
 * ========================================================================== */

/* ---------------- خريطة مصغّرة لمولدات المالك ---------------- */
const { containerRef: mapContainerRef, init: initMap, setMarkers, fitToBounds } = useLeafletMap({ zoom: 10 });

function fillMap() {
  const map = initMap();
  if (!map) return;
  const bounds = setMarkers(mapPoints.value, {
    getId: (item) => item.id,
    getLatLng: (item) => [item.lat, item.lng],
    popup: (item) => `<b>${item.name}</b>`,
  });
  fitToBounds(bounds);
}

function fuelColor(pct) {
  if (pct === null || pct === undefined) return "#9a9d97";
  return pct <= 20 ? "#D9534F" : pct <= 45 ? "#FFC107" : "#28A745";
}

/* ---------------- نشاط أخير (ملخّص مُجمّع من عدة مصادر) ---------------- */
const recentActivity = computed(() => {
  const items = [];

  const latestComplaint = complaints.value?.[0];
  if (latestComplaint) {
    items.push({
      key: `complaint-${latestComplaint.id}`,
      icon: "fa-comment-dots",
      color: "#D9534F",
      text: t("owner_dashboard.activity_new_complaint", { subject: latestComplaint.subject }),
      to: { name: "owner.complaints" },
    });
  }

  const latestOffer = activeOffers.value?.[0];
  if (latestOffer) {
    items.push({
      key: `offer-${latestOffer.id}`,
      icon: "fa-tags",
      color: "#D4AF37",
      text: t("owner_dashboard.activity_active_offer", { title: latestOffer.title }),
      to: { name: "owner.offers" },
    });
  }

  const latestReading = pendingReadings.value?.[0];
  if (latestReading) {
    items.push({
      key: `reading-${latestReading.id}`,
      icon: "fa-gauge",
      color: "#17A2B8",
      text: t("owner_dashboard.activity_pending_reading", { name: latestReading.subscriber?.name ?? "-" }),
      to: { name: "owner.meter-readings" },
    });
  }

  const latestPayment = pendingPayments.value?.[0];
  if (latestPayment) {
    items.push({
      key: `payment-${latestPayment.id}`,
      icon: "fa-wallet",
      color: "#FFC107",
      text: t("owner_dashboard.activity_pending_payment", { id: latestPayment.id }),
      to: { name: "owner.invoices", query: { tab: "payments" } },
    });
  }

  return items;
});

/* ==========================================================================
 * ==========================  قسم 5: معالجات الأحداث  ======================
 * ========================================================================== */
async function handleQuickApproveReading(reading) {
  const confirmed = await confirm({
    title: t("owner_meter_readings.approve_confirm_title"),
    message: t("owner_meter_readings.approve_confirm_message", { name: reading.subscriber?.name ?? "-" }),
    confirmLabel: t("owner_meter_readings.approve_action"),
  });
  if (!confirmed) return;

  const ok = await approveReading(reading.id);
  if (ok) {
    toast.show({
      type: "success",
      title: t("owner_meter_readings.approved_toast_title"),
      message: t("owner_meter_readings.approved_toast_message"),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_meter_readings.approve_failed_title"),
      message: approveError.value ?? t("owner_meter_readings.approve_error"),
    });
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- ===== HEADER + الطقس ===== -->
    <section v-reveal class="grid lg:grid-cols-[1.5fr_1fr] gap-4">
      <div class="glass-card p-5 lg:p-6 relative overflow-hidden">
        <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
        <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>
        <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] mb-2">
          <span>{{ t("menu.dashboard") }}</span>
        </nav>
        <div class="relative flex flex-wrap items-start justify-between gap-3">
          <div>
            <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5">{{ t("owner_dashboard.welcome", { name: authStore.user?.name ?? "-" }) }}</h1>
            <p class="text-[13px] text-[#6B6B6B] dark:text-[#aeb1ab]">{{ t("owner_dashboard.summary") }}</p>
          </div>
        </div>
      </div>

      <!-- الطقس -->
      <GazaWeatherCard />
    </section>

    <div v-if="error" class="text-[12.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-xl p-4">
      {{ error }}
    </div>

    <!-- ===== تقييمات المشتركين ===== -->
    <section v-if="!isLoadingOwnerRatings && ownerRatingsCount > 0" v-reveal class="glass-card p-5">
      <div class="flex items-center justify-between mb-3.5">
        <h2 class="text-[13.5px] font-bold flex items-center gap-2">
          <Star class="text-[#D4AF37]" aria-hidden="true" />
          {{ t("owner_dashboard.ratings_title") }}
        </h2>
        <div class="flex items-center gap-1.5 shrink-0">
          <span class="font-extrabold text-[15px]">{{ Number(ownerAverageRating).toFixed(1) }}</span>
          <Star class="text-[#D4AF37] text-[13px]" fill="currentColor" aria-hidden="true" />
          <span class="text-[11px] text-[#9a9d97]">({{ t("owner_dashboard.ratings_count", { count: ownerRatingsCount }) }})</span>
        </div>
      </div>
      <div class="space-y-2.5">
        <div v-for="r in ownerRatings.slice(0, 5)" :key="r.id" class="p-3 rounded-xl border border-[#eee8da] dark:border-white/10">
          <div class="flex items-center justify-between gap-2 mb-1">
            <span class="text-[12px] font-bold">{{ r.rated_by?.name ?? "-" }}</span>
            <div class="flex items-center gap-0.5 shrink-0">
              <Star v-for="n in 5" :key="n" class="text-[11px]" :class="n <= r.rating ? 'text-[#D4AF37]' : 'text-[#e7e2d6] dark:text-white/15'" :fill="n <= r.rating ? 'currentColor' : 'none'" aria-hidden="true" />
            </div>
          </div>
          <p v-if="r.comment" class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ r.comment }}</p>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
         =====================  قسم 1: ما يحتاج انتباهك الآن  =====================
         ========================================================================== -->

    <!-- ===== أولويات اليوم ===== -->
    <section v-if="TODAY_PRIORITIES.length" v-reveal class="glass-card p-5">
      <h2 class="text-[13.5px] font-bold mb-3.5 flex items-center gap-2">
        <Zap class="text-[#D4AF37]" aria-hidden="true" />
        {{ t("owner_dashboard.today_priorities_title") }}
      </h2>
      <div class="flex gap-3 overflow-x-auto pb-1 -mx-1 px-1">
        <RouterLink
          v-for="p in TODAY_PRIORITIES" :key="p.key" :to="p.to"
          class="dash-priority-card shrink-0 flex items-center gap-3 min-w-[15rem] p-3.5 rounded-xl border border-[#eee8da] dark:border-white/10 bg-[#f4efe5]/30 dark:bg-white/[0.03] hover:!border-[#D4AF37]/50"
        >
          <span
            class="w-10 h-10 rounded-xl flex items-center justify-center text-white shrink-0"
            :style="{ background: `linear-gradient(135deg, ${p.color}, ${p.color}cc)` }"
          >
            <AppIcon :name="p.icon" />
          </span>
          <div class="min-w-0">
            <p class="text-[12px] font-bold truncate">{{ p.label }}</p>
            <p class="text-[11px] text-[#9a9d97]">{{ p.count }}</p>
          </div>
        </RouterLink>
      </div>
    </section>

    <!-- ===== أوامر شغل بانتظار المراجعة ===== -->
    <section v-if="!isLoadingTasks && needsReviewTasks.length > 0" v-reveal class="glass-card p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-[13.5px] font-bold flex items-center gap-2">
          <ClipboardList class="text-[#8A6D1F]" aria-hidden="true" />
          {{ t("owner_technician_tasks.needs_review_title", { count: needsReviewTasks.length }) }}
        </h2>
        <RouterLink :to="{ name: 'owner.technicians', query: { tab: 'maintenance' } }" class="text-[11px] font-bold text-[#8A6D1F] hover:underline">
          {{ t("owner_dashboard.view_all") }}
        </RouterLink>
      </div>
      <div class="space-y-2">
        <RouterLink
          v-for="task in needsReviewTasks.slice(0, 4)" :key="task.id"
          :to="{ name: 'owner.technicians', query: { tab: 'maintenance' } }"
          class="flex items-center justify-between gap-2 p-3 rounded-xl border border-[#eee8da] dark:border-white/10 bg-[#f4efe5]/40 dark:bg-white/[0.03] hover:!border-[#D4AF37]/50 transition-colors"
        >
          <div class="min-w-0">
            <p class="text-[12px] font-bold truncate">{{ task.type_label }} — {{ task.generator_name }}</p>
            <p class="text-[10.5px] text-[#9a9d97]">{{ t("owner_technician_tasks.technician_label") }}: {{ task.technician?.name ?? "-" }}</p>
          </div>
          <span class="status-chip chip-warning shrink-0">{{ task.status_label }}</span>
        </RouterLink>
      </div>
    </section>

    <!-- ==========================================================================
         ========================  قسم 2: نظرة سريعة (KPIs)  ======================
         ========================================================================== -->

    <!-- ===== KPI CARDS موحّدة (9 مؤشر عام + 3 مالية/تشغيلية = 12 بطاقة) — نفس هوية بطاقات لوحة الأدمن (باستثناء العدّاد المتحرّك) ===== -->
    <!-- FIX: كانت مقسومة سابقًا على قسمين منفصلين بشبكتين مختلفتي الأعمدة
         ("نظرة سريعة" 9 بطاقات، و"الملخص المالي السريع" 3 بطاقات بشبكة
         منفصلة) — دُمجتا هون بشبكة واحدة، وطابقنا التخطيط والتفاصيل الداخلية
         لكل بطاقة (رأس الأيقونة، الخط الزخرفي bar-track أسفل البطاقة) حرفيًا
         بنفس بطاقات KPI المستخدمة بلوحة تحكم الأدمن (DashboardView.vue)، بما
         فيها عدد الأعمدة الأقصى (4 عند xl فأعلى) بدل 6. القيم هون نصّية
         ثابتة (بدون v-count-up) عن قصد — الدايركتيف بيعرض "." بدل "0"، وصفر
         حالة شائعة جدًا بهاي البطاقات، فالثبات هون قرار صحّة بيانات وليس
         تقصيرًا بالتطابق البصري. -->
    <section v-reveal>
      <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
        <h2 class="text-[15px] font-extrabold">{{ t("owner_dashboard.quick_overview") }}</h2>
      </div>

      <div v-if="extraStatsError" class="text-[12.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-xl p-4 mb-3.5">
        {{ extraStatsError }}
      </div>

      <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3.5">
        <!-- 9 بطاقات المؤشرات العامة (تعتمد على isLoadingExtraStats) -->
        <template v-if="isLoadingExtraStats">
          <div v-for="i in 9" :key="i" class="h-28 rounded-2xl thumb-loading"></div>
        </template>
        <template v-else>
          <div
            v-for="c in KPI_CARDS" :key="c.label" class="kpi-card glass-card hoverable"
            :style="{ '--kpi-color': TONE_COLORS[c.tone][0], '--kpi-color2': TONE_COLORS[c.tone][1] }"
          >
            <div class="flex items-start justify-between mb-3">
              <div class="kpi-icon"><AppIcon :name="c.icon" /></div>
            </div>
            <div class="text-xl font-extrabold truncate text-right" dir="ltr">{{ c.value }}</div>
            <div class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-2.5 line-clamp-2 text-right">{{ c.label }}</div>
            <div class="bar-track"><div class="bar-fill" style="width:100%; opacity:.35" :style="{ background: TONE_COLORS[c.tone][0] }"></div></div>
          </div>
        </template>

        <!-- 3 بطاقات مالية/تشغيلية إضافية (عمولة · شكاوى · عروض) — نفس الشبكة والهوية -->
        <RouterLink :to="{ name: 'owner.reports' }" class="kpi-card glass-card block" style="--kpi-color:#8A6D1F; --kpi-color2:#D4AF37;">
          <div class="flex items-start justify-between mb-3">
            <span class="kpi-icon"><HandCoins aria-hidden="true" /></span>
          </div>
          <p v-if="isLoadingCommissions" class="h-6 w-20 rounded thumb-loading ml-auto"></p>
          <p v-else class="text-xl font-extrabold text-right" dir="ltr">{{ totalEarned.toFixed(2) }} ₪</p>
          <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-2.5 text-right">{{ t("owner_dashboard.commission_due_kpi") }}</p>
          <div class="bar-track"><div class="bar-fill" style="width:100%; opacity:.35; background:#8A6D1F"></div></div>
        </RouterLink>

        <RouterLink :to="{ name: 'owner.complaints' }" class="kpi-card glass-card block" style="--kpi-color:#D9534F; --kpi-color2:#8A2F2A;">
          <div class="flex items-start justify-between mb-3">
            <span class="kpi-icon"><MessageCircleMore aria-hidden="true" /></span>
          </div>
          <p v-if="isLoadingComplaints" class="h-6 w-12 rounded thumb-loading ml-auto"></p>
          <p v-else class="text-xl font-extrabold text-right">{{ openComplaintsCount }}</p>
          <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-2.5 text-right">{{ t("owner_dashboard.open_complaints_kpi") }}</p>
          <div class="bar-track"><div class="bar-fill" style="width:100%; opacity:.35; background:#D9534F"></div></div>
        </RouterLink>

        <RouterLink :to="{ name: 'owner.offers' }" class="kpi-card glass-card block" style="--kpi-color:#52733D; --kpi-color2:#3E582E;">
          <div class="flex items-start justify-between mb-3">
            <span class="kpi-icon"><Tags aria-hidden="true" /></span>
          </div>
          <p v-if="isLoadingOffers" class="h-6 w-12 rounded thumb-loading ml-auto"></p>
          <p v-else class="text-xl font-extrabold text-right">{{ activeOffers.length }}</p>
          <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-2.5 text-right">{{ t("owner_dashboard.active_offers_kpi") }}</p>
          <div class="bar-track"><div class="bar-fill" style="width:100%; opacity:.35; background:#52733D"></div></div>
        </RouterLink>
      </div>
    </section>

    <!-- ==========================================================================
         ========================  قسم 3: رسوم بيانية وتحليلات  ===================
         ========================================================================== -->

    <!-- ===== توزيع الحالة + الأداء الشهري ===== -->
    <section class="grid md:grid-cols-2 xl:grid-cols-2 gap-4 items-stretch">
      <div v-reveal class="glass-card p-4 flex flex-col">
        <h3 class="text-[13.5px] font-bold mb-3">{{ t("generators_management_page.status_breakdown_title") }}</h3>
        <div v-if="isLoadingAllGenerators" class="flex-1 min-h-[10rem] thumb-loading rounded-lg"></div>
        <template v-else-if="allGenerators.length === 0">
          <div class="flex-1 min-h-[10rem] flex items-center justify-center text-[11px] text-[#9a9d97]">
            {{ t("owner_generators.no_results") }}
          </div>
        </template>
        <template v-else>
          <div class="flex-1 min-h-[10rem]"><Doughnut :data="statusChartData" :options="doughnutOptions" /></div>
          <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5 mt-3 pt-3 border-t border-[#eee8da] dark:border-white/10">
            <span v-for="item in statusLegendItems" :key="item.label" class="flex items-center gap-1.5 text-[10.5px] font-semibold text-[#6B6B6B] dark:text-[#a8aaa5]">
              <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ background: item.color }"></span>
              {{ item.label }} <b class="text-[#3a3a38] dark:text-[#eef0ec]">{{ item.value }}</b>
              <span class="text-[#9a9d97]">({{ item.pct }}%)</span>
            </span>
          </div>
        </template>
      </div>

      <div v-reveal class="glass-card p-4 flex flex-col">
        <div class="flex items-center justify-between mb-3 gap-2 flex-wrap">
          <h3 class="text-[13.5px] font-bold">{{ t("owner_dashboard.financial_performance_title") }}</h3>
          <div class="flex items-center gap-2">
            <span v-if="monthlyPerformance.isEstimate" class="text-[10px] text-[#9a9d97]">{{ t("owner_dashboard.estimate_label") }}</span>
            <div class="flex items-center rounded-full border border-[#eee8da] dark:border-white/10 p-0.5 gap-0.5">
              <button
                type="button" @click="financialChartType = 'bar'"
                class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] transition-colors"
                :class="financialChartType === 'bar' ? 'bg-[#52733D] text-white' : 'text-[#9a9d97] hover:bg-[#f4efe5]/70 dark:hover:bg-white/5'"
                :title="t('owner_dashboard.chart_type_bar')"
              >
                <ChartColumn aria-hidden="true" />
              </button>
              <button
                type="button" @click="financialChartType = 'line'"
                class="w-7 h-7 rounded-full flex items-center justify-center text-[11px] transition-colors"
                :class="financialChartType === 'line' ? 'bg-[#52733D] text-white' : 'text-[#9a9d97] hover:bg-[#f4efe5]/70 dark:hover:bg-white/5'"
                :title="t('owner_dashboard.chart_type_line')"
              >
                <ChartLine aria-hidden="true" />
              </button>
            </div>
          </div>
        </div>
        <div v-if="isLoadingMonthlyPerformance" class="flex-1 min-h-[10rem] thumb-loading rounded-lg"></div>
        <div v-else class="flex-1 min-h-[10rem]">
          <component :is="financialChartComponent" :data="monthlyChartData" :options="barOptions" />
        </div>
      </div>
    </section>

    <!-- ==========================================================================
         ====================  قسم 4: الخريطة والبيانات التشغيلية  ================
         ========================================================================== -->

    <!-- ===== الخريطة + أضعف وقود ===== -->
    <section class="grid md:grid-cols-2 gap-4 items-stretch">
      <div v-reveal class="glass-card p-4 flex flex-col">
        <h3 class="text-[13.5px] font-bold mb-3">{{ t("generators_management_page.my_generators_map_title") }}</h3>
        <div ref="mapContainerRef" class="flex-1 min-h-[16rem] rounded-xl overflow-hidden"></div>
        <p v-if="!isLoadingMapPoints && mapPoints.length === 0" class="text-[11px] text-[#9a9d97] text-center mt-2">
          {{ t("generators_map.no_coordinates") }}
        </p>
      </div>

      <div v-reveal class="glass-card p-4 flex flex-col">
        <h3 class="text-[13.5px] font-bold mb-3">{{ t("owner_dashboard.lowest_fuel_title") }}</h3>
        <div v-if="isLoadingAllGenerators" class="flex-1 space-y-2.5">
          <div v-for="i in 4" :key="i" class="h-8 rounded-lg thumb-loading"></div>
        </div>
        <div v-else-if="lowestFuelGenerators.length === 0" class="flex-1 flex items-center justify-center text-[11px] text-[#9a9d97]">
          {{ t("generators_management_page.no_fuel_data") }}
        </div>
        <div v-else class="flex-1 space-y-2.5">
          <RouterLink
            v-for="g in lowestFuelGenerators" :key="g.id" :to="{ name: 'owner.generators' }"
            class="flex items-center gap-2.5 w-full text-start hover:bg-[#f4efe5]/50 dark:hover:bg-white/5 rounded-lg p-1.5 -m-1 transition-colors"
          >
            <span class="text-[11px] font-bold truncate flex-1">{{ g.name }}</span>
            <div class="bar-track w-16"><div class="bar-fill" :style="{ width: g.fuel_percentage + '%', background: fuelColor(g.fuel_percentage) }"></div></div>
            <span class="text-[10.5px] font-bold w-9 text-end" :style="{ color: fuelColor(g.fuel_percentage) }">{{ g.fuel_percentage }}%</span>
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- ===== المولدات + الدفعات ===== -->
    <section v-reveal class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="glass-card p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-[13.5px] font-bold">{{ t("owner_dashboard.my_generators") }}</h2>
          <RouterLink :to="{ name: 'owner.generators' }" class="text-[11px] font-bold text-[#8A6D1F] hover:underline">
            {{ t("owner_dashboard.view_all") }}
          </RouterLink>
        </div>

        <div v-if="isLoading" class="space-y-2">
          <div v-for="i in 3" :key="i" class="h-14 rounded-xl thumb-loading"></div>
        </div>
        <div v-else-if="!generators.length" class="text-center text-[12.5px] text-[#9a9d97] py-8">
          {{ t("owner_dashboard.no_generators") }}
        </div>
        <div v-else class="space-y-2">
          <GeneratorSummaryCard
            v-for="generator in generators.slice(0, 5)"
            :key="generator.id"
            :generator="generator"
          />
        </div>
      </div>

      <div class="glass-card p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-[13.5px] font-bold">{{ t("owner_dashboard.pending_payments_title") }}</h2>
          <RouterLink :to="{ name: 'owner.invoices', query: { tab: 'payments' } }" class="text-[11px] font-bold text-[#8A6D1F] hover:underline">
            {{ t("owner_dashboard.view_all") }}
          </RouterLink>
        </div>

        <div v-if="isLoading" class="space-y-2">
          <div v-for="i in 3" :key="i" class="h-14 rounded-xl thumb-loading"></div>
        </div>
        <div v-else-if="!pendingPayments.length" class="text-center text-[12.5px] text-[#9a9d97] py-8">
          {{ t("owner_dashboard.no_pending_payments") }}
        </div>
        <div v-else class="space-y-2">
          <RouterLink
            v-for="payment in pendingPayments.slice(0, 5)"
            :key="payment.id"
            :to="{ name: 'owner.invoices', query: { tab: 'payments', highlight: payment.id } }"
            class="flex items-center justify-between p-3 rounded-xl border border-[#eee8da] dark:border-white/10 bg-[#f4efe5]/40 dark:bg-white/[0.03] hover:!border-[#D4AF37]/50 hover:bg-[#f4efe5]/70 dark:hover:bg-white/5 transition-colors duration-200"
          >
            <span class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t("owner_dashboard.payment_number", { id: payment.id }) }}</span>
            <span class="font-data text-[12.5px] font-bold" :dir="locale === 'ar' ? 'rtl' : 'ltr'">
              {{ Number(payment.amount_ils ?? payment.amount).toLocaleString(locale === "ar" ? "ar-EG" : "en-US") }} ₪
            </span>
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- ===== قراءات عدادات بانتظار الاعتماد (اعتماد سريع) + نشاط أخير ===== -->
    <section v-reveal class="grid grid-cols-1 lg:grid-cols-2 gap-4">
      <div class="glass-card p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-[13.5px] font-bold flex items-center gap-2">
            <Gauge class="text-[#17A2B8]" aria-hidden="true" />
            {{ t("owner_dashboard.pending_readings_title") }}
          </h2>
          <RouterLink :to="{ name: 'owner.meter-readings' }" class="text-[11px] font-bold text-[#8A6D1F] hover:underline">
            {{ t("owner_dashboard.view_all") }}
          </RouterLink>
        </div>

        <div v-if="isLoadingReadings" class="space-y-2">
          <div v-for="i in 3" :key="i" class="h-14 rounded-xl thumb-loading"></div>
        </div>
        <div v-else-if="!pendingReadings.length" class="text-center text-[12.5px] text-[#9a9d97] py-8">
          {{ t("owner_dashboard.no_pending_readings") }}
        </div>
        <div v-else class="space-y-2">
          <div
            v-for="reading in pendingReadings" :key="reading.id"
            class="flex items-center justify-between gap-2 p-3 rounded-xl border border-[#eee8da] dark:border-white/10 bg-[#f4efe5]/40 dark:bg-white/[0.03]"
            :class="{ 'opacity-50 pointer-events-none': approvingId === reading.id }"
          >
            <div class="min-w-0">
              <p class="text-[12px] font-bold truncate">{{ reading.subscriber?.name ?? "-" }}</p>
              <p class="text-[10.5px] text-[#9a9d97]" dir="ltr">{{ reading.reading_date }} · {{ reading.consumed_kw }} kW</p>
            </div>
            <button
              type="button" @click="handleQuickApproveReading(reading)"
              class="action-btn action-btn--approve shrink-0" :title="t('owner_meter_readings.approve_action')"
            >
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="approvingId === reading.id" /><Check aria-hidden="true" v-else />
            </button>
          </div>
        </div>
      </div>

      <div class="glass-card p-5">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-[13.5px] font-bold">{{ t("owner_dashboard.recent_activity_title") }}</h2>
        </div>

        <div v-if="!recentActivity.length" class="text-center text-[12.5px] text-[#9a9d97] py-8">
          {{ t("owner_dashboard.no_recent_activity") }}
        </div>
        <div v-else>
          <RouterLink
            v-for="item in recentActivity" :key="item.key" :to="item.to"
            class="timeline-item block hover:opacity-80 transition-opacity"
            :style="{ '--dot-color': item.color }"
          >
            <p class="text-[12px] font-bold flex items-center gap-2">
              <AppIcon :name="item.icon" class="text-[10px]" :style="{ color: item.color }" />
              {{ item.text }}
            </p>
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- ==========================================================================
         ========================  قسم 5: توقعات الأعطال  =======================
         ========================================================================== -->
    <section v-reveal class="glass-card p-5">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-[13.5px] font-bold">{{ t("owner_dashboard.fault_predictions_title") }}</h2>
      </div>
      <FaultPredictionsPanel />
    </section>
  </div>
</template>