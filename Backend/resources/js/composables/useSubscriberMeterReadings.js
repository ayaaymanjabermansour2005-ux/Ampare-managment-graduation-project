import { ref } from "vue";
import { useI18n } from "vue-i18n";
import meterReadingService from "@/services/meterReadingService";

export function useSubscriberMeterReadings() {
  const { t } = useI18n();
  const readings = ref([]);
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  });
  const isLoading = ref(false);
  const error = ref(null);

  async function fetchReadings(page = 1) {
    isLoading.value = true;
    error.value = null;

    try {
      const { data } = await meterReadingService.list({ page });
      const payload = data.data;
      const meta = payload.meta ?? payload;

      readings.value = payload.data ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? readings.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = err.response?.data?.message ?? t("subscriber_meter_readings_page.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  return { readings, pagination, isLoading, error, fetchReadings };
}
