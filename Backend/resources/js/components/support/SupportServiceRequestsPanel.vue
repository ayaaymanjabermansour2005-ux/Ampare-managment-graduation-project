<script setup>
import { reactive, ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { Ban, CircleAlert, ClipboardList, FilePenLine, Info, LoaderCircle, Plus, Send, TriangleAlert, X } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const props = defineProps({
  requests: { type: Array, required: true },
  isLoading: { type: Boolean, required: true },
  error: { type: [String, null], default: null },
  mySubscriptionId: { type: [Number, String, null], default: null },
  isSubmitting: { type: Boolean, required: true },
  submitError: { type: [Object, null], default: null },
  cancellingId: { type: [Number, String, null], default: null },
});

const emit = defineEmits(["submit", "cancel"]);

const { t } = useI18n();

const TYPE_LABELS = computed(() => ({
  event: t("support_center_page.request_type_event"),
  extra_capacity: t("support_center_page.request_type_extra_capacity"),
  extra_hours: t("support_center_page.request_type_extra_hours"),
  maintenance: t("support_center_page.request_type_maintenance"),
  other: t("support_center_page.request_type_other"),
}));
const TYPE_ICONS = {
  event: "fa-champagne-glasses",
  extra_capacity: "fa-bolt",
  extra_hours: "fa-clock",
  maintenance: "fa-screwdriver-wrench",
  other: "fa-ellipsis",
};
const EVENT_TYPE_LABELS = computed(() => ({
  wedding: t("support_center_page.event_type_wedding"),
  exam: t("support_center_page.event_type_exam"),
  religious_event: t("support_center_page.event_type_religious_event"),
  family_event: t("support_center_page.event_type_family_event"),
  other: t("support_center_page.event_type_other"),
}));
const TYPE_OPTIONS = computed(() => Object.entries(TYPE_LABELS.value).map(([value, label]) => ({ value, label })));
const EVENT_TYPE_OPTIONS = computed(() => Object.entries(EVENT_TYPE_LABELS.value).map(([value, label]) => ({ value, label })));

const STATUS_LABELS = computed(() => ({
  pending: t("support_center_page.request_status_pending"),
  approved: t("support_center_page.request_status_approved"),
  rejected: t("support_center_page.request_status_rejected"),
  cancelled: t("support_center_page.request_status_cancelled"),
}));
const STATUS_TONES = {
  pending: "chip-warning",
  approved: "chip-success",
  rejected: "chip-danger",
  cancelled: "chip-neutral",
};

const showForm = ref(false);
const form = reactive({
  request_type: "event",
  event_type: "wedding",
  description: "",
  extra_capacity_kw: "",
  starts_at: "",
  ends_at: "",
});

const isEvent = computed(() => form.request_type === "event");

function handleSubmit() {
  const payload = { ...form };
  if (!isEvent.value) delete payload.event_type;
  if (!payload.extra_capacity_kw) delete payload.extra_capacity_kw;
  emit("submit", payload);
}
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t("support_center_page.requests_intro") }}</p>
      <button
        v-if="mySubscriptionId"
        type="button"
        @click="showForm = !showForm"
        class="btn-fill-brand shrink-0"
        :class="{ 'btn-fill-brand--danger': showForm }"
      >
        <X aria-hidden="true" v-if="showForm" /><Plus aria-hidden="true" v-else />
        <span>{{ showForm ? t("common.cancel") : t("support_center_page.new_request_button") }}</span>
      </button>
    </div>

    <section v-if="!mySubscriptionId && !isLoading" class="alert-box">
      <CircleAlert class="shrink-0" aria-hidden="true" />
      <span>{{ t("support_center_page.request_subscription_required_notice") }}</span>
    </section>

    <section v-if="showForm" class="glass-card p-5 lg:p-6">
      <form @submit.prevent="handleSubmit" class="space-y-4">
        <div v-if="submitError?.message" class="alert-box">
          <CircleAlert class="shrink-0" aria-hidden="true" />
          <span>{{ submitError.message }}</span>
        </div>

        <div class="form-section">
          <div class="form-section-head">
            <span class="form-section-icon"><FilePenLine aria-hidden="true" /></span>
            <span>{{ t("support_center_page.request_details_section_title") }}</span>
          </div>

          <div class="grid sm:grid-cols-2 gap-3.5">
            <div>
              <label class="field-label">{{ t("support_center_page.request_type_label") }}</label>
              <AppDropdownSelect v-model="form.request_type" :options="TYPE_OPTIONS" variant="field" width-class="w-full" match-trigger-width />
            </div>
            <div v-if="isEvent">
              <label class="field-label">{{ t("support_center_page.event_type_label") }}</label>
              <AppDropdownSelect v-model="form.event_type" :options="EVENT_TYPE_OPTIONS" variant="field" width-class="w-full" match-trigger-width />
            </div>
            <div v-if="form.request_type === 'extra_capacity'">
              <label class="field-label">{{ t("support_center_page.extra_capacity_label") }}</label>
              <input v-model="form.extra_capacity_kw" type="number" step="0.1" min="0.1" class="field-input font-mono" />
            </div>
            <div>
              <label class="field-label">{{ t("support_center_page.from_label") }}</label>
              <input v-model="form.starts_at" type="datetime-local" required class="field-input" />
            </div>
            <div>
              <label class="field-label">{{ t("support_center_page.to_label") }}</label>
              <input v-model="form.ends_at" type="datetime-local" required class="field-input" />
            </div>
          </div>

          <div>
            <label class="field-label">{{ t("support_center_page.request_details_section_title") }}</label>
            <textarea v-model="form.description" required rows="3" maxlength="1000" class="field-input resize-none"></textarea>
          </div>
        </div>

        <button type="submit" :disabled="isSubmitting" class="btn-fill-brand w-full justify-center">
          <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSubmitting" /><Send aria-hidden="true" v-else />
          <span>{{ isSubmitting ? t("support_center_page.sending_ellipsis") : t("support_center_page.submit_request_button") }}</span>
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

    <section v-else-if="requests.length === 0" class="glass-card p-10 text-center max-w-md mx-auto">
      <span class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-2xl">
        <ClipboardList aria-hidden="true" />
      </span>
      <h3 class="font-extrabold text-[14px] mb-1">{{ t("support_center_page.no_requests_title") }}</h3>
      <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ t("support_center_page.no_requests_message") }}</p>
    </section>

    <div v-else class="space-y-3">
      <div
        v-for="req in requests"
        :key="req.id"
        class="glass-card hoverable p-4 lg:p-5"
        :class="{ 'border-[#D9534F]/30': req.status === 'rejected' }"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="flex items-start gap-3 min-w-0">
            <span class="w-9 h-9 rounded-full bg-gradient-to-br from-[#52733D] to-[#8A6D1F] flex items-center justify-center text-white text-[13px] shrink-0">
              <AppIcon :name="TYPE_ICONS[req.request_type] ?? 'fa-ellipsis'" />
            </span>
            <div class="min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="font-extrabold text-[13.5px]">{{ TYPE_LABELS[req.request_type] ?? req.request_type }}</h3>
                <span v-if="req.event_type" class="text-[11px] text-[#9a9d97]">({{ EVENT_TYPE_LABELS[req.event_type] }})</span>
                <span class="status-chip" :class="STATUS_TONES[req.status]">{{ STATUS_LABELS[req.status] ?? req.status }}</span>
              </div>
              <p class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1">{{ req.description }}</p>
              <p class="text-[11px] text-[#9a9d97] mt-2 font-mono">{{ req.starts_at }} — {{ req.ends_at }}</p>
            </div>
          </div>
          <p v-if="req.fee_amount" class="font-mono font-data font-extrabold text-[14px] text-[#8A6D1F] shrink-0">
            {{ req.fee_amount }} {{ req.fee_currency }}
          </p>
        </div>

        <div v-if="req.review_note" class="flex items-start gap-2 text-[11.5px] mt-3 rounded-xl px-3.5 py-2.5" style="color: #0f6c7d; background: rgba(23,162,184,0.1)">
          <Info class="mt-0.5 shrink-0" aria-hidden="true" />
          <span>{{ req.review_note }}</span>
        </div>

        <div v-if="req.status === 'pending'" class="flex gap-2 mt-3.5 pt-3.5 border-t border-[#f0ece0] dark:border-white/10">
          <button
            type="button"
            :disabled="cancellingId === req.id"
            class="btn-fill-brand btn-fill-brand--danger flex-1 justify-center"
            @click="emit('cancel', req.id)"
          >
            <LoaderCircle class="animate-spin" aria-hidden="true" v-if="cancellingId === req.id" /><Ban aria-hidden="true" v-else />
            <span>{{ t("support_center_page.cancel_request_button") }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>