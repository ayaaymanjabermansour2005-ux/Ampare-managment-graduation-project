import http from "./http";

export default {
  list(params = {}) {
    return http.get("/faults", { params });
  },
  show(id) {
    return http.get(`/faults/${id}`);
  },
  create(payload) {
    return http.post("/faults", payload);
  },
  overrideStatus(id, payload) {
    return http.patch(`/faults/${id}/override-status`, payload);
  },
  verify(id, payload) {
    return http.patch(`/faults/${id}/verify`, payload);
  },
  decideRepair(id, payload) {
    return http.patch(`/faults/${id}/decide-repair`, payload);
  },
  destroy(id) {
    return http.delete(`/faults/${id}`);
  },
  exportUrl(params = {}) {
    const cleanParams = Object.fromEntries(
      Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== ""),
    );
    const query = new URLSearchParams(cleanParams).toString();
    return `/api/v1/faults/export${query ? `?${query}` : ""}`;
  },
};