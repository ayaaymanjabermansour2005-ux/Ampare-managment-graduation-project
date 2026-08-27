import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import generatorService from "@/services/generatorService";

export function useOwnerGenerators() {
  const { t } = useI18n();
  const generators = ref([]);
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  });
  const isLoading = ref(false);
  const error = ref(null);

  const search = ref("");
  const statusFilter = ref("");

  const isSaving = ref(false);
  const saveError = ref(null);

  const deletingId = ref(null);
  const deleteError = ref(null);

  const activeCount = computed(
    () => generators.value.filter((g) => g.status === "active").length,
  );

  async function fetchGenerators(page = 1) {
    isLoading.value = true;
    error.value = null;

    try {
      const { data } = await generatorService.list({
        page,
        search: search.value || undefined,
        status: statusFilter.value || undefined,
      });
      const payload = data.data;
      const meta = payload.meta ?? payload;

      generators.value = payload.data ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? generators.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value =
        err.response?.data?.message ?? t("owner_generators.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  let searchDebounceHandle = null;
  function onSearchInput() {
    clearTimeout(searchDebounceHandle);
    searchDebounceHandle = setTimeout(() => fetchGenerators(1), 300);
  }

  function onFilterChange() {
    fetchGenerators(1);
  }

  async function createGenerator(payload) {
    isSaving.value = true;
    saveError.value = null;

    try {
      await generatorService.create(payload);
      await fetchGenerators(1);
      return true;
    } catch (err) {
      saveError.value = err.response?.data ?? {
        message: t("owner_generators.create_error"),
      };
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  async function updateGenerator(id, payload) {
    isSaving.value = true;
    saveError.value = null;

    try {
      const { data } = await generatorService.update(id, payload);
      const index = generators.value.findIndex((g) => g.id === id);
      if (index !== -1) generators.value[index] = data.data;
      return true;
    } catch (err) {
      saveError.value = err.response?.data ?? {
        message: t("owner_generators.update_error"),
      };
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  async function deleteGenerator(id) {
    deletingId.value = id;
    deleteError.value = null;

    try {
      await generatorService.destroy(id);
      await fetchGenerators(pagination.value.current_page);
      return true;
    } catch (err) {
      deleteError.value = err.response?.data?.errors?.generator?.[0]
        ?? err.response?.data?.message
        ?? t("owner_generators.delete_error");
      return false;
    } finally {
      deletingId.value = null;
    }
  }

  return {
    generators,
    pagination,
    isLoading,
    error,
    activeCount,
    search,
    statusFilter,
    fetchGenerators,
    onSearchInput,
    onFilterChange,

    isSaving,
    saveError,
    createGenerator,
    updateGenerator,

    deletingId,
    deleteError,
    deleteGenerator,
  };
}
