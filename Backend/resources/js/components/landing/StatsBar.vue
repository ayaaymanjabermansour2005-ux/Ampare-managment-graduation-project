<script setup>
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { vReveal } from "@/directives/reveal";
import { vCountUp } from "@/directives/countUp";
import publicStatsService from "@/services/publicStatsService";

const { t } = useI18n();

const stats = ref({
  subscribers_count: 0,
  active_generators_count: 0,
  owners_count: 0,
  meter_readings_count: 0,
  uptime_percentage: 0,
});
const isLoaded = ref(false);

async function loadStats() {
  try {
    const { data } = await publicStatsService.platformStats();
    stats.value = data;
  } catch {
  } finally {
    isLoaded.value = true;
  }
}

onMounted(loadStats);
</script>

<template>
  <section class="relative z-10 -mt-6 pb-16">
    <div class="max-w-6xl mx-auto px-5 sm:px-8">
      <div
        v-reveal
        class="glass-card grid grid-cols-2 md:grid-cols-4 divide-x rtl:divide-x-reverse divide-[#e2e2e2]/50 dark:divide-white/10 p-6 sm:p-8"
      >
        <div class="text-center px-2">
          <p class="text-3xl font-extrabold brand-gradient-text">
            <span v-if="isLoaded" v-count-up="stats.subscribers_count">0</span><span v-else>—</span>
          </p>
          <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1">{{ t("landing.stats.subscribers") }}</p>
        </div>
        <div class="text-center px-2">
          <p class="text-3xl font-extrabold brand-gradient-text">
            <span v-if="isLoaded" v-count-up="stats.active_generators_count">0</span><span v-else>—</span>
          </p>
          <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1">{{ t("landing.stats.generators") }}</p>
        </div>
        <div class="text-center px-2">
          <p class="text-3xl font-extrabold brand-gradient-text">
            <span v-if="isLoaded" v-count-up="{ value: stats.uptime_percentage, decimals: 1 }">0</span><span v-else>—</span>%
          </p>
          <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1">{{ t("landing.stats.uptime") }}</p>
        </div>
        <div class="text-center px-2">
          <p class="text-3xl font-extrabold brand-gradient-text">
            <span v-if="isLoaded" v-count-up="stats.meter_readings_count">0</span><span v-else>—</span>
          </p>
          <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1">{{ t("landing.stats.readings") }}</p>
        </div>
      </div>
      <p class="text-center text-[10px] text-[#9a9d97] mt-3">{{ t("landing.stats.note") }}</p>
    </div>
  </section>
</template>