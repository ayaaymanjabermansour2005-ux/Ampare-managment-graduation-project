import http from "./http";

export default {
  async publicMap() {
    const { data } = await http.get("/public/generators-map");
    return data;
  },
};