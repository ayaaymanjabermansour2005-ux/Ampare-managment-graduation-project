import http from "./http";

export default {
  list(params = {}) {
    return http.get("/offers", { params });
  },
  show(id) {
    return http.get(`/offers/${id}`);
  },
  create(payload) {
    return http.post("/offers", payload);
  },
  update(id, payload) {
    return http.patch(`/offers/${id}`, payload);
  },
  cancel(id) {
    return http.patch(`/offers/${id}/cancel`);
  },
  destroy(id) {
    return http.delete(`/offers/${id}`);
  },
  exportUrl(params = {}) {
    const cleanParams = Object.fromEntries(
      Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== ""),
    );
    const query = new URLSearchParams(cleanParams).toString();
    return `/api/v1/offers/export${query ? `?${query}` : ""}`;
  },
};
