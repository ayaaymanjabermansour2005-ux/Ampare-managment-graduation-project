import http from "./http";

export default {
  list(params = {}) {
    return http.get("/subscription-service-requests", { params });
  },
  show(id) {
    return http.get(`/subscription-service-requests/${id}`);
  },
  create(payload) {
    return http.post("/subscription-service-requests", payload);
  },
  cancel(id) {
    return http.patch(`/subscription-service-requests/${id}/cancel`);
  },
  review(id, payload) {
    return http.patch(`/subscription-service-requests/${id}/review`, payload);
  },
  transfer(id, generatorId) {
    return http.patch(`/subscriptions/${id}/transfer`, {
      generator_id: generatorId,
    });
  },
};
