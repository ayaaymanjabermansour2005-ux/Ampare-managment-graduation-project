import { ref, computed, onMounted } from "vue";
import { useI18n } from "vue-i18n";

/**
 * تحليلات/عرض لوحة أصحاب المولدات (تبويب "أصحاب المولدات"): فرز الجدول،
 * مساعدات العرض (حالة/عملة/أفاتار)، بطاقات KPI، تنبيهات اللوحة، وبيانات
 * رسوم توزيع الإيرادات/الخطط/النمو — منقولة من GeneratorOwnersView.vue
 * (FRONT-004b slice 2، God-component breakdown).
 *
 * ملاحظة: هذا الملف يستخدم مكوّنات vue-chartjs التصريحية (`<Doughnut>`/`<Bar>`/`<Line>`)
 * بدل التلاعب المباشر بـ canvas مثل SubscribersView.vue — فلا يوجد هنا كود
 * canvas ref/render-function/lifecycle للنقل، فقط كائنات البيانات المشتقة.
 */
export function useOwnersAnalytics({
  owners,
  stats,
  statusFilter,
  onFilterChange,
  router,
  revenueDistData,
  availableYears,
  fetchRevenueDistributionAction,
  openView,
}) {
  const { t, locale } = useI18n();

  /* ---------------- حالة المالك (تسمية/شارة) ---------------- */
  const STATUS_META = {
    active: { chip: "chip-success", key: "users_page.status_active" },
    inactive: { chip: "chip-info", key: "users_page.status_inactive" },
    suspended: { chip: "chip-danger", key: "users_page.status_suspended" },
    pending_review: { chip: "chip-warning", key: "owners_page.status_pending_review_admin" },
  };
  function statusLabel(s) {
    const m = STATUS_META[s];
    return m ? t(m.key) : s;
  }
  function statusChip(s) {
    return STATUS_META[s]?.chip ?? "chip-info";
  }
  const STATUS_PILLS = computed(() => [
    { value: "", label: t("common.all") },
    { value: "active", label: statusLabel("active") },
    { value: "inactive", label: statusLabel("inactive") },
    { value: "suspended", label: statusLabel("suspended") },
    { value: "pending_review", label: statusLabel("pending_review") },
  ]);

  /* ---------------- مساعدات عرض ---------------- */
  function fmtMoney(n) {
    return "₪ " + Number(n ?? 0).toLocaleString(locale.value === "ar" ? "ar-EG" : "en-US");
  }
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
  function commissionLabel(rate, mode) {
    if (rate !== null && rate !== undefined) return `${rate}%`;
    if (mode === "tiered") return t("owners_page.commission_tiered_label");
    return "-";
  }

  /* ---------------- فرز الجدول ---------------- */
  const viewMode = ref("table");
  const sortBy = ref("name-asc");
  const SORT_MAP = {
    name: "name",
    generators: "generators_count",
    subs: "active_subscriptions_count",
    rev: "monthly_revenue_ils",
  };
  const sortedOwners = computed(() => {
    const [uiKey, dir] = sortBy.value.split("-");
    const key = SORT_MAP[uiKey] ?? "name";
    return [...owners.value].sort((a, b) => {
      const av = key === "name" ? (a.name ?? "") : (a[key] ?? -1);
      const bv = key === "name" ? (b.name ?? "") : (b[key] ?? -1);
      if (typeof av === "string") return dir === "asc" ? av.localeCompare(bv) : bv.localeCompare(av);
      return dir === "asc" ? av - bv : bv - av;
    });
  });
  /** يبني كلاس CSS (شفافية + دوران) لأيقونة سهم الفرز الثابتة (ChevronDown)؛ يُستخدَم مع :class على <ChevronDown> وليس :name على <AppIcon>. */
  function sortIconClass(key) {
    const [curKey, curDir] = sortBy.value.split("-");
    if (curKey !== key) return "opacity-40";
    return curDir === "desc" ? "opacity-100 text-[#8A6D1F] dark:text-[#D4AF37] rotate-180" : "opacity-100 text-[#8A6D1F] dark:text-[#D4AF37]";
  }
  function toggleSort(key) {
    const [curKey, curDir] = sortBy.value.split("-");
    const newDir = curKey === key && curDir === "desc" ? "asc" : "desc";
    sortBy.value = `${key}-${newDir}`;
  }

  /* ---------------- بطاقات KPI ---------------- */
  const KPI_CARDS = computed(() => {
    if (!stats.value) return [];
    const s = stats.value;
    const activePct = s.total > 0 ? Math.round((s.active / s.total) * 100) : 0;
    return [
      {
        icon: "fa-user-tie", label: t("owners_page.total_owners_kpi"),
        raw: s.total, decimals: 0, tone: "primary",
      },
      {
        icon: "fa-circle-check", label: t("owners_page.active_owners_kpi"),
        raw: s.active, decimals: 0, tone: "success", suffix: `(${activePct}%)`,
      },
      {
        icon: "fa-plug-circle-bolt", label: t("owners_page.owned_generators_kpi"),
        raw: s.total_generators, decimals: 0, tone: "info",
      },
      {
        icon: "fa-wallet", label: t("owners_page.total_revenue_kpi"),
        raw: s.total_revenue_ils, decimals: 0, prefix: "₪ ", tone: "secondary",
      },
      {
        icon: "fa-chart-simple", label: t("owners_page.avg_generators_kpi"),
        raw: s.avg_generators_per_owner, decimals: 1, tone: "secondary",
      },
    ];
  });

  /* ---------------- تنبيهات اللوحة ---------------- */
  const ALERT_ICONS = {
    pending_dues: { icon: "fa-file-invoice-dollar", color: "#D9534F" },
    pending_review: { icon: "fa-user-clock", color: "#FFC107" },
    suspended_account: { icon: "fa-ban", color: "#D9534F" },
  };
  const ALERT_CHIPS = { critical: "chip-danger", warning: "chip-warning", info: "chip-info" };
  function alertTag(severity) {
    const tags = {
      critical: t("owners_page.alert_tag_critical"),
      warning: t("owners_page.alert_tag_warning"),
      info: t("owners_page.alert_tag_info"),
    };
    return tags[severity] ?? t("owners_page.alert_tag_warning");
  }

  function scrollToOwnersTable() {
    document.getElementById("owners-table-section")?.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  function handleAlertClick(alert) {
    if (alert.type === "pending_dues") {
      router.push({ name: "admin.invoices", query: { status: "overdue" } });
      return;
    }
    if (alert.type === "pending_review") {
      statusFilter.value = "pending_review";
      onFilterChange();
      scrollToOwnersTable();
      return;
    }
    if (alert.type === "suspended_account") {
      statusFilter.value = "suspended";
      onFilterChange();
      if (alert.owner_id) {
        setTimeout(() => {
          const match = owners.value.find((o) => o.id === alert.owner_id);
          if (match) openView(match);
        }, 400);
      }
      scrollToOwnersTable();
    }
  }

  /* ---------------- توزيع الإيرادات (Line chart) ---------------- */
  const revenuePeriod = ref("6");
  const revenueYear = ref(null);

  async function fetchRevenueDistribution() {
    const params =
      revenuePeriod.value === "year" && revenueYear.value
        ? { year: revenueYear.value }
        : { period: revenuePeriod.value };
    await fetchRevenueDistributionAction(params);
    if (!revenueYear.value && availableYears.value.length) {
      revenueYear.value = availableYears.value[0];
    }
  }

  function setRevenuePeriod(period) {
    revenuePeriod.value = period;
    fetchRevenueDistribution();
  }

  function setRevenueYear(year) {
    revenueYear.value = year;
    revenuePeriod.value = "year";
    fetchRevenueDistribution();
  }

  const revenueYearOptions = computed(() => availableYears.value.map((y) => ({ value: y, label: String(y) })));

  onMounted(fetchRevenueDistribution);

  const revenueDistChartData = computed(() => {
    const r = revenueDistData.value;
    return {
      labels: r.labels,
      datasets: [
        { label: t("owners_page.distributed_revenue"), data: r.revenue, borderColor: "#8A6D1F", backgroundColor: "rgba(138,109,31,0.12)", fill: true, tension: 0.4 },
        { label: t("owners_page.pending_dues"), data: r.due, borderColor: "#D9534F", backgroundColor: "rgba(217,83,79,0.08)", fill: true, tension: 0.4 },
      ],
    };
  });
  const lineOptions = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { position: "bottom", labels: { boxWidth: 10, font: { size: 10.5 } } } },
    scales: { x: { grid: { display: false } }, y: { grid: { color: "rgba(82,115,61,0.08)" } } },
  };

  /* ---------------- توزيع الخطط (Doughnut chart) ---------------- */
  const planChartData = computed(() => {
    if (!stats.value) return { labels: [], datasets: [{ data: [] }] };
    return {
      labels: stats.value.plan_distribution.labels,
      datasets: [{
        data: stats.value.plan_distribution.counts,
        backgroundColor: ["#D4AF37", "#52733D", "#17A2B8", "#8A6D1F", "#9a9d97"],
        borderWidth: 0,
      }],
    };
  });
  const doughnutOptions = { responsive: true, maintainAspectRatio: false, cutout: "68%", plugins: { legend: { position: "bottom", labels: { boxWidth: 10, font: { size: 10.5 } } } } };

  /* ---------------- نمو عدد الملاك (Bar chart) ---------------- */
  const growthChartData = computed(() => {
    if (!stats.value) return { labels: [], datasets: [{ data: [] }] };
    return {
      labels: stats.value.growth.labels,
      datasets: [{
        label: t("owners_page.new_owners_label"),
        data: stats.value.growth.counts,
        backgroundColor: "#52733D",
        borderRadius: 6,
      }],
    };
  });
  const barOptions = {
    responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
    scales: { x: { grid: { display: false } }, y: { grid: { color: "rgba(82,115,61,0.08)" }, ticks: { stepSize: 1 } } },
  };

  return {
    STATUS_META,
    statusLabel,
    statusChip,
    STATUS_PILLS,
    fmtMoney,
    initialsOf,
    avatarColor,
    commissionLabel,
    viewMode,
    sortBy,
    sortedOwners,
    sortIconClass,
    toggleSort,
    KPI_CARDS,
    ALERT_ICONS,
    ALERT_CHIPS,
    alertTag,
    handleAlertClick,
    scrollToOwnersTable,
    revenuePeriod,
    revenueYear,
    fetchRevenueDistribution,
    setRevenuePeriod,
    setRevenueYear,
    revenueYearOptions,
    revenueDistChartData,
    lineOptions,
    planChartData,
    doughnutOptions,
    growthChartData,
    barOptions,
  };
}
