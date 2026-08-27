<script setup>
import { reactive, ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { CircleAlert, LoaderCircle, PlugZap, Plus, Send, TriangleAlert, X, Zap } from "@lucide/vue";


const props = defineProps({
  faults: { type: Array, required: true },
  isLoading: { type: Boolean, required: true },
  error: { type: [String, null], default: null },
  myGeneratorId: { type: [Number, String, null], default: null },
  myGeneratorName: { type: [String, null], default: null },
  isSubmitting: { type: Boolean, required: true },
  submitError: { type: [Object, null], default: null },
});

const emit = defineEmits(["submit"]);

const { t } = useI18n();

const STATUS_LABELS = computed(() => ({
  pending_verification: t("support_center_page.fault_status_pending_verification"),
  verified: t("support_center_page.fault_status_verified"),
  rejected: t("support_center_page.fault_status_rejected"),
  in_repair: t("support_center_page.fault_status_in_repair"),
  resolved: t("support_center_page.fault_status_resolved"),
  closed: t("support_center_page.fault_status_closed"),
}));
const STATUS_TONES = {
  pending_verification: "chip-warning",
  verified: "chip-info",
  rejected: "chip-danger",
  in_repair: "chip-warning",
  resolved: "chip-success",
  closed: "chip-neutral",
};
const PRIORITY_LABELS = computed(() => ({
  low: t("support_center_page.priority_low"),
  medium: t("support_center_page.priority_medium"),
  high: t("support_center_page.priority_high"),
  critical: t("support_center_page.priority_critical"),
}));
const PRIORITY_OPTIONS = computed(() => [
  { value: "low", label: t("support_center_page.priority_low") },
  { value: "medium", label: t("support_center_page.priority_medium") },
  { value: "high", label: t("support_center_page.priority_high") },
  { value: "critical", label: t("support_center_page.priority_critical_option") },
]);

const showForm = ref(false);
const form = reactive({ title: "", description: "", priority: "medium" });

function handleSubmit() {
  emit("submit", { ...form });
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t("support_center_page.faults_intro") }}</p>
      <button
        v-if="myGeneratorId"
        type="button"
        @click="showForm = !showForm"
        class="btn-fill-brand shrink-0"
        :class="{ 'btn-fill-brand--danger': showForm }"
      >
        <X aria-hidden="true" v-if="showForm" /><Plus aria-hidden="true" v-else />
        <span>{{ showForm ? t("common.cancel") : t("support_center_page.report_fault_button") }}</span>
      </button>
    </div>

    <section v-if="!myGeneratorId && !isLoading" class="alert-box">
      <CircleAlert class="shrink-0" aria-hidden="true" />
      <span>{{ t("support_center_page.fault_subscription_required_notice") }}</span>
    </section>

    <section v-if="showForm" class="glass-card p-5 lg:p-6">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div v-if="submitError?.message" class="alert-box">
          <CircleAlert class="shrink-0" aria-hidden="true" />
          <span>{{ submitError.message }}</span>
        </div>

        <div class="form-section">
          <div class="form-section-head">
            <span class="form-section-icon"><Zap aria-hidden="true" /></span>
            <span>{{ t("support_center_page.fault_details_section_title") }}</span>
          </div>

          <p class="text-[11px] text-[#9a9d97] -mt-1"><PlugZap class="me-1" aria-hidden="true" />{{ myGeneratorName }}</p>

          <div class="grid sm:grid-cols-2 gap-3.5">
            <div class="sm:col-span-2">
              <label class="field-label">{{ t("support_center_page.fault_title_label") }}</label>
              <input
                v-model="form.title"
                type="text"
                required
                maxlength="150"
                :placeholder="t('support_center_page.fault_title_placeholder')"
                class="field-input"
              />
            </div>
            <div class="sm:col-span-2">
              <label class="field-label">{{ t("support_center_page.priority_label") }}</label>
              <AppDropdownSelect v-model="form.priority" :options="PRIORITY_OPTIONS" variant="field" width-class="w-full" match-trigger-width />
            </div>
          </div>

          <div>
            <label class="field-label">{{ t("support_center_page.details_label") }}</label>
            <textarea
              v-model="form.description"
              required
              rows="3"
              maxlength="2000"
              class="field-input resize-none"
            ></textarea>
          </div>
        </div>

        <button type="submit" :disabled="isSubmitting" class="btn-fill-brand w-full justify-center">
          <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSubmitting" /><Send aria-hidden="true" v-else />
          <span>{{ isSubmitting ? t("support_center_page.sending_ellipsis") : t("support_center_page.submit_fault_report") }}</span>
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

    <section v-else-if="faults.length === 0" class="glass-card p-10 text-center max-w-md mx-auto">
      <span class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-2xl">
        <Zap aria-hidden="true" />
      </span>
      <h3 class="font-extrabold text-[14px] mb-1">{{ t("support_center_page.no_faults_title") }}</h3>
      <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t("support_center_page.no_faults_message") }}</p>
    </section>

    <div v-else class="space-y-3">
      <div
        v-for="fault in faults"
        :key="fault.id"
        class="glass-card hoverable p-4 lg:p-5"
        :class="{ 'border-[#D9534F]/30': fault.status === 'rejected' }"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-start gap-3 min-w-0">
            <span class="w-9 h-9 rounded-full bg-gradient-to-br from-[#52733D] to-[#8A6D1F] flex items-center justify-center text-white text-[13px] shrink-0">
              <Zap aria-hidden="true" />
            </span>
            <div class="min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="font-extrabold text-[13.5px]">{{ fault.title }}</h3>
                <span class="text-[11px] text-[#9a9d97]">({{ PRIORITY_LABELS[fault.priority] ?? fault.priority }})</span>
                <span class="status-chip" :class="STATUS_TONES[fault.status] ?? 'chip-neutral'">
                  {{ STATUS_LABELS[fault.status] ?? fault.status }}
                </span>
              </div>
              <p class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1">{{ fault.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>