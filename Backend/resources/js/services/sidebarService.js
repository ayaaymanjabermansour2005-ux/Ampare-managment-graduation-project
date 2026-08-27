import http from "./http";

export default {
  badgeCounts() {
    return http.get("/admin/sidebar/badge-counts");
  },
};