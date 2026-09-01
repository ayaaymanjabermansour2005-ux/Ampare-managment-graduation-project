import http from "./http";
import { buildExportUrl } from "@/utils/exportUrl";

export default {
  list(params = {}) {
    return http.get("/complaints", { params });
  },
  show(id) {
    return http.get(`/complaints/${id}`);
  },
  create(payload) {
    return http.post("/complaints", payload);
  },
  updateStatus(id, payload) {
    return http.patch(`/complaints/${id}/status`, payload);
  },
  destroy(id) {
    return http.delete(`/complaints/${id}`);
  },
  uploadAttachment(id, formData) {
    return http.post(`/complaints/${id}/attachments`, formData);
  },
  exportUrl(params = {}) {
    return buildExportUrl("/api/v1/complaints/export", params);
  },
};
