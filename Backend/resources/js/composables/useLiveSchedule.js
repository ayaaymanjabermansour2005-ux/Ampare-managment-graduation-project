import { ref } from "vue";
import { useI18n } from "vue-i18n";
import liveScheduleService from "@/services/liveScheduleService";

export function useLiveSchedule() {
  const { t } = useI18n();
  const activeNow = ref([]);
  const upcoming = ref([]);
  const isLoading = ref(true);
  const error = ref(null);

  async function fetchSchedule(neighborhoodId = null) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await liveScheduleService.get(neighborhoodId);
      activeNow.value = data.data.active_now;
      upcoming.value = data.data.upcoming;
    } catch (err) {
      error.value = t("live_schedule_page.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  return { activeNow, upcoming, isLoading, error, fetchSchedule };
}