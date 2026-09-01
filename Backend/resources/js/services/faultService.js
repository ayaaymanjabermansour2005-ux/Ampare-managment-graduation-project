import http from "./http";
import { buildExportUrl } from "@/utils/exportUrl";

export default {
  list(params = {}) {
    return http.get("/faults", { params });
  },
  show(id) {
    return http.get(`/faults/${id}`);
  },
  create(payload) {
    return http.post("/faults", payload);
  },
  overrideStatus(id, payload) {
    return http.patch(`/faults/${id}/override-status`, payload);
  },
  verify(id, payload) {
    return http.patch(`/faults/${id}/verify`, payload);
  },
  decideRepair(id, payload) {
    return http.patch(`/faults/${id}/decide-repair`, payload);
  },
  destroy(id) {
    return http.delete(`/faults/${id}`);
  },
  exportUrl(params = {}) {
    return buildExportUrl("/api/v1/faults/export", params);
  },
};