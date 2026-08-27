import http from "./http";

export default {
  list(params = {}) {
    return http.get("/admin/owner-applications", { params });
  },
  show(id) {
    return http.get(`/admin/owner-applications/${id}`);
  },
  approve(id) {
    return http.post(`/admin/owner-applications/${id}/approve`);
  },
  reject(id, payload = {}) {
    return http.post(`/admin/owner-applications/${id}/reject`, payload);
  },
  updateInternalNote(id, payload) {
    return http.patch(`/admin/owner-applications/${id}/internal-note`, payload);
  },
  bulkApprove(applicationIds) {
    return http.post("/admin/owner-applications/bulk-approve", { application_ids: applicationIds });
  },
  bulkReject(applicationIds, reason) {
    return http.post("/admin/owner-applications/bulk-reject", { application_ids: applicationIds, reason });
  },
  exportUrl(params = {}) {
    const query = new URLSearchParams(
      Object.fromEntries(Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== "")),
    ).toString();
    return `/api/v1/admin/owner-applications/export${query ? `?${query}` : ""}`;
  },
};