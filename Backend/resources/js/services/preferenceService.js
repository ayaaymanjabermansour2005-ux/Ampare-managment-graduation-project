import http from "./http";

export default {
  index() {
    return http.get("/preferences");
  },
  update(preferences) {
    return http.patch("/preferences", { preferences });
  },
};