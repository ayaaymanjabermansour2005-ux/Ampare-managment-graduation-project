import { ref } from "vue";
import { useI18n } from "vue-i18n";
import generatorService from "@/services/generatorService";
import subscriberMeterService from "@/services/subscriberMeterService";
import subscriptionService from "@/services/subscriptionService";
import { normalizeApiError } from "@/utils/normalizeApiError";

export function useSubscriberBrowseGenerators() {
  const { t } = useI18n();
  const generators = ref([]);
  const pagination = ref({ current_page: 1, last_page: 1, total: 0 });
  const isLoading = ref(false);
  const error = ref(null);

  async function fetchGenerators(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await generatorService.available({ page });
      const payload = data.data;
      const meta = payload.meta ?? payload;
      generators.value = payload.data ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? generators.value.length,
      };
    } catch (err) {
      error.value = normalizeApiError(err, t("browse_generators_page.load_error")).message;
    } finally {
      isLoading.value = false;
    }
  }

  /* ---------------- عدادات المشترك (لاختيارها بفورم الاشتراك) ---------------- */
  const meters = ref([]);
  const isLoadingMeters = ref(false);

  async function fetchMeters() {
    isLoadingMeters.value = true;
    try {
      const { data } = await subscriberMeterService.list();
      meters.value = data.data;
    } finally {
      isLoadingMeters.value = false;
    }
  }

  const isCreatingMeter = ref(false);
  const createMeterError = ref(null);

  async function createMeter(payload) {
    isCreatingMeter.value = true;
    createMeterError.value = null;
    try {
      const { data } = await subscriberMeterService.create(payload);
      meters.value.unshift(data.data);
      return data.data;
    } catch (err) {
      const normalized = normalizeApiError(err, t("browse_generators_page.add_meter_error"));
      createMeterError.value = Object.keys(normalized.fieldErrors).length
        ? normalized.fieldErrors
        : { meter_number: [normalized.message] };
      return null;
    } finally {
      isCreatingMeter.value = false;
    }
  }

  /* ---------------- إرسال طلب الاشتراك ---------------- */
  const isSubscribing = ref(false);
  const subscribeError = ref(null);
  const subscribeErrors = ref({});

  async function subscribe(payload) {
    isSubscribing.value = true;
    subscribeError.value = null;
    subscribeErrors.value = {};
    try {
      const { data } = await subscriptionService.create(payload);
      return data;
    } catch (err) {
      const normalized = normalizeApiError(err, t("browse_generators_page.subscribe_error"));
      if (normalized.status === 422) {
        subscribeErrors.value = normalized.fieldErrors;
      } else {
        subscribeError.value = normalized.message;
      }
      return null;
    } finally {
      isSubscribing.value = false;
    }
  }

  return {
    generators,
    pagination,
    isLoading,
    error,
    fetchGenerators,
    meters,
    isLoadingMeters,
    fetchMeters,
    isCreatingMeter,
    createMeterError,
    createMeter,
    isSubscribing,
    subscribeError,
    subscribeErrors,
    subscribe,
  };
}