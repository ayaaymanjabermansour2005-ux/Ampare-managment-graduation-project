import http from "./http";

export default {
  list(generatorId) {
    return http.get(`/generators/${generatorId}/schedules`);
  },

  create(generatorId, payload) {
    return http.post(`/generators/${generatorId}/schedules`, payload);
  },

  update(scheduleId, payload) {
    return http.patch(`/generator-schedules/${scheduleId}`, payload);
  },

  destroy(scheduleId) {
    return http.delete(`/generator-schedules/${scheduleId}`);
  },
};
