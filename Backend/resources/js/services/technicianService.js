import http from "./http";

export default {
  list(params = {}) {
    return http.get("/technicians", { params });
  },
  show(id) {
    return http.get(`/technicians/${id}`);
  },
  create(payload) {
    return http.post("/technicians", payload);
  },
  createAccount(payload) {
    return http.post("/technicians/create-account", payload);
  },
  update(id, payload) {
    return http.patch(`/technicians/${id}`, payload);
  },
  destroy(id) {
    return http.delete(`/technicians/${id}`);
  },
  attachments(id) {
    return http.get(`/technicians/${id}/attachments`);
  },
  storeAttachment(id, formData) {
    return http.post(`/technicians/${id}/attachments`, formData);
  },
  exportUrl(params = {}) {
    const cleanParams = Object.fromEntries(
      Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== ""),
    );
    const query = new URLSearchParams(cleanParams).toString();
    return `/api/v1/technicians/export${query ? `?${query}` : ""}`;
  },
};
