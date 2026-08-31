import http from "./http";

export default {
  listForAdmin(params = {}) {
    return http.get("/admin/articles", { params });
  },
  create(payload) {
    return http.post("/admin/articles", payload);
  },
  update(id, payload) {
    return http.patch(`/admin/articles/${id}`, payload);
  },
  destroy(id) {
    return http.delete(`/admin/articles/${id}`);
  },
  storeAttachment(id, formData) {
    return http.post(`/admin/articles/${id}/attachments`, formData);
  },
  listPublic(params = {}) {
    return http.get("/articles", { baseURL: "/api", params });
  },
  showPublic(slug) {
    return http.get(`/articles/${slug}`, { baseURL: "/api" });
  },
};