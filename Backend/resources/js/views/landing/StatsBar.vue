<script setup>
import { useI18n } from "vue-i18n";
import { vReveal } from "@/directives/reveal";
import { vCountUp } from "@/directives/countUp";

const { t } = useI18n();
</script>

<template>
  <section class="relative z-10 -mt-6 pb-16">
    <div class="max-w-6xl mx-auto px-5 sm:px-8">
      <div
        v-reveal
        class="glass-card grid grid-cols-2 md:grid-cols-4 divide-x rtl:divide-x-reverse divide-[#e2e2e2]/50 dark:divide-white/10 p-6 sm:p-8"
      >
        <div class="text-center px-2">
          <p class="text-3xl font-extrabold brand-gradient-text"><span v-count-up="5000">0</span></p>
          <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1">{{ t("landing.stats.subscribers") }}</p>
        </div>
        <div class="text-center px-2">
          <p class="text-3xl font-extrabold brand-gradient-text"><span v-count-up="250">0</span></p>
          <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1">{{ t("landing.stats.generators") }}</p>
        </div>
        <div class="text-center px-2">
          <p class="text-3xl font-extrabold brand-gradient-text">
            <span v-count-up="{ value: 99.9, decimals: 1 }">0</span>%
          </p>
          <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1">{{ t("landing.stats.uptime") }}</p>
        </div>
        <div class="text-center px-2">
          <p class="text-3xl font-extrabold brand-gradient-text"><span v-count-up="100">0</span></p>
          <p class="text-[11px] text-[#6B6B6B] dark:text-[#a8aaa5] mt-1">{{ t("landing.stats.readings") }}</p>
        </div>
      </div>
    </div>
  </section>
</template>