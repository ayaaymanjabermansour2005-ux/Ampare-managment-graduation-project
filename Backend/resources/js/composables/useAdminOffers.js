import { ref } from "vue";
import { useI18n } from "vue-i18n";
import offerService from "@/services/offerService";

export function useAdminOffers() {
  const { t } = useI18n();
  const offers = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1, total: 0, per_page: 15 });
  const isLoading = ref(true);
  const error = ref(null);

  const search = ref("");
  const includeExpired = ref(true); // الأدمن يشوف كل العروض افتراضيًا (نشطة + منتهية/ملغاة) لأنه إشراف

  const cancellingId = ref(null);
  const cancelError = ref(null);
  const deletingId = ref(null);
  const deleteError = ref(null);

  async function fetchOffers(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await offerService.list({
        page,
        search: search.value || undefined,
        include_expired: includeExpired.value ? 1 : undefined,
      });
      const payload = data.data;
      offers.value = payload.data ?? payload;
      pagination.value = {
        current_page: payload.current_page ?? 1,
        last_page: payload.last_page ?? 1,
        total: payload.total ?? offers.value.length,
        per_page: payload.per_page ?? 15,
      };
    } catch (err) {
      error.value = err.response?.data?.message ?? t("offers_page.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  let debounceHandle = null;
  function onSearchInput() {
    clearTimeout(debounceHandle);
    debounceHandle = setTimeout(() => fetchOffers(1), 300);
  }

  async function cancelOffer(id) {
    cancellingId.value = id;
    cancelError.value = null;
    try {
      const { data } = await offerService.cancel(id);
      const index = offers.value.findIndex((o) => o.id === id);
      if (index !== -1) offers.value[index] = data.data;
      return true;
    } catch (err) {
      cancelError.value = err.response?.data?.message ?? t("offers_page.cancel_error");
      return false;
    } finally {
      cancellingId.value = null;
    }
  }

  async function deleteOffer(id) {
    deletingId.value = id;
    deleteError.value = null;
    try {
      await offerService.destroy(id);
      await fetchOffers(pagination.value.current_page);
      return true;
    } catch (err) {
      deleteError.value = err.response?.data?.message ?? t("offers_page.delete_error");
      return false;
    } finally {
      deletingId.value = null;
    }
  }

  return {
    offers,
    pagination,
    isLoading,
    error,
    search,
    cancellingId,
    cancelError,
    deletingId,
    deleteError,
    fetchOffers,
    onSearchInput,
    cancelOffer,
    deleteOffer,
  };
}