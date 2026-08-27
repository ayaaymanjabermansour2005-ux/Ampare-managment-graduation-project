<script setup>
import { ref, reactive, onMounted, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useTechnicianTasks } from "@/composables/useTechnicianTasks";
import { Check, Clock, FlagTriangleRight, Star } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const { t } = useI18n();

const {
  tasks,
  pagination,
  isLoading,
  error,
  activeCount,
  fetchTasks,
  NEXT_ACTION,
  actingId,
  actionError,
  performAction,
} = useTechnicianTasks();
const statusFilter = ref("active");

const STATUS_TONES = {
  pending: "bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400",
  assigned: "bg-info-bg text-info",
  on_the_way: "bg-info-bg text-info",
  in_progress: "bg-warning-bg text-warning",
  waiting_parts: "bg-warning-bg text-warning",
  submitted: "bg-secondary-50 text-secondary-600",
  approved: "bg-success-bg text-success",
  rejected: "bg-danger-bg text-danger",
  cancelled: "bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400",
};

const filtered = computed(() => {
  if (statusFilter.value === "all") return tasks.value;
  if (statusFilter.value === "active")
    return tasks.value.filter(
      (t) => !["approved", "rejected", "cancelled"].includes(t.status),
    );
  return tasks.value.filter((t) => t.status === statusFilter.value);
});

async function handleQuickAction(task, actionKey) {
  await performAction(task.id, actionKey);
}

const submitTarget = ref(null);
const submitNotes = ref("");

async function handleSubmit() {
  if (!submitNotes.value.trim()) return;
  const ok = await performAction(
    submitTarget.value.id,
    "submit",
    submitNotes.value,
  );
  if (ok) {
    submitTarget.value = null;
    submitNotes.value = "";
  }
}

onMounted(() => fetchTasks());
</script>

<template>
  <div class="space-y-6">
    <div>
      <p class="text-xs font-medium text-secondary-600 tracking-wide mb-1">
        {{ t("technician_tasks.eyebrow") }}
      </p>
      <h1 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">{{ t("technician_portal.nav_tasks") }}</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        <span class="font-mono font-data font-medium text-primary-600">{{
          activeCount
        }}</span>
        {{ t("technician_tasks.active_tasks_suffix") }}
      </p>
    </div>

    <div class="flex gap-2 overflow-x-auto">
      <button
        v-for="opt in [
          { v: 'active', l: t('technician_tasks.filter_active') },
          { v: 'submitted', l: t('technician_tasks.filter_submitted') },
          { v: 'approved', l: t('technician_tasks.filter_completed') },
          { v: 'all', l: t('common.all') },
        ]"
        :key="opt.v"
        type="button"
        @click="statusFilter = opt.v"
        class="shrink-0 px-4 py-2 rounded-lg text-xs font-medium border transition"
        :class="
          statusFilter === opt.v
            ? 'bg-primary-500 text-white border-primary-500'
            : 'bg-surface dark:bg-[#1c1e20] text-gray-600 dark:text-gray-300 border-border dark:border-white/10 hover:border-primary-300'
        "
      >
        {{ opt.l }}
      </button>
    </div>

    <div
      v-if="actionError"
      class="bg-danger-bg text-danger text-sm rounded-lg p-3"
    >
      {{ actionError }}
    </div>

    <div v-if="isLoading" class="space-y-3">
      <div
        v-for="i in 4"
        :key="i"
        class="h-28 rounded-card bg-gray-50 dark:bg-white/5 animate-pulse"
      ></div>
    </div>

    <div
      v-else-if="error"
      class="bg-danger-bg text-danger text-sm rounded-lg p-6 text-center"
    >
      {{ error }}
    </div>

    <div
      v-else-if="filtered.length === 0"
      class="text-center py-16 bg-surface dark:bg-[#1c1e20] rounded-card border border-dashed border-border dark:border-white/10"
    >
      <div
        class="w-14 h-14 mx-auto mb-4 rounded-full bg-success-bg flex items-center justify-center"
      >
        <Check class="text-2xl text-success" aria-hidden="true" />
      </div>
      <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-1.5">
        {{ t("technician_tasks.empty_title") }}
      </h3>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="task in filtered"
        :key="task.id"
        class="bg-surface dark:bg-[#1c1e20] rounded-card border border-border dark:border-white/10 p-4"
        :class="{ 'opacity-50 pointer-events-none': actingId === task.id }"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h3 class="font-semibold text-gray-700 dark:text-gray-200">
                {{ task.generator_name ?? t("technician_tasks.default_generator_label") }}
              </h3>
              <span
                class="text-[11px] rounded-full px-2 py-0.5"
                :class="STATUS_TONES[task.status]"
              >
                {{ task.status_label }}
              </span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ task.type_label }}</p>
          </div>
        </div>

        <p
          v-if="task.instructions"
          class="text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-white/5 rounded-lg p-3 mt-3"
        >
          {{ task.instructions }}
        </p>
        <p
          v-if="task.rejection_reason"
          class="text-sm text-danger bg-danger-bg rounded-lg p-3 mt-3"
        >
          <strong>{{ t("technician_tasks.previous_rejection_reason_label") }}</strong> {{ task.rejection_reason }}
        </p>

        <!-- FIX: completion_notes و rating كانا موجودين بالـ Resource لكن غير
             معروضين بأي واجهة — الفني كان يكتب ملاحظات الإنهاء بدون ما يشوفها
             بعدين، وتقييم صاحب المولد كان يوصل بس للباك إند بدون أي feedback مرئي. -->
        <p
          v-if="task.status === 'submitted' && task.completion_notes"
          class="text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-white/5 rounded-lg p-3 mt-3"
        >
          <strong>{{ t("technician_tasks.completion_notes_label") }}</strong> {{ task.completion_notes }}
        </p>
        <div
          v-if="task.status === 'approved' && task.rating"
          class="text-sm bg-success-bg text-success rounded-lg p-3 mt-3"
        >
          <div class="flex items-center gap-1">
            <strong>{{ t("technician_tasks.owner_rating_label") }}</strong>
            <span class="flex items-center gap-0.5" dir="ltr">
              <Star
                v-for="star in 5"
                :key="star"
                class="text-xs"
                :class="star <= task.rating.rating ? 'text-warning' : 'text-gray-300 dark:text-gray-600'"
                fill="currentColor"
                aria-hidden="true"
              />
            </span>
          </div>
          <p v-if="task.rating.comment" class="mt-1">{{ task.rating.comment }}</p>
        </div>

        <div class="flex items-center gap-2 mt-3 pt-3 border-t border-border dark:border-white/10">
          <!-- إجراء مفرد (assigned / on_the_way / waiting_parts) -->
          <button
            v-if="NEXT_ACTION[task.status]"
            type="button"
            @click="handleQuickAction(task, NEXT_ACTION[task.status].action)"
            class="flex-1 inline-flex items-center justify-center gap-2 py-2 rounded-lg text-sm font-semibold text-white bg-primary-500 hover:bg-primary-600 transition"
          >
            <AppIcon :name="NEXT_ACTION[task.status].icon" />
            {{ NEXT_ACTION[task.status].label }}
          </button>

          <!-- حالة "قيد التنفيذ" — خيارين -->
          <template v-if="task.status === 'in_progress'">
            <button
              type="button"
              @click="handleQuickAction(task, 'waitingParts')"
              class="flex-1 py-2 rounded-lg text-sm font-medium text-warning border border-warning/30 hover:bg-warning-bg transition"
            >
              <Clock class="text-xs me-1" aria-hidden="true" /> {{ t("technician_tasks.waiting_parts_button") }}
            </button>
            <button
              type="button"
              @click="submitTarget = task"
              class="flex-1 py-2 rounded-lg text-sm font-semibold text-white bg-success hover:bg-success/90 transition"
            >
              <FlagTriangleRight class="text-xs me-1" aria-hidden="true" /> {{ t("technician_tasks.finish_and_submit_button") }}
            </button>
          </template>

          <!-- FIX: زر الإلغاء اتشال — TechnicianTaskPolicy::cancel()
                         يسمح فقط للأدمن أو صاحب المولد، أبداً للفني. الزر
                         كان موجود بالغلط وكان رح يرجّع 403 دايماً لو ضغط
                         عليه الفني. الإلغاء الآن بس من شاشة إدارة صاحب
                         المولد (لو بُنيت لاحقاً). -->
        </div>
      </div>
    </div>

    <div v-if="pagination.last_page > 1" class="flex justify-center gap-2 pt-2">
      <button
        v-for="page in pagination.last_page"
        :key="page"
        type="button"
        @click="fetchTasks(page)"
        class="w-9 h-9 rounded-lg text-sm font-mono font-data transition"
        :class="
          page === pagination.current_page
            ? 'bg-primary-500 text-white'
            : 'bg-surface dark:bg-[#1c1e20] border border-border dark:border-white/10 text-gray-600 dark:text-gray-300 hover:border-primary-300'
        "
      >
        {{ page }}
      </button>
    </div>

    <!-- فورم إنهاء المهمة -->
    <Teleport to="body">
      <div
        v-if="submitTarget"
        class="fixed inset-0 z-50 bg-gray-700/40 backdrop-blur-[2px] flex items-center justify-center p-4"
        @click.self="submitTarget = null"
      >
        <div class="bg-surface dark:bg-[#1c1e20] rounded-card p-6 max-w-sm w-full max-h-[90vh] overflow-y-auto">
          <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-1">{{ t("technician_tasks.submit_modal_title") }}</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
            {{ t("technician_tasks.submit_modal_desc") }}
          </p>
          <textarea
            v-model="submitNotes"
            rows="4"
            required
            maxlength="2000"
            :placeholder="t('technician_tasks.submit_notes_placeholder')"
            class="w-full rounded-lg border border-border dark:border-white/10 bg-transparent px-3.5 py-2.5 text-sm text-gray-700 dark:text-gray-200 resize-none focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
          ></textarea>
          <div class="flex gap-3 mt-4">
            <button
              type="button"
              @click="submitTarget = null"
              class="flex-1 py-2.5 rounded-lg text-sm text-gray-600 dark:text-gray-300 border border-border dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/5 transition"
            >
              {{ t("common.cancel") }}
            </button>
            <button
              type="button"
              @click="handleSubmit"
              :disabled="!submitNotes.trim() || actingId === submitTarget.id"
              class="flex-1 py-2.5 rounded-lg text-sm font-semibold text-white bg-success hover:bg-success/90 disabled:opacity-50 transition"
            >
              {{ t("technician_tasks.submit_for_review_button") }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
