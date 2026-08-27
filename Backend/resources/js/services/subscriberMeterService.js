import http from "./http";

export default {
  list() {
    return http.get("/subscriber-meters");
  },

  show(id) {
    return http.get(`/subscriber-meters/${id}`);
  },

  create(payload) {
    return http.post("/subscriber-meters", payload);
  },

  update(id, payload) {
    return http.patch(`/subscriber-meters/${id}`, payload);
  },

  destroy(id) {
    return http.delete(`/subscriber-meters/${id}`);
  },

  qrCode(id) {
    return http.get(`/subscriber-meters/${id}/qr`);
  },
};
