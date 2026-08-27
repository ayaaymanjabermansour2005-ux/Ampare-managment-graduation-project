import http from "./http";

export default {
  list() {
    return http.get("/auth/neighborhoods");
  },
  store(payload) {
    return http.post("/admin/neighborhoods", payload);
  },
  update(id, payload) {
    return http.patch(`/admin/neighborhoods/${id}`, payload);
  },
  destroy(id) {
    return http.delete(`/admin/neighborhoods/${id}`);
  },
};