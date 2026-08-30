import { computed } from "vue";
import { useI18n } from "vue-i18n";

/**
 * تصدير قائمة المشتركين/الاشتراكات (CSV على مستوى الصفحة الحالية + رابط
 * تصدير Excel الحقيقي من الباك اند للاشتراكات) — منقولة من SubscribersView.vue
 * (FRONT-004a slice 3، God-component breakdown).
 *
 * ملاحظة: زر "تصدير PDF" اتحذف نهائيًا (FIX-031) — ما في endpoint بالباك اند
 * لتصدير PDF لقائمة الاشتراكات، فما تمت إعادته هنا.
 */
export function useSubscriberExport({ subscribers, statusFilter, subscriptionSearchTerm, exportUrl, statusLabel, formatDate }) {
  const { t } = useI18n();

  /* ---------------- تصدير قائمة الاشتراكات (Excel) ----------------
   * نفس منطق الفلاتر المطبّق على الجدول (statusFilter/subscriptionSearchTerm)
   * بينعكس على رابط التصدير، بحيث يصدّر بالضبط اللي الأدمن شايفه بالجدول.
   */
  const exportParams = computed(() => ({
    status: statusFilter.value !== "all" ? statusFilter.value : undefined,
    search: subscriptionSearchTerm.value.trim() || undefined,
  }));
  const exportExcelUrl = computed(() => exportUrl("excel", exportParams.value));

  /* ---------------- تصدير CSV (المشتركون) ----------------
   * التصدير هون على مستوى الصفحة المحمّلة حاليًا (subscribers.value) فقط، مو كل
   * السجلات. لتصدير الكل بغض النظر عن الـ pagination، يلزم إما رفع per_page مؤقتًا عبر
   * fetchSubscribers، أو إضافة Endpoint تصدير مخصص بالـ Backend (الأفضل للحجم الكبير).
   */
  function handleExportCsv() {
    const escapeCsv = (val) => {
      const s = String(val ?? "");
      return /[",\n]/.test(s) ? `"${s.replace(/"/g, '""')}"` : s;
    };
    const header = [
      t("dashboard.name"),
      t("users_page.email_label"),
      t("dashboard.phone"),
      t("dashboard.status"),
      t("subscribers_page.current_subscription_col"),
      t("subscribers_page.expires_on_col"),
      t("subscribers_page.joined_col"),
    ];
    const rows = subscribers.value.map((s) => [
      s.name,
      s.email,
      s.phone ?? "",
      statusLabel(s),
      s.active_subscription ? (s.active_subscription.generator_name ?? s.active_subscription.plan_name ?? "") : t("subscribers_page.no_subscription"),
      s.active_subscription ? formatDate(s.active_subscription.ends_at) : "",
      formatDate(s.created_at),
    ]);
    const csv = "﻿" + [header, ...rows].map((r) => r.map(escapeCsv).join(",")).join("\n");
    const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `subscribers-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
  }

  return {
    exportParams,
    exportExcelUrl,
    handleExportCsv,
  };
}
