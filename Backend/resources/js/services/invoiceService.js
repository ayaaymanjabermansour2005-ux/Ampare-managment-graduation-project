import http from "./http";
import { getLocale } from "@/i18n";

export default {
  list(params = {}) {
    return http.get("/invoices", { params });
  },

  getInvoice(invoiceId) {
    return http.get(`/invoices/${invoiceId}`);
  },

  getPaymentMethods(invoiceId) {
    return http.get(`/invoices/${invoiceId}/payment-methods`);
  },

  cancel(invoiceId) {
    return http.patch(`/invoices/${invoiceId}/cancel`);
  },
  downloadPdfUrl(invoiceId) {
    // ?lang لازم يُرسَل صراحةً هون لأن هذا رابط تنزيل مباشر (<a href
    // target="_blank">) وليس نداء axios، فما بيوصل معه Accept-Language
    // (راجع SetLocale middleware).
    return `/api/v1/invoices/${invoiceId}/pdf?lang=${getLocale()}`;
  },

  qrCode(invoiceId) {
    return http.get(`/invoices/${invoiceId}/qr`);
  },
  correct(id, finalAmount, reason) {
    return http.patch(`/invoices/${id}/correct`, {
      final_amount: finalAmount,
      reason,
    });
  },

  reissue(id) {
    return http.post(`/invoices/${id}/reissue`);
  },

  // FIX: (item 14) الباك اند فيه GET /invoices/export أصلًا (نفس الأسلوب
  // المستخدَم بـ paymentService.exportUrl/meterReadingService.exportUrl)
  // بس ما كان له method هون، فالتبويب المجاور (الدفعات) كان فيه زر تصدير
  // شغّال والفواتير لأ.
  exportUrl(params = {}) {
    const query = new URLSearchParams(params).toString();
    return `/api/v1/invoices/export${query ? `?${query}` : ""}`;
  },
};
