import http from "./http";

export default {
  list(params = {}) {
    return http.get("/meter-readings", { params });
  },
  show(id) {
    return http.get(`/meter-readings/${id}`);
  },
  createReading(payload, idempotencyKey) {
    return http.post("/meter-readings", payload, {
      idempotent: true,
      idempotencyKey,
      queueOffline: true,
    });
  },

  approve(id) {
    return http.patch(`/meter-readings/${id}/approve`);
  },

  reject(id, reason) {
    return http.patch(`/meter-readings/${id}/reject`, { reason });
  },

  overdueSubscribers(params = {}) {
    return http.get("/meter-readings/overdue-subscribers", { params });
  },

  history(subscriptionId, params = {}) {
    return http.get(`/meter-readings/subscriptions/${subscriptionId}/history`, { params });
  },

  update(id, payload) {
    return http.patch(`/meter-readings/${id}`, payload);
  },

  delete(id) {
    return http.delete(`/meter-readings/${id}`);
  },

  exportUrl(params = {}) {
    const cleanParams = Object.fromEntries(
      Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== ""),
    );
    const query = new URLSearchParams(cleanParams).toString();
    return `/api/v1/meter-readings/export${query ? `?${query}` : ""}`;
  },
};