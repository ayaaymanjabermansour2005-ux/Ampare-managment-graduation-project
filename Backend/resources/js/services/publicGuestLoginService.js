import http from "./http";

export default {
  async guestLogin(role) {
    const { data } = await http.post("/public/guest-login", { role });
    return data;
  },
};