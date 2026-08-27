import http from "./http";

export default {
  list(params = {}) {
    return http.get("/technician-tasks", { params });
  },

  show(id) {
    return http.get(`/technician-tasks/${id}`);
  },

  stats() {
    return http.get("/technician-tasks/stats");
  },

  create(payload) {
    return http.post("/technician-tasks", payload);
  },

  assign(id, payload) {
    return http.patch(`/technician-tasks/${id}/assign`, payload);
  },
  review(id, payload) {
    return http.patch(`/technician-tasks/${id}/review`, payload);
  },

  onTheWay(id) {
    return http.patch(`/technician-tasks/${id}/on-the-way`);
  },
  start(id) {
    return http.patch(`/technician-tasks/${id}/start`);
  },
  waitingParts(id) {
    return http.patch(`/technician-tasks/${id}/waiting-parts`);
  },
  submit(id, completionNotes) {
    return http.patch(`/technician-tasks/${id}/submit`, {
      completion_notes: completionNotes,
    });
  },
  cancel(id) {
    return http.patch(`/technician-tasks/${id}/cancel`);
  },
  rate(id, payload) {
    return http.post(`/technician-tasks/${id}/rate`, payload);
  },
};
