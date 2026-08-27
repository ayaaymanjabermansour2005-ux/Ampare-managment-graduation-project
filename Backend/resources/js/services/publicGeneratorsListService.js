import http from "./http";

export default {
  async list() {
    const { data } = await http.get("/public/generators-list");
    return data;
  },
};