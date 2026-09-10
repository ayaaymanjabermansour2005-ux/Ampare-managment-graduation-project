import { ref } from "vue";
import { useI18n } from "vue-i18n";
import adminDashboardService from "@/services/adminDashboardService";
import { normalizeApiError } from "@/utils/normalizeApiError";

/**
 * يجمع بيانات المخططات الأربعة بلوحة تحكم الأدمن: الإيرادات مقابل
 * المستحقات، نمو المشتركين، مشتريات الوقود، والصيانة/الأعطال حسب المدينة.
 * كل مخطط بحالة تحميل/خطأ خاصة فيه، فشل مخطط واحد لا يمنع عرض البقية.
 */
export function useAdminDashboardCharts() {
  const { t } = useI18n();

  const revenue = ref(null);
  const isLoadingRevenue = ref(true);
  const revenueError = ref(null);

  const subscriberGrowth = ref(null);
  const isLoadingSubscriberGrowth = ref(true);
  const subscriberGrowthError = ref(null);

  const fuel = ref(null);
  const isLoadingFuel = ref(true);
  const fuelError = ref(null);

  const maintenance = ref(null);
  const isLoadingMaintenance = ref(true);
  // اسم "maintenanceChartError" مش "maintenanceError" عمدًا — DashboardView.vue
  // بيستهلك useAdminDashboardExtras().maintenanceError لخطأ فورم جدولة الصيانة
  // بنفس الوقت، فلازم اسمين مختلفين لتفادي تصادم الـ destructuring.
  const maintenanceChartError = ref(null);

  /** يجلب مخطط الإيرادات مقابل المستحقات، بفلتر فترة (6/12 شهر) أو سنة محدَّدة. */
  async function fetchRevenue(params = {}) {
    isLoadingRevenue.value = true;
    revenueError.value = null;
    try {
      const { data } = await adminDashboardService.revenueChart(params);
      revenue.value = data.data;
    } catch (err) {
      revenueError.value = normalizeApiError(err, t("dashboard.revenue_chart_load_error")).message;
    } finally {
      isLoadingRevenue.value = false;
    }
  }

  /** يجلب مخطط نمو عدد المشتركين الجدد خلال آخر 6 أشهر. */
  async function fetchSubscriberGrowth() {
    isLoadingSubscriberGrowth.value = true;
    subscriberGrowthError.value = null;
    try {
      const { data } = await adminDashboardService.subscriberGrowthChart();
      subscriberGrowth.value = data.data;
    } catch (err) {
      subscriberGrowthError.value = normalizeApiError(err, t("dashboard.subscriber_growth_load_error")).message;
    } finally {
      isLoadingSubscriberGrowth.value = false;
    }
  }

  /** يجلب مخطط كميات الوقود المشتراة خلال آخر 7 أيام. */
  async function fetchFuel() {
    isLoadingFuel.value = true;
    fuelError.value = null;
    try {
      const { data } = await adminDashboardService.fuelChart();
      fuel.value = data.data;
    } catch (err) {
      fuelError.value = normalizeApiError(err, t("dashboard.fuel_chart_load_error")).message;
    } finally {
      isLoadingFuel.value = false;
    }
  }

  /** يجلب مخطط طلبات الصيانة والأعطال موزّعة حسب المدينة خلال آخر 30 يومًا. */
  async function fetchMaintenance() {
    isLoadingMaintenance.value = true;
    maintenanceChartError.value = null;
    try {
      const { data } = await adminDashboardService.maintenanceChart();
      maintenance.value = data.data;
    } catch (err) {
      maintenanceChartError.value = normalizeApiError(err, t("dashboard.maintenance_chart_load_error")).message;
    } finally {
      isLoadingMaintenance.value = false;
    }
  }

  /**
   * يشغّل المخططات الثلاثة عديمة الفلاتر معًا (يُستدعى من onMounted بالواجهة).
   * fetchRevenue() مستقلة عمدًا عنها — تُستدعى من المكوّن مباشرة بفلاتر
   * الفترة/السنة، بدل إعادة تحميل كل المخططات عند مجرّد تبديل الفترة.
   * كل دالة فرعية تلتقط خطأها بنفسها، فـ Promise.all هون لا يرفض أبدًا.
   */
  async function loadAllCharts() {
    await Promise.all([
      fetchSubscriberGrowth(),
      fetchFuel(),
      fetchMaintenance(),
    ]);
  }

  return {
    revenue,
    isLoadingRevenue,
    revenueError,
    fetchRevenue,
    subscriberGrowth,
    isLoadingSubscriberGrowth,
    subscriberGrowthError,
    fuel,
    isLoadingFuel,
    fuelError,
    maintenance,
    isLoadingMaintenance,
    maintenanceChartError,
    loadAllCharts,
  };
}
