<script setup>
import { useI18n } from "vue-i18n";
import { vReveal } from "@/directives/reveal";

const { t } = useI18n();
</script>

<template>
  <section class="py-20">
    <div class="max-w-5xl mx-auto px-5 sm:px-8">
      <div class="glass-card pricing-highlight p-8 sm:p-14 text-center relative overflow-hidden" v-reveal>
        <div class="absolute -top-24 -end-24 w-72 h-72 bg-[#D4AF37]/20 rounded-full blur-[100px] pointer-events-none"></div>
        <div class="absolute -bottom-24 -start-24 w-72 h-72 bg-[#52733D]/20 rounded-full blur-[100px] pointer-events-none"></div>
        <div class="relative">
          <h2 class="text-3xl sm:text-4xl font-extrabold mb-4">{{ t("landing.cta.title") }}</h2>
          <p class="text-[14px] text-[#666] dark:text-[#aeb1ab] max-w-xl mx-auto mb-8">{{ t("landing.cta.description") }}</p>
          <div class="flex flex-wrap items-center justify-center gap-3">
            <RouterLink
              :to="{ name: 'register' }"
              class="btn-fill bg-gradient-to-l from-[#3E582E] via-[#52733D] to-[#8A6D1F] text-white font-bold text-sm px-7 py-3.5 rounded-full shadow-lg hover:-translate-y-0.5 transition-transform duration-300"
            >
              {{ t("landing.cta.primary") }}
            </RouterLink>
            <a href="#contact" class="btn-outline-fill font-bold text-sm px-7 py-3.5 rounded-full">
              {{ t("landing.cta.secondary") }}
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>