import { ref } from "vue";
import generatorService from "@/services/generatorService";
import subscriptionService from "@/services/subscriptionService";
import { useMeterReadingForm } from "./useMeterReadingForm";

export function useTechnicianMeterReadings() {
  const generators = ref([]);
  const selectedGeneratorId = ref(null);
  const isLoadingGenerators = ref(true);

  async function loadGenerators() {
    isLoadingGenerators.value = true;
    try {
      const { data } = await generatorService.list({ per_page: 100 });
      const payload = data.data;
      generators.value = payload.data ?? payload;
      if (generators.value.length)
        selectedGeneratorId.value = generators.value[0].id;
    } catch {
      // MeterReadingsView.vue awaits this then loadSubscriptions() sequentially
      // with no surrounding try/catch — an unhandled rejection here previously
      // skipped loadSubscriptions() entirely. Fall back to empty (same as the
      // "no generators assigned" state) instead of leaving both calls unresolved.
      generators.value = [];
    } finally {
      isLoadingGenerators.value = false;
    }
  }

  const subscriptions = ref([]);
  const isLoadingSubscriptions = ref(false);

  async function loadSubscriptions() {
    if (!selectedGeneratorId.value) {
      subscriptions.value = [];
      return;
    }
    isLoadingSubscriptions.value = true;
    try {
      const { data } = await subscriptionService.list({ per_page: 100 });
      const payload = data.data;
      const all = payload.data ?? payload;
      subscriptions.value = all.filter(
        (s) =>
          s.generator?.id === selectedGeneratorId.value &&
          s.status === "active",
      );
    } catch {
      subscriptions.value = [];
    } finally {
      isLoadingSubscriptions.value = false;
    }
  }

  const { submitReading } = useMeterReadingForm();

  return {
    generators,
    selectedGeneratorId,
    isLoadingGenerators,
    loadGenerators,
    subscriptions,
    isLoadingSubscriptions,
    loadSubscriptions,
    submitReading,
  };
}
