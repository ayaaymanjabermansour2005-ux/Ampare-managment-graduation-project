<script setup>
import { reactive, ref, computed, onMounted, watch } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { useMySubscription } from "@/composables/useMySubscription";
import { useSubscriptionMeterTransfer } from "@/composables/useSubscriptionMeterTransfer";
import { useConfirm } from "@/composables/useConfirm";
import { vReveal } from "@/directives/reveal";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { ArrowRightLeft, CircleAlert, FilePenLine, FileQuestionMark, LoaderCircle, MessageCircleMore, Send, TriangleAlert, X } from "@lucide/vue";


const emit = defineEmits(["loaded"]);

const {
  subscription,
  isLoadingInitial,
  initialError,
  hasActiveOrPendingSubscription,
  loadInitial,
  contractDownloadUrl,
} = useMySubscription();

const {
  isLoadingMeters,
  loadMeters,
  eligibleMetersFor,
  isLoadingRequests,
  loadMyRequests,
  latestRequestFor,
  isCreating,
  createError,
  createRequest,
} = useSubscriptionMeterTransfer();

const router = useRouter();
const { confirm } = useConfirm();
const { t } = useI18n();

function goToMessage() {
  router.push({
    name: "subscriber.messages",
    query: { startUserId: subscription.value?.generator?.owner_id },
  });
}

const STATUS_LABELS = computed(() => ({
  pending: t("my_subscription_page.status_pending"),
  active: t("my_subscription_page.status_active"),
  suspended: t("my_subscription_page.status_suspended"),
  cancelled: t("my_subscription_page.status_cancelled"),
  rejected: t("my_subscription_page.status_rejected"),
}));
const STATUS_TONES = {
  pending: "chip-warning",
  active: "chip-success",
  suspended: "chip-info",
  cancelled: "chip-neutral",
  rejected: "chip-danger",
};
const SCHEDULE_LABELS = computed(() => ({
  day: t("browse_generators_page.schedule_day"),
  night: t("browse_generators_page.schedule_night"),
  "24h": t("browse_generators_page.schedule_24h"),
  custom: t("browse_generators_page.schedule_custom"),
}));
const TRANSFER_STATUS_LABELS = computed(() => ({
  pending: t("my_subscription_page.transfer_status_pending"),
  approved: t("my_subscription_page.transfer_status_approved"),
  rejected: t("my_subscription_page.transfer_status_rejected"),
}));
const TRANSFER_STATUS_TONES = {
  pending: "chip-warning",
  approved: "chip-success",
  rejected: "chip-danger",
};

/* ===== طلب نقل عداد الاشتراك — Workflow حقيقي: طلب المشترك ← مراجعة
 * صاحب المولد ← تنفيذ فعلي على subscriber_meter_id عند الموافقة ===== */
const currentMeterId = computed(() => subscription.value?.subscriber_meter?.id ?? null);
const eligibleMeters = computed(() => eligibleMetersFor(currentMeterId.value));
const eligibleMeterOptions = computed(() =>
  eligibleMeters.value.map((m) => ({
    value: m.id,
    label: m.property_label ? `${m.meter_number} — ${m.property_label}` : m.meter_number,
  })),
);
const latestTransferRequest = computed(() =>
  subscription.value ? latestRequestFor(subscription.value.id) : null,
);
const canOpenTransferModal = computed(
  () =>
    subscription.value?.status === "active" &&
    eligibleMeters.value.length > 0 &&
    latestTransferRequest.value?.status !== "pending",
);

const showTransferModal = ref(false);
const transferForm = reactive({
  to_subscriber_meter_id: "",
  reason: "",
});

function openTransferModal() {
  transferForm.to_subscriber_meter_id = "";
  transferForm.reason = "";
  showTransferModal.value = true;
}
function closeTransferModal() {
  if (isCreating.value) return;
  showTransferModal.value = false;
}

async function handleTransfer() {
  if (!transferForm.to_subscriber_meter_id) return;

  const confirmed = await confirm({
    title: t("my_subscription_page.transfer_confirm_title"),
    message: t("my_subscription_page.transfer_confirm_message"),
    confirmLabel: t("my_subscription_page.transfer_confirm_send_label"),
  });
  if (!confirmed) return;

  const result = await createRequest({
    subscription_id: subscription.value.id,
    to_subscriber_meter_id: transferForm.to_subscriber_meter_id,
    reason: transferForm.reason || undefined,
  });
  if (result) {
    showTransferModal.value = false;
  }
}

/* ===== إعلام الحاوية بحالة الاشتراك — تُستخدم لتلوين الهيدر ونقطة
 * التنبيه فوق التاب، وكمان لتحديد التاب الافتراضي المفتوح ===== */
watch(
  [subscription, hasActiveOrPendingSubscription, isLoadingInitial],
  () => {
    emit("loaded", {
      status: subscription.value?.status ?? null,
      hasActiveOrPendingSubscription: hasActiveOrPendingSubscription.value,
      isLoading: isLoadingInitial.value,
    });
  },
  { immediate: true },
);

onMounted(async () => {
  await loadInitial();
  loadMeters();
  loadMyRequests();
});
</script>

<template>
  <div>
    <div v-if="isLoadingInitial" class="glass-card h-40 thumb-loading"></div>

    <section v-else-if="initialError" v-reveal class="glass-card p-8 text-center text-[12.5px] text-[#D9534F]">
      <TriangleAlert class="text-xl mb-2 block" aria-hidden="true" />
      {{ initialError }}
    </section>

    <div v-else-if="hasActiveOrPendingSubscription" v-reveal class="space-y-4">
      <section class="glass-card p-6 max-w-lg">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-extrabold text-[15px]">{{ subscription.generator?.name }}</h3>
          <span class="status-chip" :class="STATUS_TONES[subscription.status]">
            {{ STATUS_LABELS[subscription.status] ?? subscription.status }}
          </span>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="info-row-simple flex-col !items-start">
            <span class="text-[10.5px]">{{ t("my_subscription_page.agreed_price_label") }}</span>
            <b class="font-mono font-data text-[13px]">{{ subscription.agreed_price_per_kw }} {{ subscription.currency }}/kW</b>
          </div>
          <div class="info-row-simple flex-col !items-start">
            <span class="text-[10.5px]">{{ t("my_subscription_page.operating_period_label") }}</span>
            <b class="text-[13px]">{{ SCHEDULE_LABELS[subscription.schedule] ?? subscription.schedule }}</b>
          </div>
          <div class="info-row-simple flex-col !items-start">
            <span class="text-[10.5px]">{{ t("my_subscription_page.start_date_label") }}</span>
            <b class="font-mono text-[13px]">{{ subscription.start_date }}</b>
          </div>
          <div v-if="subscription.next_reading_due_date" class="info-row-simple flex-col !items-start">
            <span class="text-[10.5px]">{{ t("my_subscription_page.next_reading_due_label") }}</span>
            <b class="font-mono text-[13px]">{{ subscription.next_reading_due_date }}</b>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-4 mt-5 pt-4 border-t border-[#eee8da] dark:border-white/10">
          <button type="button" @click="goToMessage" class="inline-flex items-center gap-2 text-[12px] font-bold text-[#52733D] dark:text-[#8cc35a] hover:underline">
            <MessageCircleMore aria-hidden="true" />
            <span>{{ t("my_subscription_page.message_owner_link") }}</span>
          </button>

          <a
            v-if="contractDownloadUrl"
            :href="contractDownloadUrl"
            target="_blank"
            class="inline-flex items-center gap-2 text-[12px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] hover:text-[#8A6D1F] transition"
          >
            <FilePenLine aria-hidden="true" />
            <span>{{ t("my_subscription_page.download_contract_link") }}</span>
          </a>
        </div>
      </section>

      <section v-if="canOpenTransferModal || latestTransferRequest" class="glass-card p-5 max-w-lg">
        <div class="flex items-center justify-between gap-3">
          <div>
            <h3 class="font-extrabold text-[13px] flex items-center gap-2">
              <ArrowRightLeft class="text-[#8A6D1F] text-xs" aria-hidden="true" />
              <span>{{ t("my_subscription_page.transfer_section_title") }}</span>
            </h3>
            <p class="text-[11.5px] text-[#9a9d97] mt-0.5">{{ t("my_subscription_page.transfer_section_desc") }}</p>
          </div>
          <button
            v-if="canOpenTransferModal"
            type="button"
            @click="openTransferModal"
            class="shrink-0 text-[11.5px] font-bold text-[#8A6D1F] border border-[#8A6D1F]/30 rounded-full px-3.5 py-1.5 hover:bg-[#8A6D1F]/10 transition"
          >
            {{ t("my_subscription_page.request_transfer_toggle") }}
          </button>
        </div>

        <div v-if="latestTransferRequest" class="flex items-center justify-between gap-3 mt-4 pt-4 border-t border-[#eee8da] dark:border-white/10">
          <span class="text-[11.5px] text-[#9a9d97]">{{ t("my_subscription_page.latest_request_status_label") }}</span>
          <span class="status-chip" :class="TRANSFER_STATUS_TONES[latestTransferRequest.status]">
            {{ TRANSFER_STATUS_LABELS[latestTransferRequest.status] ?? latestTransferRequest.status }}
          </span>
        </div>
      </section>
    </div>

    <!-- ما في اشتراك فعّال/معلّق حاليًا -->
    <section v-else v-reveal class="glass-card p-10 text-center max-w-md mx-auto">
      <span class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-[#52733D] to-[#3E582E] text-white flex items-center justify-center text-2xl">
        <FileQuestionMark aria-hidden="true" />
      </span>
      <h3 class="font-extrabold text-[14px] mb-1">{{ t("my_subscription_page.no_active_subscription_title") }}</h3>
      <p class="text-[11.5px] text-[#9a9d97]">{{ t("my_subscription_page.no_active_subscription_message") }}</p>
    </section>

    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-250 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showTransferModal"
          class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
          @click.self="closeTransferModal"
        >
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-md max-h-[90vh] flex flex-col shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--gold shrink-0">
              <div class="modal-head-brand__inner flex-1">
                <span class="modal-head-brand__icon"><ArrowRightLeft aria-hidden="true" /></span>
                <div class="min-w-0 flex-1">
                  <h3 class="modal-head-brand__title truncate">{{ t("my_subscription_page.transfer_section_title") }}</h3>
                  <p class="modal-head-brand__subtitle">{{ t("my_subscription_page.transfer_section_desc") }}</p>
                </div>
              </div>
              <button type="button" @click="closeTransferModal" class="modal-head-brand__close">
                <X aria-hidden="true" />
              </button>
            </div>

            <form @submit.prevent="handleTransfer" class="p-5 space-y-3.5 overflow-y-auto">
              <div v-if="createError?.message" class="alert-box">
                <CircleAlert class="shrink-0" aria-hidden="true" />
                <span>{{ createError.message }}</span>
              </div>

              <p v-if="eligibleMeterOptions.length === 0" class="text-[12px] text-[#9a9d97]">
                {{ t("my_subscription_page.no_eligible_meters_message") }}
              </p>
              <div v-else class="form-field">
                <label>{{ t("my_subscription_page.select_new_meter_label") }}</label>
                <AppDropdownSelect
                  v-model="transferForm.to_subscriber_meter_id"
                  :options="eligibleMeterOptions"
                  variant="field"
                  :placeholder="t('my_subscription_page.select_meter_placeholder')"
                />
              </div>
              <div class="form-field">
                <label>{{ t("my_subscription_page.transfer_reason_label") }}</label>
                <textarea v-model="transferForm.reason" required rows="2" maxlength="500"></textarea>
              </div>

              <div class="modal-footer-brand !px-0 !pb-0">
                <button type="button" @click="closeTransferModal" class="btn-outline-brand">
                  {{ t("common.cancel") }}
                </button>
                <button type="submit" :disabled="isCreating || !transferForm.to_subscriber_meter_id" class="btn-fill-brand">
                  <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isCreating" /><Send aria-hidden="true" v-else />
                  <span>{{ isCreating ? t("my_subscription_page.sending_transfer_ellipsis") : t("my_subscription_page.send_transfer_request_button") }}</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>