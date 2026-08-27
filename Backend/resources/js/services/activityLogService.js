import http from "./http";

export default {
  index(params = {}) {
    return http.get("/activity-logs", { params });
  },
};