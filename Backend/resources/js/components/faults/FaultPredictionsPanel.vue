<script setup>
import { onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useFaultPredictions } from "@/composables/useFaultPredictions";
import { useConfirm } from "@/composables/useConfirm";
import { HeartPulse } from "@lucide/vue";

const {
  predictions,
  pagination,
  isLoading,
  error,
  fetchPredictions,
  decidingId,
  decisionError,
  confirmPrediction,
  dismissPrediction,
} = useFaultPredictions();

const { confirm } = useConfirm();
const { t } = useI18n();

async function handleConfirm(prediction) {
  const confirmed = await confirm({
    title: t("fault_predictions.confirm_title"),
    message: t("fault_predictions.confirm_message", { type: prediction.prediction_type, generator: prediction.generator?.name ?? "—" }),
    confirmLabel: t("fault_predictions.confirm_action"),
  });
  if (confirmed) await confirmPrediction(prediction.id);
}

async function handleDismiss(prediction) {
  const confirmed = await confirm({
    title: t("fault_predictions.dismiss_title"),
    message: t("fault_predictions.dismiss_message"),
    confirmLabel: t("fault_predictions.dismiss_action"),
    variant: "danger",
  });
  if (confirmed) await dismissPrediction(prediction.id);
}

onMounted(() => fetchPredictions());

defineExpose({ fetchPredictions });
</script>

<template>
  <div class="space-y-3.5">
    <div v-if="decisionError" class="text-[12.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-xl p-3">
      {{ decisionError }}
    </div>

    <div v-if="isLoading" class="space-y-2">
      <div v-for="i in 3" :key="i" class="h-20 rounded-xl thumb-loading"></div>
    </div>

    <div v-else-if="error" class="text-[12.5px] text-[#D9534F] bg-[#D9534F]/10 rounded-xl p-6 text-center">
      {{ error }}
    </div>

    <div v-else-if="predictions.length === 0" class="text-center py-10 text-[12.5px] text-[#9a9d97]">
      <HeartPulse class="text-2xl text-[#c9cdc2] mb-2 block" aria-hidden="true" />
      {{ t("fault_predictions.empty") }}
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="prediction in predictions"
        :key="prediction.id"
        class="rounded-xl border border-[#eee8da] dark:border-white/10 bg-[#f4efe5]/40 dark:bg-white/[0.03] p-4"
        :class="{ 'opacity-50 pointer-events-none': decidingId === prediction.id }"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
              <p class="font-bold text-[13px]">{{ prediction.prediction_type }}</p>
              <span class="status-chip chip-warning">{{ prediction.status_label }}</span>
              <span v-if="prediction.confidence !== null" class="status-chip chip-info">
                {{ t("fault_predictions.confidence", { percent: Math.round(prediction.confidence * 100) }) }}
              </span>
            </div>
            <p class="text-[11px] text-[#9a9d97] mt-1">
              {{ prediction.generator?.name ?? "—" }} · {{ prediction.source_label }}
            </p>
            <p v-if="prediction.recommendation" class="text-[12px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-2">
              {{ prediction.recommendation }}
            </p>
          </div>
        </div>

        <div class="flex gap-2 mt-3 pt-3 border-t border-[#eee8da] dark:border-white/10">
          <button
            type="button"
            @click="handleConfirm(prediction)"
            :disabled="decidingId === prediction.id"
            class="btn-fill relative flex-1 py-2 rounded-full text-[12.5px] font-bold text-white bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] shadow-md transition disabled:opacity-50"
          >
            {{ t("fault_predictions.confirm_button") }}
          </button>
          <button
            type="button"
            @click="handleDismiss(prediction)"
            :disabled="decidingId === prediction.id"
            class="flex-1 py-2 rounded-full text-[12.5px] font-bold text-[#D9534F] border border-[#D9534F]/30 hover:bg-[#D9534F]/10 transition disabled:opacity-50"
          >
            {{ t("fault_predictions.reject_button") }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="pagination.last_page > 1" class="flex justify-center gap-1.5 pt-2">
      <button
        v-for="page in pagination.last_page"
        :key="page"
        type="button"
        @click="fetchPredictions(page)"
        class="w-8 h-8 rounded-lg text-[12px] font-bold transition-colors"
        :class="
          page === pagination.current_page
            ? 'bg-gradient-to-l from-[#3E582E] to-[#52733D] text-white'
            : 'hover:bg-[#EBF1E7] dark:hover:bg-white/5 text-[#6B6B6B] dark:text-[#a8aaa5]'
        "
      >
        {{ page }}
      </button>
    </div>
  </div>
</template>
