import http from "./http";

export default {
  list(params = {}) {
    return http.get("/admin/login-logs", { params });
  },

  exportUrl(params = {}) {
    const cleanParams = Object.fromEntries(
      Object.entries(params).filter(([, v]) => v !== undefined && v !== null && v !== ""),
    );
    const query = new URLSearchParams(cleanParams).toString();
    return `/api/v1/admin/login-logs/export${query ? `?${query}` : ""}`;
  },
};
