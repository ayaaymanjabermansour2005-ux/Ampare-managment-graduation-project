import http from "./http";

export default {
  list(params = {}) {
    return http.get("/admin/contact-messages", { params });
  },
  show(id) {
    return http.get(`/admin/contact-messages/${id}`);
  },
  updateStatus(id, payload) {
    return http.patch(`/admin/contact-messages/${id}/status`, payload);
  },
};