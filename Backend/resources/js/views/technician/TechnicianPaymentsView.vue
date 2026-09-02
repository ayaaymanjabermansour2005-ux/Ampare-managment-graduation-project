<script setup>
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useTechnicianPayments } from "@/composables/useTechnicianPayments";
import { vReveal } from "@/directives/reveal";
import { Check, LoaderCircle, Wallet, X } from "@lucide/vue";

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

const STATUS_CHIP = {
  pending: "chip-warning",
  approved: "chip-success",
  rejected: "chip-danger",
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
  <div class="space-y-5">
    <!-- ===== رأس الصفحة ===== -->
    <section v-reveal class="glass-card p-5 relative overflow-hidden">
      <div class="absolute -end-16 -top-16 w-56 h-56 bg-[#52733D]/15 dark:bg-[#8cc35a]/10 rounded-full blur-[90px] pointer-events-none"></div>
      <div class="relative flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3 min-w-0">
          <span class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#3E582E] to-[#52733D] text-white flex items-center justify-center text-base shrink-0">
            <Wallet aria-hidden="true" />
          </span>
          <div class="min-w-0">
            <p class="text-[11px] font-bold text-[#8A6D1F] dark:text-[#D4AF37] tracking-wide mb-0.5">
              {{ t("technician_payments.eyebrow") }}
            </p>
            <h1 class="text-lg font-extrabold truncate">{{ t("technician_portal.nav_payments") }}</h1>
          </div>
        </div>
        <span class="status-chip chip-warning shrink-0">{{ t("technician_payments.pending_notice", { count: pendingCount }) }}</span>
      </div>
    </section>

    <div v-if="actionError" class="alert-box"><X class="shrink-0" aria-hidden="true" /> {{ actionError }}</div>

    <!-- ===== حالة التحميل ===== -->
    <div v-if="isLoading" class="space-y-3">
      <div v-for="i in 3" :key="i" class="h-24 rounded-2xl bg-[#f4efe5]/60 dark:bg-white/5 animate-pulse"></div>
    </div>

    <!-- ===== حالة الخطأ ===== -->
    <div v-else-if="error" class="glass-card p-6 text-center text-[12.5px] text-[#D9534F]">
      {{ error }}
    </div>

    <!-- ===== حالة فارغة ===== -->
    <div v-else-if="payments.length === 0" class="glass-card border-dashed p-12 text-center">
      <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center text-[#52733D] dark:text-[#8cc35a]">
        <Wallet class="text-2xl" aria-hidden="true" />
      </div>
      <h3 class="text-[13.5px] font-bold mb-1.5">{{ t("technician_payments.empty_title") }}</h3>
    </div>

    <!-- ===== قائمة الدفعات ===== -->
    <div v-else class="space-y-3">
      <section
        v-reveal
        v-for="payment in payments"
        :key="payment.id"
        class="glass-card p-4 sm:p-5 transition"
        :class="{ 'opacity-50 pointer-events-none': actingId === payment.id }"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <h3 class="font-mono font-extrabold text-[15px]">
                {{ Number(payment.amount).toLocaleString() }}
                <span class="text-[11px] font-sans font-normal text-[#6B6B6B] dark:text-[#a8aaa5]">{{ payment.currency }}</span>
              </h3>
              <span class="status-chip" :class="STATUS_CHIP[payment.status]">{{ payment.status_label }}</span>
            </div>
            <p class="text-[11.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1">
              {{ t("technician_payments.from_owner_prefix") }} {{ payment.owner_name ?? t("technician_payments.default_owner_label") }}
              <template v-if="payment.payment_method"> · {{ payment.payment_method.type }}</template>
            </p>
          </div>
        </div>

        <p v-if="payment.note" class="text-[12.5px] bg-[#EBF1E7] dark:bg-white/5 rounded-xl p-3 mt-3">
          {{ payment.note }}
        </p>
        <p v-if="payment.rejection_reason" class="text-[12.5px] text-[#D9534F] bg-[#D9534F]/10 border border-[#D9534F]/20 rounded-xl p-3 mt-3">
          <strong>{{ t("technician_payments.rejection_reason_label") }}</strong> {{ payment.rejection_reason }}
        </p>

        <div v-if="payment.status === 'pending'" class="flex items-center gap-2 mt-4 pt-3.5 border-t border-[#f0ece0] dark:border-white/5">
          <button type="button" @click="handleApprove(payment)" class="flex-1 inline-flex items-center justify-center gap-2 py-2.5 rounded-full text-[12.5px] font-bold text-white bg-[#28A745] hover:bg-[#28A745]/90 transition">
            <Check aria-hidden="true" /> {{ t("technician_payments.approve_button") }}
          </button>
          <button type="button" @click="openReject(payment)" class="flex-1 inline-flex items-center justify-center gap-2 py-2.5 rounded-full text-[12.5px] font-bold text-[#D9534F] border border-[#D9534F]/35 hover:bg-[#D9534F]/10 transition">
            <X aria-hidden="true" /> {{ t("technician_payments.reject_button") }}
          </button>
        </div>
      </section>
    </div>

    <!-- ===== ترقيم الصفحات ===== -->
    <div v-if="pagination.last_page > 1" class="flex justify-center gap-2 pt-1">
      <button
        v-for="page in pagination.last_page"
        :key="page"
        type="button"
        @click="fetchPayments(page)"
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

    <!-- ===== نافذة رفض الدفعة ===== -->
    <Teleport to="body">
      <Transition enter-active-class="transition duration-250 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="rejectTarget" class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="rejectTarget = null">
          <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
            <div class="modal-head-brand modal-head-brand--danger">
              <div class="modal-head-brand__inner">
                <span class="modal-head-brand__icon"><X aria-hidden="true" /></span>
                <div class="min-w-0">
                  <h3 class="modal-head-brand__title">{{ t("technician_payments.reject_modal_title") }}</h3>
                  <p class="modal-head-brand__subtitle">{{ t("technician_payments.reject_modal_desc") }}</p>
                </div>
              </div>
              <button :aria-label="t('common.close')" type="button" @click="rejectTarget = null" class="modal-head-brand__close"><X aria-hidden="true" /></button>
            </div>

            <div class="p-5">
              <label class="field-label">{{ t("technician_payments.reject_reason_placeholder") }}</label>
              <textarea
                v-model="rejectReason"
                rows="4"
                required
                maxlength="500"
                :placeholder="t('technician_payments.reject_reason_placeholder')"
                class="field-input resize-none focus:!border-[#D9534F]"
              ></textarea>
            </div>

            <div class="modal-footer-brand">
              <button type="button" @click="rejectTarget = null" class="btn-outline-brand">{{ t("technician_payments.reject_cancel_action") }}</button>
              <button
                type="button"
                @click="handleReject"
                :disabled="!rejectReason.trim() || actingId === rejectTarget?.id"
                class="btn-fill-brand btn-fill-brand--danger"
              >
                <LoaderCircle class="animate-spin" aria-hidden="true" v-if="actingId === rejectTarget?.id" /><X aria-hidden="true" v-else />
                {{ t("technician_payments.reject_confirm_action") }}
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>