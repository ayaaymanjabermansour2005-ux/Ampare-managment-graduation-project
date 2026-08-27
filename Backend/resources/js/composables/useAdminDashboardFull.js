import { ref, computed } from "vue";
import adminDashboardService from "@/services/adminDashboardService";
import { useGeneratorsTable } from "./useGeneratorsTable";

export function useAdminDashboardFull() {
  const stats = ref(null);
  const isLoadingStats = ref(true);

  async function fetchStats() {
    isLoadingStats.value = true;
    try {
      const { data } = await adminDashboardService.stats();
      stats.value = data.data;
    } finally {
      isLoadingStats.value = false;
    }
  }

  const invoiceBreakdown = ref(null);
  const isLoadingBreakdown = ref(true);

  async function fetchInvoiceBreakdown() {
    isLoadingBreakdown.value = true;
    try {
      const { data } = await adminDashboardService.invoiceStatusBreakdown();
      invoiceBreakdown.value = data.data;
    } finally {
      isLoadingBreakdown.value = false;
    }
  }

  const invoicePercentages = computed(() => {
    const b = invoiceBreakdown.value;
    if (!b || !b.total) return { paid: 0, pending: 0, overdue: 0 };
    return {
      paid: Math.round((b.paid / b.total) * 100),
      pending: Math.round((b.pending / b.total) * 100),
      overdue: Math.round((b.overdue / b.total) * 100),
    };
  });

  const alerts = ref([]);
  const isLoadingAlerts = ref(true);

  async function fetchAlerts() {
    isLoadingAlerts.value = true;
    try {
      const { data } = await adminDashboardService.alerts();
      alerts.value = data.data;
    } finally {
      isLoadingAlerts.value = false;
    }
  }

  const generatorsTable = useGeneratorsTable({ perPage: 8 });

  async function loadAll() {
    await Promise.all([
      fetchStats(),
      fetchInvoiceBreakdown(),
      fetchAlerts(),
      generatorsTable.fetchGenerators(1),
      generatorsTable.fetchCities(),
    ]);
  }

  return {
    stats,
    isLoadingStats,
    fetchStats,
    invoiceBreakdown,
    invoicePercentages,
    isLoadingBreakdown,
    fetchInvoiceBreakdown,
    alerts,
    isLoadingAlerts,
    fetchAlerts,
    ...generatorsTable,
    loadAll,
  };
}