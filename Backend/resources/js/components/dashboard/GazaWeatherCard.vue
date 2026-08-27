<script setup>
import { useI18n } from "vue-i18n";
import { useGazaWeather } from "@/composables/useGazaWeather";
import AppIcon from "@/components/ui/AppIcon.vue";

const { t } = useI18n();
const weather = useGazaWeather();
</script>

<template>
  <div class="glass-card p-4 relative">
    <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
      <span class="text-[11.5px] font-bold text-[#6B6B6B] dark:text-[#a8aaa5] flex items-center gap-1.5">
        <AppIcon :name="weather.conditionIcon.value" style="color:#8A6D1F" /> {{ t("dashboard.weather_title") }}
      </span>
      <span v-if="!weather.isLoading.value" class="status-chip" :class="weather.loadChipClass.value">{{ weather.loadChipLabel.value }}</span>
      <span v-else class="status-chip thumb-loading" style="min-width:5.5rem"></span>
    </div>
    <div v-if="weather.isLoading.value" class="space-y-2.5" aria-hidden="true">
      <div class="flex items-end justify-between">
        <div class="space-y-1.5">
          <div class="thumb-loading rounded-md" style="width:5.5rem;height:1.9rem"></div>
          <div class="thumb-loading rounded-md" style="width:9.5rem;height:0.75rem"></div>
        </div>
      </div>
      <div class="thumb-loading rounded-full" style="width:100%;height:0.5rem"></div>
    </div>
    <div v-else>
      <div class="flex items-end justify-between">
        <div>
          <div class="flex items-baseline gap-2">
            <div class="text-3xl font-extrabold">{{ weather.temp.value }}°</div>
            <div class="text-[11px] font-semibold text-[#8A6D1F]">{{ weather.conditionLabel.value }}</div>
          </div>
          <div class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5]">
            {{ weather.rain.value }}% · {{ weather.humidity.value }}% · {{ weather.wind.value }} km/h{{ weather.isEstimate.value ? " *" : "" }}
          </div>
        </div>
        <div class="text-end">
          <div class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mb-1">{{ t("dashboard.expected_peak") }}</div>
          <div class="text-base font-bold text-[#8A6D1F]">{{ weather.peakLabel.value }}</div>
        </div>
      </div>
      <div class="mt-3 bar-track"><div class="bar-fill" :style="{ width: weather.loadPercent.value + '%', background: 'linear-gradient(90deg,#52733D,#D4AF37)' }"></div></div>
      <p class="text-[10.5px] text-[#9a9d97] mt-1.5">{{ weather.note.value }}</p>
    </div>
  </div>
</template>