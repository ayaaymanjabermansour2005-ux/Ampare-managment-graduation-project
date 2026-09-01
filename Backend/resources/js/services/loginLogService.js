import http from "./http";
import { buildExportUrl } from "@/utils/exportUrl";

export default {
  list(params = {}) {
    return http.get("/admin/login-logs", { params });
  },

  exportUrl(params = {}) {
    return buildExportUrl("/api/v1/admin/login-logs/export", params);
  },
};
