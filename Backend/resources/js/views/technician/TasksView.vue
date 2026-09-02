<script setup>
import { ref, reactive, onMounted, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { useTechnicianTasks } from "@/composables/useTechnicianTasks";
import { vReveal } from "@/directives/reveal";
import { Check, Clock, ClipboardList, FlagTriangleRight, LoaderCircle, Star, X } from "@lucide/vue";
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

watch(statusFilter, (val) => {
  fetchTasks(1, val === "active" || val === "all" ? null : val);
});

const STATUS_CHIP = {
  pending: "chip-neutral",
  assigned: "chip-info",
  on_the_way: "chip-info",
  in_progress: "chip-warning",
  waiting_parts: "chip-warning",
  submitted: "chip-neutral",
  approved: "chip-success",
  rejected: "chip-danger",
  cancelled: "chip-neutral",
};

const FILTERS = computed(() => [
  { v: "active", l: t("technician_tasks.filter_active") },
  { v: "submitted", l: t("technician_tasks.filter_submitted") },
  { v: "approved", l: t("technician_tasks.filter_completed") },
  { v: "all", l: t("common.all") },
]);

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

onMounted(() => fetchTasks(1, null));
</script>

<template>
  <div class="space-y-5">
    <!-- ===== رأس الصفحة ===== -->
    <section v-reveal class="glass-card p-5 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-56 h-56 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[90px] pointer-events-none"></div>
      <div class="relative flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
          <span class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-base shrink-0">
            <ClipboardList aria-hidden="true" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] font-bold text-[#8A6D1F] dark:text-[#D4AF37] tracking-wide mb-0.5">
              {{ t("technician_tasks.eyebrow") }}
            </p>
            <h1 class="text-lg font-extrabold truncate">{{ t("technician_portal.nav_tasks") }}</h1>
          </div>
        </div>
        <span class="status-chip chip-neutral shrink-0">
          <span class="font-mono font-extrabold">{{ activeCount }}</span>
          {{ t("technician_tasks.active_tasks_suffix") }}
        </span>
      </div>
    </section>

    <!-- ===== فلاتر الحالة ===== -->
    <div class="flex gap-1.5 overflow-x-auto pb-0.5">
      <button
        v-for="opt in FILTERS"
        :key="opt.v"
        type="button"
        @click="statusFilter = opt.v"
        class="shrink-0 px-4 py-2 rounded-full text-[12px] font-bold transition-colors"
        :class="
          statusFilter === opt.v
            ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-sm'
            : 'bg-[#f4efe5]/70 dark:bg-white/5 text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/10'
        "
      >
        {{ opt.l }}
      </button>
    </div>

    <div v-if="actionError" class="alert-box"><X class="shrink-0" aria-hidden="true" /> {{ actionError }}</div>

    <!-- ===== حالة التحميل ===== -->
    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="h-28 rounded-2xl bg-[#f4efe5]/60 dark:bg-white/5 animate-pulse"></div>
    </div>

    <!-- ===== حالة الخطأ ===== -->
    <div v-else-if="error" class="glass-card p-6 text-center text-[12.5px] text-[#D9534F]">
      {{ error }}
    </div>

    <!-- ===== حالة فارغة ===== -->
    <div v-else-if="filtered.length === 0" class="glass-card border-dashed p-12 text-center">
      <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center text-[#52733D] dark:text-[#8cc35a]">
        <Check class="text-2xl" aria-hidden="true" />
      </div>
      <h3 class="text-[13.5px] font-bold mb-1.5">{{ t("technician_tasks.empty_title") }}</h3>
    </div>

    <!-- ===== قائمة المهام ===== -->
    <div v-else class="space-y-3">
      <section
        v-reveal
        v-for="task in filtered"
        :key="task.id"
        class="glass-card p-4 sm:p-5 transition"
        :class="{ 'opacity-50 pointer-events-none': actingId === task.id }"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <h3 class="font-bold text-[13px]">
                {{ task.generator_name ?? t("technician_tasks.default_generator_label") }}
              </h3>
              <span class="status-chip" :class="STATUS_CHIP[task.status]">{{ task.status_label }}</span>
            </div>
            <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1">{{ task.type_label }}</p>
          </div>
        </div>

        <p v-if="task.instructions" class="text-[12.5px] bg-[#EBF1E7] dark:bg-white/5 rounded-xl p-3 mt-3">
          {{ task.instructions }}
        </p>
        <p v-if="task.rejection_reason" class="text-[12.5px] text-[#D9534F] bg-[#D9534F]/10 border border-[#D9534F]/20 rounded-xl p-3 mt-3">
          <strong>{{ t("technician_tasks.previous_rejection_reason_label") }}</strong> {{ task.rejection_reason }}
        </p>
        <p
          v-if="task.status === 'submitted' && task.completion_notes"
          class="text-[12.5px] bg-[#EBF1E7] dark:bg-white/5 rounded-xl p-3 mt-3"
        >
          <strong>{{ t("technician_tasks.completion_notes_label") }}</strong> {{ task.completion_notes }}
        </p>
        <div
          v-if="task.status === 'approved' && task.rating"
          class="text-[12.5px] bg-[#28A745]/10 border border-[#28A745]/25 text-[#1f7a37] dark:text-[#7fe19c] rounded-xl p-3 mt-3"
        >
          <div class="flex items-center gap-1.5">
            <strong>{{ t("technician_tasks.owner_rating_label") }}</strong>
            <span class="flex items-center gap-0.5" dir="ltr">
              <Star
                v-for="star in 5"
                :key="star"
                class="text-xs"
                :class="star <= task.rating.rating ? 'text-[#D4AF37]' : 'text-[#e7e2d6] dark:text-white/15'"
                fill="currentColor"
                aria-hidden="true"
              />
            </span>
          </div>
          <p v-if="task.rating.comment" class="mt-1">{{ task.rating.comment }}</p>
        </div>

        <div class="flex items-center gap-2 mt-4 pt-3.5 border-t border-[#f0ece0] dark:border-white/5">
          <!-- إجراء مفرد (assigned / on_the_way / waiting_parts) -->
          <button
            v-if="NEXT_ACTION[task.status]"
            type="button"
            @click="handleQuickAction(task, NEXT_ACTION[task.status].action)"
            class="btn-fill-brand flex-1 justify-center"
          >
            <AppIcon :name="NEXT_ACTION[task.status].icon" />
            {{ NEXT_ACTION[task.status].label }}
          </button>

          <!-- حالة "قيد التنفيذ" — خيارين -->
          <template v-if="task.status === 'in_progress'">
            <button
              type="button"
              @click="handleQuickAction(task, 'waitingParts')"
              class="flex-1 py-2.5 rounded-full text-[12.5px] font-bold text-[#8A6D1F] border border-[#8A6D1F]/35 hover:bg-[#8A6D1F]/10 transition"
            >
              <Clock class="text-xs me-1" aria-hidden="true" /> {{ t("technician_tasks.waiting_parts_button") }}
            </button>
            <button
              type="button"
              @click="submitTarget = task"
              class="flex-1 py-2.5 rounded-full text-[12.5px] font-bold text-white bg-[#28A745] hover:bg-[#28A745]/90 transition"
            >
              <FlagTriangleRight class="text-xs me-1" aria-hidden="true" /> {{ t("technician_tasks.finish_and_submit_button") }}
            </button>
          </template>
        </div>
      </section>
    </div>

    <!-- ===== ترقيم الصفحات ===== -->
    <div v-if="pagination.last_page > 1" class="flex justify-center gap-2 pt-1">
      <button
        v-for="page in pagination.last_page"
        :key="page"
        type="button"
        @click="fetchTasks(page, statusFilter === 'active' || statusFilter === 'all' ? null : statusFilter)"
        class="w-9 h-9 rounded-full text-[12.5px] font-mono font-bold transition"
        :class="
          page === pagination.current_page
            ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white shadow-sm'
            : 'bg-[#f4efe5]/70 dark:bg-white/5 text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-white/60 dark:hover:bg-white/10'
        "
      >
        {{ page }}
      </button>
    </div>

    <!-- ===== نافذة إنهاء المهمة ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="submitTarget" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="submitTarget = null">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--green">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><FlagTriangleRight aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title">{{ t("technician_tasks.submit_modal_title") }}</h3>
                  <p class="modal-head-brand__subtitle">{{ t("technician_tasks.submit_modal_desc") }}</p>
                </div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="submitTarget = null" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>

            <div class="p-5">
              <label class="field-label">{{ t("technician_tasks.submit_notes_placeholder") }}</label>
              <textarea
                v-model="submitNotes"
                rows="4"
                required
                maxlength="2000"
                :placeholder="t('technician_tasks.submit_notes_placeholder')"
                class="field-input resize-none"
              ></textarea>
            </div>

            <div class="modal-footer-brand">
              <button type="button" @click="submitTarget = null" class="btn-outline-brand">{{ t("common.cancel") }}</button>
              <button
                type="button"
                @click="handleSubmit"
                :disabled="!submitNotes.trim() || actingId === submitTarget?.id"
                class="btn-fill-brand"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="actingId === submitTarget?.id" /><Check aria-hidden="true" v-else />
                {{ t("technician_tasks.submit_for_review_button") }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>