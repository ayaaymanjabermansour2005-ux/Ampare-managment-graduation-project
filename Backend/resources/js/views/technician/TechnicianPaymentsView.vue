<script setup>
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useTechnicianPayments } from "@/composables/useTechnicianPayments";
import { Check, Wallet, X } from "@lucide/vue";

const { t } = useI18n();

const {
  payments,
  pagination,
  isLoading,
  error,
  pendingCount,
  fetchPayments,
  actingId,
  actionError,
  approve,
  reject,
} = useTechnicianPayments();

const STATUS_TONES = {
  pending: "bg-warning-bg text-warning",
  approved: "bg-success-bg text-success",
  rejected: "bg-danger-bg text-danger",
};

const rejectTarget = ref(null);
const rejectReason = ref("");

function openReject(payment) {
  rejectTarget.value = payment;
  rejectReason.value = "";
}

async function handleApprove(payment) {
  await approve(payment.id);
}

async function handleReject() {
  if (!rejectReason.value.trim()) return;
  const ok = await reject(rejectTarget.value.id, rejectReason.value.trim());
  if (ok) {
    rejectTarget.value = null;
    rejectReason.value = "";
  }
}

onMounted(() => fetchPayments());
</script>

<template>
  <div class="space-y-6">
    <div>
      <p class="text-xs font-medium text-secondary-600 tracking-wide mb-1">
        {{ t("technician_payments.eyebrow") }}
      </p>
      <h1 class="text-2xl font-semibold text-gray-700 dark:text-gray-200">{{ t("technician_portal.nav_payments") }}</h1>
      <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        {{ t("technician_payments.pending_notice", { count: pendingCount }) }}
      </p>
    </div>

    <div
      v-if="actionError"
      class="bg-danger-bg text-danger text-sm rounded-lg p-3"
    >
      {{ actionError }}
    </div>

    <div v-if="isLoading" class="space-y-3">
      <div
        v-for="i in 3"
        :key="i"
        class="h-24 rounded-card bg-gray-50 dark:bg-white/5 animate-pulse"
      ></div>
    </div>

    <div
      v-else-if="error"
      class="bg-danger-bg text-danger text-sm rounded-lg p-6 text-center"
    >
      {{ error }}
    </div>

    <div
      v-else-if="payments.length === 0"
      class="text-center py-16 bg-surface dark:bg-[#1c1e20] rounded-card border border-dashed border-border dark:border-white/10"
    >
      <div
        class="w-14 h-14 mx-auto mb-4 rounded-full bg-primary-50 dark:bg-primary-500/10 flex items-center justify-center"
      >
        <Wallet class="text-2xl text-primary-500" aria-hidden="true" />
      </div>
      <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-1.5">
        {{ t("technician_payments.empty_title") }}
      </h3>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="payment in payments"
        :key="payment.id"
        class="bg-surface dark:bg-[#1c1e20] rounded-card border border-border dark:border-white/10 p-4"
        :class="{ 'opacity-50 pointer-events-none': actingId === payment.id }"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <h3 class="font-mono font-data font-semibold text-gray-700 dark:text-gray-200 text-lg">
                {{ Number(payment.amount).toLocaleString() }}
                <span class="text-xs font-sans font-normal text-gray-500 dark:text-gray-400">{{
                  payment.currency
                }}</span>
              </h3>
              <span
                class="text-[11px] rounded-full px-2 py-0.5"
                :class="STATUS_TONES[payment.status]"
              >
                {{ payment.status_label }}
              </span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
              {{ t("technician_payments.from_owner_prefix") }} {{ payment.owner_name ?? t("technician_payments.default_owner_label") }}
              <template v-if="payment.payment_method">
                · {{ payment.payment_method.type }}
              </template>
            </p>
          </div>
        </div>

        <p
          v-if="payment.note"
          class="text-sm text-gray-600 dark:text-gray-300 bg-gray-50 dark:bg-white/5 rounded-lg p-3 mt-3"
        >
          {{ payment.note }}
        </p>
        <p
          v-if="payment.rejection_reason"
          class="text-sm text-danger bg-danger-bg rounded-lg p-3 mt-3"
        >
          <strong>{{ t("technician_payments.rejection_reason_label") }}</strong> {{ payment.rejection_reason }}
        </p>

        <div
          v-if="payment.status === 'pending'"
          class="flex items-center gap-2 mt-3 pt-3 border-t border-border dark:border-white/10"
        >
          <button
            type="button"
            @click="handleApprove(payment)"
            class="flex-1 inline-flex items-center justify-center gap-2 py-2 rounded-lg text-sm font-semibold text-white bg-success hover:bg-success/90 transition"
          >
            <Check aria-hidden="true" /> {{ t("technician_payments.approve_button") }}
          </button>
          <button
            type="button"
            @click="openReject(payment)"
            class="flex-1 inline-flex items-center justify-center gap-2 py-2 rounded-lg text-sm font-semibold text-danger border border-danger/30 hover:bg-danger-bg transition"
          >
            <X aria-hidden="true" /> {{ t("technician_payments.reject_button") }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="pagination.last_page > 1" class="flex justify-center gap-2 pt-2">
      <button
        v-for="page in pagination.last_page"
        :key="page"
        type="button"
        @click="fetchPayments(page)"
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

    <Teleport to="body">
      <div
        v-if="rejectTarget"
        class="fixed inset-0 z-50 bg-gray-700/40 backdrop-blur-[2px] flex items-center justify-center p-4"
        @click.self="rejectTarget = null"
      >
        <div class="bg-surface dark:bg-[#1c1e20] rounded-card p-6 max-w-sm w-full max-h-[90vh] overflow-y-auto">
          <h3 class="font-semibold text-gray-700 dark:text-gray-200 mb-1">{{ t("technician_payments.reject_modal_title") }}</h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">
            {{ t("technician_payments.reject_modal_desc") }}
          </p>
          <textarea
            v-model="rejectReason"
            rows="4"
            required
            maxlength="500"
            :placeholder="t('technician_payments.reject_reason_placeholder')"
            class="w-full rounded-lg border border-border dark:border-white/10 bg-transparent px-3.5 py-2.5 text-sm text-gray-700 dark:text-gray-200 resize-none focus:outline-none focus:ring-2 focus:ring-danger/20 focus:border-danger"
          ></textarea>
          <div class="flex gap-3 mt-4">
            <button
              type="button"
              @click="rejectTarget = null"
              class="flex-1 py-2.5 rounded-lg text-sm text-gray-600 dark:text-gray-300 border border-border dark:border-white/10 hover:bg-gray-50 dark:hover:bg-white/5 transition"
            >
              {{ t("technician_payments.reject_cancel_action") }}
            </button>
            <button
              type="button"
              @click="handleReject"
              :disabled="!rejectReason.trim() || actingId === rejectTarget.id"
              class="flex-1 py-2.5 rounded-lg text-sm font-semibold text-white bg-danger hover:bg-danger/90 disabled:opacity-50 transition"
            >
              {{ t("technician_payments.reject_confirm_action") }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>
