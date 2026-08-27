import { ref } from "vue";
import { useI18n } from "vue-i18n";
import faultService from "@/services/faultService";
import subscriptionService from "@/services/subscriptionService";

export function useSubscriberFaults() {
  const { t } = useI18n();
  const faults = ref([]);
  const isLoading = ref(true);
  const error = ref(null);

  async function fetchFaults() {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await faultService.list({ per_page: 30 });
      const payload = data.data;
      faults.value = payload.data ?? payload;
    } catch (err) {
      error.value =
        err.response?.data?.message ?? t("support_center_page.faults_load_error");
    } finally {
      isLoading.value = false;
    }
  }

  const myGeneratorId = ref(null);
  const myGeneratorName = ref(null);

  async function loadMyGenerator() {
    const { data } = await subscriptionService.list({ per_page: 1 });
    const payload = data.data;
    const sub = (payload.data ?? payload)[0];
    if (sub) {
      myGeneratorId.value = sub.generator?.id;
      myGeneratorName.value = sub.generator?.name;
    }
  }

  const isSubmitting = ref(false);
  const submitError = ref(null);

  async function reportFault(payload) {
    isSubmitting.value = true;
    submitError.value = null;
    try {
      const { data } = await faultService.create({
        ...payload,
        generator_id: myGeneratorId.value,
      });
      faults.value.unshift(data.data);
      return true;
    } catch (err) {
      submitError.value = err.response?.data ?? {
        message: t("support_center_page.fault_submit_error"),
      };
      return false;
    } finally {
      isSubmitting.value = false;
    }
  }

  return {
    faults,
    isLoading,
    error,
    fetchFaults,
    myGeneratorId,
    myGeneratorName,
    loadMyGenerator,
    isSubmitting,
    submitError,
    reportFault,
  };
}
