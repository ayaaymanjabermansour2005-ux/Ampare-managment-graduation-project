import http from "./http";

export default {
  get(neighborhoodId = null) {
    return http.get("/live-schedule", {
      params: neighborhoodId ? { neighborhood_id: neighborhoodId } : {},
    });
  },
};