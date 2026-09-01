import { getLocale } from "@/i18n";

/**
 * يبني رابط تصدير (Excel/CSV/PDF) يتضمن فلاتر الجدول الحالية + لغة الموقع
 * الحالية (?lang=ar|en). ضروري لأن روابط التصدير تُفتح كـ <a target="_blank">
 * (تصفّح مباشر من المتصفح)، فما بتوصل عبر axios ولا بتحمل هيدر
 * Accept-Language تلقائيًا — الباك اند (SetLocale middleware) بيعتمد بالضبط
 * على ?lang= كـ fallback بهاي الحالة.
 */
export function buildExportUrl(path, params = {}) {
  const cleanParams = Object.fromEntries(
    Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== ""),
  );
  cleanParams.lang = getLocale();
  const query = new URLSearchParams(cleanParams).toString();
  return `${path}${query ? `?${query}` : ""}`;
}
