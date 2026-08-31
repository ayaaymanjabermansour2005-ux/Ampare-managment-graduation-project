<script setup>
import { reactive, ref, onMounted, computed } from "vue";
import { useI18n } from "vue-i18n";
import { useOwnerServiceRequests } from "@/composables/useOwnerServiceRequests";
import { usePermissions } from "@/composables/usePermissions";
import { useToastStore } from "@/stores/toast";
import { vReveal } from "@/directives/reveal";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { CalendarClock, Check, ChevronLeft, ChevronRight, CircleAlert, CircleCheck, LoaderCircle, Pencil, Save, TriangleAlert, X, Zap } from "@lucide/vue";

const { can } = usePermissions();
const toast = useToastStore();
const { t } = useI18n();

const {
  requests,
  pagination,
  isLoading,
  error,
  hasRequests,
  fetchRequests,

  reviewingId,
  isReviewing,
  reviewError,
  startReviewing,
  cancelReviewing,
  reviewRequest,
} = useOwnerServiceRequests();

const STATUS_META = {
  pending: "chip-warning",
  approved: "chip-success",
  rejected: "chip-danger",
  cancelled: "chip-neutral",
};
function statusLabel(status) {
  return t(`owner_service_requests.status.${status}`, status);
}

const REQUEST_TYPE_KEYS = {
  event: "owner_service_requests.type_event",
  extra_capacity: "owner_service_requests.type_extra_capacity",
  extra_hours: "owner_service_requests.type_extra_hours",
  maintenance: "owner_service_requests.type_maintenance",
  medical_priority: "owner_service_requests.type_medical_priority",
  other: "owner_service_requests.type_other",
};
function requestTypeLabel(type) {
  const key = REQUEST_TYPE_KEYS[type];
  return key ? t(key) : type;
}

const DECISION_OPTIONS = computed(() => [
  { value: "approved", label: t("owner_service_requests.decision_approved") },
  { value: "rejected", label: t("owner_service_requests.decision_rejected") },
]);

const reviewForm = reactive({ decision: "approved", review_note: "", fee_amount: "", fee_currency: "ILS" });

function openReviewForm(request) {
  reviewForm.decision = "approved";
  reviewForm.review_note = "";
  reviewForm.fee_amount = "";
  reviewForm.fee_currency = "ILS";
  startReviewing(request.id);
}

async function handleReviewSubmit(requestId) {
  const ok = await reviewRequest(requestId, { ...reviewForm });
  if (ok) {
    toast.show({
      type: "success",
      title: t("owner_service_requests.reviewed_toast_title"),
      message: reviewForm.decision === "approved" ? t("owner_service_requests.approved_toast_message") : t("owner_service_requests.rejected_toast_message"),
    });
  } else {
    toast.show({
      type: "danger",
      title: t("owner_service_requests.review_failed_title"),
      message: reviewError.value ?? t("owner_service_requests.review_error"),
    });
  }
}

onMounted(() => fetchRequests());
</script>

<template>
  <div class="space-y-6">
    <!-- ===== HEADER ===== -->
    <section v-reveal class="glass-card p-5 lg:p-6 relative overflow-hidden">
      <div class="absolute -start-16 -top-16 w-72 h-72 bg-[#D4AF37]/20 dark:bg-[#D4AF37]/25 rounded-full blur-[100px] pointer-events-none"></div>
      <div class="absolute -end-10 -bottom-16 w-72 h-72 bg-[#52733D]/20 dark:bg-[#8cc35a]/15 rounded-full blur-[100px] pointer-events-none"></div>
      <nav class="relative flex items-center justify-start gap-1.5 text-[11px] text-[#9a9d97] mb-2">
        <span>{{ t("owner_dashboard.breadcrumb") }}</span>
        <ChevronLeft class="text-[9px]" aria-hidden="true" />
        <span class="text-[#52733D] dark:text-[#8cc35a] font-bold">{{ t("owner_service_requests.title") }}</span>
      </nav>
      <div class="relative">
        <p class="text-[11px] font-bold text-[#8A6D1F] tracking-wide mb-1">{{ t("owner_service_requests.eyebrow") }}</p>
        <h1 class="text-xl lg:text-2xl font-extrabold mb-1.5 flex items-center gap-2.5">
          <span class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-base">
            <Zap aria-hidden="true" />
          </span>
          {{ t("owner_service_requests.title") }}
        </h1>
        <p class="text-[12.5px] text-[#6B6B6B] dark:text-[#aeb1ab] max-w-lg">{{ t("owner_service_requests.subtitle") }}</p>
      </div>
    </section>

    <!-- ===== LIST ===== -->
    <section v-reveal class="space-y-3">
      <div v-if="error" class="glass-card text-center py-8 text-[12px] text-[#D9534F]">{{ error }}</div>

      <div v-else-if="isLoading" class="space-y-2">
        <div v-for="i in 4" :key="i" class="h-24 rounded-2xl thumb-loading"></div>
      </div>

      <div v-else-if="!hasRequests" class="glass-card text-center py-12">
        <CircleCheck class="text-2xl text-[#c9cdc2] mb-2" aria-hidden="true" />
        <p class="text-[12px] text-[#9a9d97]">{{ t("owner_service_requests.no_requests") }}</p>
      </div>

      <div v-else class="space-y-3">
        <div v-for="request in requests" :key="request.id" class="glass-card p-4">
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="flex items-center gap-2 flex-wrap mb-1">
                <h3 class="font-bold text-[13px]">{{ requestTypeLabel(request.request_type) }}</h3>
                <span v-if="request.is_high_priority" class="status-chip chip-danger">{{ t("owner_service_requests.high_priority") }}</span>
                <span class="status-chip" :class="STATUS_META[request.status] ?? 'chip-info'">{{ statusLabel(request.status) }}</span>
              </div>
              <p class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5]">{{ request.description }}</p>
              <p class="text-[10.5px] text-[#9a9d97] mt-2 flex items-center gap-1.5 flex-wrap">
                <span>{{ t("owner_service_requests.requested_by", { name: request.requested_by_name ?? "—" }) }}</span>
                <span>— {{ request.subscription?.generator_name }}</span>
                <span v-if="request.extra_capacity_kw" class="inline-flex items-center gap-1"><Zap class="text-[9px]" aria-hidden="true" /> {{ request.extra_capacity_kw }} kW</span>
              </p>
              <p class="text-[10.5px] text-[#9a9d97] mt-1 flex items-center gap-1">
                <CalendarClock class="text-[9px]" aria-hidden="true" />
                {{ request.starts_at }} — {{ request.ends_at }}
              </p>
              <p v-if="request.review_note" class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-2 bg-[#f4efe5]/70 dark:bg-white/5 rounded-lg p-2.5">
                {{ t("owner_service_requests.review_note_prefix") }} {{ request.review_note }}
              </p>
            </div>

            <button
              v-if="can('service-requests.review') && request.status === 'pending' && reviewingId !== request.id"
              type="button" @click="openReviewForm(request)"
              class="action-btn action-btn--edit shrink-0" :title="t('owner_service_requests.review_button')"
            >
              <Pencil aria-hidden="true" />
            </button>
          </div>

          <form v-if="reviewingId === request.id" @submit.prevent="handleReviewSubmit(request.id)" class="mt-4 pt-4 border-t border-[#f0ece0] dark:border-white/5 space-y-3">
            <div v-if="reviewError" class="alert-box"><CircleAlert class="shrink-0" aria-hidden="true" /> {{ reviewError }}</div>

            <div>
              <label class="field-label">{{ t("owner_service_requests.decision_label") }}</label>
              <AppDropdownSelect
                v-model="reviewForm.decision"
                :options="DECISION_OPTIONS"
                variant="field" width-class="w-full" match-trigger-width
              />
            </div>

            <div v-if="reviewForm.decision === 'approved'">
              <label class="field-label">{{ t("owner_service_requests.fee_amount_label") }}</label>
              <input v-model="reviewForm.fee_amount" type="number" min="0" step="0.01" :placeholder="t('owner_service_requests.fee_amount_placeholder')" class="field-input" />
            </div>

            <div>
              <label class="field-label">{{ t("owner_service_requests.review_note_label") }}</label>
              <textarea v-model="reviewForm.review_note" rows="2" maxlength="1000" class="field-input resize-none"></textarea>
            </div>

            <div class="flex gap-2">
              <button type="submit" :disabled="isReviewing" class="btn-fill-brand">
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isReviewing" /><Save aria-hidden="true" v-else />
                {{ isReviewing ? t("owner_service_requests.saving_ellipsis") : t("owner_service_requests.save") }}
              </button>
              <button type="button" @click="cancelReviewing" class="btn-outline-brand">{{ t("common.cancel") }}</button>
            </div>
          </form>
        </div>
      </div>

      <div v-if="pagination.last_page > 1" class="flex flex-wrap items-center justify-between gap-2 pt-1 text-[11px] text-[#9a9d97]">
        <span>{{ t("owner_service_requests.pagination_text", { current: pagination.current_page, last: pagination.last_page, total: pagination.total }) }}</span>
        <div class="flex items-center gap-1">
          <button :aria-label="t('common.previous_page')" type="button" :disabled="pagination.current_page <= 1" @click="fetchRequests(pagination.current_page - 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronRight class="text-[10px]" aria-hidden="true" /></button>
          <button type="button" :disabled="pagination.current_page >= pagination.last_page" @click="fetchRequests(pagination.current_page + 1)" class="w-7 h-7 rounded-lg hover:bg-[#EBF1E7] dark:hover:bg-white/5 disabled:opacity-40"><ChevronLeft class="text-[10px]" aria-hidden="true" /></button>
        </div>
      </div>
    </section>
  </div>
</template>
