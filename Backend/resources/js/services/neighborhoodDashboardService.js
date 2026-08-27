import http from "./http";

export default {
  summary() {
    return http.get("/neighborhoods/dashboard");
  },
};
