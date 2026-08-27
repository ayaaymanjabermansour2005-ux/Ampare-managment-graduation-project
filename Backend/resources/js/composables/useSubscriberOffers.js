import { ref } from "vue";
import { useI18n } from "vue-i18n";
import offerService from "@/services/offerService";

export function useSubscriberOffers() {
  const { t } = useI18n();
  const offers = ref([]);
  const isLoading = ref(true);
  const error = ref(null);

  async function fetchOffers() {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await offerService.list({ per_page: 20 });
      const payload = data.data;
      offers.value = payload.data ?? payload;
    } catch (err) {
      error.value = err.response?.data?.message ?? t("offers_page.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  return { offers, isLoading, error, fetchOffers };
}
