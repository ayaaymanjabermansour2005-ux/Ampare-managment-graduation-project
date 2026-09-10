import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import adminDashboardService from "@/services/adminDashboardService";
import { normalizeApiError } from "@/utils/normalizeApiError";
import { useGeneratorsTable } from "./useGeneratorsTable";

/**
 * يجمع بيانات القسم الرئيسي بلوحة تحكم الأدمن: مؤشرات KPI، توزيع حالة
 * الفواتير، التنبيهات اللحظية، وجدول المولدات (عبر useGeneratorsTable).
 * كل مصدر بيانات مستقل بحالة تحميل/خطأ خاصة فيه، حتى فشل مصدر واحد لا يمنع
 * عرض بقية أقسام لوحة التحكم.
 */
export function useAdminDashboardFull() {
  const { t } = useI18n();

  const stats = ref(null);
  const isLoadingStats = ref(true);
  const statsError = ref(null);

  /** يجلب مؤشرات KPI الرئيسية (عدد المولدات، المشتركين، الفواتير المتأخرة...). */
  async function fetchStats() {
    isLoadingStats.value = true;
    statsError.value = null;
    try {
      const { data } = await adminDashboardService.stats();
      stats.value = data.data;
    } catch (err) {
      statsError.value = normalizeApiError(err, t("dashboard.stats_load_error")).message;
    } finally {
      isLoadingStats.value = false;
    }
  }

  const invoiceBreakdown = ref(null);
  const isLoadingBreakdown = ref(true);
  const breakdownError = ref(null);

  /** يجلب أعداد الفواتير مصنّفة حسب الحالة (مدفوعة/معلّقة/متأخرة) لمخطط Doughnut. */
  async function fetchInvoiceBreakdown() {
    isLoadingBreakdown.value = true;
    breakdownError.value = null;
    try {
      const { data } = await adminDashboardService.invoiceStatusBreakdown();
      invoiceBreakdown.value = data.data;
    } catch (err) {
      breakdownError.value = normalizeApiError(err, t("dashboard.breakdown_load_error")).message;
    } finally {
      isLoadingBreakdown.value = false;
    }
  }

  /** يحوّل أعداد invoiceBreakdown الخام إلى نسب مئوية جاهزة للعرض، بحماية من القسمة على صفر. */
  const invoicePercentages = computed(() => {
    const b = invoiceBreakdown.value;
    if (!b || !b.total) return { paid: 0, pending: 0, overdue: 0 };
    return {
      paid: Math.round((b.paid / b.total) * 100),
      pending: Math.round((b.pending / b.total) * 100),
      overdue: Math.round((b.overdue / b.total) * 100),
    };
  });

  const alerts = ref([]);
  const isLoadingAlerts = ref(true);
  const alertsError = ref(null);

  /** يجلب التنبيهات اللحظية (وقود منخفض، عطل حرج، فاتورة متأخرة...) لبطاقة "تنبيهات فورية". */
  async function fetchAlerts() {
    isLoadingAlerts.value = true;
    alertsError.value = null;
    try {
      const { data } = await adminDashboardService.alerts();
      alerts.value = data.data;
    } catch (err) {
      alertsError.value = normalizeApiError(err, t("dashboard.alerts_load_error")).message;
    } finally {
      isLoadingAlerts.value = false;
    }
  }

  const generatorsTable = useGeneratorsTable({ perPage: 8 });

  /**
   * يشغّل تحميل كل أقسام هذا composable معًا (يُستدعى من onMounted بالواجهة).
   * كل دالة فرعية تلتقط أخطاءها بنفسها (انظر fetchStats/fetchInvoiceBreakdown/
   * fetchAlerts أعلاه)، فـ Promise.all هون لا يرفض أبدًا حتى لو فشل أحد المصادر —
   * فشل مصدر واحد يظهر كحالة خطأ في قسمه فقط، دون إيقاف تحميل الباقي.
   */
  async function loadAll() {
    await Promise.all([
      fetchStats(),
      fetchInvoiceBreakdown(),
      fetchAlerts(),
      generatorsTable.fetchGenerators(1),
      generatorsTable.fetchCities(),
    ]);
  }

  return {
    stats,
    isLoadingStats,
    statsError,
    fetchStats,
    invoiceBreakdown,
    invoicePercentages,
    isLoadingBreakdown,
    breakdownError,
    fetchInvoiceBreakdown,
    alerts,
    isLoadingAlerts,
    alertsError,
    fetchAlerts,
    ...generatorsTable,
    loadAll,
  };
}
