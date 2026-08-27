import http from "./http";

export default {
  list(params = {}) {
    return http.get("/platform-commissions", { params });
  },
  summary() {
    return http.get("/platform-commissions/summary");
  },
  updateStatus(id, status) {
    return http.patch(`/platform-commissions/${id}/status`, { status });
  },
  downloadReportPdfUrl() {
    return "/api/v1/platform-commissions/report-pdf";
  },
  exportUrl() {
    return "/api/v1/platform-commissions/export";
  },
};
