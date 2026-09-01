<script setup>
import { useI18n } from "vue-i18n";
import { vReveal } from "@/directives/reveal";
import AppIcon from "@/components/ui/AppIcon.vue";

const { t } = useI18n();

const ITEMS = [
  { icon: "fa-hands-holding-child", key: "displaced" },
  { icon: "fa-house-chimney-medical", key: "critical" },
  { icon: "fa-people-roof", key: "transparency" },
];
</script>

<template>
  <section id="human" class="py-14 sm:py-24 section-tint-sage">
    <div class="max-w-6xl mx-auto px-4 sm:px-8">
      <div class="text-center max-w-xl mx-auto mb-8 sm:mb-14" v-reveal>
        <span class="text-[10px] sm:text-[11px] font-bold text-[#8A6D1F] tracking-wide">{{ t("landing.human.eyebrow") }}</span>
        <h2 class="text-2xl sm:text-4xl font-extrabold mt-2">{{ t("landing.human.title") }}</h2>
        <p class="text-[12.5px] sm:text-[13px] text-[#666] dark:text-[#aeb1ab] mt-2 sm:mt-3">{{ t("landing.human.subtitle") }}</p>
      </div>
      <div class="grid grid-cols-3 gap-2.5 sm:gap-5">
        <div v-for="(item, i) in ITEMS" :key="item.key" v-reveal="(i % 3) * 90" class="glass-card human-card p-2.5 sm:p-6">
          <div class="human-card-icon w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-[#EBF1E7] dark:bg-white/5 flex items-center justify-center text-[#8A6D1F] mb-3 sm:mb-4">
            <AppIcon :name="item.icon" />
          </div>
          <h3 class="font-bold text-[11.5px] sm:text-[15px] mb-1.5 sm:mb-2 leading-snug">{{ t(`landing.human.items.${item.key}_title`) }}</h3>
          <p class="human-card-desc text-[10.5px] sm:text-[13px] text-[#6B6B6B] dark:text-[#a8aaa5] leading-relaxed">{{ t(`landing.human.items.${item.key}_desc`) }}</p>
        </div>
      </div>
    </div>
  </section>
</template>
