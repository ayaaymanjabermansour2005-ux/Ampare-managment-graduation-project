<script setup>
import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import { FileText, User, Wallet, X, ZoomIn } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";
import AttachmentPreviewModal from "@/components/ui/AttachmentPreviewModal.vue";
import { usePermissions } from "@/composables/usePermissions";


const props = defineProps({
  open: { type: Boolean, default: false },
  payment: { type: Object, default: null },
  isLoadingDetail: { type: Boolean, default: false },
  isActing: { type: Boolean, default: false },
  actionError: { type: String, default: null },
});

const emit = defineEmits(["close", "approve", "reject", "request-correction"]);

const { t } = useI18n();
const { can } = usePermissions();

const METHOD_ICONS = { wallet: "fa-wallet", bank: "fa-building-columns", cash: "fa-money-bill-wave" };
const STATUS_CHIP = { pending: "chip-warning", paid: "chip-success", rejected: "chip-danger", needs_correction: "chip-info" };

function methodLabel(method) {
  return t(`owner_payments.method.${method}`, method);
}
function statusLabel(s) {
  return t(`owner_payments.status.${s}`, s);
}
function statusChip(s) {
  return STATUS_CHIP[s] ?? null;
}

const activeAction = ref(null);
const reasonText = ref("");

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      activeAction.value = null;
      reasonText.value = "";
    }
  },
);

const previewingAttachment = ref(null);

const imageAttachments = computed(
  () =>
    props.payment?.attachments?.filter((a) =>
      a.mime_type?.startsWith("image/"),
    ) ?? [],
);
const otherAttachments = computed(
  () =>
    props.payment?.attachments?.filter(
      (a) => !a.mime_type?.startsWith("image/"),
    ) ?? [],
);

function submitReject() {
  if (!reasonText.value.trim()) return;
  emit("reject", reasonText.value);
}
function submitCorrection() {
  if (!reasonText.value.trim()) return;
  emit("request-correction", reasonText.value);
}
</script>

<template>
  <Teleport to="body">
    <!-- Backdrop -->
    <Transition
      enter-active-class="transition-opacity duration-200"
      leave-active-class="transition-opacity duration-150"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-40 bg-black/40 backdrop-blur-sm"
        @click="emit('close')"
      ></div>
    </Transition>

    <!-- Panel -->
    <Transition
      enter-active-class="transition-transform duration-250 ease-out"
      leave-active-class="transition-transform duration-200 ease-in"
      enter-from-class="translate-x-full rtl:-translate-x-full"
      leave-to-class="translate-x-full rtl:-translate-x-full"
    >
      <aside
        v-if="open"
        class="fixed inset-y-0 start-0 z-50 w-full max-w-lg bg-white dark:bg-[#1c1e20] shadow-2xl flex flex-col"
        role="dialog"
        aria-modal="true"
      >
        <!-- Header -->
        <header
          class="flex items-center justify-between px-5 lg:px-6 py-4 border-b border-[#e7e2d6] dark:border-white/10 shrink-0 bg-[#faf7ef]/80 dark:bg-white/5"
        >
          <h2 class="text-[15px] font-extrabold flex items-center gap-2.5">
            <span
              class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] text-white flex items-center justify-center text-sm"
            >
              <Wallet aria-hidden="true" />
            </span>
            {{ t("owner_payments.review_title") }}
          </h2>
          <button :aria-label="$t('common.close')"
            type="button"
            @click="emit('close')"
            class="w-8 h-8 rounded-full flex items-center justify-center text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-black/5 dark:hover:bg-white/10 transition"
          >
            <X aria-hidden="true" />
          </button>
        </header>

        <!-- Loading -->
        <div
          v-if="isLoadingDetail"
          class="flex-1 flex items-center justify-center"
        >
          <div
            class="w-8 h-8 border-2 border-[#D4AF37]/30 border-t-[#8A6D1F] rounded-full animate-spin"
          ></div>
        </div>

        <!-- Content -->
        <div
          v-else-if="payment"
          class="flex-1 overflow-y-auto px-5 lg:px-6 py-5 space-y-5"
        >
          <!-- Amount card -->
          <div class="glass-card p-4">
            <div class="flex items-start justify-between gap-3">
              <div>
                <p class="text-[11px] text-[#9a9d97] mb-1">{{ t("owner_payments.amount_sent_label") }}</p>
                <p class="text-2xl font-extrabold bg-gradient-to-l from-[#3E582E] to-[#8A6D1F] bg-clip-text text-transparent">
                  {{ payment.amount }}
                  <span class="text-xs font-bold text-[#6B6B6B] dark:text-[#a8aaa5]">{{
                    payment.currency
                  }}</span>
                </p>
              </div>
              <span
                class="status-chip"
                :class="statusChip(payment.status)"
              >
                {{ statusLabel(payment.status) }}
              </span>
            </div>

            <div class="flex items-center gap-4 mt-3.5 pt-3.5 border-t border-[#e7e2d6]/70 dark:border-white/10 text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5]">
              <span class="flex items-center gap-1.5">
                <AppIcon :name="METHOD_ICONS[payment.payment_method_type] ?? 'fa-wallet'" class="text-[#8A6D1F]" />
                {{ methodLabel(payment.payment_method_type) }}
              </span>
              <span v-if="payment.subscriber?.name" class="flex items-center gap-1.5">
                <User class="text-[#8A6D1F]" aria-hidden="true" />
                {{ payment.subscriber.name }}
              </span>
            </div>
          </div>

          <!-- Generator / note -->
          <div
            v-if="payment.generator?.name || payment.note"
            class="glass-card p-4 space-y-3 text-[12px]"
          >
            <div
              v-if="payment.generator?.name"
              class="flex justify-between gap-3"
            >
              <span class="text-[#9a9d97]">{{ t("owner_payments.generator_label") }}</span>
              <b>{{ payment.generator.name }}</b>
            </div>
            <div v-if="payment.note">
              <p class="text-[#9a9d97] mb-1.5">{{ t("owner_payments.subscriber_note_label") }}</p>
              <p class="text-[12px] bg-[#f4efe5]/70 dark:bg-white/5 rounded-lg p-3 leading-relaxed">
                {{ payment.note }}
              </p>
            </div>
          </div>

          <!-- Attachments -->
          <div>
            <h3 class="text-[13px] font-bold mb-2.5">{{ t("owner_payments.proof_of_payment") }}</h3>

            <div
              v-if="imageAttachments.length === 0 && otherAttachments.length === 0"
              class="text-center py-8 glass-card text-[11.5px] text-[#9a9d97]"
            >
              {{ t("owner_payments.no_attachments") }}
            </div>

            <div v-else class="grid grid-cols-2 gap-3">
              <button
                v-for="att in imageAttachments"
                :key="att.id"
                type="button"
                :aria-label="`${$t('common.view')}: ${att.original_name}`"
                @click="previewingAttachment = att"
                class="relative aspect-video rounded-xl overflow-hidden border border-[#e7e2d6] dark:border-white/10 group"
              >
                <img
                  :src="att.preview_url"
                  :alt="att.original_name"
                  class="w-full h-full object-cover"
                />
                <div
                  class="absolute inset-0 bg-black/0 group-hover:bg-black/40 transition-colors flex items-center justify-center"
                >
                  <ZoomIn class="text-white opacity-0 group-hover:opacity-100 transition-opacity" aria-hidden="true" />
                </div>
              </button>

              <button
                v-for="att in otherAttachments"
                :key="att.id"
                type="button"
                @click="previewingAttachment = att"
                class="glass-card hoverable flex items-center gap-2.5 p-3 col-span-2 text-[12px] text-start"
              >
                <FileText class="text-[15px] text-[#D9534F]" aria-hidden="true" />
                <span class="truncate">{{ att.original_name }}</span>
              </button>
            </div>
          </div>

          <!-- Error -->
          <div
            v-if="actionError"
            class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2.5"
          >
            {{ actionError }}
          </div>

          <!-- Reject / correction form -->
          <div v-if="activeAction" class="glass-card p-4 space-y-3">
            <label class="block text-[12px] font-bold">
              {{
                activeAction === "reject"
                  ? t("owner_payments.reject_reason_label")
                  : t("owner_payments.correction_reason_label")
              }}
            </label>
            <textarea
              v-model="reasonText"
              rows="3"
              :required="activeAction === 'correction' || activeAction === 'reject'"
              class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3.5 py-2.5 text-[12px] outline-none focus:border-[#8A6D1F] resize-none"
            ></textarea>
            <div class="flex gap-2.5">
              <button
                type="button"
                @click="activeAction = null"
                class="flex-1 text-[12px] font-bold py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 transition"
              >
                {{ t("owner_payments.cancel") }}
              </button>
              <button
                type="button"
                :disabled="isActing || ((activeAction === 'correction' || activeAction === 'reject') && !reasonText.trim())"
                @click="activeAction === 'reject' ? submitReject() : submitCorrection()"
                class="flex-1 text-[12px] font-bold py-2.5 rounded-full text-white shadow-md transition disabled:opacity-50"
                :class="
                  activeAction === 'reject'
                    ? 'bg-gradient-to-l from-[#8A2E2E] to-[#D9534F]'
                    : 'bg-gradient-to-l from-[#A3760A] to-[#FFC107]'
                "
              >
                {{ isActing ? t("owner_payments.sending_ellipsis") : t("owner_payments.confirm_send") }}
              </button>
            </div>
          </div>
        </div>

        <!-- Footer actions -->
        <footer
          v-if="payment?.status === 'pending' && !activeAction && (can('payments.approve') || can('payments.reject'))"
          class="grid grid-cols-3 gap-2.5 px-5 lg:px-6 py-4 border-t border-[#e7e2d6] dark:border-white/10 shrink-0 bg-[#faf7ef]/80 dark:bg-white/5"
        >
          <button
            v-if="can('payments.approve')"
            type="button"
            @click="activeAction = 'correction'"
            :disabled="isActing"
            class="py-2.5 rounded-full text-[11.5px] font-bold text-[#a3760a] border border-[#FFC107]/40 hover:bg-[#FFC107]/10 transition disabled:opacity-40"
          >
            {{ t("owner_payments.request_correction") }}
          </button>
          <button
            v-if="can('payments.reject')"
            type="button"
            @click="activeAction = 'reject'"
            :disabled="isActing"
            class="py-2.5 rounded-full text-[11.5px] font-bold text-[#D9534F] border border-[#D9534F]/40 hover:bg-[#D9534F]/10 transition disabled:opacity-40"
          >
            {{ t("owner_payments.reject") }}
          </button>
          <button
            v-if="can('payments.approve')"
            type="button"
            @click="emit('approve')"
            :disabled="isActing"
            class="btn-fill relative py-2.5 rounded-full text-[11.5px] font-bold text-white bg-gradient-to-l from-[#3E582E] to-[#52733D] shadow-md transition disabled:opacity-50"
          >
            {{ isActing ? "..." : t("owner_payments.approve") }}
          </button>
        </footer>
      </aside>
    </Transition>

    <AttachmentPreviewModal :attachment="previewingAttachment" @close="previewingAttachment = null" />
  </Teleport>
</template>