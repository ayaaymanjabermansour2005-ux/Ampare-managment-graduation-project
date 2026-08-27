import http from "./http";

export default {
  ownerStats() {
    return http.get("/owner/dashboard/stats");
  },
  subscriberStats() {
    return http.get("/subscriber/dashboard/stats");
  },
  technicianStats() {
    return http.get("/technician/dashboard/stats");
  },
};
