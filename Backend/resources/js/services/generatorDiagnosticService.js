import http from "./http";

export default {
  list(generatorId, params = {}) {
    return http.get(`/generators/${generatorId}/diagnostics`, { params });
  },
  create(generatorId, payload) {
    return http.post(`/generators/${generatorId}/diagnostics`, payload);
  },
  analyze(readingId) {
    return http.post(`/generator-diagnostics/${readingId}/analyze`);
  },
};
