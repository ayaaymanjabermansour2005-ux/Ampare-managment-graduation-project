import { ref } from "vue";
import generatorService from "@/services/generatorService";
import { useGeneratorsTable } from "./useGeneratorsTable";

export function useAdminGenerators() {
  const table = useGeneratorsTable({ perPage: 12 });

  const stats = ref(null);
  const isLoadingStats = ref(true);

  async function fetchStats() {
    isLoadingStats.value = true;
    try {
      const { data } = await generatorService.stats();
      stats.value = data.data;
    } finally {
      isLoadingStats.value = false;
    }
  }

  async function createGenerator(payload) {
    const ok = await table.createGenerator(payload);
    if (ok) await fetchStats();
    return ok;
  }

  async function updateGenerator(id, payload) {
    const ok = await table.updateGenerator(id, payload);
    if (ok) await fetchStats();
    return ok;
  }

  async function deleteGenerator(id) {
    const ok = await table.deleteGenerator(id);
    if (ok) await fetchStats();
    return ok;
  }

  async function loadAll() {
    await Promise.all([table.fetchGenerators(1), fetchStats()]);
  }

  return {
    ...table,
    createGenerator,
    updateGenerator,
    deleteGenerator,
    stats,
    isLoadingStats,
    fetchStats,
    loadAll,
  };
}