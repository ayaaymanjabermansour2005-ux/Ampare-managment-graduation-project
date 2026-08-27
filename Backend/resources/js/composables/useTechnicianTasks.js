import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import technicianTaskService from "@/services/technicianTaskService";

const CANCELLABLE_STATUSES = [
  "assigned",
  "on_the_way",
  "in_progress",
  "waiting_parts",
];

export function useTechnicianTasks() {
  const { t } = useI18n();

  const NEXT_ACTION = {
    assigned: {
      action: "onTheWay",
      label: t("technician_tasks.action_on_the_way"),
      icon: "fa-route",
      tone: "primary",
    },
    on_the_way: {
      action: "start",
      label: t("technician_tasks.action_start"),
      icon: "fa-play",
      tone: "primary",
    },
    waiting_parts: {
      action: "start",
      label: t("technician_tasks.action_resume"),
      icon: "fa-play",
      tone: "primary",
    },
    in_progress: null,
  };

  const tasks = ref([]);
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  });
  const isLoading = ref(false);
  const error = ref(null);

  const activeCount = computed(
    () =>
      tasks.value.filter(
        (t) => !["approved", "rejected", "cancelled"].includes(t.status),
      ).length,
  );

  async function fetchTasks(page = 1) {
    isLoading.value = true;
    error.value = null;

    try {
      const { data } = await technicianTaskService.list({ page });
      const payload = data.data;

      tasks.value = payload.data ?? payload;
      pagination.value = {
        current_page: payload.current_page ?? 1,
        last_page: payload.last_page ?? 1,
        total: payload.total ?? tasks.value.length,
        per_page: payload.per_page ?? 15,
      };
    } catch (err) {
      error.value = err.response?.data?.message ?? t("technician_tasks.load_error");
    } finally {
      isLoading.value = false;
    }
  }

  function replaceInList(updated) {
    const index = tasks.value.findIndex((t) => t.id === updated.id);
    if (index !== -1) tasks.value[index] = updated;
  }

  const actingId = ref(null);
  const actionError = ref(null);

  async function performAction(taskId, actionName, payload) {
    actingId.value = taskId;
    actionError.value = null;

    try {
      const { data } = await technicianTaskService[actionName](taskId, payload);
      replaceInList(data.data);
      return true;
    } catch (err) {
      actionError.value = err.response?.data?.message ?? t("technician_tasks.action_error");
      return false;
    } finally {
      actingId.value = null;
    }
  }

  return {
    tasks,
    pagination,
    isLoading,
    error,
    activeCount,
    fetchTasks,
    NEXT_ACTION,
    CANCELLABLE_STATUSES,
    actingId,
    actionError,
    performAction,
  };
}
