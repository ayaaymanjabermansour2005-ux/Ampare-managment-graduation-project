import http from "./http";

export default {
  list(params = {}) {
    return http.get("/technician-payments", { params });
  },
  show(id) {
    return http.get(`/technician-payments/${id}`);
  },
  create(payload) {
    return http.post("/technician-payments", payload, { idempotent: true });
  },
  approve(id) {
    return http.patch(`/technician-payments/${id}/approve`);
  },
  reject(id, reason) {
    return http.patch(`/technician-payments/${id}/reject`, { reason });
  },
  attachments(id) {
    return http.get(`/technician-payments/${id}/attachments`);
  },
  storeAttachment(id, formData) {
    return http.post(`/technician-payments/${id}/attachments`, formData);
  },
};
