import http from "./http";

export default {
  list(params = {}) {
    return http.get("/fault-predictions", { params });
  },
  show(id) {
    return http.get(`/fault-predictions/${id}`);
  },
  confirm(id) {
    return http.patch(`/fault-predictions/${id}/confirm`);
  },
  dismiss(id) {
    return http.patch(`/fault-predictions/${id}/dismiss`);
  },
};
