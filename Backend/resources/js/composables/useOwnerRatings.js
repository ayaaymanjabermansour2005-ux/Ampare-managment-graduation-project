import { normalizeApiError } from "@/utils/normalizeApiError";
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import ownerRatingService from "@/services/ownerRatingService";

/**
 * GET /owners/{id}/ratings كان مبنيًا بالكامل بالباك اند (Controller، Policy،
 * Resource) بدون أي واجهة تعرضه — لا لصاحب المولد نفسه ولا للأدمن.
 */
export function useOwnerRatings() {
  const { t } = useI18n();
  const ratings = ref([]);
  const averageRating = ref(null);
  const ratingsCount = ref(0);
  const isLoading = ref(false);
  const error = ref(null);

  async function fetchRatings(ownerId, params = {}) {
    if (!ownerId) return;
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await ownerRatingService.listForOwner(ownerId, params);
      const payload = data.data;
      ratings.value = payload.ratings?.data ?? [];
      averageRating.value = payload.average_rating ?? null;
      ratingsCount.value = payload.ratings_count ?? 0;
    } catch (err) {
      error.value = normalizeApiError(err, t("owner_ratings.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  return {
    ratings,
    averageRating,
    ratingsCount,
    isLoading,
    error,
    fetchRatings,
  };
}
