import http from "./http";
import { getLocale } from "@/i18n";

export default {
  list(params = {}) {
    return http.get("/subscriptions", { params });
  },

  show(id) {
    return http.get(`/subscriptions/${id}`);
  },

  create(payload) {
    return http.post("/subscriptions", payload);
  },

  // إنشاء اشتراك من طرف مالك المولد نيابةً عن مشترك موجود، على أحد مولداته هو
  // (مسار منفصل عن create() الذاتي للمشترك — انظر SubscriptionController::storeByOwner).
  createByOwner(payload) {
    return http.post("/owner/subscriptions", payload);
  },

  updateStatus(id, status) {
    return http.patch(`/subscriptions/${id}/status`, { status });
  },

  transfer(id, generatorId) {
    return http.patch(`/subscriptions/${id}/transfer`, {
      generator_id: generatorId,
    });
  },

  // نقل اشتراك بين مولدات مالك المولد نفسه (SubscriptionController::transferByOwner).
  transferByOwner(id, generatorId) {
    return http.patch(`/owner/subscriptions/${id}/transfer`, {
      generator_id: generatorId,
    });
  },

  downloadContractPdfUrl(id) {
    // ?lang لازم يُرسَل صراحةً هون لأن هذا رابط تنزيل مباشر (<a href
    // target="_blank">) وليس نداء axios، فما بيوصل معه Accept-Language
    // (راجع SetLocale middleware).
    return `/api/v1/subscriptions/${id}/contract-pdf?lang=${getLocale()}`;
  },

  exportUrl(params = {}) {
    const cleanParams = Object.fromEntries(
      Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== ""),
    );
    const query = new URLSearchParams(cleanParams).toString();
    return `/api/v1/subscriptions/export${query ? `?${query}` : ""}`;
  },

  exportExcelUrl(params = {}) {
    return this.exportUrl(params);
  },

  updateNotes(id, notes) {
    return http.patch(`/subscriptions/${id}/notes`, { notes });
  },
};