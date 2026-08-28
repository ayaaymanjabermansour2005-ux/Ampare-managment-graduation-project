<script setup>
import { reactive, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import { LoaderCircle, RotateCw, X } from "@lucide/vue";

const { t } = useI18n();

const props = defineProps({
  open: { type: Boolean, default: false },
  payment: { type: Object, default: null },
  isSubmitting: { type: Boolean, default: false },
  serverError: { type: Object, default: null },
});

const emit = defineEmits(["close", "submit"]);

const form = reactive({ amount: "", transaction_reference: "", note: "" });
const files = ref([]);

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      form.amount = props.payment?.amount ?? "";
      form.transaction_reference = "";
      form.note = "";
      files.value = [];
    }
  },
);

function handleFileChange(e) {
  files.value = Array.from(e.target.files ?? []).slice(0, 5);
}

function fieldError(field) {
  return props.serverError?.errors?.[field]?.[0] ?? null;
}

function submit() {
  emit("submit", { ...form, attachments: files.value });
}
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200"
      leave-active-class="transition-opacity duration-150"
      enter-from-class="opacity-0"
      leave-to-class="opacity-0"
    >
      <div
        v-if="open"
        class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-end sm:items-center justify-center p-0 sm:p-4"
        @click.self="emit('close')"
      >
        <div
          class="glass-card !bg-white/98 dark:!bg-[#1c1e20]/98 w-full sm:max-w-md max-h-[90vh] overflow-y-auto rounded-b-none sm:rounded-3xl shadow-2xl"
        >
          <!-- Header -->
          <header
            class="flex items-center justify-between px-5 py-4 border-b border-[#e7e2d6] dark:border-white/10 sticky top-0 bg-[#faf7ef]/90 dark:bg-[#1c1e20]/90 backdrop-blur-sm"
          >
            <h3 class="text-[14px] font-extrabold flex items-center gap-2.5">
              <span
                class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#8A6D1F] to-[#D4AF37] text-white flex items-center justify-center text-[12px]"
              >
                <RotateCw aria-hidden="true" />
              </span>
              {{ t("payment_resubmit_form.title") }}
            </h3>
            <button :aria-label="$t('common.close')"
              type="button"
              @click="emit('close')"
              class="w-8 h-8 rounded-full flex items-center justify-center text-[#6B6B6B] dark:text-[#a8aaa5] hover:bg-black/5 dark:hover:bg-white/10 transition"
            >
              <X aria-hidden="true" />
            </button>
          </header>

          <div class="p-5 space-y-4">
            <div
              v-if="serverError?.message && !serverError?.errors"
              class="text-[11.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-lg px-3 py-2.5"
            >
              {{ serverError.message }}
            </div>

            <div>
              <label class="block text-[12px] font-bold mb-1.5">{{ t("payment_resubmit_form.amount_label") }}</label>
              <input
                v-model="form.amount"
                type="number"
                step="0.01"
                min="0.01"
                class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[13px] font-bold outline-none focus:border-[#8A6D1F] transition-colors"
              />
              <p v-if="fieldError('amount')" class="text-[11px] text-[#D9534F] mt-1">
                {{ fieldError("amount") }}
              </p>
            </div>

            <div>
              <label class="block text-[12px] font-bold mb-1.5">
                {{ t("payment_resubmit_form.transaction_reference_label") }}
                <span class="text-[#9a9d97] font-normal">{{ t("payment_resubmit_form.optional_suffix") }}</span>
              </label>
              <input
                v-model="form.transaction_reference"
                type="text"
                maxlength="100"
                class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] outline-none focus:border-[#8A6D1F] transition-colors"
              />
            </div>

            <div>
              <label class="block text-[12px] font-bold mb-1.5">
                {{ t("payment_resubmit_form.note_label") }}
                <span class="text-[#9a9d97] font-normal">{{ t("payment_resubmit_form.optional_suffix") }}</span>
              </label>
              <textarea
                v-model="form.note"
                rows="2"
                maxlength="500"
                class="w-full bg-[#f4efe5]/70 dark:bg-white/5 border border-[#e7e2d6] dark:border-white/10 rounded-xl px-3.5 py-2.5 text-[12.5px] resize-none outline-none focus:border-[#8A6D1F] transition-colors"
              ></textarea>
            </div>

            <div>
              <label class="block text-[12px] font-bold mb-1.5">
                {{ t("payment_resubmit_form.new_proof_label") }}
                <span class="text-[#9a9d97] font-normal">{{ t("payment_resubmit_form.max_files_suffix") }}</span>
              </label>
              <input
                type="file"
                multiple
                accept=".jpg,.jpeg,.png,.pdf"
                @change="handleFileChange"
                class="w-full text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5] file:me-3 file:py-2 file:px-3.5 file:rounded-full file:border-0 file:bg-gradient-to-l file:from-[#8A6D1F] file:to-[#D4AF37] file:text-white file:text-[11px] file:font-bold"
              />
              <p v-if="files.length" class="text-[11px] text-[#9a9d97] mt-1.5">
                {{ t("payment_resubmit_form.files_selected_count", { count: files.length }) }}
              </p>
            </div>
          </div>

          <!-- Footer -->
          <footer
            class="flex gap-2.5 px-5 py-4 border-t border-[#e7e2d6] dark:border-white/10 sticky bottom-0 bg-[#faf7ef]/90 dark:bg-[#1c1e20]/90 backdrop-blur-sm"
          >
            <button
              type="button"
              @click="emit('close')"
              class="flex-1 text-[12.5px] font-bold py-2.5 rounded-full border border-[#e7e2d6] dark:border-white/10 hover:bg-[#f4efe5]/60 dark:hover:bg-white/5 transition"
            >
              {{ t("common.cancel") }}
            </button>
            <button
              type="button"
              @click="submit"
              :disabled="isSubmitting"
              class="flex-1 btn-fill relative text-[12.5px] font-bold py-2.5 rounded-full text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] shadow-md flex items-center justify-center gap-2 disabled:opacity-50 transition"
            >
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSubmitting" />
              {{ isSubmitting ? t("payment_resubmit_form.submit_loading") : t("payment_resubmit_form.submit_idle") }}
            </button>
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>