<script setup>
import { CircleAlert, FilePenLine, Info, LoaderCircle, MessageCircleMore, Plus, Send, TriangleAlert, X } from "@lucide/vue";

/**
 * SupportComplaintsPanel.vue — مكوّن عرض (Presentational) بحت.
 * لا يستدعي أي composable ولا service مباشرة؛ يستقبل كل شي عبر props
 * ويصدر الأحداث (submit/paginate) للحاوية الأب (SupportCenterView) لتنفذها.
 * الهوية البصرية موحّدة مع SupportServiceRequestsPanel.vue (glass-card,
 * status-chip, form-section, btn-fill-brand...).
 */
import { reactive, ref, computed } from "vue";
import { useI18n } from "vue-i18n";

const props = defineProps({
  complaints: { type: Array, required: true },
  pagination: { type: Object, required: true },
  isLoading: { type: Boolean, required: true },
  error: { type: [String, null], default: null },
  hasComplaints: { type: Boolean, required: true },
  isSubmittingNew: { type: Boolean, required: true },
  newComplaintError: { type: [String, null], default: null },
});

const emit = defineEmits(["submit", "paginate"]);

const { t } = useI18n();

const STATUS_LABELS = computed(() => ({
  pending: t("support_center_page.complaint_status_pending"),
  in_progress: t("support_center_page.complaint_status_in_progress"),
  resolved: t("support_center_page.complaint_status_resolved"),
}));
const STATUS_TONES = {
  pending: "chip-warning",
  in_progress: "chip-info",
  resolved: "chip-success",
};

const showNewForm = ref(false);
const newComplaintForm = reactive({ subject: "", description: "" });

function handleCreate() {
  emit("submit", { ...newComplaintForm });
}

/* الحاوية الأب هي اللي بتقرر نجاح الإرسال (بترجع true/false من الـ composable)،
 * فهون منعتمد على انتفاء الخطأ + انتهاء isSubmittingNew كإشارة غير مباشرة
 * لتفريغ الفورم. الأنضف من هيك مستقبلاً: نخلي submit تكون async ونستنى
 * نتيجتها مباشرة بدل الاعتماد على watch — لو احتجت أضيفها. */
function resetFormIfSuccess() {
  newComplaintForm.subject = "";
  newComplaintForm.description = "";
  showNewForm.value = false;
}
defineExpose({ resetFormIfSuccess });
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t("support_center_page.complaints_intro") }}</p>
      <button
        type="button"
        @click="showNewForm = !showNewForm"
        class="btn-fill-brand shrink-0"
        :class="{ 'btn-fill-brand--danger': showNewForm }"
      >
        <X aria-hidden="true" v-if="showNewForm" /><Plus aria-hidden="true" v-else />
        <span>{{ showNewForm ? t("common.cancel") : t("support_center_page.new_complaint_button") }}</span>
      </button>
    </div>

    <section v-if="showNewForm" class="glass-card p-5 lg:p-6">
      <form @submit.prevent="handleCreate" class="space-y-4">
        <div v-if="newComplaintError" class="alert-box">
          <CircleAlert class="shrink-0" aria-hidden="true" />
          <span>{{ newComplaintError }}</span>
        </div>

        <div class="form-section">
          <div class="form-section-head">
            <span class="form-section-icon"><FilePenLine aria-hidden="true" /></span>
            <span>{{ t("support_center_page.complaint_details_section_title") }}</span>
          </div>

          <div>
            <label class="field-label">{{ t("support_center_page.complaint_subject_label") }}</label>
            <input
              v-model="newComplaintForm.subject"
              type="text"
              required
              maxlength="191"
              class="field-input"
            />
          </div>
          <div>
            <label class="field-label">{{ t("support_center_page.details_label") }}</label>
            <textarea
              v-model="newComplaintForm.description"
              required
              rows="3"
              maxlength="2000"
              class="field-input resize-none"
            ></textarea>
          </div>
        </div>

        <button type="submit" :disabled="isSubmittingNew" class="btn-fill-brand w-full justify-center">
          <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSubmittingNew" /><Send aria-hidden="true" v-else />
          <span>{{ isSubmittingNew ? t("support_center_page.sending_ellipsis") : t("support_center_page.submit_complaint_button") }}</span>
        </button>
      </form>
    </section>

    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="glass-card h-24 thumb-loading"></div>
    </div>

    <section v-else-if="error" class="glass-card p-8 text-center text-[12.5px] text-[#D9534F]">
      <TriangleAlert class="text-xl mb-2 block" aria-hidden="true" />
      {{ error }}
    </section>

    <section v-else-if="!hasComplaints" class="glass-card p-10 text-center max-w-md mx-auto">
      <span class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-2xl">
        <MessageCircleMore aria-hidden="true" />
      </span>
      <h3 class="font-extrabold text-[14px] mb-1">{{ t("support_center_page.no_complaints_title") }}</h3>
      <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t("support_center_page.no_complaints_message") }}</p>
    </section>

    <div v-else class="space-y-3">
      <div
        v-for="complaint in complaints"
        :key="complaint.id"
        class="glass-card hoverable p-4 lg:p-5"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-start gap-3 min-w-0">
            <span class="w-9 h-9 rounded-full bg-gradient-to-br from-[#52733D] to-[#8A6D1F] flex items-center justify-center text-white text-[13px] shrink-0">
              <MessageCircleMore aria-hidden="true" />
            </span>
            <div class="min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="font-extrabold text-[13.5px]">{{ complaint.subject }}</h3>
                <span class="status-chip" :class="STATUS_TONES[complaint.status] ?? 'chip-neutral'">
                  {{ STATUS_LABELS[complaint.status] ?? complaint.status }}
                </span>
              </div>
              <p class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1">{{ complaint.description }}</p>
            </div>
          </div>
        </div>

        <div v-if="complaint.resolution_note" class="flex items-start gap-2 text-[11.5px] mt-3 rounded-xl px-3.5 py-2.5" style="color: #0f6c7d; background: rgba(23,162,184,0.1)">
          <Info class="mt-0.5 shrink-0" aria-hidden="true" />
          <span><strong>{{ t("support_center_page.resolution_note_label") }}</strong> {{ complaint.resolution_note }}</span>
        </div>
      </div>
    </div>

    <div v-if="pagination.last_page > 1" class="flex justify-center flex-wrap gap-2 pt-2">
      <button
        v-for="page in pagination.last_page"
        :key="page"
        type="button"
        @click="emit('paginate', page)"
        class="w-9 h-9 rounded-lg text-[12px] font-mono font-data font-bold transition-colors"
        :class="
          page === pagination.current_page
            ? 'bg-gradient-to-l from-[#3E582E] to-[#8A6D1F] text-white shadow-md'
            : 'bg-white/60 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 text-[#6B6B6B] dark:text-[#a8aaa5] hover:border-[#8A6D1F]/40'
        "
      >
        {{ page }}
      </button>
    </div>
  </div>
</template>