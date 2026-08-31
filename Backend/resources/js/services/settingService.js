import http from "./http";

export default {
  index() {
    return http.get("/admin/settings");
  },
  update(settings) {
    return http.patch("/admin/settings", { settings });
  },
  publicIdentity() {
    return http.get("/platform-identity");
  },
};
