import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import generatorScheduleService from "@/services/generatorScheduleService";

export function useGeneratorSchedules(generatorId) {
  const { t } = useI18n();
  const schedules = ref([]);
  const isLoading = ref(false);
  const isSaving = ref(false);
  const error = ref(null);

  const activeNow = computed(
    () => schedules.value.find((s) => s.is_active_now) ?? null,
  );
  const upcoming = computed(() =>
    schedules.value
      .filter((s) => !s.is_active_now && new Date(s.starts_at) > new Date())
      .sort((a, b) => new Date(a.starts_at) - new Date(b.starts_at)),
  );

  async function fetchSchedules() {
    isLoading.value = true;
    error.value = null;

    try {
      const { data } = await generatorScheduleService.list(generatorId);
      schedules.value = data.data;
    } catch (err) {
      error.value = err.response?.data?.message ?? t("owner_generators.schedule_load_error");
    } finally {
      isLoading.value = false;
    }
  }

  async function createSchedule(payload) {
    isSaving.value = true;
    error.value = null;

    try {
      await generatorScheduleService.create(generatorId, payload);
      await fetchSchedules();
    } catch (err) {
      error.value = err.response?.data?.message ?? t("owner_generators.schedule_create_error");
      throw err;
    } finally {
      isSaving.value = false;
    }
  }

  async function deleteSchedule(scheduleId) {
    try {
      await generatorScheduleService.destroy(scheduleId);
      schedules.value = schedules.value.filter((s) => s.id !== scheduleId);
    } catch (err) {
      error.value = err.response?.data?.message ?? t("owner_generators.schedule_delete_error");
      throw err;
    }
  }

  return {
    schedules,
    isLoading,
    isSaving,
    error,
    activeNow,
    upcoming,
    fetchSchedules,
    createSchedule,
    deleteSchedule,
  };
}
