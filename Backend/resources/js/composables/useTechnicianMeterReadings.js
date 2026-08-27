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
