<script setup>
import { ref, computed, watch } from "vue";
import { useI18n } from "vue-i18n";
import subscriptionService from "@/services/subscriptionService";
import generatorService from "@/services/generatorService";
import { normalizeApiError } from "@/utils/normalizeApiError";
import AppDropdownSelect from "@/components/ui/AppDropdownSelect.vue";
import { Check, LoaderCircle, Route, X } from "@lucide/vue";


const { t } = useI18n();
const props = defineProps({
  open: { type: Boolean, default: false },
  subscription: { type: Object, default: null },
});
const emit = defineEmits(["close", "transferred"]);

const generators = ref([]);
const selectedGeneratorId = ref(null);
const isLoading = ref(false);
const isSaving = ref(false);
const error = ref(null);
const generatorSelectOptions = computed(() => generators.value.map((g) => ({ value: g.id, label: g.name })));

watch(
  () => props.open,
  async (isOpen) => {
    if (!isOpen) return;
    isLoading.value = true;
    error.value = null;
    selectedGeneratorId.value = null;
    try {
      const { data } = await generatorService.list({ per_page: 100 });
      const payload = data.data;
      generators.value = (payload.data ?? payload).filter(
        (g) => g.id !== props.subscription?.generator?.id,
      );
    } finally {
      isLoading.value = false;
    }
  },
);

async function submitTransfer() {
  isSaving.value = true;
  error.value = null;
  try {
    const { data } = await subscriptionService.transfer(
      props.subscription.id,
      selectedGeneratorId.value,
    );
    emit("transferred", data.data);
    emit("close");
  } catch (err) {
    error.value = normalizeApiError(err, t("transfer_subscription_modal.generic_error")).message;
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
              <span class="modal-head-brand__icon"><Route class="rtl:-scale-x-100" aria-hidden="true" /></span>
              <div class="min-w-0">
                <h3 class="modal-head-brand__title">{{ t("transfer_subscription_modal.title") }}</h3>
                <p class="modal-head-brand__subtitle">{{ t("transfer_subscription_modal.current_generator_label", { name: subscription?.generator?.name }) }}</p>
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

          <div v-if="isLoading" class="flex items-center justify-center gap-2 text-[11.5px] text-[#9a9d97] dark:text-[#8f938a] py-6">
            <LoaderCircle class="animate-spin" aria-hidden="true" />
            {{ t("transfer_subscription_modal.loading") }}
          </div>

          <AppDropdownSelect
            v-else
            v-model="selectedGeneratorId"
            :options="generatorSelectOptions"
            :placeholder="t('transfer_subscription_modal.select_placeholder')"
            variant="field" width-class="w-full" match-trigger-width
          />
          </div>

          <div class="modal-footer-brand">
            <button
              type="button"
              @click="emit('close')"
              class="btn-outline-brand"
            >
              {{ t("transfer_subscription_modal.cancel") }}
            </button>
            <button
              type="button"
              :disabled="!selectedGeneratorId || isSaving"
              @click="submitTransfer"
              class="btn-fill-brand"
            >
              <LoaderCircle class="animate-spin" aria-hidden="true" v-if="isSaving" /><Check aria-hidden="true" v-else />
              {{ isSaving ? t("transfer_subscription_modal.transferring") : t("transfer_subscription_modal.transfer") }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>