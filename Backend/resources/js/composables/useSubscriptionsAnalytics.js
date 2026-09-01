import { ref, computed, watch, onBeforeUnmount, nextTick } from "vue";
import { useI18n } from "vue-i18n";
import Chart from "chart.js/auto";

/**
 * FRONT-004a (slice 1): derived analytics, label/formatting helpers, and
 * Chart.js rendering for the admin Subscriptions tab — extracted out of
 * SubscribersView.vue's <script setup>, which owned all of this inline
 * alongside unrelated concerns (God-component breakdown).
 *
 * Takes the reactive state it needs (already owned by useAdminSubscriptionsData)
 * as arguments rather than importing/fetching anything itself, so this
 * composable has exactly one responsibility: turn `subscriptions`/`monthlyGrowth`
 * into the KPIs, alerts, and four Chart.js canvases the view renders.
 *
 * @param {import('vue').Ref<Array>} subscriptions
 * @param {import('vue').Ref<{total:number}>} subscriptionPagination
 * @param {import('vue').Ref<{labels:string[],newCounts:number[],cancelledCounts:number[],isEstimate:boolean}>} monthlyGrowth
 * @param {() => void} computeMonthlyGrowthFromPage
 */
export function useSubscriptionsAnalytics({ subscriptions, subscriptionPagination, monthlyGrowth, computeMonthlyGrowthFromPage }) {
  const { t, locale } = useI18n();

  Chart.defaults.font.family = "Cairo, sans-serif";
  Chart.defaults.color = "#6B6B6B";

  /* ---------------- تنسيق الحالة (بنفس منطق الألوان بباقي الصفحات) ----------------
   * ملاحظة: "cancelled" ما إلها chip-* رسمي بنظام التصميم (بس chip-warning/success/info/danger
   * موجودين ومستخدمين بصفحات تانية زي قراءات العدادات وأصحاب المولدات)، فمنستخدملها لون مخصّص
   * عبر style بدل افتراض اسم كلاس مش موجود.
   */
  const STATUS_META = {
    pending: { key: "subscriptions_page.status_pending", chip: "chip-warning", color: "#FFC107" },
    active: { key: "subscriptions_page.status_active", chip: "chip-success", color: "#28A745" },
    suspended: { key: "subscriptions_page.status_suspended", chip: "chip-info", color: "#17A2B8" },
    cancelled: { key: "subscriptions_page.status_cancelled", chip: null, color: "#9a9d97" },
    rejected: { key: "subscriptions_page.status_rejected", chip: "chip-danger", color: "#D9534F" },
  };
  function subscriptionStatusLabel(status) {
    const m = STATUS_META[status];
    if (!m) return status;
    return t(m.key);
  }
  function statusChip(status) {
    return STATUS_META[status]?.chip ?? null;
  }
  function statusChipStyle(status) {
    const m = STATUS_META[status];
    if (!m || m.chip) return {};
    return { color: m.color, background: m.color + "1A" };
  }

  /* ---------------- تنسيق نوع الخطة (نفس منطق نسخة الـ HTML) ---------------- */
  const PLAN_META = {
    full: { key: "subscriptions_page.plan_full", cls: "plan-full", icon: "fa-circle-check" },
    hours: { key: "subscriptions_page.plan_hours", cls: "plan-hours", icon: "fa-clock" },
  };
  function planMeta(plan) {
    return PLAN_META[plan] ?? null;
  }
  function planLabel(plan) {
    const m = PLAN_META[plan];
    if (!m) return "-";
    return t(m.key);
  }

  /* ==================================================================
   * تحليلات لوحة الاشتراكات (رسوم بيانية / تنبيهات / أقرب للانتهاء)
   * ملاحظة نطاق البيانات: كل التحليلات التالية محسوبة من subscriptions.value، يعني من
   * اشتراكات الصفحة الحالية فقط بالجدول (بنفس قيد بطاقات الـ KPI أعلاه) - لأنه ما في
   * endpoint إحصائيات عام للاشتراكات (زي المذكور بالتعليق فوق نظام حالة الهيدر). لو صار
   * عندنا endpoint مماثل لاحقًا (مثال: subscriptionService.stats() / monthlyStats() /
   * activityLog())، هاي الدوال بتحاول تستخدمه أولًا وبترجع تلقائيًا للحساب المحلي لو مش موجود.
   * ================================================================== */

  /* ---------------- فرق الأيام عن اليوم (لتصنيف "على وشك الانتهاء" و"منتهي") ---------------- */
  function subscriptionDaysUntil(dateStr) {
    if (!dateStr) return null;
    const end = new Date(dateStr);
    if (Number.isNaN(end.getTime())) return null;
    const now = new Date();
    end.setHours(0, 0, 0, 0);
    now.setHours(0, 0, 0, 0);
    return Math.round((end - now) / 86400000);
  }

  /* ---------------- تصنيف مشتق لأغراض العرض التحليلي فقط (نشط / على وشك / منتهي / موقوف) ---------------- */
  const EXPIRING_SOON_DAYS = 14;
  function derivedStatusKey(sub) {
    if (sub.status === "suspended") return "suspended";
    if (sub.status === "cancelled" || sub.status === "rejected") return "expired";
    const d = subscriptionDaysUntil(sub.end_date);
    if (d !== null && d < 0) return "expired";
    if (d !== null && d <= EXPIRING_SOON_DAYS) return "expiring";
    return "active";
  }
  const DERIVED_STATUS_META = {
    active: { key: "users_page.status_active", color: "#28A745", chip: "chip-success" },
    expiring: { key: "subscriptions_page.derived_expiring", color: "#FFC107", chip: "chip-warning" },
    expired: { key: "subscriptions_page.derived_expired", color: "#D9534F", chip: "chip-danger" },
    suspended: { key: "users_page.status_suspended", color: "#17A2B8", chip: "chip-info" },
  };
  function derivedStatusLabel(key) {
    return t(DERIVED_STATUS_META[key].key);
  }
  const derivedStatusCounts = computed(() => {
    const counts = { active: 0, expiring: 0, expired: 0, suspended: 0 };
    subscriptions.value.forEach((s) => { counts[derivedStatusKey(s)]++; });
    return counts;
  });
  const statusDistribution = computed(() => {
    const total = subscriptions.value.length || 1;
    return Object.entries(DERIVED_STATUS_META).map(([key, meta]) => ({
      key,
      label: t(meta.key),
      color: meta.color,
      count: derivedStatusCounts.value[key],
      pct: Math.round((derivedStatusCounts.value[key] / total) * 100),
    }));
  });

  /* ---------------- فلتر سريع من الرسم البياني/الأسطورة (فوق فلتر الحالة الحقيقي بالجدول) ---------------- */
  const derivedFilter = ref(null); // 'active' | 'expiring' | 'expired' | 'suspended' | null
  function applyDerivedFilter(key) {
    derivedFilter.value = derivedFilter.value === key ? null : key;
  }

  /* ---------------- توزيع نوع الخطة ---------------- */
  const planCounts = computed(() => {
    const counts = { full: 0, hours: 0 };
    subscriptions.value.forEach((s) => { if (counts[s.plan] !== undefined) counts[s.plan]++; });
    return counts;
  });

  /* ---------------- الاشتراكات المنتهية خلال الأسابيع القادمة (تجميع بحسب المدى الزمني) ---------------- */
  const expiringBuckets = computed(() => {
    const buckets = [0, 0, 0, 0, 0];
    subscriptions.value.forEach((s) => {
      const d = subscriptionDaysUntil(s.end_date);
      if (d === null || d < 0) return;
      if (d <= 7) buckets[0]++;
      else if (d <= 14) buckets[1]++;
      else if (d <= 21) buckets[2]++;
      else if (d <= 28) buckets[3]++;
      else if (d <= 60) buckets[4]++;
    });
    return buckets;
  });
  const expiringBucketLabels = computed(() => [
    t("subscriptions_page.expiring_bucket_this_week"),
    t("subscriptions_page.expiring_bucket_next_week"),
    t("subscriptions_page.expiring_bucket_2weeks"),
    t("subscriptions_page.expiring_bucket_3weeks"),
    t("subscriptions_page.expiring_bucket_2months"),
  ]);

  /* ---------------- KPI Cards (من الصفحة الحالية) ---------------- */
  const countOnPage = (status) => subscriptions.value.filter((s) => s.status === status).length;

  /* ---------------- تنبيهات متعلقة بالاشتراكات (مشتقة من البيانات) ---------------- */
  const subsAlerts = computed(() => {
    const c = derivedStatusCounts.value;
    const pendingCount = countOnPage("pending");
    const all = [
      {
        icon: "fa-hourglass-half",
        title: t("subscriptions_page.alert_pending_title"),
        desc: t("subscriptions_page.alert_pending_desc", { count: pendingCount }),
        color: "#FFC107",
        chip: "chip-warning",
        tag: t("subscriptions_page.tag_alert"),
        show: pendingCount > 0,
      },
      {
        icon: "fa-calendar-xmark",
        title: t("subscriptions_page.alert_expiring_title"),
        desc: t("subscriptions_page.alert_expiring_desc", { count: c.expiring, days: EXPIRING_SOON_DAYS }),
        color: "#FFC107",
        chip: "chip-warning",
        tag: t("subscriptions_page.tag_alert"),
        show: c.expiring > 0,
      },
      {
        icon: "fa-ban",
        title: t("subscriptions_page.alert_suspended_title"),
        desc: t("subscriptions_page.alert_suspended_desc", { count: c.suspended }),
        color: "#17A2B8",
        chip: "chip-info",
        tag: t("subscriptions_page.tag_info"),
        show: c.suspended > 0,
      },
      {
        icon: "fa-calendar-xmark",
        title: t("subscriptions_page.alert_expired_title"),
        desc: t("subscriptions_page.alert_expired_desc", { count: c.expired }),
        color: "#D9534F",
        chip: "chip-danger",
        tag: t("owners_page.alert_tag_critical"),
        show: c.expired > 0,
      },
      {
        icon: "fa-circle-check",
        title: t("subscriptions_page.alert_active_title"),
        desc: t("subscriptions_page.alert_active_desc", { count: c.active }),
        color: "#28A745",
        chip: "chip-success",
        tag: t("subscriptions_page.tag_good"),
        show: true,
      },
    ];
    return all.filter((a) => a.show);
  });

  function subscriptionTimeAgo(dateStr) {
    const diffMs = Date.now() - new Date(dateStr).getTime();
    const mins = Math.max(0, Math.floor(diffMs / 60000));
    if (mins < 1) return t("subscriptions_page.time_just_now");
    if (mins < 60) return t("subscriptions_page.time_mins_ago_short", { mins });
    const hours = Math.floor(mins / 60);
    if (hours < 24) return t("subscribers_page.time_hours_ago", { hours });
    const days = Math.floor(hours / 24);
    return t("subscribers_page.time_days_ago", { days });
  }

  /* ---------------- الأقرب للانتهاء ---------------- */
  const topExpiring = computed(() =>
    subscriptions.value
      .filter((s) => s.status !== "suspended" && s.status !== "cancelled" && s.status !== "rejected")
      .slice()
      .sort((a, b) => new Date(a.end_date) - new Date(b.end_date))
      .slice(0, 5)
  );
  function expiringLabel(sub) {
    const d = subscriptionDaysUntil(sub.end_date);
    if (d === null) return "-";
    if (d < 0) return t("subscriptions_page.expired_marker");
    return t("subscriptions_page.days_remaining", { d });
  }
  function expiringColor(sub) {
    const d = subscriptionDaysUntil(sub.end_date);
    if (d === null) return "#9a9d97";
    if (d < 0) return "#D9534F";
    if (d <= EXPIRING_SOON_DAYS) return "#FFC107";
    return "#28A745";
  }

  const SUBSCRIPTION_STATUS_PILLS = computed(() => [
    { value: "all", label: t("common.all") },
    { value: "pending", label: subscriptionStatusLabel("pending") },
    { value: "active", label: subscriptionStatusLabel("active") },
    { value: "suspended", label: subscriptionStatusLabel("suspended") },
    { value: "cancelled", label: subscriptionStatusLabel("cancelled") },
    { value: "rejected", label: subscriptionStatusLabel("rejected") },
  ]);

  const SUBSCRIPTION_KPI_CARDS = computed(() => [
    { icon: "fa-file-contract", label: t("subscriptions_page.total_subscriptions"), value: subscriptionPagination.value.total, tone: "primary" },
    { icon: "fa-circle-check", label: subscriptionStatusLabel("active") + t("common.this_page_suffix"), value: countOnPage("active"), tone: "success" },
    { icon: "fa-hourglass-half", label: subscriptionStatusLabel("pending") + t("common.this_page_suffix"), value: countOnPage("pending"), tone: "warning" },
    { icon: "fa-circle-pause", label: subscriptionStatusLabel("suspended") + t("common.this_page_suffix"), value: countOnPage("suspended"), tone: "info" },
  ]);

  /* ---------------- مؤشر حالة النظام بالهيدر (نفس أسلوب صفحة إدارة المولدات) ---------------- */
  const systemStatusInfo = computed(() => {
    const pendingCount = countOnPage("pending");
    if (pendingCount === 0) {
      return {
        label: t("subscriptions_page.no_requests_awaiting_review"),
        color: "#28A745",
      };
    }
    return {
      label: t("subscriptions_page.requests_awaiting_review", { count: pendingCount }),
      color: "#FFC107",
    };
  });

  /* ==================================================================
   * رسم بيانات لوحة التحليلات (Chart.js) - نفس الرسوم البيانية الأربعة بنسخة الـ HTML
   * (نمو الاشتراكات الشهري / توزيع الحالات / حسب نوع الخطة / المنتهية خلال الأسابيع القادمة)
   * ================================================================== */
  const growthCanvas = ref(null);
  const statusCanvas = ref(null);
  const planCanvas = ref(null);
  const expiringCanvas = ref(null);
  let growthChart = null;
  let statusChart = null;
  let planChart = null;
  let expiringChart = null;

  function renderGrowthChart() {
    if (!growthCanvas.value) return;
    if (growthChart) growthChart.destroy();
    growthChart = new Chart(growthCanvas.value, {
      type: "line",
      data: {
        labels: monthlyGrowth.value.labels,
        datasets: [
          {
            label: t("subscriptions_page.chart_new_contracts"),
            data: monthlyGrowth.value.newCounts,
            borderColor: "#8A6D1F",
            backgroundColor: "rgba(138,109,31,0.12)",
            fill: true,
            tension: 0.4,
          },
          {
            label: t("subscriptions_page.chart_cancelled_contracts"),
            data: monthlyGrowth.value.cancelledCounts,
            borderColor: "#D9534F",
            backgroundColor: "rgba(217,83,79,0.08)",
            fill: true,
            tension: 0.4,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: "bottom", labels: { boxWidth: 10, font: { size: 11 } } } },
        scales: { x: { grid: { display: false } }, y: { grid: { color: "rgba(82,115,61,0.08)" } } },
      },
    });
  }

  function renderStatusChart() {
    if (!statusCanvas.value) return;
    if (statusChart) statusChart.destroy();
    const dist = statusDistribution.value;
    statusChart = new Chart(statusCanvas.value, {
      type: "doughnut",
      data: {
        labels: dist.map((d) => d.label),
        datasets: [{ data: dist.map((d) => d.count), backgroundColor: dist.map((d) => d.color), borderWidth: 0 }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "70%",
        plugins: { legend: { display: false } },
        onClick: (evt, elements) => {
          if (!elements.length) return;
          applyDerivedFilter(dist[elements[0].index].key);
        },
      },
    });
  }

  function renderPlanChart() {
    if (!planCanvas.value) return;
    if (planChart) planChart.destroy();
    planChart = new Chart(planCanvas.value, {
      type: "bar",
      data: {
        labels: [planLabel("full"), planLabel("hours")],
        datasets: [
          {
            label: t("subscriptions_page.chart_subscriptions_label"),
            data: [planCounts.value.full, planCounts.value.hours],
            backgroundColor: ["#52733D", "#17A2B8"],
            borderRadius: 6,
          },
        ],
      },
      options: {
        indexAxis: "y",
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { grid: { color: "rgba(82,115,61,0.08)" } }, y: { grid: { display: false } } },
      },
    });
  }

  function renderExpiringChart() {
    if (!expiringCanvas.value) return;
    if (expiringChart) expiringChart.destroy();
    expiringChart = new Chart(expiringCanvas.value, {
      type: "bar",
      data: {
        labels: expiringBucketLabels.value,
        datasets: [
          {
            label: t("subscriptions_page.chart_expiring_subscriptions_label"),
            data: expiringBuckets.value,
            backgroundColor: "#D4AF37",
            borderRadius: 6,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { grid: { display: false } }, y: { grid: { color: "rgba(82,115,61,0.08)" } } },
      },
    });
  }

  function renderAllCharts() {
    nextTick(() => {
      renderGrowthChart();
      renderStatusChart();
      renderPlanChart();
      renderExpiringChart();
    });
  }

  /* لما بيانات الصفحة تتغيّر: نعيد حساب نمو الاشتراكات التقريبي (لو ما في endpoint حقيقي) ونعيد رسم كل الرسوم */
  watch(subscriptions, () => {
    if (monthlyGrowth.value.isEstimate) computeMonthlyGrowthFromPage();
    renderAllCharts();
  });
  /* لما تتغيّر اللغة: تحديث تسميات الأشهر/الحالات/الخطط والرسوم كلها */
  watch(locale, () => {
    if (monthlyGrowth.value.isEstimate) computeMonthlyGrowthFromPage();
    renderAllCharts();
  });

  onBeforeUnmount(() => {
    growthChart?.destroy();
    statusChart?.destroy();
    planChart?.destroy();
    expiringChart?.destroy();
  });

  return {
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
  };
}
