import http from "./http";

export default {
  submit(subscriptionId, payload) {
    return http.post(`/subscriptions/${subscriptionId}/owner-rating`, payload);
  },
  listForOwner(ownerId, params = {}) {
    return http.get(`/owners/${ownerId}/ratings`, { params });
  },
};
