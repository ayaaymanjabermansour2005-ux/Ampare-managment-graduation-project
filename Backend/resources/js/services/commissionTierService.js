import http from "./http";

export default {
  list() {
    return http.get("/commission-tiers");
  },
  create(payload) {
    return http.post("/commission-tiers", payload);
  },
  update(id, payload) {
    return http.patch(`/commission-tiers/${id}`, payload);
  },
  destroy(id) {
    return http.delete(`/commission-tiers/${id}`);
  },
};
