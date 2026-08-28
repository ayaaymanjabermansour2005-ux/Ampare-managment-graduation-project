import http from "./http";

export default {
  adminList(params = {}) {
    return http.get("/admin/article-comments", { params });
  },
  approve(id) {
    return http.post(`/admin/article-comments/${id}/approve`);
  },
  reject(id) {
    return http.post(`/admin/article-comments/${id}/reject`);
  },
  reply(id, adminReply) {
    return http.post(`/admin/article-comments/${id}/reply`, { admin_reply: adminReply });
  },
  deleteReply(id) {
    return http.delete(`/admin/article-comments/${id}/reply`);
  },
  destroy(id) {
    return http.delete(`/admin/article-comments/${id}`);
  },

  publicList(slug, params = {}) {
    return http.get(`/articles/${slug}/comments`, { baseURL: "/api", params });
  },
  publicStore(slug, payload) {
    return http.post(`/articles/${slug}/comments`, payload, { baseURL: "/api" });
  },
  ratingShow(slug) {
    return http.get(`/articles/${slug}/rating`, { baseURL: "/api" });
  },
  ratingStore(slug, rating) {
    return http.post(`/articles/${slug}/rating`, { rating }, { baseURL: "/api" });
  },
};
