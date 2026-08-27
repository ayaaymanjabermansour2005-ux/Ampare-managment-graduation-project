import { ref } from "vue";
import { useI18n } from "vue-i18n";
import generatorService from "@/services/generatorService";

const SORT_MAP = {
  "name-asc": { key: "name", dir: "asc" },
  "name-desc": { key: "name", dir: "desc" },
  "fuel-asc": { key: "fuel_percentage", dir: "asc" },
  "fuel-desc": { key: "fuel_percentage", dir: "desc" },
  "subs-asc": { key: "active_subscriptions_count", dir: "asc" },
  "subs-desc": { key: "active_subscriptions_count", dir: "desc" },
  "rev-asc": { key: "monthly_revenue_ils", dir: "asc" },
  "rev-desc": { key: "monthly_revenue_ils", dir: "desc" },
};

/**
 * @param {{ perPage?: number }} options
 */
export function useGeneratorsTable(options = {}) {
  const { t } = useI18n();
  const perPage = options.perPage ?? 12;

  const generators = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: perPage });
  const isLoading = ref(true);
  const error = ref(null);

  const search = ref("");
  const statusFilter = ref("");
  const sortBy = ref("fuel-asc");

  const areaFilter = ref("");
  const ownerFilter = ref("");
  const cities = ref([]);

  const isSaving = ref(false);
  const saveError = ref(null);

  const deletingId = ref(null);
  const deleteError = ref(null);

  async function fetchCities() {
    const { data } = await generatorService.cities();
    cities.value = data.data;
  }

  async function fetchGenerators(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await generatorService.list({
        page,
        per_page: pagination.value.per_page,
        search: search.value || undefined,
        status: statusFilter.value || undefined,
        city: areaFilter.value || undefined,
        owner_id: ownerFilter.value || undefined,
      });
      const payload = data.data;
      let list = payload.data ?? payload;
      // ملاحظة إصلاح حرج: الباك اند بيرجّع شكل Resource Collection مُصفَّح
      // { data: [...], links, meta: {current_page, last_page, total, per_page} }
      // — كانت قيم الترقيم تُقرأ من المستوى الخاطئ (payload مباشرة بدل
      // payload.meta)، فكانت current_page/last_page/total كلها undefined
      // بصمت، وأزرار التنقّل بين الصفحات ما تظهر أبدًا مهما كان عدد المولدات.
      const meta = payload.meta ?? payload;

      // فرز محلي (Client-side) — الباك اند حاليًا بيدعم بحث/فلترة بس مش فرز مخصّص
      const { key, dir } = SORT_MAP[sortBy.value] ?? SORT_MAP["name-asc"];
      list = [...list].sort((a, b) => {
        const av = key === "name" ? a.name : (a[key] ?? -1);
        const bv = key === "name" ? b.name : (b[key] ?? -1);
        if (typeof av === "string") return dir === "asc" ? av.localeCompare(bv) : bv.localeCompare(av);
        return dir === "asc" ? av - bv : bv - av;
      });

      generators.value = list;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? list.length,
        per_page: meta.per_page ?? perPage,
      };
    } catch (err) {
      error.value = err.response?.data?.message ?? t("owner_generators.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  let debounceHandle = null;
  function onSearchInput() {
    clearTimeout(debounceHandle);
    debounceHandle = setTimeout(() => fetchGenerators(1), 300);
  }
  function onFilterChange() {
    fetchGenerators(1);
  }
  function onSortChange() {
    fetchGenerators(pagination.value.current_page);
  }

  async function createGenerator(payload) {
    isSaving.value = true;
    saveError.value = null;
    try {
      await generatorService.create(payload);
      await fetchGenerators(1);
      return true;
    } catch (err) {
      saveError.value = err.response?.data ?? { message: t("owner_generators.create_error") };
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
      saveError.value = err.response?.data ?? { message: t("owner_generators.update_error") };
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
      // ملاحظة إصلاح: أخطاء 422 (مثل منع الحذف بسبب اشتراكات فعالة) يرجعها
      // الباك اند برسالة عامة بحقل message ("بيانات غير صالحة")، والرسالة
      // المفيدة الفعلية موجودة بحقل errors.generator — كان الكود يقرأ
      // message بس، فيخفي عن الأدمن سبب الرفض الحقيقي.
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
    search,
    statusFilter,
    sortBy,
    areaFilter,
    ownerFilter,
    cities,
    isSaving,
    saveError,
    deletingId,
    deleteError,
    fetchCities,
    fetchGenerators,
    onSearchInput,
    onFilterChange,
    onSortChange,
    createGenerator,
    updateGenerator,
    deleteGenerator,
  };
}