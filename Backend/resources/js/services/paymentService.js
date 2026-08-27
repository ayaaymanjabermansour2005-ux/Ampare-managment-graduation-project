import http from "./http";

export default {
  list(params = {}) {
    return http.get("/payments", { params });
  },

  show(id) {
    return http.get(`/payments/${id}`);
  },

  createPayment(formData, idempotencyKey) {
    return http.post("/payments", formData, {
      idempotent: true,
      idempotencyKey,
      queueOffline: true,
    });
  },

  payViaGateway(payload, idempotencyKey) {
    return http.post("/payments/gateway", payload, {
      idempotent: true,
      idempotencyKey,
    });
  },

  approve(id) {
    return http.patch(`/payments/${id}/approve`);
  },

  reject(id, payload) {
    return http.patch(`/payments/${id}/reject`, payload);
  },

  needsCorrection(id, payload) {
    return http.patch(`/payments/${id}/needs-correction`, payload);
  },

  resubmit(id, formData) {
    return http.patch(`/payments/${id}/resubmit`, formData);
  },

  cancel(id) {
    return http.patch(`/payments/${id}/cancel`);
  },

  exportUrl(params = {}) {
    const query = new URLSearchParams(params).toString();
    return `/api/v1/payments/export${query ? `?${query}` : ""}`;
  },
};