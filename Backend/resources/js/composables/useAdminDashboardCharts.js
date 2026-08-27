import { ref } from "vue";
import adminDashboardService from "@/services/adminDashboardService";

export function useAdminDashboardCharts() {
  const revenue = ref(null); 
  const isLoadingRevenue = ref(true);

  const subscriberGrowth = ref(null); 
  const isLoadingSubscriberGrowth = ref(true);

  const fuel = ref(null); 
  const isLoadingFuel = ref(true);

  const maintenance = ref(null); 
  const isLoadingMaintenance = ref(true);

  async function fetchRevenue(params = {}) {
    isLoadingRevenue.value = true;
    try {
      const { data } = await adminDashboardService.revenueChart(params);
      revenue.value = data.data;
    } finally {
      isLoadingRevenue.value = false;
    }
  }

  async function fetchSubscriberGrowth() {
    isLoadingSubscriberGrowth.value = true;
    try {
      const { data } = await adminDashboardService.subscriberGrowthChart();
      subscriberGrowth.value = data.data;
    } finally {
      isLoadingSubscriberGrowth.value = false;
    }
  }

  async function fetchFuel() {
    isLoadingFuel.value = true;
    try {
      const { data } = await adminDashboardService.fuelChart();
      fuel.value = data.data;
    } finally {
      isLoadingFuel.value = false;
    }
  }

  async function fetchMaintenance() {
    isLoadingMaintenance.value = true;
    try {
      const { data } = await adminDashboardService.maintenanceChart();
      maintenance.value = data.data;
    } finally {
      isLoadingMaintenance.value = false;
    }
  }

  // ملاحظة: fetchRevenue() مستقلة عمدًا عن loadAllCharts() — تُستدعى من
  // المكوّن مباشرة بفلاتر الفترة/السنة (نفس نمط fetchRevenueDistribution
  // المستقلة بصفحة أصحاب المولدات)، بدل إعادة تحميل كل المخططات الأخرى
  // عند مجرّد تبديل فترة الإيرادات.
  async function loadAllCharts() {
    await Promise.all([
      fetchSubscriberGrowth(),
      fetchFuel(),
      fetchMaintenance(),
    ]);
  }

  return {
    revenue,
    isLoadingRevenue,
    fetchRevenue,
    subscriberGrowth,
    isLoadingSubscriberGrowth,
    fuel,
    isLoadingFuel,
    maintenance,
    isLoadingMaintenance,
    loadAllCharts,
  };
}