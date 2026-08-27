import axios from "axios";
import http from "./http";
import { getLocale } from "@/i18n";

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
    return axios.get(`/api/articles/${slug}/comments`, {
      params,
      headers: { "Accept-Language": getLocale() },
    });
  },
  publicStore(slug, payload) {
    return axios.post(`/api/articles/${slug}/comments`, payload, {
      headers: { "Accept-Language": getLocale() },
    });
  },
  ratingShow(slug) {
    return axios.get(`/api/articles/${slug}/rating`, {
      headers: { "Accept-Language": getLocale() },
    });
  },
  ratingStore(slug, rating) {
    return axios.post(`/api/articles/${slug}/rating`, { rating }, {
      headers: { "Accept-Language": getLocale() },
    });
  },
};
