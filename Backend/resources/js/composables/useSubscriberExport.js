import { computed } from "vue";
import userService from "@/services/userService";

/**
 * تصدير قائمة المشتركين/الاشتراكات (روابط تصدير Excel حقيقية من الباك اند
 * لكليهما) — منقولة من SubscribersView.vue (FRONT-004a slice 3، God-component
 * breakdown).
 *
 * ملاحظة: زر "تصدير PDF" اتحذف نهائيًا (FIX-031) — ما في endpoint بالباك اند
 * لتصدير PDF لقائمة الاشتراكات، فما تمت إعادته هنا.
 */
export function useSubscriberExport({
  subscribers,
  statusFilter,
  subscriptionSearchTerm,
  exportUrl,
  subscriberSearchTerm,
  subscriberStatusFilter,
}) {
  /* ---------------- تصدير قائمة الاشتراكات (Excel) ----------------
   * نفس منطق الفلاتر المطبّق على الجدول (statusFilter/subscriptionSearchTerm)
   * بينعكس على رابط التصدير، بحيث يصدّر بالضبط اللي الأدمن شايفه بالجدول.
   */
  const exportParams = computed(() => ({
    status: statusFilter.value !== "all" ? statusFilter.value : undefined,
    search: subscriptionSearchTerm.value.trim() || undefined,
  }));
  const exportExcelUrl = computed(() => exportUrl("excel", exportParams.value));

  /* ---------------- تصدير قائمة المشتركين (Excel) ----------------
   * FIX: كان تصدير المشتركين يتم محليًا (CSV من طرف المتصفح) ومقتصرًا على
   * الصفحة الحالية المحمَّلة فقط (subscribers.value)، رغم أن الباك اند يوفر
   * GET /users/subscribers-export (تصدير كامل من السيرفر لكل السجلات
   * المطابقة للفلاتر). استبدلناه بنفس نمط بقية صفحات الأدمن
   * (users/owners/generators export).
   */
  const subscribersExportExcelUrl = computed(() =>
    userService.subscribersExportUrl({
      search: subscriberSearchTerm?.value || undefined,
      subscription_status: subscriberStatusFilter?.value || undefined,
    }),
  );

  return {
    exportParams,
    exportExcelUrl,
    subscribersExportExcelUrl,
  };
}
