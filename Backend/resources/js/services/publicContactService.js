import http from "./http";

export default {
  async submit(payload) {
    const { data } = await http.post("/public/contact-messages", payload);
    return data;
  },
};