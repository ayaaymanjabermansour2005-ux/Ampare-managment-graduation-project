<script setup>
import { ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import invoiceService from "@/services/invoiceService";
import { Check, FilePenLine, LoaderCircle, X } from "@lucide/vue";


const { t } = useI18n();
const props = defineProps({
  open: { type: Boolean, default: false },
  invoice: { type: Object, default: null },
});
const emit = defineEmits(["close", "updated"]);

const finalAmount = ref(0);
const reason = ref("");
const isSaving = ref(false);
const error = ref(null);

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen && props.invoice) {
      finalAmount.value = props.invoice.final_amount;
      reason.value = "";
      error.value = null;
    }
  },
);

async function submitCorrection() {
  isSaving.value = true;
  error.value = null;
  try {
    const { data } = await invoiceService.correct(
      props.invoice.id,
      finalAmount.value,
      reason.value,
    );
    emit("updated", data.data);
    emit("close");
  } catch (err) {
    error.value = err.response?.data?.message ?? t("invoice_correction_modal.generic_error");
  } finally {
    isSaving.value = false;
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm"
        @click.self="emit('close')"
      >
        <div class="glass-card modal-panel-pop !bg-white/98 dark:!bg-[#1c1e20]/98 w-full max-w-sm shadow-2xl overflow-hidden rounded-2xl">
          <div class="modal-head-brand modal-head-brand--gold">
            <div class="modal-head-brand__inner">
              <span class="modal-head-brand__icon"><FilePenLine aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ t("invoice_correction_modal.title", { id: invoice?.id }) }}</h3>
                <p class="modal-head-brand__subtitle">{{ t("invoice_correction_modal.current_amount_label", { amount: invoice?.final_amount, currency: invoice?.currency }) }}</p>
              </div>
            </div>
            <button :aria-label="t('common.close')" type="button" class="modal-head-brand__close" @click="emit('close')">
              <X aria-hidden="true" />
            </button>
          </div>

          <div class="p-5">
          <div v-if="error" class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2 mb-3">
            {{ error }}
          </div>

          <div class="space-y-3">
            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ t("invoice_correction_modal.new_amount_label") }}</label>
              <input
                v-model.number="finalAmount"
                type="number"
                step="0.01"
                min="0"
                class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] font-mono outline-none focus:border-[#8A6D1F]"
              />
            </div>

            <div>
              <label class="text-[11px] font-bold block mb-1.5">{{ t("invoice_correction_modal.reason_label") }}</label>
              <textarea
                v-model="reason"
                rows="2"
                maxlength="500"
                :placeholder="t('invoice_correction_modal.reason_placeholder')"
                class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-lg px-3 py-2 text-[12.5px] outline-none focus:border-[#8A6D1F] resize-none"
              ></textarea>
            </div>
          </div>

          </div>

          <div class="modal-footer-brand">
            <button
              type="button"
              @click="emit('close')"
              class="btn-outline-brand"
            >
              {{ t("invoice_correction_modal.cancel") }}
            </button>
            <button
              type="button"
              :disabled="!reason || isSaving"
              @click="submitCorrection"
              class="btn-fill-brand"
            >
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" /><Check aria-hidden="true" v-else />
              {{ isSaving ? t("invoice_correction_modal.submitting") : t("invoice_correction_modal.submit") }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>