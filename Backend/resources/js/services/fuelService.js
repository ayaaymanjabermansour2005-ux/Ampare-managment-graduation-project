import http from "./http";

export default {
  purchases(generatorId, params = {}) {
    return http.get(`/generators/${generatorId}/fuel/purchases`, { params });
  },
  storePurchase(generatorId, formData) {
    return http.post(`/generators/${generatorId}/fuel/purchases`, formData);
  },
  readings(generatorId, params = {}) {
    return http.get(`/generators/${generatorId}/fuel/readings`, { params });
  },
  storeReading(generatorId, formData) {
    return http.post(`/generators/${generatorId}/fuel/readings`, formData);
  },
  consumption(generatorId, params = {}) {
    return http.get(`/generators/${generatorId}/fuel/consumption`, { params });
  },
  costPerKwh(generatorId) {
    return http.get(`/generators/${generatorId}/fuel/cost-per-kwh`);
  },
  status(generatorId) {
    return http.get(`/generators/${generatorId}/fuel/status`);
  },
};
