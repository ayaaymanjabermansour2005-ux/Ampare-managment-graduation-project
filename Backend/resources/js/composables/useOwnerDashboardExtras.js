import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import generatorService from "@/services/generatorService";
import invoiceService from "@/services/invoiceService";

export function useOwnerDashboardExtras() {
  const { t, locale } = useI18n();

  const MONTHS_AR = ["يناير", "فبراير", "مارس", "أبريل", "مايو", "يونيو", "يوليو", "أغسطس", "سبتمبر", "أكتوبر", "نوفمبر", "ديسمبر"];
  const MONTHS_EN = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

  /* ---------------- كل مولدات المالك ---------------- */
  const allGenerators = ref([]);
  const isLoadingAllGenerators = ref(true);

  async function fetchAllGenerators() {
    isLoadingAllGenerators.value = true;
    try {
      const { data } = await generatorService.list({ per_page: 100 });
      const payload = data.data;
      allGenerators.value = payload.data ?? payload;
    } catch {
      allGenerators.value = [];
    } finally {
      isLoadingAllGenerators.value = false;
    }
  }

  /* ---------------- توزيع الحالة ---------------- */
  const statusBreakdown = computed(() => {
    const counts = { active: 0, maintenance: 0, inactive: 0, pending_verification: 0, rejected: 0 };
    allGenerators.value.forEach((g) => {
      if (counts[g.status] !== undefined) counts[g.status]++;
    });
    return counts;
  });

  const statusChartData = computed(() => {
    const s = statusBreakdown.value;
    return {
      labels: [
        t("status.active"),
        t("status.maintenance"),
        t("generators_management_page.inactive_rejected"),
        t("status.pending_verification"),
      ],
      datasets: [
        {
          data: [s.active, s.maintenance, s.inactive + s.rejected, s.pending_verification],
          backgroundColor: ["#28A745", "#FFC107", "#D9534F", "#17A2B8"],
          borderWidth: 0,
        },
      ],
    };
  });

  const statusLegendItems = computed(() => {
    const chart = statusChartData.value;
    const total = chart.datasets[0].data.reduce((sum, n) => sum + n, 0) || 1;
    return chart.labels.map((label, i) => ({
      label,
      color: chart.datasets[0].backgroundColor[i],
      value: chart.datasets[0].data[i],
      pct: Math.round((chart.datasets[0].data[i] / total) * 100),
    }));
  });

  /* ---------------- أضعف 5 مولدات وقودًا ---------------- */
  const lowestFuelGenerators = computed(() =>
    [...allGenerators.value]
      .filter((g) => g.fuel_percentage !== null && g.fuel_percentage !== undefined)
      .sort((a, b) => a.fuel_percentage - b.fuel_percentage)
      .slice(0, 5),
  );

  const mapPoints = ref([]);
  const isLoadingMapPoints = ref(true);
  const mapPointsError = ref(null);

  async function fetchMapPoints() {
    isLoadingMapPoints.value = true;
    mapPointsError.value = null;
    try {
      const { data } = await generatorService.mapPoints();
      mapPoints.value = data.data;
    } catch (err) {
      mapPoints.value = [];
      mapPointsError.value = normalizeApiError(err, t("generators_map.load_error")).message;
    } finally {
      isLoadingMapPoints.value = false;
    }
  }

  const monthlyPerformance = ref({ labels: [], paidAmounts: [], isEstimate: true });
  const isLoadingMonthlyPerformance = ref(true);

  async function fetchMonthlyPerformance() {
    isLoadingMonthlyPerformance.value = true;
    try {
      const { data } = await invoiceService.list({ per_page: 100 });
      const payload = data.data;
      const invoices = payload.data ?? payload;

      const now = new Date();
      const months = [];
      for (let i = 5; i >= 0; i--) {
        const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
        months.push({ year: d.getFullYear(), month: d.getMonth(), amount: 0 });
      }

      invoices.forEach((inv) => {
        if (!["paid", "partially_paid"].includes(inv.status)) return;
        const dateStr = inv.paid_at ?? inv.created_at ?? inv.due_date;
        if (!dateStr) return;
        const d = new Date(dateStr);
        const bucket = months.find((m) => m.year === d.getFullYear() && m.month === d.getMonth());
        if (!bucket) return;

        const finalAmount = Number(inv.final_amount ?? 0);
        const remaining = Number(inv.remaining_balance_ils ?? 0);
        bucket.amount += inv.status === "paid" ? finalAmount : Math.max(finalAmount - remaining, 0);
      });

      monthlyPerformance.value = {
        labels: months.map((m) => (locale.value === "ar" ? MONTHS_AR[m.month] : MONTHS_EN[m.month])),
        paidAmounts: months.map((m) => Math.round(m.amount)),
        isEstimate: true,
      };
    } catch {
      monthlyPerformance.value = { labels: [], paidAmounts: [], isEstimate: true };
    } finally {
      isLoadingMonthlyPerformance.value = false;
    }
  }

  /* ---------------- تصدير/طباعة ---------------- */
  function generatorsExportUrl(params = {}) {
    return generatorService.exportUrl(params);
  }
  function printDashboard() {
    window.print();
  }

  async function loadAll() {
    await Promise.all([fetchAllGenerators(), fetchMonthlyPerformance(), fetchMapPoints()]);
  }

  return {
    allGenerators,
    isLoadingAllGenerators,
    fetchAllGenerators,

    statusBreakdown,
    statusChartData,
    statusLegendItems,

    lowestFuelGenerators,

    mapPoints,
    isLoadingMapPoints,
    mapPointsError,
    fetchMapPoints,

    monthlyPerformance,
    isLoadingMonthlyPerformance,
    fetchMonthlyPerformance,

    generatorsExportUrl,
    printDashboard,

    loadAll,
  };
}