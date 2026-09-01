<script setup>
import { useI18n } from "vue-i18n";
import { vReveal } from "@/directives/reveal";
import { useScrollGlow } from "@/composables/useScrollGlow";
import AppIcon from "@/components/ui/AppIcon.vue";

const { t } = useI18n();

// خلفية الـ parallax المرتبطة بسكرول الصفحة (نفس المستخدمة بقسم Analytics)
const servicesStage = useScrollGlow();

const SERVICES = ["subscribe", "reading", "payment", "maintenance", "complaints", "notifications"];
const ICONS = {
  subscribe: "fa-bolt",
  reading: "fa-gauge",
  payment: "fa-wallet",
  maintenance: "fa-screwdriver-wrench",
  complaints: "fa-comment-dots",
  notifications: "fa-bell",
};
</script>

<template>
  <section id="services" ref="servicesStage" class="py-14 sm:py-24 scroll-glow-stage section-tint-gold">
    <!-- خلفية parallax مرتبطة بسكرول الصفحة العادي — زخرفية بس، ما بتأثر على المحتوى -->
    <div class="scroll-glow-bg" aria-hidden="true">
      <span class="scroll-glow-blob scroll-glow-blob--1"></span>
      <span class="scroll-glow-blob scroll-glow-blob--2"></span>
      <span class="scroll-glow-blob scroll-glow-blob--3"></span>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-8 scroll-glow-content">
      <div class="text-center max-w-xl mx-auto mb-8 sm:mb-14" v-reveal>
        <span class="text-[10px] sm:text-[11px] font-bold text-[#8A6D1F] tracking-wide">{{ t("landing.services.eyebrow") }}</span>
        <h2 class="text-2xl sm:text-4xl font-extrabold mt-2 mb-2 sm:mb-3">{{ t("landing.services.title") }}</h2>
        <p class="text-[12.5px] sm:text-[14px] text-[#666] dark:text-[#aeb1ab]">{{ t("landing.services.subtitle") }}</p>
      </div>
      <div class="grid grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-5">
        <div v-for="(s, i) in SERVICES" :key="s" v-reveal="(i % 3) * 90" class="glass-card services-fill-card p-3.5 sm:p-6">
          <div class="services-icon-box w-9 h-9 sm:w-11 sm:h-11 rounded-lg bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center mb-2.5 sm:mb-4">
            <AppIcon :name="ICONS[s]" class="services-icon text-[13px] sm:text-base text-[#8A6D1F]" />
          </div>
          <h3 class="services-card-title font-bold text-[12.5px] sm:text-[15px] mb-1 sm:mb-1.5 leading-snug">{{ t(`landing.services.items.${s}_title`) }}</h3>
<p class="services-card-desc text-[11px] sm:text-[13px] leading-relaxed">{{ t(`landing.services.items.${s}_desc`) }}</p>        </div>
      </div>
    </div>
  </section>
</template>