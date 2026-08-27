import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import technicianTaskService from "@/services/technicianTaskService";

export function useOwnerTechnicianTasks() {
  const { t } = useI18n();
  const tasks = ref([]);
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    total: 0,
    per_page: 15,
  });
  const isLoading = ref(false);
  const error = ref(null);

  const needsReviewTasks = computed(() =>
    tasks.value.filter((t) => t.status === "submitted"),
  );
  const needsRatingTasks = computed(() =>
    tasks.value.filter((t) => t.status === "approved" && !t.rating),
  );
  const otherTasks = computed(() =>
    tasks.value.filter(
      (t) =>
        !needsReviewTasks.value.includes(t) &&
        !needsRatingTasks.value.includes(t),
    ),
  );

  async function fetchTasks(page = 1) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await technicianTaskService.list({ page });
      const payload = data.data;
      const meta = payload.meta ?? payload;

      tasks.value = payload.data ?? payload;
      pagination.value = {
        current_page: meta.current_page ?? 1,
        last_page: meta.last_page ?? 1,
        total: meta.total ?? tasks.value.length,
        per_page: meta.per_page ?? 15,
      };
    } catch (err) {
      error.value = err.response?.data?.message ?? t("owner_technician_tasks.load_error");
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

  async function reviewTask(taskId, decision, rejectionReason = null) {
    actingId.value = taskId;
    actionError.value = null;
    try {
      const { data } = await technicianTaskService.review(taskId, {
        decision,
        rejection_reason: rejectionReason,
      });
      replaceInList(data.data);
      return true;
    } catch (err) {
      actionError.value =
        err.response?.data?.message ?? t("owner_technician_tasks.review_error");
      return false;
    } finally {
      actingId.value = null;
    }
  }

  async function rateTask(taskId, rating, comment) {
    actingId.value = taskId;
    actionError.value = null;
    try {
      const { data } = await technicianTaskService.rate(taskId, {
        rating,
        comment,
      });
      const task = tasks.value.find((t) => t.id === taskId);
      if (task) task.rating = data.data;
      return true;
    } catch (err) {
      actionError.value = err.response?.data?.message ?? t("owner_technician_tasks.rate_error");
      return false;
    } finally {
      actingId.value = null;
    }
  }

  const isCreating = ref(false);
  const createError = ref(null);

  async function createTask(payload) {
    isCreating.value = true;
    createError.value = null;
    try {
      await technicianTaskService.create(payload);
      await fetchTasks(1);
      return true;
    } catch (err) {
      createError.value = err.response?.data?.message ?? t("owner_technician_tasks.create_error");
      return false;
    } finally {
      isCreating.value = false;
    }
  }

  const isAssigning = ref(false);
  const assignError = ref(null);

  async function assignTask(taskId, technicianId) {
    isAssigning.value = true;
    assignError.value = null;
    try {
      const { data } = await technicianTaskService.assign(taskId, { technician_id: technicianId });
      replaceInList(data.data);
      return true;
    } catch (err) {
      assignError.value = err.response?.data?.message ?? t("owner_technician_tasks.assign_error");
      return false;
    } finally {
      isAssigning.value = false;
    }
  }

  return {
    tasks,
    pagination,
    isLoading,
    error,
    needsReviewTasks,
    needsRatingTasks,
    otherTasks,
    actingId,
    actionError,
    fetchTasks,
    reviewTask,
    rateTask,
    isCreating,
    createError,
    createTask,
    isAssigning,
    assignError,
    assignTask,
  };
}
