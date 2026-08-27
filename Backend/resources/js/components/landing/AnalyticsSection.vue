<script setup>
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { vReveal } from "@/directives/reveal";
import publicAnalyticsService from "@/services/publicAnalyticsService";
import { useScrollGlow } from "@/composables/useScrollGlow";
import { Gauge, HandCoins, PlugZap, TriangleAlert } from "@lucide/vue";

const { t, n, locale } = useI18n();

// خلفية الـ parallax المرتبطة بسكرول الصفحة (composable مشترك)
const analyticsStage = useScrollGlow();

function formatNum(value) {
  return Number(value || 0).toLocaleString(locale.value === "ar" ? "ar-EG" : "en-US");
}
function localizedGeneratorName(g) {
  return locale.value === "en" && g.name_en ? g.name_en : g.name;
}

const status = ref("loading"); // loading | ready | error
const quickStats = ref({ today_revenue_ils: 0, active_generators_count: 0, open_faults_count: 0, monthly_readings_count: 0 });
const revenueGrowth = ref({ labels: [], values: [] });
const subscriberGrowth = ref({ labels: [], values: [] });
const paymentMethods = ref([]);
const topGenerators = ref([]);
const animated = ref(false);

async function loadAnalytics() {
  status.value = "loading";
  try {
    const { data } = await publicAnalyticsService.platformAnalytics();
    quickStats.value = data.quick_stats;
    revenueGrowth.value = data.revenue_growth;
    subscriberGrowth.value = data.subscriber_growth;
    paymentMethods.value = data.payment_methods;
    topGenerators.value = data.top_generators;
    status.value = "ready";
    requestAnimationFrame(() => setTimeout(() => (animated.value = true), 100));
  } catch {
    status.value = "error";
  }
}

function maxOf(arr) {
  return Math.max(1, ...arr);
}

function paymentMethodLabel(type) {
  return t(`landing.analytics.labels.${type}`);
}

onMounted(loadAnalytics);
</script>

<template>
  <section ref="analyticsStage" class="py-14 sm:py-24 grid-texture scroll-glow-stage">
    <!-- خلفية parallax مرتبطة بسكرول الصفحة العادي — زخرفية بس، ما بتأثر على المحتوى -->
    <div class="scroll-glow-bg" aria-hidden="true">
      <span class="scroll-glow-blob scroll-glow-blob--1"></span>
      <span class="scroll-glow-blob scroll-glow-blob--2"></span>
      <span class="scroll-glow-blob scroll-glow-blob--3"></span>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-8 scroll-glow-content">
      <div class="text-center max-w-xl mx-auto mb-8 sm:mb-14" v-reveal>
        <span class="text-[10px] sm:text-[11px] font-bold text-[#8A6D1F] tracking-wide">{{ t("landing.analytics.eyebrow") }}</span>
        <h2 class="text-2xl sm:text-4xl font-extrabold mt-2">{{ t("landing.analytics.title") }}</h2>
        <p class="text-[12.5px] sm:text-[13px] text-[#666] dark:text-[#aeb1ab] mt-2 sm:mt-3">{{ t("landing.analytics.subtitle") }}</p>
      </div>

      <div v-if="status === 'loading'" class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-4 mb-5 sm:mb-8">
        <div v-for="i in 4" :key="i" class="glass-card h-16 sm:h-24 animate-pulse"></div>
      </div>

      <template v-else-if="status === 'ready'">
        <!-- ===== كروت الأرقام السريعة: عمودين على الموبايل ===== -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 sm:gap-4 mb-5 sm:mb-8" v-reveal>
          <div class="glass-card p-2.5 sm:p-5 text-center">
            <div class="w-7 h-7 sm:w-9 sm:h-9 mx-auto mb-1 sm:mb-2 rounded-lg bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center text-[#8A6D1F]"><HandCoins class="text-[11px] sm:text-sm" aria-hidden="true" /></div>
            <p class="text-[13px] sm:text-lg font-extrabold brand-gradient-text truncate">₪{{ formatNum(quickStats.today_revenue_ils) }}</p>
            <p class="text-[8.5px] sm:text-[10.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5 sm:mt-1 leading-snug">{{ t("landing.analytics.quick_stats.today_revenue") }}</p>
          </div>
          <div class="glass-card p-2.5 sm:p-5 text-center">
            <div class="w-7 h-7 sm:w-9 sm:h-9 mx-auto mb-1 sm:mb-2 rounded-lg bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center text-[#8A6D1F]"><PlugZap class="text-[11px] sm:text-sm" aria-hidden="true" /></div>
            <p class="text-[13px] sm:text-lg font-extrabold brand-gradient-text truncate">{{ formatNum(quickStats.active_generators_count) }}</p>
            <p class="text-[8.5px] sm:text-[10.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5 sm:mt-1 leading-snug">{{ t("landing.analytics.quick_stats.active_generators") }}</p>
          </div>
          <div class="glass-card p-2.5 sm:p-5 text-center">
            <div class="w-7 h-7 sm:w-9 sm:h-9 mx-auto mb-1 sm:mb-2 rounded-lg bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center text-[#8A6D1F]"><TriangleAlert class="text-[11px] sm:text-sm" aria-hidden="true" /></div>
            <p class="text-[13px] sm:text-lg font-extrabold text-[#8A6D1F] truncate">{{ formatNum(quickStats.open_faults_count) }}</p>
            <p class="text-[8.5px] sm:text-[10.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5 sm:mt-1 leading-snug">{{ t("landing.analytics.quick_stats.maintenance_alerts") }}</p>
          </div>
          <div class="glass-card p-2.5 sm:p-5 text-center">
            <div class="w-7 h-7 sm:w-9 sm:h-9 mx-auto mb-1 sm:mb-2 rounded-lg bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center text-[#8A6D1F]"><Gauge class="text-[11px] sm:text-sm" aria-hidden="true" /></div>
            <p class="text-[13px] sm:text-lg font-extrabold brand-gradient-text truncate">{{ formatNum(quickStats.monthly_readings_count) }}</p>
            <p class="text-[8.5px] sm:text-[10.5px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-0.5 sm:mt-1 leading-snug">{{ t("landing.analytics.quick_stats.monthly_readings") }}</p>
          </div>
        </div>

        <!-- ===== نمو الإيرادات / المشتركين: عمودين على الموبايل ===== -->
        <div class="grid grid-cols-2 md:grid-cols-2 gap-2.5 sm:gap-5">
          <div v-reveal class="glass-card p-2.5 sm:p-6">
            <h3 class="font-bold text-[10.5px] sm:text-[14px] mb-2 sm:mb-4 leading-snug">{{ t("landing.analytics.groups.revenue_growth") }}</h3>
            <div class="space-y-1.5 sm:space-y-3">
              <div v-for="(label, i) in revenueGrowth.labels" :key="label">
                <div class="flex justify-between text-[8.5px] sm:text-[11px] mb-0.5 sm:mb-1 gap-1">
                  <span class="truncate">{{ t(`landing.analytics.labels.${label}`) }}</span>
                  <span class="shrink-0">₪{{ formatNum(revenueGrowth.values[i]) }}</span>
                </div>
                <div class="bar-track"><div class="bar-fill" :style="{ width: animated ? (revenueGrowth.values[i] / maxOf(revenueGrowth.values)) * 100 + '%' : '0%' }"></div></div>
              </div>
            </div>
          </div>

          <div v-reveal class="glass-card p-2.5 sm:p-6">
            <h3 class="font-bold text-[10.5px] sm:text-[14px] mb-2 sm:mb-4 leading-snug">{{ t("landing.analytics.groups.subscriber_growth") }}</h3>
            <div class="space-y-1.5 sm:space-y-3">
              <div v-for="(label, i) in subscriberGrowth.labels" :key="label">
                <div class="flex justify-between text-[8.5px] sm:text-[11px] mb-0.5 sm:mb-1 gap-1">
                  <span class="truncate">{{ t(`landing.analytics.labels.${label}`) }}</span>
                  <span class="shrink-0">{{ subscriberGrowth.values[i] }}</span>
                </div>
                <div class="bar-track"><div class="bar-fill" :style="{ width: animated ? (subscriberGrowth.values[i] / maxOf(subscriberGrowth.values)) * 100 + '%' : '0%' }"></div></div>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== طرق الدفع / أكثر المولدات: عمودين على الموبايل ===== -->
        <div class="grid grid-cols-2 md:grid-cols-2 gap-2.5 sm:gap-5 mt-2.5 sm:mt-5">
          <div v-reveal class="glass-card p-2.5 sm:p-6">
            <h3 class="font-bold text-[10.5px] sm:text-[14px] mb-2 sm:mb-4 leading-snug">{{ t("landing.analytics.payment_methods_title") }}</h3>
            <div v-if="paymentMethods.length === 0" class="text-[9.5px] sm:text-[12px] text-[#9a9d97] text-center py-3 sm:py-4">{{ t("landing.analytics.no_data_yet") }}</div>
            <div v-else class="space-y-1.5 sm:space-y-3">
              <div v-for="pm in paymentMethods" :key="pm.type">
                <div class="flex justify-between text-[8.5px] sm:text-[11px] mb-0.5 sm:mb-1 gap-1">
                  <span class="truncate">{{ paymentMethodLabel(pm.type) }}</span>
                  <span class="shrink-0">{{ pm.percentage }}%</span>
                </div>
                <div class="bar-track"><div class="bar-fill" :style="{ width: animated ? pm.percentage + '%' : '0%' }"></div></div>
              </div>
            </div>
          </div>

          <div v-reveal class="glass-card p-2.5 sm:p-6">
            <h3 class="font-bold text-[10.5px] sm:text-[14px] mb-2 sm:mb-4 leading-snug">{{ t("landing.analytics.top_generators_title") }}</h3>
            <div v-if="topGenerators.length === 0" class="text-[9.5px] sm:text-[12px] text-[#9a9d97] text-center py-3 sm:py-4">{{ t("landing.analytics.no_data_yet") }}</div>
            <div v-else class="space-y-1.5 sm:space-y-3">
              <div
                v-for="(g, i) in topGenerators"
                :key="g.name"
                class="flex items-center justify-between gap-1.5 sm:gap-2 text-[9px] sm:text-[12.5px]"
                :class="i < topGenerators.length - 1 ? 'border-b border-[#e2e2e2]/50 dark:border-white/10 pb-1.5 sm:pb-3' : ''"
              >
                <div class="flex items-center gap-1.5 sm:gap-3 min-w-0">
                  <span class="w-4 h-4 sm:w-6 sm:h-6 shrink-0 rounded-full bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center text-[7.5px] sm:text-[10px] font-bold text-[#8A6D1F]">{{ i + 1 }}</span>
                  <div class="min-w-0">
                    <p class="font-semibold truncate">{{ localizedGeneratorName(g) }}</p>
                    <p class="text-[8px] sm:text-[10.5px] text-[#9a9d97] truncate">{{ g.city }}</p>
                  </div>
                </div>
                <span class="font-bold text-[#52733D] dark:text-[#8cc35a] shrink-0 whitespace-nowrap">{{ g.subscribers_count }}</span>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>
  </section>
</template>