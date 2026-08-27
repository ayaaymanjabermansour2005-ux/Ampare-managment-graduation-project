<script setup>
import { onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useGeneratorHealthReports } from "@/composables/useGeneratorHealthReports";
import { Lightbulb } from "@lucide/vue";

const props = defineProps({
  generatorId: { type: [Number, String], required: true },
});

const { t } = useI18n();
const { reports, isLoading, error, fetchReports } = useGeneratorHealthReports(
  props.generatorId,
);

const RISK_TONES = {
  low: "bg-success-bg text-success",
  medium: "bg-warning-bg text-warning",
  high: "bg-danger-bg text-danger",
};

onMounted(() => fetchReports());
</script>

<template>
  <div class="space-y-4">
    <h3 class="font-semibold text-gray-700 dark:text-gray-300">
      {{ t("generator_health_reports.title") }}
    </h3>

    <div v-if="error" class="bg-danger-bg text-danger text-sm rounded-lg p-3">
      {{ error }}
    </div>

    <div v-if="isLoading" class="space-y-2">
      <div
        v-for="i in 2"
        :key="i"
        class="h-24 rounded-card bg-gray-50 dark:bg-white/5 animate-pulse"
      ></div>
    </div>

    <div
      v-else-if="reports.length === 0"
      class="text-center py-10 bg-surface dark:bg-[#1c1e20] rounded-card border border-dashed border-border dark:border-white/10"
    >
      <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ t("generator_health_reports.empty") }}
      </p>
    </div>

    <div
      v-for="report in reports"
      :key="report.id"
      class="bg-surface dark:bg-[#1c1e20] border border-border dark:border-white/10 rounded-card p-4 space-y-2"
    >
      <div class="flex items-center justify-between">
        <span class="text-xs text-gray-500 dark:text-gray-400 font-mono"
          >{{ report.period_start }} — {{ report.period_end }}</span
        >
        <span
          class="text-[11px] rounded-full px-2 py-0.5"
          :class="RISK_TONES[report.risk_level]"
        >
          {{ report.risk_level_label }}
        </span>
      </div>
      <p class="text-sm text-gray-700 dark:text-gray-300">{{ report.summary }}</p>
      <p
        v-if="report.recommendation"
        class="text-xs text-secondary-600 dark:text-secondary-300 bg-secondary-50 dark:bg-secondary-500/10 rounded-lg p-2"
      >
        <Lightbulb class="me-1" aria-hidden="true" />{{ report.recommendation }}
      </p>
    </div>
  </div>
</template>
