import http from "./http";

export default {
  list(params = {}) {
    return http.get("/complaints", { params });
  },
  show(id) {
    return http.get(`/complaints/${id}`);
  },
  create(payload) {
    return http.post("/complaints", payload);
  },
  updateStatus(id, payload) {
    return http.patch(`/complaints/${id}/status`, payload);
  },
  destroy(id) {
    return http.delete(`/complaints/${id}`);
  },
  uploadAttachment(id, formData) {
    return http.post(`/complaints/${id}/attachments`, formData);
  },
  exportUrl(params = {}) {
    const cleanParams = Object.fromEntries(
      Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== ""),
    );
    const query = new URLSearchParams(cleanParams).toString();
    return `/api/v1/complaints/export${query ? `?${query}` : ""}`;
  },
};
