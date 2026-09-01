<script setup>
import { useI18n } from "vue-i18n";
import { vReveal } from "@/directives/reveal";
import { Quote } from "@lucide/vue";
import AppIcon from "@/components/ui/AppIcon.vue";


const { t } = useI18n();

const PILLARS = [
  { key: "pillar1", icon: "fa-solid fa-gauge", num: "01" },
  { key: "pillar2", icon: "fa-solid fa-shield-halved", num: "02" },
  { key: "pillar3", icon: "fa-solid fa-people-group", num: "03" },
];
</script>

<template>
  <section id="story" class="py-14 sm:py-20 relative overflow-hidden section-tint-sage">
    <span class="story-watermark" aria-hidden="true"><Quote aria-hidden="true" /></span>

    <div class="max-w-3xl mx-auto px-5 sm:px-8 text-center relative" v-reveal>
      <span class="text-[10px] sm:text-[11px] font-bold text-[#8A6D1F] tracking-wide">{{ t("landing.story.eyebrow") }}</span>
      <h2 class="text-2xl sm:text-4xl font-extrabold mt-2 mb-3 sm:mb-5">{{ t("landing.story.title") }}</h2>
      <p class="text-[13px] sm:text-[14px] text-[#666] dark:text-[#aeb1ab] leading-relaxed">{{ t("landing.story.description") }}</p>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-8 relative mt-10 sm:mt-16">
      <div class="story-connector hidden sm:block" aria-hidden="true"></div>
      <div class="grid grid-cols-3 gap-2.5 sm:gap-6 relative">
        <div
          v-for="(p, i) in PILLARS"
          :key="p.key"
          v-reveal="i * 130"
          class="story-pillar glass-card p-2.5 pt-6 sm:p-6 sm:pt-8 text-center relative"
        >
          <span class="story-pillar-num">{{ p.num }}</span>
          <div class="story-pillar-icon">
            <AppIcon :name="p.icon" />
          </div>
          <h3 class="font-bold text-[11.5px] sm:text-[14.5px] mb-1 sm:mb-1.5 leading-snug">{{ t(`landing.story.${p.key}_title`) }}</h3>
          <p class="hidden sm:block text-[13px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-relaxed">{{ t(`landing.story.${p.key}_desc`) }}</p>
          <p class="story-pillar-desc-mobile sm:hidden text-[10.5px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-snug">{{ t(`landing.story.${p.key}_desc`) }}</p>
        </div>
      </div>
    </div>
  </section>
</template>