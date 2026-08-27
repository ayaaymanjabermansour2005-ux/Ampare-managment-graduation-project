import http from "./http";

export default {
  async platformAnalytics() {
    const { data } = await http.get("/public/platform-analytics");
    return data;
  },
};