import http from "./http";

export default {
  stats() {
    return http.get("/admin/dashboard/stats");
  },
  paymentsFinancialSummary() {
    return http.get("/admin/dashboard/payments-financial-summary");
  },
  paymentsActivity() {
    return http.get("/admin/dashboard/payments-activity");
  },
  invoiceStatusBreakdown() {
    return http.get("/admin/dashboard/invoice-status-breakdown");
  },
  alerts() {
    return http.get("/admin/dashboard/alerts");
  },
  revenueChart(params = {}) {
    return http.get("/admin/dashboard/charts/revenue", { params });
  },
  subscriberGrowthChart() {
    return http.get("/admin/dashboard/charts/subscriber-growth");
  },
  fuelChart() {
    return http.get("/admin/dashboard/charts/fuel");
  },
  maintenanceChart() {
    return http.get("/admin/dashboard/charts/maintenance");
  },
  generatorsMap() {
    return http.get("/admin/dashboard/generators-map");
  },
};