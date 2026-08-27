import http from "./http";

export default {
  async platformStats() {
    const { data } = await http.get("/public/platform-stats");
    return data;
  },
};