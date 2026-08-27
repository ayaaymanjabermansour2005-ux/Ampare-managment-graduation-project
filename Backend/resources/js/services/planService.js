import http from "./http";

export default {
  list() {
    return http.get("/plans");
  },
  assign(userId, planId) {
    return http.patch(`/users/${userId}/plan`, { plan_id: planId });
  },
};
