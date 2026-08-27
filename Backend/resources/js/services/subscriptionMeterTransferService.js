import http from "./http";

export default {
  list(params = {}) {
    return http.get("/subscription-meter-transfers", { params });
  },

  create(payload) {
    return http.post("/subscription-meter-transfers", payload);
  },

  approve(id) {
    return http.patch(`/subscription-meter-transfers/${id}/approve`);
  },

  reject(id, rejectionReason) {
    return http.patch(`/subscription-meter-transfers/${id}/reject`, {
      rejection_reason: rejectionReason,
    });
  },
};
