import http from "./http";
import { buildExportUrl } from "@/utils/exportUrl";

export default {
  list(params = {}) {
    return http.get("/users", { params });
  },

  show(id) {
    return http.get(`/users/${id}`);
  },

  update(id, payload) {
    return http.patch(`/users/${id}`, payload);
  },

  updateAvatar(id, file) {
    const formData = new FormData();
    formData.append("avatar", file);
    return http.post(`/users/${id}/avatar`, formData);
  },

  destroy(id) {
    return http.delete(`/users/${id}`);
  },

  lockedList() {
    return http.get("/users/locked");
  },

  unlock(id) {
    return http.patch(`/users/${id}/unlock`);
  },
  sendPasswordResetLink(id) {
    return http.post(`/users/${id}/send-password-reset-link`);
  },
  setPassword(id, password, passwordConfirmation) {
    return http.post(`/users/${id}/set-password`, {
      password,
      password_confirmation: passwordConfirmation,
    });
  },
  updateCommissionSettings(id, payload) {
    return http.patch(`/users/${id}/commission-settings`, payload);
  },
  createGeneratorOwner(payload) {
    return http.post("/users/generator-owners", payload);
  },
  createSubscriber(payload) {
    return http.post("/users/subscribers", payload);
  },
  createTechnician(payload) {
    return http.post("/users/technicians", payload);
  },
  ownersStats(params = {}) {
    return http.get("/users/owners-stats", { params });
  },
  ownersExportUrl(params = {}) {
    return buildExportUrl("/api/v1/users/owners-export", params);
  },
  subscribersStats() {
    return http.get("/users/subscribers-stats");
  },
  subscribersExportUrl(params = {}) {
    return buildExportUrl("/api/v1/users/subscribers-export", params);
  },
  exportUrl(params = {}) {
    return buildExportUrl("/api/v1/users/export", params);
  },

  sendBulkPaymentReminder(subscriberIds) {
    return http.post("/users/bulk-payment-reminder", { subscriber_ids: subscriberIds });
  },

  // تذكير دفع جماعي من طرف مالك المولد — يقتصر تلقائيًا على مشتركين لهم
  // اشتراك فعلي على أحد مولدات المالك الحالي (UserController::sendOwnerBulkPaymentReminder).
  sendOwnerBulkPaymentReminder(subscriberIds) {
    return http.post("/owner/subscribers/bulk-payment-reminder", { subscriber_ids: subscriberIds });
  },

  // بحث محدود (id/name/email/phone) عن مستخدمين بدور "مشترك" — لخطوة اختيار
  // المشترك بإضافة اشتراك من طرف مالك المولد (UserController::ownerSubscriberLookup).
  ownerSubscriberLookup(search) {
    return http.get("/owner/subscriber-lookup", { params: { search } });
  },

  // عدادات مشترك محدَّد (id/meter_number/property_label/status) — لملء قائمة
  // اختيار العداد بعد اختيار المشترك (UserController::ownerSubscriberMeters).
  ownerSubscriberMeters(userId) {
    return http.get(`/owner/subscriber-lookup/${userId}/meters`);
  },
};