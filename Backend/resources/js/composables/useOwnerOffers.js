import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import offerService from "@/services/offerService";
import subscriptionService from "@/services/subscriptionService";

export function useOwnerOffers() {
  const { t } = useI18n();
  const offers = ref([]);
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  });
  const isLoading = ref(true);
  const error = ref(null);

  const search = ref("");

  async function fetchOffers(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await offerService.list({
        page,
        search: search.value || undefined,
      });
      const payload = data.data;
      const meta = payload.meta ?? payload;

      offers.value = payload.data ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? offers.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("owner_offers.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  let searchDebounceHandle = null;
  function onSearchInput() {
    clearTimeout(searchDebounceHandle);
    searchDebounceHandle = setTimeout(() => fetchOffers(1), 300);
  }

  const mySubscribers = ref([]);
  async function loadMySubscribers() {
    const { data } = await subscriptionService.list({ per_page: 100 });
    const payload = data.data;
    const all = payload.data ?? payload;
    const uniqueMap = new Map();
    all.forEach((s) => {
      if (s.subscriber?.id) uniqueMap.set(s.subscriber.id, s.subscriber.name);
    });
    mySubscribers.value = Array.from(uniqueMap, ([id, name]) => ({ id, name }));
  }

  const isSaving = ref(false);
  const saveError = ref(null);

  async function createOffer(payload) {
    isSaving.value = true;
    saveError.value = null;
    try {
      await offerService.create(payload);
      await fetchOffers(1);
      return true;
    } catch (err) {
      saveError.value = err.response?.data ?? { message: t("owner_offers.create_error") };
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  async function updateOffer(id, payload) {
    isSaving.value = true;
    saveError.value = null;
    try {
      const { data } = await offerService.update(id, payload);
      const index = offers.value.findIndex((o) => o.id === id);
      if (index !== -1) offers.value[index] = data.data;
      return true;
    } catch (err) {
      saveError.value = err.response?.data ?? { message: t("owner_offers.update_error") };
      return false;
    } finally {
      isSaving.value = false;
    }
  }

  const cancellingId = ref(null);
  const cancelError = ref(null);

  async function cancelOffer(id) {
    cancellingId.value = id;
    cancelError.value = null;
    try {
      const { data } = await offerService.cancel(id);
      const index = offers.value.findIndex((o) => o.id === id);
      if (index !== -1) offers.value[index] = data.data;
      return true;
    } catch (err) {
      cancelError.value = normalizeApiError(err, t("owner_offers.cancel_error")).message;
      return false;
    } finally {
      cancellingId.value = null;
    }
  }

  return {
    offers,
    pagination,
    isLoading,
    error,
    search,
    fetchOffers,
    onSearchInput,
    mySubscribers,
    loadMySubscribers,
    isSaving,
    saveError,
    createOffer,
    updateOffer,
    cancellingId,
    cancelError,
    cancelOffer,
  };
}
