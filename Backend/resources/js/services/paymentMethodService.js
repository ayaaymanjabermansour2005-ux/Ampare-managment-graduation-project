import http from "./http";

export default {
  list(params = {}) {
    return http.get("/payment-methods", { params });
  },
  create(payload) {
    return http.post("/payment-methods", payload);
  },
  update(id, payload) {
    return http.put(`/payment-methods/${id}`, payload);
  },
  destroy(id) {
    return http.delete(`/payment-methods/${id}`);
  },
};